<?php

namespace App\Http\Controllers;

use App\Services\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class OtpController extends Controller
{
    public function request(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'purpose' => ['sometimes', Rule::in(['recovery', 'activation'])]]);
        $purpose = $data['purpose'] ?? 'recovery';
        $id = $otp->request($data['email'], $purpose, $request->ip());
        $request->session()->forget('otp_grant');
        $request->session()->put(['otp_challenge' => $id, 'otp_purpose' => $purpose]);

        return redirect()->route('otp.verify')->with('status', OtpService::MESSAGE);
    }

    public function form(Request $request): View|RedirectResponse
    {
        return $request->session()->has('otp_challenge') ? view('auth.verify') : redirect()->route('password.request');
    }

    public function verify(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $grant = $otp->verify((string) $request->session()->get('otp_challenge'), (string) $request->session()->get('otp_purpose'), $data['code']);
        if (! $grant) {
            return back()->withErrors(['code' => 'Código inválido, vencido o agotado. Solicita otro si es necesario.']);
        }
        $request->session()->regenerate();
        $request->session()->put('otp_grant', $grant);

        return redirect()->route('password.reset');
    }

    public function resetForm(Request $request): View|RedirectResponse
    {
        return $request->session()->has('otp_grant') ? view('auth.reset') : redirect()->route('password.request');
    }

    public function complete(Request $request, OtpService $otp): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'confirmed', 'max:255', Password::min(12)->letters()->numbers()], 'email' => 'prohibited', 'user_id' => 'prohibited', 'token' => 'prohibited']);
        if (! $otp->complete((string) $request->session()->get('otp_challenge'), (string) $request->session()->get('otp_purpose'), (string) $request->session()->get('otp_grant'), $data['password'])) {
            return back()->withErrors(['password' => 'La autorización no es válida o venció. Vuelve a solicitar un código.']);
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Contraseña actualizada. Puedes ingresar cuando tu cuenta esté activa y habilitada por Coordinación.');
    }
}
