<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Region;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Nivel 1: Admin / Gerente / Supervisor
        if ($this->isAdminLevel($user)) {
            return redirect()->route('admin.dashboard');
        }

        // Nivel 2 y 3: Coordinadores e Ingenieros
        return Inertia::render('Dashboard/Index', [
            'stats' => $this->getStatsForUser($user),
            'userRole' => $user->global_role,
            // Mapa organizacional solo para ingenieros
            'organizationMap' => $user->global_role === 'ingeniero' 
                ? $this->getEngineerOrganizationMap($user) 
                : null,
        ]);
    }

    /**
     * Dashboard administrativo global
     */
    public function admin(Request $request) 
    {
        $user = $request->user();

        if (!$this->isAdminLevel($user)) {
            abort(403, 'Acceso restringido a Estructura Global.');
        }

        $stats = [
            'companies' => Team::count(),
            'regions'   => Region::count(),
            'branches'  => Branch::count(),
            'engineers' => User::whereNotIn('global_role', ['admin', 'gerente', 'supervisor', 'coordinador'])
                ->orWhereNull('global_role')
                ->count(),
            'coordinators' => User::where('global_role', 'coordinador')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * FASE 7: Preparación de datos (Agrupamiento jerárquico)
     * Estructura: Compañía -> Región -> Sucursales
     * 
     * Usa los nuevos métodos del modelo User
     */
private function getEngineerOrganizationMap(User $user)
{
    // Cargamos activas con sus relaciones jerárquicas
    return $user->activeBranches()
        ->with(['region.team']) 
        ->get()
        ->groupBy(fn($branch) => $branch->region->team->name ?? 'Sin Compañía')
        ->map(function ($branchesInTeam) {
            return $branchesInTeam->groupBy(fn($branch) => $branch->region->name ?? 'Sin Región')
                ->map(function($branches) {
                    return $branches->map(fn($branch) => [
                        'id'              => $branch->id,
                        'name'            => $branch->name,
                        'address'         => $branch->address,
                        'zone_name'       => $branch->zone_name,
                        'assignment_type' => $branch->pivot->assignment_type,
                        'is_external'     => (bool)$branch->pivot->is_external,
                        'assigned_at'     => $branch->pivot->assigned_at,
                    ]);
                });
        });
}

    private function isAdminLevel(User $user): bool
    {
        return $user->isGlobalAdmin() 
            || ($user->global_role && in_array($user->global_role, ['gerente', 'supervisor']));
    }

    private function getStatsForUser(User $user): array
    {
        return ($user->global_role === 'coordinador') 
            ? $this->getCoordinatorStats($user) 
            : $this->getEngineerStats($user);
    }

    private function getCoordinatorStats(User $user): array
    {
        $teamId = $user->current_team_id;

        return [
            'totalTiendas'   => Branch::where('team_id', $teamId)->count(),
            'totalRegiones'  => Region::where('team_id', $teamId)->count(),
            'tiendasActivas' => Branch::where('team_id', $teamId)
                ->where('status', 'active')
                ->count(),
            'ingenierosAsignados' => $user->currentTeam->users()
                ->where('global_role', 'ingeniero')
                ->count(),
        ];
    }

    /**
     * ✅ ACTUALIZADO: Usa los nuevos métodos del modelo User
     * Ya no usa DB::table, aprovecha las relaciones Eloquent
     */
  private function getEngineerStats(User $user): array
{
    // Obtenemos la colección una sola vez para evitar múltiples queries de agregación
    $activeBranches = $user->activeBranches()->get();

    return [
        'totalTiendas'    => $activeBranches->count(),
        'totalPrimarias'  => $activeBranches->where('pivot.assignment_type', 'primary')->count(),
        'totalSoporte'    => $activeBranches->where('pivot.assignment_type', 'support')->count(),
        'totalExternas'   => $activeBranches->where('pivot.is_external', true)->count(),
        
        // Usamos la colección cargada para obtener IDs únicos sin volver a la BD
        'regionesActivas' => $activeBranches->pluck('region_id')->unique()->count(),
        'companiesActivas'=> $activeBranches->pluck('team_id')->unique()->count(),
        
        //'tareasActivas'   => $user->tasks()->where('status', 'active')->count(),
        //'actividadesHoy'  => $user->activities()->whereDate('created_at', now()->today())->count(),
    ];
}
}