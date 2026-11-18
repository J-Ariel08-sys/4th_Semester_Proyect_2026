<?php

namespace Tests\Feature;

use App\Http\Controllers\ManagementController;
use App\Models\User;
use App\Services\DecisionAnalysis;
use App\Services\OtpService;
use App\Services\RotationService;
use Database\Seeders\ClinicalDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccessAndManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function user(string $name): User
    {
        return User::where('email', $name.'@siclin.test')->firstOrFail();
    }

    public function test_new_accounts_need_password_and_coordination_approval_except_administrators(): void
    {
        $this->seed();
        Queue::fake();
        $cohort = DB::table('cohorts')->insertGetId(['name' => 'Cohorte de habilitación', 'entry_year' => 2026, 'active' => true]);
        foreach (['interno', 'tutor', 'coordinador', 'administrador'] as $role) {
            $email = 'nueva.'.$role.'@siclin.test';
            $password = 'NuevaClaveVerificada2026!';
            $this->actingAs($this->user('admin'))->post(route('management.save', 'usuarios'), ['name' => 'Cuenta '.$role, 'email' => strtoupper($email), 'role' => $role, 'active' => 1, 'password' => $password, 'password_confirmation' => $password])->assertSessionHasNoErrors();
            $user = User::where('email', $email)->firstOrFail();
            $this->assertTrue(Hash::check($password, $user->password));
            $this->assertNotNull($user->activated_at);
            $this->assertNull($user->access_approved_at);
            $this->post(route('logout'));
            if ($role !== 'administrador') {
                $this->post('/login', ['email' => $email, 'password' => $password])->assertSessionHasErrors('email');
                $this->assertGuest();
                $this->actingAs($this->user('coordinador'));
                if ($role === 'interno') {
                    $this->get(route('management.edit', ['habilitaciones', $user->id]))->assertOk()->assertSee('Matrícula')->assertSee('Eliminar registro');
                    $this->post(route('management.save', ['internos', $user->id]), ['cohort_id' => 0, 'matricula' => 'LP-NUEVA', 'academic_average' => 85, 'merit' => 70, 'eligible' => 0])->assertSessionHasNoErrors();
                    $this->assertNull($user->fresh()->cohort_id);
                    $this->post(route('management.save', ['internos', $user->id]), ['cohort_id' => 1, 'matricula' => 'LP-NUEVA', 'academic_average' => 85, 'merit' => 70, 'eligible' => 1])->assertSessionHasNoErrors();
                    $this->assertNotNull($user->fresh()->cohort_id);
                } else {
                    $this->get(route('management.edit', ['habilitaciones', $user->id]))->assertOk()->assertSee('Autorizar ingreso');
                    $this->post(route('management.save', ['habilitaciones', $user->id]), ['phone' => '70000000', 'access_enabled' => 1])->assertSessionHasNoErrors();
                }
                $this->assertSame($this->user('coordinador')->id, $user->fresh()->access_approved_by);
                $this->post(route('logout'));
            }
            $this->post('/login', ['email' => $email, 'password' => $password])->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);
            $this->get(route('dashboard'))->assertOk();
            if ($role !== 'administrador') {
                $this->get(route('management', 'usuarios'))->assertForbidden();
            }
            $this->post(route('logout'));
        }
        $details = DB::table('audit_logs')->pluck('details')->implode(' ');
        $this->assertStringNotContainsString('NuevaClaveVerificada2026!', $details);
        $this->assertSame(3, DB::table('audit_logs')->where('action', 'cuenta.habilitada')->count());
    }

    public function test_login_attempts_do_not_consume_password_reset_or_otp_route_limits(): void
    {
        $this->seed();
        for ($i = 0; $i < 12; $i++) {
            $this->post('/login', ['email' => 'inexistente'.$i.'@siclin.test', 'password' => 'ClaveIncorrecta2026!'])->assertRedirect();
        }
        $this->post(route('otp.check'), ['code' => '123456'])->assertRedirect()->assertSessionHasErrors('code');
        $this->post(route('password.update'), ['password' => 'ClaveSinAutorizacion2026!', 'password_confirmation' => 'ClaveSinAutorizacion2026!'])->assertRedirect()->assertSessionHasErrors('password');
    }

    public function test_admin_password_edit_revokes_sessions_and_otp_and_new_password_logs_in(): void
    {
        $this->seed();
        Queue::fake();
        $intern = $this->user('interno');
        $challenge = app(OtpService::class)->request($intern->email, 'recovery', '127.0.0.1');
        DB::table('sessions')->insert(['id' => 'old-intern-session', 'user_id' => $intern->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($this->user('admin'))->get(route('management.edit', ['usuarios', $intern->id]))->assertOk()->assertSee('Nueva contraseña');
        $newPassword = 'ClaveAdministrada2026!';
        $fields = ['name' => $intern->name, 'email' => $intern->email, 'role' => $intern->role, 'active' => 1];
        $this->post(route('management.save', ['usuarios', $intern->id]), $fields + ['password' => $newPassword, 'password_confirmation' => $newPassword])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sessions', ['id' => 'old-intern-session']);
        $this->assertNotNull(DB::table('otp_challenges')->where('id', $challenge)->value('revoked_at'));
        $this->assertSame(2, $intern->fresh()->security_version);
        $this->assertTrue(Hash::check($newPassword, $intern->fresh()->password));
        $this->post(route('management.save', ['usuarios', $intern->id]), $fields + ['password' => '', 'password_confirmation' => ''])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check($newPassword, $intern->fresh()->password));
        $this->post(route('logout'));
        $this->post('/login', ['email' => $intern->email, 'password' => 'SiclinDemo2026!'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $intern->email, 'password' => $newPassword])->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk();
        $this->assertStringNotContainsString($newPassword, DB::table('audit_logs')->pluck('details')->implode(' '));
    }

    public function test_administrator_has_full_academic_scope_including_private_documents(): void
    {
        $this->seed();
        Storage::fake('local');
        Queue::fake();
        $this->seed(ClinicalDemoSeeder::class);
        $assignment = DB::table('assignments')->first();
        $intern = $this->user('interno.clinico');
        $this->actingAs($intern)->post(route('reports.personal'))->assertSessionHasNoErrors();
        $report = DB::table('reports')->first();
        $this->actingAs($this->user('admin'));
        foreach (['rotations', 'clinical', 'shifts', 'reports', 'scenarios', 'audit', 'permissions', 'settings'] as $route) {
            $this->get(route($route))->assertOk();
        }
        foreach (array_keys(ManagementController::definitions()) as $resource) {
            $this->get(route('management', $resource))->assertOk()->assertDontSee('Importar nómina');
        }
        $this->get(route('clinical'))->assertSee('Camila Rojas');
        $this->get(route('reports'))->assertSee($report->token);
        $this->get(route('reports.download', $report->token))->assertOk();
        $this->post(route('clinical.record', $assignment->id), ['date' => now()->toDateString(), 'attendance' => 'presente', 'grade' => 85])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clinical_records', ['assignment_id' => $assignment->id, 'recorded_by' => $this->user('admin')->id, 'grade' => 85]);
        $this->actingAs($this->user('tutor2'))->get(route('reports.download', $report->token))->assertForbidden();
        foreach (['/nomina/importar', '/nomina/confirmar'] as $path) {
            $this->post($path)->assertNotFound();
        }
        $this->get('/nomina/plantilla')->assertNotFound();
    }

    public function test_every_catalog_edit_can_delete_with_audit_without_losing_linked_history(): void
    {
        $this->seed();
        Storage::fake('local');
        Queue::fake();
        $this->seed(ClinicalDemoSeeder::class);
        $assignment = DB::table('assignments')->first();
        $clinicalPassword = collect(json_decode(Storage::disk('local')->get('demo-accounts.json'), true))->firstWhere('email', 'interno.clinico@siclin.test')['password'];
        $this->actingAs($this->user('admin'))->post(route('reports.create', $assignment->cycle_id))->assertSessionHasNoErrors();
        $report = DB::table('reports')->first();
        $cohort = DB::table('cohorts')->insertGetId(['name' => 'Para eliminar', 'entry_year' => 2026]);
        $specialty = DB::table('specialties')->value('id');
        $requirement = DB::table('academic_requirements')->insertGetId(['cohort_id' => $cohort, 'specialty_id' => $specialty, 'min_days' => 21, 'required_blocks' => 2]);
        $resources = [
            'hospitales' => ['hospitals', DB::table('places')->where('id', $assignment->place_id)->value('hospital_id')],
            'plazas' => ['places', $assignment->place_id],
            'convocatorias' => ['cycles', $assignment->cycle_id],
            'requisitos' => ['academic_requirements', $requirement],
            'cohortes' => ['cohorts', $cohort],
            'especialidades' => ['specialties', $specialty],
            'internos' => ['users', $this->user('interno2')->id],
            'tutores' => ['users', $this->user('tutor2')->id],
            'habilitaciones' => ['users', $this->user('interno3')->id],
            'usuarios' => ['users', $this->user('interno.clinico')->id],
        ];
        foreach ($resources as $resource => [$table, $id]) {
            $this->get(route('management.edit', [$resource, $id]))->assertOk()->assertSee('Eliminar registro');
            $this->delete(route('management.destroy', [$resource, $id]), ['reason' => 'Retirado durante la prueba de trazabilidad.'])->assertSessionHasNoErrors();
            $this->assertNotNull(DB::table($table)->where('id', $id)->value('deleted_at'));
            $this->get(route('management.edit', [$resource, $id]))->assertNotFound();
            $this->delete(route('management.destroy', [$resource, $id]), ['reason' => 'Intento repetido de eliminación.'])->assertNotFound();
        }
        $this->assertDatabaseHas('assignments', ['id' => $assignment->id]);
        $this->get(route('reports.download', $report->token))->assertOk();
        $this->get(route('clinical'))->assertOk()->assertSee('Historial archivado');
        $this->post(route('clinical.record', $assignment->id), ['date' => now()->toDateString(), 'attendance' => 'presente'])->assertNotFound();
        $this->assertSame(10, DB::table('audit_logs')->where('action', 'gestion.eliminada')->count());
        $log = json_decode(DB::table('audit_logs')->where('action', 'gestion.eliminada')->orderByDesc('id')->value('details'), true);
        $this->assertArrayNotHasKey('password', $log['registro']);
        $this->assertArrayNotHasKey('remember_token', $log['registro']);
        $this->post(route('logout'));
        $this->post('/login', ['email' => 'interno.clinico@siclin.test', 'password' => $clinicalPassword])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_removed_places_and_accounts_are_unavailable_to_new_planning_and_cannot_bypass_permissions(): void
    {
        $this->seed();
        $admin = $this->user('admin');
        $intern = $this->user('interno');
        $cycle = DB::table('cycles')->value('id');
        $places = DB::table('places')->orderBy('id')->pluck('id')->all();
        $this->actingAs($intern)->post(route('applications.store', $cycle), ['preferences' => $places])->assertSessionHasNoErrors();
        $this->delete(route('management.destroy', ['plazas', $places[0]]), ['reason' => 'Eliminación no autorizada.'])->assertForbidden();
        $this->actingAs($this->user('coordinador'))->delete(route('management.destroy', ['usuarios', $intern->id]), ['reason' => 'Eliminación no autorizada.'])->assertForbidden();
        $this->delete(route('management.destroy', ['habilitaciones', $admin->id]), ['reason' => 'No puede eliminar un administrador.'])->assertForbidden();
        $this->actingAs($admin)->delete(route('management.destroy', ['usuarios', $admin->id]), ['reason' => 'No puede eliminarse a sí mismo.'])->assertSessionHasErrors('eliminar');
        $this->delete(route('management.destroy', ['plazas', $places[0]]), [])->assertSessionHasErrors('reason');
        $this->delete(route('management.destroy', ['plazas', $places[0]]), ['reason' => 'Plaza retirada de futuras propuestas.'])->assertSessionHasNoErrors();
        $service = app(RotationService::class);
        $input = $service->input($cycle, DecisionAnalysis::POLICIES['A1']);
        $this->assertSame([(string) $places[1]], $input['internos'][0]['preferencias']);
        $this->assertSame((string) $places[1], $service->evaluate($input)['asignaciones'][0]['plazaId']);
    }

    public function test_revoking_access_blocks_existing_sessions_and_unapproved_users_cannot_supply_their_own_approval(): void
    {
        $this->seed();
        $tutor = $this->user('tutor');
        $this->actingAs($this->user('coordinador'))->post(route('management.save', ['habilitaciones', $tutor->id]), ['access_enabled' => 0])->assertSessionHasNoErrors();
        $this->assertNull($tutor->fresh()->access_approved_at);
        $this->actingAs($tutor->fresh())->withSession(['security_version' => 1])->get(route('dashboard'))->assertRedirect(route('login'));
        $this->post('/login', ['email' => $tutor->email, 'password' => 'SiclinDemo2026!', 'access_approved_at' => now(), 'role' => 'administrador'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame('tutor', $tutor->fresh()->role);
    }
}
