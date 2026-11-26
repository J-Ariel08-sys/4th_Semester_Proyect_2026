<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OptimizerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_php_engine_generates_scenarios_rotations_and_shifts_end_to_end(): void
    {
        $this->seed();
        $cycle = DB::table('cycles')->first();
        $places = DB::table('places')->pluck('id')->all();
        foreach (['interno', 'interno2', 'interno3'] as $index => $email) {
            $user = User::where('email', $email.'@siclin.test')->firstOrFail();
            $this->actingAs($user)->post(route('applications.store', $cycle->id), ['preferences' => $index === 1 ? array_reverse($places) : $places])->assertSessionHasNoErrors();
        }
        $this->actingAs(User::where('role', 'coordinador')->first());
        $this->post(route('cycles.transition', $cycle->id), ['status' => 'cerrada'])->assertSessionHasNoErrors();
        $this->post(route('scenarios.simulate'), ['cycle_id' => $cycle->id, 'name' => 'Integración real', 'alpha' => 0.5])->assertRedirect()->assertSessionHasNoErrors();
        $study = DB::table('scenario_studies')->orderByDesc('id')->first();
        $result = json_decode($study->result, true);
        $this->assertCount(3, $result['simulation']['metrics']);
        foreach ($result['simulation']['metrics'] as $row) {
            $this->assertSame(3, $row[0]['asignados']);
            $this->assertSame(0, $row[2]['asignados']);
        }
        $this->post(route('scenarios.choose', $study->id), ['chosen' => 'A3', 'justification' => 'Política elegida en prueba de integración real.'])->assertSessionHasNoErrors();
        $this->post(route('rotations.generate', $cycle->id), ['study_id' => $study->id])->assertSessionHasNoErrors();
        $run = DB::table('optimization_runs')->first();
        $this->post(route('rotations.review', $run->id))->assertSessionHasNoErrors();
        $this->post(route('rotations.approve', $run->id))->assertSessionHasNoErrors();
        $this->post(route('rotations.publish', $run->id))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('assignments', 3);
        $this->assertDatabaseHas('cycles', ['id' => $cycle->id, 'status' => 'publicada']);
        $assignment = DB::table('assignments')->orderBy('id')->first();
        $start = now()->addDays(8)->setTime(8, 0);
        $end = $start->copy()->addHours(6);
        $this->post(route('availability.store'), ['tutor_id' => $assignment->tutor_id, 'starts_at' => $start->copy()->subHour()->toDateTimeString(), 'ends_at' => $end->copy()->addHour()->toDateTimeString()])->assertSessionHasNoErrors();
        $this->post(route('shifts.generate'), ['name' => 'Integración real guardias', 'rest_hours' => 12, 'slots' => [['assignment_id' => $assignment->id, 'starts_at' => $start->toDateTimeString(), 'ends_at' => $end->toDateTimeString()]]])->assertSessionHasNoErrors();
        $this->post(route('shifts.approve', DB::table('shift_batches')->value('id')))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('shift_batches', ['status' => 'aprobada']);
        $this->get(route('scenarios'))->assertOk();
        $this->get(route('reports'))->assertOk();
    }
}
