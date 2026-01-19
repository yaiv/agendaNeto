<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    
 public function share(Request $request): array
{
    $user = $request->user();

    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url,
                
                // 👇 NUESTRAS PERSONALIZACIONES
                'is_global_admin' => $user->isGlobalAdmin(),
                'global_role' => $user->global_role,
                
                // 👇 LO QUE FALTABA (CRÍTICO PARA APPLAYOUT)
                'current_team_id' => $user->current_team_id,
                'current_team' => $user->currentTeam, // Objeto completo del equipo
                'all_teams' => $user->allTeams(),     // Para el dropdown de cambio de equipo
            ] : null,
        ],
    ]);
}
}
