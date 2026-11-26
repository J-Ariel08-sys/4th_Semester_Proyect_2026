<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Artisan::command('siclin:health', function () {
    \Illuminate\Support\Facades\DB::select('select 1');
    foreach (['users', 'jobs', 'otp_challenges'] as $table) {
        if (! \Illuminate\Support\Facades\Schema::hasTable($table)) { return 1; }
    }
    return 0;
});
\Illuminate\Support\Facades\Schedule::call(function () {
    \Illuminate\Support\Facades\DB::table('otp_challenges')->where('expires_at', '<', now()->subDay())->delete();
    \Illuminate\Support\Facades\DB::table('otp_limits')->where('window_start', '<', now()->subDay())->delete();
    \Illuminate\Support\Facades\DB::table('optimization_runs')->where('execution_status', 'ejecutando')->where('started_at', '<', now()->subMinutes(4))->update(['execution_status' => 'fallida', 'diagnostic' => 'Worker interrumpido; reintento autorizado disponible.', 'updated_at' => now()]);
    foreach (['scenario_studies','shift_batches'] as $table) { \Illuminate\Support\Facades\DB::table($table)->where('execution_status','ejecutando')->where('started_at','<',now()->subMinutes(4))->update(['execution_status'=>'fallida','updated_at'=>now()]); }
})->everyMinute()->name('siclin-maintenance')->withoutOverlapping();

\Illuminate\Support\Facades\Artisan::command('siclin:e2e-reset', function () {
    if (config('database.connections.pgsql.database') !== 'siclin_e2e_test' || config('database.connections.pgsql.username') !== 'siclin_e2e' || ! app()->environment('local')) { $this->error('Solo se permite en la base aislada siclin_e2e_test con usuario siclin_e2e.'); return 1; }
    return $this->call('migrate:fresh', ['--seed'=>true,'--force'=>true]);
});
