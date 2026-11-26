<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class AssignmentHistory
{
    public static function capture(object $assignment, string $reason): void
    {
        DB::table('assignment_history')->insert(['assignment_id' => $assignment->id, 'run_id' => $assignment->run_id, 'place_id' => $assignment->place_id, 'tutor_id' => $assignment->tutor_id, 'changed_by' => auth()->id(), 'reason' => $reason, 'created_at' => now()]);
        DB::table('reports')->where('cycle_id', $assignment->cycle_id)->update(['status' => 'historico']);
    }
}
