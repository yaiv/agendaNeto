<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsGlobalAdmin
{
    /**
     * Nivel 1:
     * - Admin Global
     * - Gerente
     * - Supervisor
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            !$user ||
            !(
                $user->isGlobalAdmin() ||
                in_array($user->global_role, ['gerente', 'supervisor'])
            )
        ) {
            if ($request->inertia()) {
                abort(403, 'Acceso restringido a administradores.');
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
