<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsGlobalAdmin
{
    /**
     * Verifica que el usuario autenticado sea un Administrador Global (Nivel 1).
     * Roles permitidos: 'gerente', 'supervisor'
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si no hay usuario o no es admin global, rechazar
        if (!$user || !$user->isGlobalAdmin()) {
            // Si es una petición Inertia (AJAX), devolver 403 JSON
            if ($request->inertia()) {
                abort(403, 'Acceso restringido a administradores globales.');
            }

            // Si es navegación directa, redirigir al dashboard
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}