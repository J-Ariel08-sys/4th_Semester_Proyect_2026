<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClinicalController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RotationController;
use App\Http\Controllers\ScenarioController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SwapController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::view('/recuperar', 'auth.forgot')->name('password.request');
    Route::post('/recuperar', [AuthController::class, 'emailReset'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/restablecer/{token}', fn (string $token) => view('auth.reset', compact('token')))->name('password.reset');
    Route::post('/restablecer', [AuthController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});
Route::get('/verificar/{token}', [ReportController::class, 'verify'])->middleware('throttle:30,1')->name('reports.verify');
Route::middleware(['auth', 'role:administrador,coordinador,tutor,interno'])->group(function () {
    Route::get('/panel', [PortalController::class, 'index'])->name('dashboard');
    Route::post('/notificaciones/{notification}/leer', [PortalController::class, 'readNotification'])->name('notifications.read');
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
    Route::get('/rotes', [RotationController::class, 'index'])->name('rotations');
    Route::post('/seguimiento/{assignment}/permuta', [SwapController::class, 'request'])->middleware('role:interno')->name('swaps.request');
    Route::post('/permutas/{swap}/consentir', [SwapController::class, 'consent'])->middleware('role:interno')->name('swaps.consent');
    Route::get('/seguimiento', [ClinicalController::class, 'index'])->name('clinical');
    Route::post('/seguimiento/{assignment}/incidencias', [ClinicalController::class, 'incident'])->name('incidents.store');
    Route::get('/reportes', [ReportController::class, 'index'])->name('reports');
    Route::get('/comprobantes/{token}', [ReportController::class, 'receipt'])->name('applications.receipt');
    Route::get('/guardias', [ShiftController::class, 'index'])->name('shifts');
    Route::post('/rotes/{cycle}/postular', [RotationController::class, 'apply'])->middleware('role:interno')->name('applications.store');
    Route::middleware('role:administrador,coordinador,tutor')->group(function () {
        Route::post('/seguimiento/{assignment}/registro', [ClinicalController::class, 'record'])->name('clinical.record');
        Route::post('/disponibilidad', [ShiftController::class, 'availability'])->name('availability.store');
        Route::post('/guardias/{shift}/cumplimiento', [ShiftController::class, 'performed'])->name('shifts.performed');
    });
    Route::middleware('role:administrador,coordinador')->group(function () {
        Route::post('/nomina/importar', [ImportController::class, 'store'])->middleware('role:administrador')->name('users.import');
        Route::post('/permutas/{swap}/aprobar', [SwapController::class, 'approve'])->name('swaps.approve');
        Route::get('/gestion/{resource}', [ManagementController::class, 'index'])->name('management');
        Route::get('/gestion/{resource}/{id}/editar', [ManagementController::class, 'index'])->name('management.edit');
        Route::post('/gestion/{resource}/{id?}', [ManagementController::class, 'save'])->name('management.save');
        Route::post('/convocatorias/{cycle}/estado', [ManagementController::class, 'transition'])->name('cycles.transition');
        Route::post('/rotes/{cycle}/optimizar', [RotationController::class, 'generate'])->name('rotations.generate');
        Route::post('/propuestas/{run}/aprobar', [RotationController::class, 'approve'])->name('rotations.approve');
        Route::post('/incidencias/{incident}/resolver', [ClinicalController::class, 'resolve'])->name('incidents.resolve');
        Route::post('/asignaciones/{assignment}/reasignar', [ClinicalController::class, 'reassign'])->name('assignments.reassign');
        Route::get('/escenarios', [ScenarioController::class, 'index'])->name('scenarios');
        Route::post('/escenarios/simular', [ScenarioController::class, 'simulate'])->middleware('throttle:3,1')->name('scenarios.simulate');
        Route::post('/escenarios', [ScenarioController::class, 'store'])->name('scenarios.store');
        Route::post('/escenarios/{study}/elegir', [ScenarioController::class, 'choose'])->name('scenarios.choose');
        Route::post('/guardias/generar', [ShiftController::class, 'generate'])->name('shifts.generate');
        Route::post('/guardias/lotes/{batch}/aprobar', [ShiftController::class, 'approve'])->name('shifts.approve');
        Route::post('/reportes/{cycle}/generar', [ReportController::class, 'create'])->name('reports.create');
        Route::get('/reportes/{token}/pdf', [ReportController::class, 'download'])->name('reports.download');
        Route::get('/bitacora', [PortalController::class, 'audit'])->name('audit');
    });
});
