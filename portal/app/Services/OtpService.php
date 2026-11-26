<?php

namespace App\Services;

use App\Jobs\SendOtp;
use App\Jobs\SendSecurityNotice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService
{
    public const MESSAGE = 'Si la cuenta está habilitada, recibirás instrucciones para recuperar el acceso';

    public function digest(string $value): string
    {
        $secret = config('siclin.otp_secret');
        if (! is_string($secret) || strlen($secret) < 32) {
            throw new \RuntimeException('Configura OTP_SECRET con al menos 32 caracteres.');
        }
        return hash_hmac('sha256', $value, $secret);
    }

    public function request(string $email, string $purpose, string $ip): string
    {
        abort_unless(in_array($purpose, ['recovery', 'activation'], true), 422);
        $email = Str::lower(trim($email));
        $account = $this->digest('account:'.$email.':'.$purpose);
        $ipKey = $this->digest('ip:'.$ip);
        return DB::transaction(function () use ($email, $purpose, $account, $ipKey) {
            foreach ([$ipKey, $account] as $key) {
                DB::table('otp_limits')->insertOrIgnore(['key' => $key, 'count' => 0, 'window_start' => now()]);
            }
            $ipLimit = DB::table('otp_limits')->where('key', $ipKey)->lockForUpdate()->first();
            $limit = DB::table('otp_limits')->where('key', $account)->lockForUpdate()->first();
            $current = DB::table('otp_challenges')->where('account_hash', $account)->orderByDesc('created_at')->first();
            foreach ([$ipLimit, $limit] as $row) {
                if (Carbon::parse($row->window_start)->addSeconds(config('siclin.otp_window'))->lte(now())) {
                    $row->count = 0;
                    DB::table('otp_limits')->where('key', $row->key)->update(['count' => 0, 'window_start' => now()]);
                }
            }
            if ($ipLimit->count >= config('siclin.otp_ip_requests') || $limit->count >= config('siclin.otp_account_requests') || ($limit->last_request_at && Carbon::parse($limit->last_request_at)->addSeconds(config('siclin.otp_cooldown'))->gt(now()))) {
                return $current?->id ?? Str::random(64);
            }
            foreach ([$ipLimit, $limit] as $row) {
                DB::table('otp_limits')->where('key', $row->key)->update(['count' => $row->count + 1, 'last_request_at' => now()]);
            }
            $user = DB::table('users')->where('email', $email)->where('active', true)->lockForUpdate()->first();
            if ($user && (($purpose === 'activation') !== ($user->activated_at === null))) {
                $user = null;
            }
            DB::table('otp_challenges')->where('account_hash', $account)->whereNull('consumed_at')->update(['revoked_at' => now()]);
            $id = Str::random(64);
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            DB::table('otp_challenges')->insert(['id' => $id, 'user_id' => $user?->id, 'account_hash' => $account, 'purpose' => $purpose, 'verifier' => $this->digest($id.':'.$purpose.':'.($user?->id ?? '').':'.$code), 'expires_at' => now()->addSeconds(config('siclin.otp_ttl')), 'created_at' => now(), 'updated_at' => now()]);
            SendOtp::dispatch($id, $code, config('siclin.mail_mode'))->onQueue(config('siclin.mail_queue'))->afterCommit();
            Audit::record('otp.solicitado', ['proposito' => $purpose]);
            return $id;
        }, 3);
    }

    public function verify(string $id, string $purpose, string $code): ?string
    {
        return DB::transaction(function () use ($id, $purpose, $code) {
            $row = DB::table('otp_challenges')->where('id', $id)->lockForUpdate()->first();
            if (! $row || $row->purpose !== $purpose || $row->revoked_at || $row->verified_at || $row->consumed_at || Carbon::parse($row->expires_at)->lte(now()) || $row->attempts >= config('siclin.otp_attempts')) {
                return null;
            }
            $valid = hash_equals($row->verifier, $this->digest($id.':'.$purpose.':'.($row->user_id ?? '').':'.$code));
            DB::table('otp_challenges')->where('id', $id)->update(['attempts' => $row->attempts + 1, 'updated_at' => now()]);
            if (! $valid || ! $row->user_id || ! DB::table('users')->where('id', $row->user_id)->where('active', true)->exists()) {
                return null;
            }
            $grant = Str::random(64);
            DB::table('otp_challenges')->where('id', $id)->update(['verified_at' => now(), 'grant_verifier' => $this->digest('grant:'.$grant), 'grant_expires_at' => now()->addSeconds(config('siclin.otp_grant_ttl'))]);
            return $grant;
        }, 3);
    }

    public function complete(string $id, string $purpose, string $grant, string $password): bool
    {
        return DB::transaction(function () use ($id, $purpose, $grant, $password) {
            $row = DB::table('otp_challenges')->where('id', $id)->lockForUpdate()->first();
            if (! $row || $row->purpose !== $purpose || ! $row->verified_at || $row->revoked_at || $row->consumed_at || ! $row->grant_verifier || ! $row->grant_expires_at || Carbon::parse($row->grant_expires_at)->lte(now()) || ! hash_equals($row->grant_verifier, $this->digest('grant:'.$grant))) {
                return false;
            }
            $user = DB::table('users')->where('id', $row->user_id)->lockForUpdate()->first();
            if (! $user || ! $user->active || (($purpose === 'activation') !== ($user->activated_at === null))) {
                return false;
            }
            DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($password), 'remember_token' => Str::random(60), 'security_version' => $user->security_version + 1, 'activated_at' => $user->activated_at ?? now(), 'email_verified_at' => now(), 'updated_at' => now()]);
            DB::table('otp_challenges')->where('id', $id)->update(['consumed_at' => now(), 'grant_verifier' => null]);
            DB::table('otp_challenges')->where('user_id', $user->id)->where('id', '!=', $id)->whereNull('consumed_at')->update(['revoked_at' => now()]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            Audit::record($purpose === 'activation' ? 'cuenta.activada' : 'clave.restablecida', ['usuario' => $user->id]);
            SendSecurityNotice::dispatch($user->id, config('siclin.mail_mode'))->onQueue(config('siclin.mail_queue'))->afterCommit();
            return true;
        }, 3);
    }
}
