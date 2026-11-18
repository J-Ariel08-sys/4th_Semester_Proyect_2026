<?php

namespace App\Http\Controllers;

use App\Jobs\SendSecurityNotice;
use App\Models\User;
use App\Services\AccountAccess;
use App\Services\Audit;
use App\Services\OtpService;
use App\Services\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function index(): View
    {
        return view('account');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['phone' => 'nullable|string|max:40', 'current_password' => 'required|current_password', 'password' => ['nullable', 'confirmed', 'max:255', Password::min(12)->letters()->numbers()], 'role' => 'prohibited', 'eligible' => 'prohibited', 'merit' => 'prohibited', 'academic_average' => 'prohibited']);
        DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $user->phone = $data['phone'] ?? null;
            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
                $user->forceFill(AccountAccess::revoke($user));
                $request->session()->put('security_version', $user->security_version);
                SendSecurityNotice::dispatch($user->id, config('siclin.mail_mode'))->onQueue(config('siclin.mail_queue'))->afterCommit();
            }
            $user->save();
            Audit::record('cuenta.actualizada', ['usuario' => $user->id]);
        });
        $request->session()->regenerate();

        return back()->with('status', 'Datos personales actualizados.');
    }

    public function activation(Request $request, OtpService $otp, User $user): RedirectResponse
    {
        abort_unless($user->active && ! $user->activated_at, 422, 'La cuenta debe estar habilitada y pendiente de activación.');
        $otp->request($user->email, 'activation', $request->ip());

        return back()->with('status', 'Se solicitó el código de activación. El destinatario debe abrir Recuperar acceso → Activar mi cuenta nueva.');
    }

    public function settings(): View
    {
        return view('settings', ['rubric' => Settings::rubric()]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate(['support' => 'required|string|max:200']);
        Settings::set('general', $data);

        return back()->with('status', 'Configuración guardada.');
    }

    public function rubric(Request $request): RedirectResponse
    {
        $data = $request->validate(['weights' => 'required|array:conocimiento,procedimientos,comunicacion,profesionalismo', 'weights.*' => 'required|numeric|between:0,100']);
        if (count($data['weights']) !== 4 || abs(array_sum($data['weights']) - 100) > 0.00001) {
            throw ValidationException::withMessages(['weights' => 'Los cuatro pesos deben sumar 100 %.']);
        }
        Settings::set('rubric', $data['weights']);

        return back()->with('status', 'Rúbrica de demostración actualizada; los registros anteriores conservan su cálculo.');
    }
}
