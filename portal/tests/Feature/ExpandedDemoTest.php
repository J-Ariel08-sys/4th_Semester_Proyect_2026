<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DecisionAnalysis;
use App\Services\RotationService;
use Database\Seeders\ClinicalDemoSeeder;
use Database\Seeders\ExpandedDemoSeeder;
use Database\Seeders\PresentationDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpandedDemoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_expansion_calculates_real_cases_and_preserves_existing_data_on_repeated_loading(): void
    {
        Storage::fake('local');
        config(['siclin.demo' => true]);
        $this->seed();
        $this->seed(ClinicalDemoSeeder::class);
        $this->seed(PresentationDemoSeeder::class);
        $baseCycle = DB::table('cycles')->where('status', 'abierta')->first();
        DB::table('hospitals')->orderBy('id')->get()->each(fn ($hospital) => DB::table('hospitals')->where('id', $hospital->id)->update(['name' => 'Hospital renombrado por el usuario '.$hospital->id]));
        $originalHospitals = DB::table('hospitals')->orderBy('id')->get();
        $clinical = DB::table('assignments')->first();
        $coordinator = User::where('email', 'coordinador@siclin.test')->firstOrFail();
        $this->actingAs($coordinator)->post(route('clinical.record', $clinical->id), ['date' => now()->toDateString(), 'attendance' => 'presente', 'notes' => 'Registro previo del usuario que debe conservarse.'])->assertSessionHasNoErrors();
        $previous = DB::table('clinical_records')->first();
        $this->seed(ExpandedDemoSeeder::class);
        $this->assertSame($coordinator->id, auth()->id());
        $manifest = json_decode(DB::table('settings')->where('key', ExpandedDemoSeeder::MARKER)->value('value'), true);
        $this->assertCount(7, $manifest['cycles']);
        $this->assertDatabaseCount('cohorts', 2);
        $this->assertDatabaseCount('academic_requirements', 10);
        $this->assertDatabaseCount('applications', 60);
        $this->assertDatabaseCount('assignments', 37);
        $this->assertDatabaseCount('clinical_records', 61);
        $this->assertDatabaseCount('reports', 7);
        $this->assertDatabaseCount('incidents', 4);
        $this->assertDatabaseHas('swap_requests', ['status' => 'consentida']);
        $this->assertDatabaseHas('cycles', ['id' => $baseCycle->id, 'status' => 'abierta']);
        $this->assertDatabaseMissing('applications', ['cycle_id' => $baseCycle->id, 'user_id' => User::where('email', 'interno@siclin.test')->value('id')]);
        $this->assertEquals($previous, DB::table('clinical_records')->find($previous->id));
        $this->assertEquals($originalHospitals, DB::table('hospitals')->whereIn('id', $originalHospitals->pluck('id'))->orderBy('id')->get());
        $service = app(RotationService::class);
        $originalDemo = $service->evaluate($service->input($baseCycle->id, DecisionAnalysis::POLICIES['A1']));
        $this->assertCount(2, $originalDemo['asignaciones']);
        $this->assertCount(0, $originalDemo['pendientes']);
        foreach ($manifest['runs'] as $name => $id) {
            $run = DB::table('optimization_runs')->find($id);
            $this->assertSame('completada', $run->execution_status);
            $result = json_decode($run->result, true);
            $this->assertSame('php-min-cost-flow/1', $result['engine']);
            $this->assertCount($name === 'escasez' ? 2 : 12, $result['asignaciones']);
            $this->assertCount($name === 'escasez' ? 2 : 0, $result['pendientes']);
        }
        foreach ($manifest['studies'] as $id) {
            $study = DB::table('scenario_studies')->find($id);
            $this->assertSame('completada', $study->execution_status);
            $result = json_decode($study->result, true);
            $this->assertSame('php-min-cost-flow/1', $result['simulation']['engine']);
            foreach ($result['simulation']['metrics'] as $metrics) {
                $this->assertCount(3, $metrics);
            }
        }
        foreach (DB::table('reports')->get() as $report) {
            $pdf = Storage::disk('local')->get($report->path);
            $this->assertStringStartsWith('%PDF-', $pdf);
            $this->assertSame($report->sha256, hash('sha256', $pdf));
        }
        foreach (['rotations', 'scenarios', 'clinical', 'reports'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $privateReport = DB::table('reports')->whereNotNull('user_id')->first();
        $consolidated = DB::table('reports')->whereNull('user_id')->first();
        $this->get(route('reports'))->assertSee($consolidated->token)->assertDontSee($privateReport->token);
        $this->get(route('reports.download', $privateReport->token))->assertForbidden();
        $review = $manifest['runs']['revision'];
        $this->post(route('rotations.approve', $review))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('optimization_runs', ['id' => $review, 'plan_status' => 'aprobado']);
        $this->post(route('rotations.publish', $review))->assertSessionHasNoErrors();
        $this->assertSame(12, DB::table('assignments')->where('cycle_id', $manifest['cycles']['revision'])->count());
        $this->post(route('rotations.review', $manifest['runs']['escasez']))->assertSessionHasNoErrors();
        $this->post(route('rotations.approve', $manifest['runs']['escasez']))->assertSessionHasErrors('propuesta');
        $swap = DB::table('swap_requests')->first();
        $this->post(route('swaps.approve', $swap->id))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('swap_requests', ['id' => $swap->id, 'status' => 'aprobada']);
        $this->assertDatabaseHas('assignments', ['id' => $swap->source_id, 'place_id' => $swap->target_place_id]);
        $this->post(route('cycles.transition', $manifest['cycles']['borrador']), ['status' => 'abierta'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cycles', ['id' => $manifest['cycles']['borrador'], 'status' => 'abierta']);
        $daniela = User::where('email', 'interno.demo07@siclin.test')->firstOrFail();
        $place = DB::table('places')->where('cycle_id', $manifest['cycles']['oferta'])->value('id');
        $this->actingAs($daniela)->post(route('applications.store', $manifest['cycles']['oferta']), ['preferences' => [$place]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('applications', ['cycle_id' => $manifest['cycles']['oferta'], 'user_id' => $daniela->id]);
        $intern = User::where('email', 'interno.demo01@siclin.test')->firstOrFail();
        $intern->forceFill(['password' => 'ClaveCambiadaPorElUsuario2026!'])->save();
        $password = $intern->fresh()->password;
        DB::table('incidents')->where('status', 'abierta')->update(['status' => 'resuelta', 'resolution' => 'Resuelta durante la presentación por el usuario.']);
        $counts = [];
        foreach (['users', 'cycles', 'applications', 'assignments', 'clinical_records', 'reports', 'incidents', 'swap_requests', 'shifts', 'scenario_studies'] as $table) {
            $counts[$table] = DB::table($table)->count();
        }
        $this->seed(ExpandedDemoSeeder::class);
        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertSame($password, $intern->fresh()->password);
        $this->assertDatabaseHas('optimization_runs', ['id' => $review, 'plan_status' => 'publicado']);
        $this->assertSame(0, DB::table('incidents')->where('status', 'abierta')->count());
        $this->actingAs($intern)->get(route('clinical'))->assertOk()->assertSee($intern->name)->assertDontSee('Gabriel Ampliación Demo');
        $consolidated = DB::table('reports')->whereNull('user_id')->first();
        $this->get(route('reports.download', $consolidated->token))->assertForbidden();
        $own = DB::table('reports')->where('user_id', $intern->id)->first();
        $this->get(route('reports.download', $own->token))->assertOk();
    }

    public function test_expansion_requires_the_demo_flag(): void
    {
        config(['siclin.demo' => false]);
        $this->expectException(\RuntimeException::class);
        $this->seed(ExpandedDemoSeeder::class);
    }
}
