<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClinicalController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\OtpController;
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
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:60,1,login:');
    Route::view('/recuperar', 'auth.forgot')->name('password.request');
    Route::post('/recuperar', [OtpController::class, 'request'])->name('password.email');
    Route::get('/codigo', [OtpController::class, 'form'])->name('otp.verify');
    Route::post('/codigo', [OtpController::class, 'verify'])->middleware('throttle:30,1,otp-verify:')->name('otp.check');
    Route::get('/restablecer', [OtpController::class, 'resetForm'])->name('password.reset');
    Route::post('/restablecer', [OtpController::class, 'complete'])->middleware('throttle:10,1,password-reset:')->name('password.update');
});
Route::get('/verificar/{token}', [ReportController::class, 'verify'])->middleware('throttle:30,1,report-verify:')->name('reports.verify');
Route::middleware(['auth', 'role:administrador,coordinador,tutor,interno'])->group(function () {
    Route::get('/panel', [PortalController::class, 'index'])->name('dashboard');
    Route::get('/mi-cuenta', [AccountController::class, 'index'])->name('account');
    Route::post('/mi-cuenta', [AccountController::class, 'update'])->name('account.update');
    Route::view('/ayuda', 'help')->name('help');
    Route::post('/notificaciones/{notification}/leer', [PortalController::class, 'readNotification'])->name('notifications.read');
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
    Route::middleware('role:administrador')->group(function () {
        Route::get('/bitacora', [PortalController::class, 'audit'])->name('audit');
        Route::view('/permisos', 'permissions')->name('permissions');
        Route::get('/configuracion', [AccountController::class, 'settings'])->name('settings');
        Route::post('/configuracion', [AccountController::class, 'saveSettings'])->name('settings.save');
        Route::post('/usuarios/{user}/activar', [AccountController::class, 'activation'])->name('users.activate');
    });
    Route::middleware('role:administrador,coordinador')->group(function () {
        Route::get('/gestion/{resource}', [ManagementController::class, 'index'])->name('management');
        Route::get('/gestion/{resource}/{id}/editar', [ManagementController::class, 'index'])->name('management.edit');
        Route::delete('/gestion/{resource}/{id}', [ManagementController::class, 'destroy'])->name('management.destroy');
        Route::post('/gestion/{resource}/{id?}', [ManagementController::class, 'save'])->name('management.save');
    });
    Route::middleware('role:coordinador,tutor,interno')->group(function () {
        Route::get('/rotes', [RotationController::class, 'index'])->name('rotations');
        Route::get('/seguimiento', [ClinicalController::class, 'index'])->name('clinical');
        Route::post('/seguimiento/{assignment}/incidencias', [ClinicalController::class, 'incident'])->name('incidents.store');
        Route::get('/reportes', [ReportController::class, 'index'])->name('reports');
        Route::get('/reportes/csv', [ReportController::class, 'csv'])->name('reports.csv');
        Route::post('/reportes/libreta', [ReportController::class, 'personal'])->name('reports.personal');
        Route::get('/reportes/{token}/pdf', [ReportController::class, 'download'])->name('reports.download');
        Route::get('/comprobantes/{token}', [ReportController::class, 'receipt'])->name('applications.receipt');
        Route::get('/guardias', [ShiftController::class, 'index'])->name('shifts');
    });
    Route::middleware('role:interno')->group(function () {
        Route::post('/rotes/{cycle}/postular', [RotationController::class, 'apply'])->name('applications.store');
        Route::post('/seguimiento/{assignment}/permuta', [SwapController::class, 'request'])->name('swaps.request');
        Route::post('/permutas/{swap}/consentir', [SwapController::class, 'consent'])->name('swaps.consent');
    });
    Route::middleware('role:coordinador,tutor')->group(function () {
        Route::post('/seguimiento/{assignment}/registro', [ClinicalController::class, 'record'])->name('clinical.record');
        Route::post('/disponibilidad', [ShiftController::class, 'availability'])->name('availability.store');
        Route::post('/guardias/{shift}/cumplimiento', [ShiftController::class, 'performed'])->name('shifts.performed');
    });
    Route::middleware('role:coordinador')->group(function () {
        Route::post('/permutas/{swap}/aprobar', [SwapController::class, 'approve'])->name('swaps.approve');
        Route::post('/convocatorias/{cycle}/estado', [ManagementController::class, 'transition'])->name('cycles.transition');
        Route::post('/rotes/{cycle}/excepcion', [RotationController::class, 'exceptional'])->name('applications.exceptional');
        Route::post('/rotes/{cycle}/optimizar', [RotationController::class, 'generate'])->name('rotations.generate');
        Route::get('/propuestas/{run}/estado', [RotationController::class, 'status'])->name('rotations.status');
        Route::post('/propuestas/{run}/cancelar', [RotationController::class, 'cancel'])->name('rotations.cancel');
        Route::post('/propuestas/{run}/reintentar', [RotationController::class, 'retry'])->name('rotations.retry');
        Route::post('/propuestas/{run}/revision', [RotationController::class, 'review'])->name('rotations.review');
        Route::post('/propuestas/{run}/aprobar', [RotationController::class, 'approve'])->name('rotations.approve');
        Route::post('/propuestas/{run}/publicar', [RotationController::class, 'publish'])->name('rotations.publish');
        Route::post('/incidencias/{incident}/resolver', [ClinicalController::class, 'resolve'])->name('incidents.resolve');
        Route::post('/asignaciones/{assignment}/reasignar', [ClinicalController::class, 'reassign'])->name('assignments.reassign');
        Route::get('/escenarios/{study}/estado', [ScenarioController::class, 'status'])->middleware('role:coordinador')->name('scenarios.status');
        Route::get('/guardias/lotes/{batch}/estado', [ShiftController::class, 'status'])->middleware('role:coordinador')->name('shifts.status');
        Route::get('/escenarios', [ScenarioController::class, 'index'])->name('scenarios');
        Route::post('/escenarios/simular', [ScenarioController::class, 'simulate'])->middleware('throttle:3,1,scenario-simulate:')->name('scenarios.simulate');
        Route::get('/escenarios/plantilla', [ScenarioController::class, 'template'])->name('scenarios.template');
        Route::post('/escenarios/importar', [ScenarioController::class, 'import'])->name('scenarios.import');
        Route::get('/escenarios/{study}/exportar/{format}', [ScenarioController::class, 'export'])->where('format', 'xlsx|pdf')->name('scenarios.export');
        Route::post('/escenarios', [ScenarioController::class, 'store'])->name('scenarios.store');
        Route::post('/escenarios/{study}/elegir', [ScenarioController::class, 'choose'])->name('scenarios.choose');
        Route::post('/guardias/generar', [ShiftController::class, 'generate'])->name('shifts.generate');
        Route::post('/guardias/lotes/{batch}/aprobar', [ShiftController::class, 'approve'])->name('shifts.approve');
        Route::post('/reportes/{cycle}/generar', [ReportController::class, 'create'])->name('reports.create');
        Route::post('/rubrica', [AccountController::class, 'rubric'])->name('rubric.save');
    });
});
