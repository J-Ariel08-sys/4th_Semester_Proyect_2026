<?php

namespace Tests\Feature;

use App\Jobs\OptimizeRotations;
use App\Models\User;
use App\Services\{DecisionAnalysis, RotationOptimizer, ShiftOptimizer};
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\{DB, Queue};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthorizationAndDecisionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_administrator_cannot_read_or_change_academic_records_and_last_admin_is_protected(): void
    {
        $this->seed();
        Queue::fake();
        $admin = User::where('role','administrador')->first();
        $this->actingAs($admin);
        foreach (['rotations','clinical','shifts','reports','scenarios'] as $route) { $this->get(route($route))->assertForbidden(); }
        $this->post(route('management.save','hospitales'), [])->assertForbidden();
        $this->post(route('clinical.record',1), [])->assertForbidden();
        $this->post(route('rotations.approve',1), [])->assertForbidden();
        $this->post(route('management.save',['usuarios',$admin->id]), ['name'=>$admin->name,'email'=>$admin->email,'role'=>'coordinador','active'=>0])->assertSessionHasErrors('role');
        $this->post(route('management.save','usuarios'), ['name'=>'Nuevo Demo','email'=>'NUEVO@example.test','role'=>'interno','active'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users',['email'=>'nuevo@example.test','activated_at'=>null,'eligible'=>false]);
    }

    public function test_internal_cannot_elevate_own_role_or_change_academic_fields(): void
    {
        $this->seed();
        $user = User::where('email','interno@siclin.test')->firstOrFail();
        $this->actingAs($user)->post(route('account.update'), ['current_password'=>'SiclinDemo2026!','role'=>'administrador','eligible'=>1])->assertSessionHasErrors(['role','eligible']);
        $this->post(route('management.save',['internos',$user->id]), [])->assertForbidden();
        $this->assertSame('interno',$user->fresh()->role);
    }

    public function test_cost_matrix_rectangular_hand_calculation_and_boundary_alpha(): void
    {
        $analysis = new DecisionAnalysis;
        $matrix = ['A1'=>['E1'=>4,'E2'=>12], 'A2'=>['E1'=>7,'E2'=>8], 'A3'=>['E1'=>6,'E2'=>10]];
        $result = $analysis->calculate($matrix,0.5,'costs');
        $this->assertSame(['A2'],$result['winners']['maximin']);
        $this->assertSame(['A1'],$result['winners']['maximax']);
        $this->assertSame(['A2'],$result['winners']['laplace']);
        $this->assertSame(['A3'],$result['winners']['savage']);
        $this->assertEquals(['E1'=>0,'E2'=>4],$result['regrets']['A1']);
        $this->assertEquals(-7.5,$result['scores']['A2']['hurwicz']);
        $this->assertSame(['A2'],$analysis->calculate($matrix,0,'costs')['winners']['hurwicz']);
        $this->assertSame(['A1'],$analysis->calculate($matrix,1,'costs')['winners']['hurwicz']);
        $this->expectException(ValidationException::class);
        $analysis->calculate(['A1'=>[1,2],'A2'=>[3]],0.5);
    }

    public function test_benefit_ties_and_nonfinite_inputs(): void
    {
        $analysis = new DecisionAnalysis;
        $result = $analysis->calculate(['A1'=>[1,1],'A2'=>[1,1]],0);
        $this->assertSame(['A1','A2'],$result['winners']['savage']);
        $this->expectException(ValidationException::class);
        $analysis->calculate(['A1'=>[INF]],0.5);
    }

    public function test_merit_changes_first_choice_when_all_interns_receive_a_place(): void
    {
        $input = ['plazas'=>[['id'=>'a','capacidad'=>1,'reserva'=>0],['id'=>'b','capacidad'=>1,'reserva'=>0]], 'internos'=>[['id'=>'low','habilitado'=>true,'merito'=>20,'preferencias'=>['a','b'],'plazaAnterior'=>null],['id'=>'high','habilitado'=>true,'merito'=>90,'preferencias'=>['a','b'],'plazaAnterior'=>null]], 'politica'=>DecisionAnalysis::POLICIES['A1'],'segundos'=>2];
        $result = (new RotationOptimizer)->solve($input);
        $this->assertCount(2,$result['asignaciones']);
        $this->assertSame('a',collect($result['asignaciones'])->keyBy('internoId')['high']['plazaId']);
        $input['internos'][0]['merito']=100;
        $result = (new RotationOptimizer)->solve($input);
        $this->assertSame('a',collect($result['asignaciones'])->keyBy('internoId')['low']['plazaId']);
    }

    public function test_reserve_eligibility_shared_supervision_and_insufficient_capacity(): void
    {
        $input = ['plazas'=>[['id'=>'a','capacidad'=>2,'reserva'=>1,'tutorId'=>'t'],['id'=>'b','capacidad'=>2,'reserva'=>0,'tutorId'=>'t']], 'tutores'=>['t'=>1], 'internos'=>[['id'=>'i1','habilitado'=>true,'merito'=>90,'preferencias'=>['a'],'plazaAnterior'=>null],['id'=>'i2','habilitado'=>true,'merito'=>50,'preferencias'=>['b'],'plazaAnterior'=>null],['id'=>'i3','habilitado'=>false,'merito'=>100,'preferencias'=>['a'],'plazaAnterior'=>null]], 'politica'=>DecisionAnalysis::POLICIES['A1'],'segundos'=>2];
        $result = (new RotationOptimizer)->solve($input);
        $this->assertCount(1,$result['asignaciones']);
        $this->assertCount(2,$result['pendientes']);
        $this->assertTrue($result['search_complete']);
        $this->assertSame('i1',$result['asignaciones'][0]['internoId']);
    }

    public function test_180_interns_use_180_places_in_45_capacity_groups(): void
    {
        $input=['plazas'=>[],'internos'=>[],'politica'=>DecisionAnalysis::POLICIES['A1'],'segundos'=>10];
        for ($i=0;$i<45;$i++) { $input['plazas'][]=['id'=>'p'.$i,'capacidad'=>5,'reserva'=>1]; }
        for ($i=0;$i<180;$i++) { $input['internos'][]=['id'=>'i'.$i,'habilitado'=>true,'merito'=>80,'preferencias'=>['p'.($i%45)],'plazaAnterior'=>null]; }
        $result=(new RotationOptimizer)->solve($input);
        $this->assertCount(180,$result['asignaciones']);
        $this->assertEmpty($result['pendientes']);
        $this->assertSame(180,$result['primerasPreferencias']);
        $this->assertTrue($result['search_complete']);
        foreach ($result['ocupacion'] as $count) { $this->assertSame(4,$count); }
        fwrite(STDERR, 'PHP benchmark 180 internos / 45 grupos: '.$result['duration_ms']." ms\n");
    }

    public function test_shift_search_handles_non_greedy_coverage_and_rest_conflicts(): void
    {
        $input=['turnos'=>[['id'=>'1','internoId'=>'i1','inicio'=>100000,'fin'=>103600,'tutoresPermitidos'=>['a','b']],['id'=>'2','internoId'=>'i2','inicio'=>100000,'fin'=>103600,'tutoresPermitidos'=>['a']]],'cargaPreviaMinutos'=>['a'=>0,'b'=>0],'disponibilidadMinutos'=>['a'=>60,'b'=>120],'descansoHoras'=>12,'segundos'=>2];
        $result=(new ShiftOptimizer)->solve($input);
        $this->assertSame('b',$result['turnos'][0]['tutorId']);
        $this->assertSame('a',$result['turnos'][1]['tutorId']);
        $input['turnos'][1]['internoId']='i1';
        $result=(new ShiftOptimizer)->solve($input);
        $this->assertCount(1,array_filter($result['turnos'],fn ($s)=>$s['tutorId']!==null));
    }

    public function test_repeated_jobs_cannot_duplicate_result_and_retry_is_authorized(): void
    {
        $this->seed();
        Queue::fake();
        $user=User::where('email','interno@siclin.test')->firstOrFail();
        $cycle=DB::table('cycles')->first();
        $this->actingAs($user)->post(route('applications.store',$cycle->id),['preferences'=>DB::table('places')->pluck('id')->all()]);
        $this->actingAs(User::where('role','coordinador')->first());
        $this->post(route('cycles.transition',$cycle->id),['status'=>'cerrada']);
        $study=DB::table('scenario_studies')->first();
        $this->post(route('scenarios.choose',$study->id),['chosen'=>'A1','justification'=>'Prueba de cola persistente e idempotencia.']);
        $this->post(route('rotations.generate',$cycle->id),['study_id'=>$study->id]);
        $this->post(route('rotations.generate',$cycle->id),['study_id'=>$study->id]);
        $this->assertDatabaseCount('optimization_runs',1);
        $id=DB::table('optimization_runs')->value('id');
        $job=new OptimizeRotations($id);
        $job->handle(app(\App\Services\RotationService::class));
        $job->handle(app(\App\Services\RotationService::class));
        $this->assertDatabaseHas('optimization_runs',['id'=>$id,'execution_status'=>'completada','attempts'=>1]);
        $this->assertDatabaseCount('assignments',0);
        $this->actingAs($user)->get(route('rotations.status',$id))->assertForbidden();
    }

    public function test_cohort_completeness_duration_and_postulation_deadline_are_checked(): void
    {
        $this->seed();
        $cohort=DB::table('cohorts')->insertGetId(['name'=>'Cohorte demo','entry_year'=>2026,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('users')->where('role','interno')->update(['cohort_id'=>$cohort]);
        $cycle=DB::table('cycles')->first();
        DB::table('cycles')->where('id',$cycle->id)->update(['cohort_id'=>$cohort]);
        DB::table('academic_requirements')->insert(['cohort_id'=>$cohort,'specialty_id'=>$cycle->specialty_id,'min_days'=>60,'required_blocks'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $intern=User::where('email','interno@siclin.test')->firstOrFail();
        $this->actingAs($intern)->post(route('applications.store',$cycle->id),['preferences'=>DB::table('places')->pluck('id')->all()])->assertSessionHasNoErrors();
        $input=app(\App\Services\RotationService::class)->input($cycle->id,DecisionAnalysis::POLICIES['A1']);
        $this->assertCount(3,$input['internos']);
        $this->assertCount(0,array_filter($input['internos'],fn($i)=>$i['habilitado']));
        DB::table('academic_requirements')->update(['min_days'=>20]);
        $input=app(\App\Services\RotationService::class)->input($cycle->id,DecisionAnalysis::POLICIES['A1']);
        $this->assertCount(1,array_filter($input['internos'],fn($i)=>$i['habilitado']));
        $result=(new RotationOptimizer)->solve($input);
        $this->assertCount(2,$result['pendientes']);
        $this->travelTo(\Carbon\Carbon::parse($cycle->closes_at));
        $this->actingAs(User::where('email','interno2@siclin.test')->first())->post(route('applications.store',$cycle->id),['preferences'=>DB::table('places')->pluck('id')->all()])->assertSessionHasErrors('preferences');
    }

    public function test_exceptional_application_keeps_old_receipt_and_invalidates_snapshot(): void
    {
        $this->seed();
        $cycle=DB::table('cycles')->first();$places=DB::table('places')->pluck('id')->all();
        $intern=User::where('email','interno@siclin.test')->firstOrFail();
        $this->actingAs($intern)->post(route('applications.store',$cycle->id),['preferences'=>$places]);
        $old=DB::table('applications')->where('user_id',$intern->id)->first();
        $service=app(\App\Services\RotationService::class);
        $hash=$service->hash($service->input($cycle->id,DecisionAnalysis::POLICIES['A1']));
        $this->actingAs(User::where('role','coordinador')->first());
        $this->post(route('cycles.transition',$cycle->id),['status'=>'cerrada']);
        $this->post(route('applications.exceptional',$cycle->id),['user_id'=>$intern->id,'preferences'=>array_reverse($places),'reason'=>'Corrección excepcional con autorización académica documentada.'])->assertSessionHasNoErrors();
        $this->assertNotSame($hash,$service->hash($service->input($cycle->id,DecisionAnalysis::POLICIES['A1'])));
        $this->assertDatabaseHas('application_versions',['receipt'=>$old->receipt]);
        $this->actingAs($intern)->get(route('applications.receipt',$old->receipt))->assertOk();
        $this->actingAs(User::where('email','interno2@siclin.test')->first())->get(route('applications.receipt',$old->receipt))->assertForbidden();
    }

    public function test_rubric_requires_one_hundred_percent_and_admin_cannot_edit_it(): void
    {
        $this->seed();
        $this->actingAs(User::where('role','coordinador')->first());
        $this->post(route('rubric.save'),['weights'=>['conocimiento'=>40,'procedimientos'=>20,'comunicacion'=>20,'profesionalismo'=>10]])->assertSessionHasErrors('weights');
        $this->post(route('rubric.save'),['weights'=>['conocimiento'=>40,'procedimientos'=>20,'comunicacion'=>20,'profesionalismo'=>20]])->assertSessionHasNoErrors();
        $this->assertEquals(40,\App\Services\Settings::rubric()['conocimiento']);
        $this->actingAs(User::where('role','administrador')->first())->post(route('rubric.save'),[])->assertForbidden();
    }
}
