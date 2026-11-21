<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AssignmentPublished;
use App\Services\DecisionAnalysis;
use App\Services\RotationOptimizer;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiclinWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 13)->setTime(10, 0));
        Http::preventStrayRequests();
    }

    private function user(string $role): User
    {
        return User::where('email', $role.'@siclin.test')->firstOrFail();
    }

    private function applyAndClose(): void
    {
        $cycle = DB::table('cycles')->first();
        $places = DB::table('places')->where('cycle_id', $cycle->id)->pluck('id')->all();
        $this->actingAs($this->user('interno'))->post(route('applications.store', $cycle->id), ['preferences' => $places])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->user('coordinador'))->post(route('cycles.transition', $cycle->id), ['status' => 'cerrada'])->assertRedirect();
        $study = DB::table('scenario_studies')->first();
        $this->post(route('scenarios.choose', $study->id), ['chosen' => 'A3', 'justification' => 'Elegimos equilibrio por su comportamiento conservador.'])->assertRedirect()->assertSessionHasNoErrors();
    }

    private function propose(): int
    {
        $this->applyAndClose();
        $place = DB::table('places')->first();
        $intern = $this->user('interno');
        $this->post(route('rotations.generate', $place->cycle_id), ['study_id' => DB::table('scenario_studies')->value('id')])->assertRedirect()->assertSessionHasNoErrors();
        $input = json_decode(DB::table('optimization_runs')->value('input'), true);
        $this->assertSame((string) $intern->id, $input['internos'][0]['id']);
        $this->assertSame(DecisionAnalysis::POLICIES['A3'], $input['politica']);

        $run = DB::table('optimization_runs')->value('id');
        $this->post(route('rotations.review', $run))->assertSessionHasNoErrors();
        $this->post(route('rotations.approve', $run))->assertSessionHasNoErrors();

        return $run;
    }

    private function approve(): int
    {
        $run = $this->propose();
        $this->post(route('rotations.publish', $run))->assertRedirect()->assertSessionHasNoErrors();

        return DB::table('assignments')->value('id');
    }

    public function test_login_roles_and_deactivation_are_enforced(): void
    {
        $this->seed();
        $this->get('/panel')->assertRedirect(route('login'));
        $this->post('/login', ['email' => 'interno@siclin.test', 'password' => 'SiclinDemo2026!'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user('interno'));
        $this->get(route('management', 'usuarios'))->assertForbidden();
        $this->post(route('scenarios.store'), [])->assertForbidden();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($this->user('coordinador'))->get(route('management', 'usuarios'))->assertForbidden();
        $user = $this->user('tutor');
        $user->active = false;
        $user->save();
        $this->actingAs($user)->get('/panel')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_locks_after_five_failures_and_password_reset_revokes_sessions(): void
    {
        $this->seed();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'interno@siclin.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => 'interno@siclin.test', 'password' => 'SiclinDemo2026!'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->travel(301)->seconds();
        $this->post('/login', ['email' => 'interno@siclin.test', 'password' => 'SiclinDemo2026!'])->assertRedirect(route('dashboard'));
    }

    public function test_all_role_pages_render_and_private_actions_are_scoped(): void
    {
        $this->seed();
        foreach (['coordinador', 'tutor', 'interno'] as $name) {
            $this->actingAs($this->user($name));
            foreach (['dashboard', 'rotations', 'clinical', 'reports', 'shifts'] as $route) {
                $this->get(route($route))->assertOk();
            }
        }
        $this->actingAs($this->user('admin'));
        $this->get(route('management', 'usuarios'))->assertOk();
        $this->get(route('audit'))->assertOk();
        $this->get(route('clinical'))->assertOk();
        $this->actingAs($this->user('coordinador'));
        foreach (['internos', 'hospitales', 'especialidades', 'convocatorias', 'plazas'] as $resource) {
            $this->get(route('management', $resource))->assertOk();
        }
        $this->get(route('scenarios'))->assertOk();
        $this->get(route('audit'))->assertForbidden();
    }

    public function test_rotations_lists_today_and_future_and_blocks_past_applications(): void
    {
        $this->seed();
        $this->applyAndClose();
        $past = DB::table('cycles')->first();
        DB::table('cycles')->where('id', $past->id)->update([
            'name' => 'Convocatoria pasada',
            'status' => 'abierta',
            'starts_on' => now()->subDay()->toDateString(),
            'opens_at' => now()->subDays(2),
            'closes_at' => now()->addDay(),
        ]);
        foreach (['Convocatoria de hoy' => now(), 'Convocatoria futura' => now()->addDay()] as $name => $date) {
            DB::table('cycles')->insert([
                'name' => $name,
                'specialty_id' => $past->specialty_id,
                'starts_on' => $date->toDateString(),
                'ends_on' => $date->toDateString(),
                'opens_at' => now()->subDays(2),
                'closes_at' => now()->subDay(),
                'status' => 'abierta',
            ]);
        }

        $this->actingAs($this->user('interno2'))->get(route('rotations'))
            ->assertOk()->assertSeeInOrder(['Convocatoria de hoy', 'Convocatoria futura'])
            ->assertDontSee('Convocatoria pasada')->assertDontSee('Registrar mis preferencias');
        $place = DB::table('places')->where('cycle_id', $past->id)->value('id');
        $this->post(route('applications.store', $past->id), ['preferences' => [$place]])
            ->assertSessionHasErrors('preferences');
        $this->assertDatabaseCount('applications', 1);

        $receipt = DB::table('applications')->where('cycle_id', $past->id)->value('receipt');
        $this->actingAs($this->user('interno'))->get(route('rotations'))->assertOk()
            ->assertSee('Comprobantes anteriores')->assertSee(route('applications.receipt', $receipt))
            ->assertDontSee('data-cycle-id="'.$past->id.'"', false);
    }

    public function test_management_validates_capacity_and_escapes_names(): void
    {
        $this->seed();
        $this->actingAs($this->user('coordinador'));
        $this->post(route('management.save', 'hospitales'), ['name' => '<script>alert(1)</script>', 'agreement_until' => '2027-12-31', 'active' => 1])->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('management', 'hospitales'))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $cycle = DB::table('cycles')->first();
        DB::table('cycles')->where('id', $cycle->id)->update(['status' => 'borrador']);
        $this->post(route('management.save', 'plazas'), ['cycle_id' => $cycle->id, 'hospital_id' => DB::table('hospitals')->value('id'), 'tutor_id' => $this->user('tutor')->id, 'capacity' => 1, 'reserve' => 2])->assertSessionHasErrors('reserve');
        $this->assertDatabaseCount('places', 2);
    }

    public function test_application_is_immutable_and_receipt_is_private(): void
    {
        $this->seed();
        $cycle = DB::table('cycles')->first();
        $place = DB::table('places')->first();
        $this->actingAs($this->user('interno'))->post(route('applications.store', $cycle->id), ['preferences' => [$place->id]])->assertSessionHasNoErrors();
        $receipt = DB::table('applications')->value('receipt');
        $this->get(route('applications.receipt', $receipt))->assertOk()->assertSee('data:image/svg+xml', false);
        $this->post(route('applications.store', $cycle->id), ['preferences' => [$place->id]])->assertSessionHasErrors('preferences');
        $this->actingAs($this->user('interno2'))->get(route('applications.receipt', $receipt))->assertForbidden();
        $user = $this->user('interno2');
        $user->eligible = false;
        $user->save();
        $this->post(route('applications.store', $cycle->id), ['preferences' => [$place->id]])->assertSessionHasErrors('preferences');
        $this->assertDatabaseCount('applications', 1);
    }

    public function test_proposal_approval_publishes_assignments_and_blocks_replay(): void
    {
        $this->seed();
        $run = $this->propose();
        $this->assertDatabaseCount('assignments', 0);
        $this->get(route('rotations'))->assertOk();
        $this->post(route('rotations.publish', $run))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('assignments', 1);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->user('interno')->id, 'type' => AssignmentPublished::class]);
        $this->assertDatabaseHas('cycles', ['status' => 'publicada']);
        $this->post(route('rotations.publish', $run))->assertConflict();
    }

    public function test_stale_proposal_and_bad_solver_output_cannot_publish(): void
    {
        $this->seed();
        $run = $this->propose();
        $user = $this->user('interno');
        $user->eligible = false;
        $user->save();
        $this->post(route('rotations.publish', $run))->assertSessionHasErrors('propuesta');
        $this->assertDatabaseCount('assignments', 0);
        $this->mock(RotationOptimizer::class)->shouldReceive('solve')->once()->andReturn(['hardScore' => 0, 'asignaciones' => [['internoId' => 'unknown', 'plazaId' => '1']], 'pendientes' => []]);
        $this->post(route('rotations.generate', DB::table('cycles')->value('id')), ['study_id' => DB::table('scenario_studies')->value('id')])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('optimization_runs', 2);
        $this->assertDatabaseHas('optimization_runs', ['execution_status' => 'fallida']);
    }

    public function test_solver_failure_keeps_the_cycle_closed(): void
    {
        $this->seed();
        $this->applyAndClose();
        $this->mock(RotationOptimizer::class)->shouldReceive('solve')->once()->andThrow(new \RuntimeException('Motor interrumpido'));
        $this->post(route('rotations.generate', DB::table('cycles')->value('id')), ['study_id' => DB::table('scenario_studies')->value('id')])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('assignments', 0);
        $this->assertDatabaseHas('optimization_runs', ['execution_status' => 'fallida']);
    }

    public function test_clinical_record_and_incident_are_scoped_to_the_assigned_tutor(): void
    {
        $this->seed();
        $assignment = $this->approve();
        $this->travel(8)->days();
        $data = ['date' => now()->toDateString(), 'attendance' => 'presente', 'grade' => 88, 'notes' => 'Cumple la actividad supervisada.'];
        $this->actingAs($this->user('tutor2'))->post(route('clinical.record', $assignment), $data)->assertNotFound();
        $this->actingAs($this->user('tutor'))->post(route('clinical.record', $assignment), $data)->assertSessionHasNoErrors();
        $this->post(route('clinical.record', $assignment), $data)->assertSessionHasErrors('date');
        $this->get(route('clinical'))->assertOk()->assertSee('88');
        $this->actingAs($this->user('interno2'))->post(route('incidents.store', $assignment), ['type' => 'otra', 'description' => 'No tengo acceso a este rote.'])->assertNotFound();
        $this->actingAs($this->user('interno'))->post(route('incidents.store', $assignment), ['type' => 'correccion', 'description' => 'Solicito revisar la observación.'])->assertSessionHasNoErrors();
        $this->actingAs($this->user('coordinador'))->post(route('incidents.resolve', DB::table('incidents')->value('id')), ['resolution' => 'Se revisó el caso con el tutor responsable.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('incidents', ['status' => 'resuelta']);
    }

    public function test_decision_criteria_ties_negative_values_and_sensitivity(): void
    {
        $this->seed();
        $analysis = new DecisionAnalysis;
        $r = $analysis->calculate(['A1' => [90, 60, 20], 'A2' => [75, 65, 40], 'A3' => [80, 70, 60]], 0.5);
        $this->assertSame(['A3'], $r['winners']['maximin']);
        $this->assertSame(['A1'], $r['winners']['maximax']);
        $this->assertEquals(70, $r['scores']['A3']['hurwicz']);
        $this->assertEquals(10, $r['scores']['A3']['savage']);
        $this->assertCount(11, $r['sensitivity']);
        $ties = $analysis->calculate(['A1' => [-3, -2, -1], 'A2' => [-3, -2, -1], 'A3' => [-3, -2, -1]], 1);
        $this->assertSame(['A1', 'A2', 'A3'], $ties['winners']['savage']);
        $this->actingAs($this->user('coordinador'))->post(route('scenarios.store'), ['name' => 'Estudio prueba', 'alpha' => 0.5, 'matrix' => ['A1' => [3, 2, 1], 'A2' => [2, 2, 2], 'A3' => [1, 2, 3]]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('scenario_studies', 2);
    }

    public function test_pdf_contains_snapshot_and_verification_does_not_expose_intern_names(): void
    {
        $this->seed();
        Storage::fake('local');
        $this->approve();
        $cycle = DB::table('cycles')->value('id');
        $this->post(route('reports.create', $cycle))->assertRedirect()->assertSessionHasNoErrors();
        $report = DB::table('reports')->first();
        $this->assertNotNull($report);
        $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($report->path));
        $this->get(route('reports.download', $report->token))->assertOk();
        $this->post(route('logout'));
        $this->get(route('reports.verify', $report->token))->assertOk()->assertSee('Documento registrado e íntegro')->assertDontSee('Ana Demo');
        $this->actingAs($this->user('interno2'))->get(route('reports.download', $report->token))->assertForbidden();
    }

    public function test_report_storage_failure_leaves_no_report_or_success_audit(): void
    {
        $this->seed();
        $this->approve();
        $cycle = DB::table('cycles')->value('id');
        $disk = \Mockery::mock(FilesystemAdapter::class);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $disk->shouldReceive('put')->twice()->andReturn(false);

        $this->from(route('reports'))->post(route('reports.create', $cycle))
            ->assertRedirect(route('reports'))->assertSessionHasErrors('documento')->assertSessionMissing('status');

        $this->actingAs($this->user('interno'))->from(route('reports'))->post(route('reports.personal'))
            ->assertRedirect(route('reports'))->assertSessionHasErrors('documento')->assertSessionMissing('status');

        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'acta.emitida']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'libreta.emitida']);
    }

    public function test_shift_availability_rest_approval_and_completion(): void
    {
        $this->seed();
        $assignment = $this->approve();
        $start = now()->addDays(8)->setTime(8, 0);
        $end = $start->copy()->addHours(6);
        $data = ['name' => 'Guardias de prueba', 'rest_hours' => 12, 'slots' => [['assignment_id' => $assignment, 'starts_at' => $start->toDateTimeString(), 'ends_at' => $end->toDateTimeString()]]];
        $this->post(route('shifts.generate'), $data)->assertSessionHasNoErrors();
        $pending = DB::table('shift_batches')->value('id');
        $this->post(route('shifts.approve', $pending))->assertSessionHasErrors('guardias');
        $this->post(route('availability.store'), ['tutor_id' => $this->user('tutor')->id, 'starts_at' => $start->copy()->subDay()->toDateTimeString(), 'ends_at' => $end->copy()->addDays(3)->toDateTimeString()])->assertSessionHasNoErrors();
        $this->post(route('shifts.generate'), $data)->assertSessionHasNoErrors();
        $batch = DB::table('shift_batches')->max('id');
        $this->post(route('shifts.approve', $batch))->assertSessionHasNoErrors();
        $shift = DB::table('shifts')->where('batch_id', $batch)->first();
        $this->post(route('shifts.performed', $shift->id))->assertUnprocessable();
        $this->post(route('shifts.generate'), $data)->assertSessionHasNoErrors();
        $clash = DB::table('shift_batches')->max('id');
        $this->post(route('shifts.approve', $clash))->assertSessionHasErrors('guardias');
        $this->travelTo($end->copy()->addHour());
        $this->actingAs($this->user('tutor'))->post(route('shifts.performed', $shift->id))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'performed' => true]);
        $this->get(route('shifts'))->assertOk();
    }

    public function test_reoptimization_preserves_assignment_identity_and_previous_place(): void
    {
        $this->seed();
        $assignment = $this->approve();
        $cycle = DB::table('cycles')->value('id');
        $this->post(route('rotations.generate', $cycle), ['study_id' => DB::table('scenario_studies')->value('id')])->assertSessionHasNoErrors();
        $run = DB::table('optimization_runs')->orderByDesc('id')->first();
        $this->assertSame((string) DB::table('places')->value('id'), json_decode($run->input, true)['internos'][0]['plazaAnterior']);
        $this->post(route('rotations.review', $run->id))->assertSessionHasNoErrors();
        $this->post(route('rotations.approve', $run->id))->assertSessionHasNoErrors();
        $this->post(route('rotations.publish', $run->id))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('assignments', 1);
        $this->assertDatabaseHas('assignments', ['id' => $assignment, 'run_id' => $run->id]);
    }

    public function test_swap_requires_other_intern_consent_and_preserves_capacity(): void
    {
        $this->seed();
        $sourceId = $this->approve();
        $source = DB::table('assignments')->find($sourceId);
        $secondPlace = DB::table('places')->where('id', '!=', $source->place_id)->first();
        $targetId = DB::table('assignments')->insertGetId(['cycle_id' => $source->cycle_id, 'user_id' => $this->user('interno2')->id, 'place_id' => $secondPlace->id, 'tutor_id' => $secondPlace->tutor_id, 'run_id' => $source->run_id, 'status' => 'aprobada', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->user('interno'))->post(route('swaps.request', $sourceId), ['target_id' => $targetId, 'reason' => 'Solicitamos intercambiar nuestros hospitales.'])->assertSessionHasNoErrors();
        $swap = DB::table('swap_requests')->value('id');
        $this->actingAs($this->user('coordinador'))->post(route('swaps.approve', $swap))->assertConflict();
        $this->actingAs($this->user('interno'))->post(route('swaps.consent', $swap), ['accept' => 1])->assertForbidden();
        $this->actingAs($this->user('interno2'))->post(route('swaps.consent', $swap), ['accept' => 1])->assertSessionHasNoErrors();
        $this->actingAs($this->user('coordinador'))->post(route('swaps.approve', $swap))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assignments', ['id' => $sourceId, 'place_id' => $secondPlace->id]);
        $this->assertDatabaseHas('assignments', ['id' => $targetId, 'place_id' => $source->place_id]);
        $this->assertDatabaseHas('swap_requests', ['id' => $swap, 'status' => 'aprobada']);
        $this->get(route('clinical'))->assertOk();
    }

    public function test_rubric_grade_is_computed_from_criteria(): void
    {
        $this->seed();
        $assignment = $this->approve();
        $this->travel(8)->days();
        $this->actingAs($this->user('tutor'))->post(route('clinical.record', $assignment), ['date' => now()->toDateString(), 'attendance' => 'presente', 'grade' => 100, 'competencies' => ['conocimiento' => 80, 'procedimientos' => 60, 'comunicacion' => 100, 'profesionalismo' => 80]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clinical_records', ['assignment_id' => $assignment, 'grade' => 80]);
    }
}
