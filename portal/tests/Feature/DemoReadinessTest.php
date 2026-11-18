<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ClinicalDemoSeeder;
use Database\Seeders\PresentationDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoReadinessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_setup_preserves_progress_and_has_actionable_cases_for_each_role(): void
    {
        Storage::fake('local');
        config(['siclin.demo' => true]);
        $this->seed();
        foreach (['admin', 'coordinador', 'tutor', 'tutor2', 'interno', 'interno2', 'interno3'] as $username) {
            $user = User::where('email', $username.'@siclin.test')->firstOrFail();
            $this->assertTrue(Hash::check('SiclinDemo2026!', $user->password));
            $this->post('/login', ['email' => $user->email, 'password' => 'SiclinDemo2026!'])->assertRedirect(route('dashboard'));
            $this->post(route('logout'))->assertRedirect(route('login'));
            $user->forceFill(['password' => 'OtraClaveTemporal2026!'])->save();
        }
        $this->seed();
        foreach (['admin', 'coordinador', 'tutor', 'tutor2', 'interno', 'interno2', 'interno3'] as $username) {
            $user = User::where('email', $username.'@siclin.test')->firstOrFail();
            $this->assertTrue(Hash::check('SiclinDemo2026!', $user->password));
            $this->post('/login', ['email' => $user->email, 'password' => 'SiclinDemo2026!'])->assertRedirect(route('dashboard'));
            $this->post(route('logout'))->assertRedirect(route('login'));
        }
        $this->seed(ClinicalDemoSeeder::class);
        $this->seed(PresentationDemoSeeder::class);
        $this->assertDatabaseCount('applications', 2);
        $this->assertDatabaseCount('assignments', 1);
        $this->assertDatabaseCount('shifts', 1);
        $this->assertDatabaseCount('clinical_records', 0);
        $ana = User::where('email', 'interno@siclin.test')->firstOrFail();
        $this->assertDatabaseMissing('applications', ['user_id' => $ana->id]);
        $assignment = DB::table('assignments')->first();
        $this->actingAs(User::findOrFail($assignment->tutor_id))->post(route('clinical.record', $assignment->id), [
            'date' => now()->toDateString(), 'attendance' => 'presente',
            'competencies' => ['conocimiento' => 80, 'procedimientos' => 60, 'comunicacion' => 100, 'profesionalismo' => 80],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clinical_records', ['assignment_id' => $assignment->id, 'grade' => 80]);
        $accounts = Storage::disk('local')->get('demo-accounts.json');
        $this->seed(ClinicalDemoSeeder::class);
        $this->seed(PresentationDemoSeeder::class);
        $this->assertDatabaseCount('applications', 2);
        $this->assertDatabaseCount('assignments', 1);
        $this->assertDatabaseCount('clinical_records', 1);
        $this->assertSame($accounts, Storage::disk('local')->get('demo-accounts.json'));

        foreach (['administrador', 'coordinador', 'interno', 'tutor'] as $role) {
            $this->actingAs(User::where('role', $role)->firstOrFail());
            $this->get(route('dashboard'))->assertOk();
            $this->get(route('help'))->assertOk()->assertSee('Recorrido del sistema');
            $this->get(route('rotations'))->assertOk();
        }
        $this->actingAs($ana)->get(route('dashboard'))->assertDontSee('Camila Rojas');
        $otherTutor = User::where('email', 'tutor2@siclin.test')->firstOrFail();
        $this->actingAs($otherTutor)->get(route('dashboard'))->assertDontSee('Camila Rojas');
    }

    public function test_demo_reset_refuses_any_database_other_than_the_dedicated_demo(): void
    {
        $this->seed();
        $count = User::count();
        $this->app['env'] = 'local';
        config(['siclin.demo' => true]);
        $this->artisan('siclin:demo', ['--reset' => true])->assertFailed();
        $this->assertSame($count, User::count());
    }
}
