<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()?->active || ($request->session()->has('security_version') && $request->session()->get('security_version') !== $request->user()->security_version)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }
        abort_unless(in_array($request->user()->role, $roles, true), 403, 'Tu rol no tiene permiso para esta acción.');

        return $next($request);
    }
}
