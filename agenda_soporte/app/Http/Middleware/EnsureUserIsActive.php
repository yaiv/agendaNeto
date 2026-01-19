<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Verificamos si hay usuario, si tiene perfil, y si el estatus NO es activo
        if ($user && $user->profile && $user->profile->status !== 'active') {
            
            // Cerramos la sesión
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Lo mandamos fuera
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta ha sido desactivada.']);
        }

        return $next($request);
    }
}