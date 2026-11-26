<?php
namespace App\Jobs;

use App\Services\ScenarioService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ComputeScenario implements ShouldQueue
{
    use Queueable;
    public int $tries=1;
    public int $timeout=180;
    public function __construct(public int $studyId) {}
    public function handle(ScenarioService $service): void
    {
        if (! DB::table('scenario_studies')->where('id',$this->studyId)->where('execution_status','en_cola')->update(['execution_status'=>'ejecutando','started_at'=>now()])) { return; }
        try {
            $row=DB::table('scenario_studies')->find($this->studyId);
            $input=json_decode($row->input,true,flags:JSON_THROW_ON_ERROR);
            $answer=$service->calculate($input['base'],$input['parameters']);
            DB::table('scenario_studies')->where('id',$row->id)->where('execution_status','ejecutando')->update(['matrix'=>json_encode($answer['matrix']),'result'=>json_encode($answer['result']),'execution_status'=>'completada','updated_at'=>now()]);
        } catch (\Throwable) { $this->failed(null); }
    }
    public function failed(?\Throwable $exception): void
    {
        DB::table('scenario_studies')->where('id',$this->studyId)->whereIn('execution_status',['en_cola','ejecutando'])->update(['execution_status'=>'fallida','diagnostic'=>'Cálculo interrumpido o datos inválidos. Genera otra simulación.','updated_at'=>now()]);
    }
}
