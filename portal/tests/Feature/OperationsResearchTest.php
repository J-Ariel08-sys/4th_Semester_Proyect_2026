<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MarkovService;
use App\Services\OperationsDecisionService;
use App\Services\QueueingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OperationsResearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function user(string $name): User
    {
        return User::where('email', $name.'@siclin.test')->firstOrFail();
    }

    public function test_mms_queueing_matches_exact_erlang_c_values_and_handles_overload(): void
    {
        $service = new QueueingService;

        $sin = $service->solve(3, 11.0, 4.0);
        $this->assertTrue($sin['estable']);
        $this->assertTrue($sin['sobrecarga']);
        $this->assertEqualsWithDelta(50.80, $sin['wq_minutes'], 0.02);
        $this->assertEqualsWithDelta(91.67, $sin['rho_pct'], 0.05);

        $con = $service->solve(3, 11.0, 5.0);
        $this->assertTrue($con['estable']);
        $this->assertFalse($con['sobrecarga']);
        $this->assertEqualsWithDelta(8.13, $con['wq_minutes'], 0.02);
        $this->assertEqualsWithDelta(73.33, $con['rho_pct'], 0.05);

        $unstable = $service->solve(2, 12.0, 5.0);
        $this->assertFalse($unstable['estable']);
        $this->assertTrue($unstable['sobrecarga']);
        $this->assertNull($unstable['wq_minutes']);
    }

    public function test_maximin_decision_recommends_a3_with_expected_payoffs(): void
    {
        $queueing = (new QueueingService)->compareHospital((object) [
            'id' => 1,
            'name' => 'Hospital de Clínicas',
            'tutors_count' => 3,
            'lambda_e1' => 8.0,
            'lambda_e2' => 10.0,
            'lambda_e3' => 11.0,
            'mu_without_interns' => 4.0,
            'mu_with_interns' => 5.0,
        ], 12, 20);

        $result = (new OperationsDecisionService)->evaluate(
            ['E1' => 0.485, 'E2' => 0.338, 'E3' => 0.177],
            $queueing,
            [
                'plazas_ofertadas' => 20,
                'cupos_utilizables' => 17,
                'cupos_reserva' => 3,
                'postulantes' => 12,
                'internos_habilitados' => 12,
            ]
        );

        $this->assertSame('A3', $result['politica_recomendada']);
        $this->assertEquals(40.0, $result['matriz']['A1']['peor_escenario']);
        $this->assertEquals(48.0, $result['matriz']['A2']['peor_escenario']);
        $this->assertEquals(68.0, $result['matriz']['A3']['peor_escenario']);
        $this->assertEqualsWithDelta(84.75, $result['matriz']['A3']['valor_esperado'], 0.05);
        $this->assertEquals(15.0, $result['porcentaje_brigadas']);
    }

    public function test_markov_chain_computes_stationary_mfpt_forecast_and_traffic_light(): void
    {
        $service = new MarkovService;

        // Current state E1 -> P(E3)=0.08 -> semaforo-verde
        $green = $service->analyze([
            ['year_month' => '2026-01', 'state' => 'E1'],
        ]);
        $this->assertSame('semaforo-verde', $green['semaforo']['css_class']);
        $this->assertCount(6, $green['prediccion_6_meses']);
        $this->assertEqualsWithDelta(
            1.0,
            $green['estacionaria']['E1'] + $green['estacionaria']['E2'] + $green['estacionaria']['E3'],
            0.002
        );
        $this->assertGreaterThan(5.0, $green['tiempo_primer_paso']['m13']);

        // Current state E2 -> P(E3)=0.20 -> semaforo-amarillo
        $yellow = $service->analyze([
            ['year_month' => '2026-01', 'state' => 'E1'],
            ['year_month' => '2026-02', 'state' => 'E2'],
        ]);
        $this->assertSame('semaforo-amarillo', $yellow['semaforo']['css_class']);

        // Current state E3 -> P(E3)=0.50 -> semaforo-rojo
        $red = $service->analyze([
            ['year_month' => '2026-01', 'state' => 'E2'],
            ['year_month' => '2026-02', 'state' => 'E3'],
        ]);
        $this->assertSame('semaforo-rojo', $red['semaforo']['css_class']);
        $this->assertTrue($red['semaforo']['emergencia_habilitada']);
    }

    public function test_dashboard_api_monthly_state_and_sprint_views_work_end_to_end(): void
    {
        $this->seed();
        Queue::fake();

        $coordinator = $this->user('coordinador');
        $intern = $this->user('interno');

        // Intern cannot access IO dashboard API
        $this->actingAs($intern)->getJson(route('io.dashboard.api'))->assertForbidden();

        // Coordinator can access unified IO API and dashboard page
        $this->actingAs($coordinator)
            ->getJson(route('io.dashboard.api'))
            ->assertOk()
            ->assertJsonStructure([
                'resumen_bd' => ['hospitales_activos', 'internos_habilitados', 'plazas_ofertadas'],
                'colas' => ['s_tutores', 'escenarios' => ['E1', 'E2', 'E3']],
                'decisiones' => ['politica_recomendada', 'matriz' => ['A1', 'A2', 'A3']],
                'markov' => ['semaforo', 'prediccion_6_meses', 'estacionaria', 'tiempo_primer_paso'],
            ])
            ->assertJsonPath('decisiones.politica_recomendada', 'A3');

        $this->actingAs($coordinator)->get(route('io.dashboard'))
            ->assertOk()
            ->assertSee('Capacidad y Descongestión Hospitalaria')
            ->assertSee('Matriz de Pagos y Política Recomendada')
            ->assertSee('Semáforo Predictivo de Contingencias y Proyección a 6 Meses');

        // Register monthly observed states (E1 -> E3 triggers red traffic light)
        $this->actingAs($coordinator)
            ->post(route('io.monthly-state.store'), [
                'year_month' => '2026-09',
                'state' => 'E1',
                'notes' => 'Operación regular en red La Paz',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($coordinator)
            ->post(route('io.monthly-state.store'), [
                'year_month' => '2026-10',
                'state' => 'E3',
                'notes' => 'Pico epidemiológico de contingencia',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($coordinator)
            ->getJson(route('io.dashboard.api'))
            ->assertOk()
            ->assertJsonPath('markov.estado_actual', 'E3')
            ->assertJsonPath('markov.semaforo.css_class', 'semaforo-rojo')
            ->assertJsonPath('markov.transiciones_observadas', 1);

        // Sprint 2, 3, 4 screens render their respective IO panels
        $this->actingAs($coordinator)->get(route('management', 'hospitales'))
            ->assertOk()
            ->assertSee('Capacidad y Descongestión Hospitalaria');

        $this->actingAs($coordinator)->get(route('rotations'))
            ->assertOk()
            ->assertSee('Matriz de Pagos y Política Recomendada');

        $this->actingAs($coordinator)->get(route('clinical'))
            ->assertOk()
            ->assertSee('Semáforo Predictivo de Contingencias y Proyección a 6 Meses')
            ->assertSee('semaforo-rojo');
    }

    public function test_apply_policy_a3_creates_study_and_dispatches_optimization(): void
    {
        $this->seed();
        Queue::fake();

        $coordinator = $this->user('coordinador');
        $cycle = DB::table('cycles')->where('status', 'cerrada')->first();
        if (! $cycle) {
            $specialtyId = DB::table('specialties')->value('id');
            $hospitalId = DB::table('hospitals')->value('id');
            $tutorId = $this->user('tutor')->id;
            $internId = $this->user('interno')->id;
            $cycleId = DB::table('cycles')->insertGetId([
                'name' => 'Convocatoria IO Test',
                'specialty_id' => $specialtyId,
                'starts_on' => now()->addDays(15)->toDateString(),
                'ends_on' => now()->addDays(45)->toDateString(),
                'opens_at' => now()->subDays(5),
                'closes_at' => now()->subDay(),
                'status' => 'cerrada',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $placeId = DB::table('places')->insertGetId([
                'cycle_id' => $cycleId,
                'hospital_id' => $hospitalId,
                'tutor_id' => $tutorId,
                'capacity' => 5,
                'reserve' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('applications')->insert([
                'cycle_id' => $cycleId,
                'user_id' => $internId,
                'preferences' => json_encode([$placeId]),
                'receipt' => (string) \Illuminate\Support\Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $cycleId = $cycle->id;
        }

        $this->actingAs($coordinator)
            ->post(route('io.apply-a3', $cycleId))
            ->assertRedirect(route('rotations').'#propuestas')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('scenario_studies', [
            'chosen' => 'A3',
            'execution_status' => 'completada',
        ]);
        $this->assertDatabaseHas('optimization_runs', [
            'cycle_id' => $cycleId,
        ]);
    }
}
