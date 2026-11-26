<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AccountAccess
{
    /** @return array{security_version: int, remember_token: null} */
    public static function revoke(object $user): array
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('otp_challenges')->where('user_id', $user->id)->whereNull('consumed_at')->update(['revoked_at' => now()]);

        return ['security_version' => $user->security_version + 1, 'remember_token' => null];
    }
}
