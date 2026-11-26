<?php

namespace Tests\Feature;

use App\Jobs\{SendOtp, SendSecurityNotice};
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\{DB, Hash, Mail, Queue};
use Tests\TestCase;

class OtpSecurityTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seed();
    }

    private function user(): User
    {
        return User::where('email', 'interno@siclin.test')->firstOrFail();
    }

    private function challenge(string $purpose = 'recovery', ?string $email = null): SendOtp
    {
        $id = app(OtpService::class)->request($email ?? $this->user()->email, $purpose, '127.0.0.1');
        return Queue::pushed(SendOtp::class)->first(fn ($job) => $job->challengeId === $id);
    }

    public function test_public_response_is_generic_for_existing_missing_and_disabled_accounts(): void
    {
        $this->user()->forceFill(['active' => false])->save();
        foreach (['interno2@siclin.test', 'missing@siclin.test', 'interno@siclin.test'] as $email) {
            $this->post(route('password.email'), ['email' => $email])->assertRedirect(route('otp.verify'))->assertSessionHas('status', OtpService::MESSAGE);
            $response = $this->get(route('otp.verify'))->assertOk();
            $job = Queue::pushed(SendOtp::class)->last();
            $response->assertDontSee($job->code);
        }
        $this->assertSame(1, DB::table('otp_challenges')->whereNotNull('user_id')->count());
    }

    public function test_complete_browser_flow_binds_grant_and_revokes_sessions(): void
    {
        $this->post(route('password.email'), ['email' => strtoupper($this->user()->email)])->assertRedirect(route('otp.verify'));
        $job = Queue::pushed(SendOtp::class)->last();
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $job->code);
        $this->assertNotSame($job->code, DB::table('otp_challenges')->value('verifier'));
        $password = ['password' => 'ChangedPassword2026!', 'password_confirmation' => 'ChangedPassword2026!'];
        $this->post(route('password.update'), $password)->assertSessionHasErrors('password');
        $this->post(route('otp.check'), ['code' => $job->code])->assertRedirect(route('password.reset'));
        $this->post(route('password.update'), [...$password, 'user_id' => $this->user()->id])->assertSessionHasErrors('user_id');
        DB::table('sessions')->insert(['id' => 'previous-session', 'user_id' => $this->user()->id, 'payload' => 'old', 'last_activity' => time()]);
        $this->post(route('password.update'), $password)->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseMissing('sessions', ['id' => 'previous-session']);
        $this->post('/login', ['email' => $this->user()->email, 'password' => 'SiclinDemo2026!'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $this->user()->email, 'password' => $password['password']])->assertRedirect(route('dashboard'));
        Queue::assertPushed(SendSecurityNotice::class);
        $this->assertSame(2, $this->user()->security_version);
    }

    public function test_attempts_expiry_replay_and_purpose_are_enforced(): void
    {
        $service = app(OtpService::class);
        $job = $this->challenge();
        $this->assertNull($service->verify($job->challengeId, 'activation', $job->code));
        $wrong = $job->code === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 5; $i++) { $this->assertNull($service->verify($job->challengeId, 'recovery', $wrong)); }
        $this->assertNull($service->verify($job->challengeId, 'recovery', $job->code));
        $this->travel(61)->seconds();
        $job = $this->challenge();
        $grant = $service->verify($job->challengeId, 'recovery', $job->code);
        $this->assertNotNull($grant);
        $this->assertNull($service->verify($job->challengeId, 'recovery', $job->code));
        $this->assertFalse($service->complete($job->challengeId, 'activation', $grant, 'AnotherPassword2026!'));
        $this->travel(301)->seconds();
        $this->assertFalse($service->complete($job->challengeId, 'recovery', $grant, 'AnotherPassword2026!'));
        $this->travel(900)->seconds();
        $job = $this->challenge();
        $this->travel(601)->seconds();
        $this->assertNull($service->verify($job->challengeId, 'recovery', $job->code));
    }

    public function test_cooldown_and_rejected_resends_preserve_current_challenge(): void
    {
        $service = app(OtpService::class);
        $first = $this->challenge();
        $this->assertSame($first->challengeId, $service->request($this->user()->email, 'recovery', '127.0.0.1'));
        Queue::assertPushed(SendOtp::class, 1);
        $this->travel(61)->seconds(); $second = $this->challenge();
        $this->assertNull($service->verify($first->challengeId, 'recovery', $first->code));
        $this->travel(61)->seconds(); $third = $this->challenge();
        $this->travel(61)->seconds();
        $this->assertSame($third->challengeId, $service->request($this->user()->email, 'recovery', '127.0.0.1'));
        $this->assertNotNull($service->verify($third->challengeId, 'recovery', $third->code));
        Queue::assertPushed(SendOtp::class, 3);
    }

    public function test_activation_is_separate_and_disabled_accounts_cannot_reset(): void
    {
        $this->user()->forceFill(['activated_at' => null])->save();
        $service = app(OtpService::class);
        $recovery = $this->challenge();
        $this->assertNull($service->verify($recovery->challengeId, 'recovery', $recovery->code));
        $activation = $this->challenge('activation');
        $grant = $service->verify($activation->challengeId, 'activation', $activation->code);
        $this->assertTrue($service->complete($activation->challengeId, 'activation', $grant, 'Activation2026!'));
        $this->assertFalse($service->complete($activation->challengeId, 'activation', $grant, 'Activation2026!'));
        $this->travel(61)->seconds();
        $recovery = $this->challenge();
        $grant = $service->verify($recovery->challengeId, 'recovery', $recovery->code);
        $this->user()->forceFill(['active' => false])->save();
        $this->assertFalse($service->complete($recovery->challengeId, 'recovery', $grant, 'Disabled2026!'));
    }

    public function test_delayed_and_revoked_jobs_do_not_send_and_live_job_sends_once(): void
    {
        Mail::fake();
        $old = $this->challenge();
        $this->travel(61)->seconds(); $new = $this->challenge();
        $old->handle();
        $this->assertNull(DB::table('otp_challenges')->where('id', $old->challengeId)->value('sent_at'));
        $new->handle();
        $sent = DB::table('otp_challenges')->where('id', $new->challengeId)->value('sent_at');
        $this->assertNotNull($sent);
        $this->travel(2)->seconds(); $new->handle();
        $this->assertSame($sent, DB::table('otp_challenges')->where('id', $new->challengeId)->value('sent_at'));
        $this->travel(61)->seconds(); $expired = $this->challenge();
        $this->travel(601)->seconds(); $expired->handle();
        $this->assertNull(DB::table('otp_challenges')->where('id', $expired->challengeId)->value('sent_at'));
    }

    public function test_smtp_failure_is_sanitized_and_mode_switch_drops_local_job(): void
    {
        $job = $this->challenge();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('SECRET SMTP CONTENT'));
        try { $job->handle(); $this->fail('SMTP failure expected'); } catch (\RuntimeException $exception) {
            $this->assertStringNotContainsString('SECRET', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
        $this->assertNull(DB::table('otp_challenges')->value('sent_at'));
        config(['siclin.mail_mode' => 'external']);
        $job->handle();
        $this->assertNull(DB::table('otp_challenges')->value('sent_at'));
    }

    public function test_ip_limits_and_grants_do_not_cross_accounts(): void
    {
        config(['siclin.otp_ip_requests' => 2]);
        $service = app(OtpService::class);
        $one = $this->challenge();
        $two = $this->challenge('recovery', 'interno2@siclin.test');
        $service->request('interno3@siclin.test', 'recovery', '127.0.0.1');
        Queue::assertPushed(SendOtp::class, 2);
        $grant = $service->verify($one->challengeId, 'recovery', $one->code);
        $this->assertFalse($service->complete($two->challengeId, 'recovery', $grant, 'CrossAccount2026!'));
        $this->assertTrue(Hash::check('SiclinDemo2026!', User::where('email','interno2@siclin.test')->first()->password));
    }

    public function test_database_job_payload_encrypts_the_otp(): void
    {
        $job = $this->challenge();
        $connection = new \Illuminate\Queue\DatabaseQueue(DB::connection(), 'jobs', 'mail-local', 240, false);
        $connection->setContainer(app());
        $job->beforeCommit();
        $id = $connection->push($job, '', 'mail-local');
        $payload = DB::table('jobs')->where('id', $id)->value('payload');
        $command = json_decode($payload, true)['data']['command'];
        $this->assertStringNotContainsString($job->code, $command);
        $decoded = unserialize(decrypt($command));
        $this->assertSame($job->code, $decoded->code);
    }
}
