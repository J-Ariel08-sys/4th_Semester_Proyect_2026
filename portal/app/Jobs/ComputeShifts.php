<?php
namespace App\Jobs;

use App\Services\ShiftPlanner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ComputeShifts implements ShouldQueue
{
    use Queueable;
    public int $tries=1;
    public int $timeout=180;
    public function __construct(public int $batchId) {}
    public function handle(ShiftPlanner $planner): void
    {
        if (! DB::table('shift_batches')->where('id',$this->batchId)->where('execution_status','en_cola')->update(['execution_status'=>'ejecutando','started_at'=>now()])) { return; }
        try {
            $row=DB::table('shift_batches')->find($this->batchId);
            $input=json_decode($row->input,true,flags:JSON_THROW_ON_ERROR);
            $planned=$planner->calculateSnapshot($input['snapshot']);
            DB::transaction(function () use ($planned) {
                $row=DB::table('shift_batches')->where('id',$this->batchId)->lockForUpdate()->first();
                if ($row->execution_status !== 'ejecutando') { return; }
                foreach ($planned['slots'] as $slot) {
                    unset($slot['user_id']);
                    DB::table('shifts')->insert([...$slot,'batch_id'=>$row->id,'created_at'=>now(),'updated_at'=>now()]);
                }
                DB::table('shift_batches')->where('id',$row->id)->update(['execution_status'=>'completada','diagnostic'=>json_encode($planned['diagnostic']),'updated_at'=>now()]);
            });
        } catch (\Throwable) { $this->failed(null); }
    }
    public function failed(?\Throwable $exception): void
    {
        DB::table('shift_batches')->where('id',$this->batchId)->whereIn('execution_status',['en_cola','ejecutando'])->update(['execution_status'=>'fallida','diagnostic'=>json_encode(['error'=>'Cálculo interrumpido o datos inválidos. Genera otro calendario.']),'updated_at'=>now()]);
    }
}
