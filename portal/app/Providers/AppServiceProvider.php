<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::define('manage-accounts', fn ($user) => $user->active && $user->role === 'administrador');
        \Illuminate\Support\Facades\Gate::define('manage-academics', fn ($user) => $user->active && $user->role === 'coordinador');
        \Illuminate\Support\Facades\Gate::define('download-report', fn ($user, $report) => $user->active && ($report->user_id === $user->id || ($report->user_id === null && $user->role === 'coordinador')));
    }
}
