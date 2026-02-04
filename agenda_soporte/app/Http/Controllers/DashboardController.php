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

        // Cargamos las sucursales activas una sola vez para optimizar (Eager Loading)
        $activeBranches = $user->activeBranches()->with(['region.team'])->get();

        // Nivel 2 y 3: Coordinadores e Ingenieros
        return Inertia::render('Dashboard/Index', [
            'stats' => $this->getStatsForUser($user, $activeBranches),
            'userRole' => $user->global_role,
            
            // Mapa organizacional estructurado para la jerarquía
            'organizationMap' => $user->global_role === 'ingeniero' 
                ? $this->buildOrganizationMap($activeBranches) 
                : null,

            // Lista plana formateada para las CecoCards
            'cecos' => $user->global_role === 'ingeniero' 
                ? $this->formatCecosForCards($activeBranches) 
                : [],
        ]);
    }

    /**
     * Formatea la lista de sucursales para las tarjetas CECO e incluye el STATUS
     */
    private function formatCecosForCards($branches)
    {
        return $branches->map(fn($branch) => [
            'id'              => $branch->id,
            'name'            => $branch->name,
            'status'          => $branch->status, // <-- Agregado desde tu DB
            'eco_number'      => $branch->external_id_eco ?? 'N/A',
            'ceco_number'     => $branch->external_id_ceco ?? 'N/A', // Por si lo ocupas después
            'zone_name'       => $branch->zone_name ?? 'Sin Zona',
            'address'         => $branch->address ?? 'Por definir - Seeder',
            'assignment_type' => $branch->pivot->assignment_type ?? 'N/A',
            'is_external'     => (bool)($branch->pivot->is_external ?? false),
            'assigned_at'     => $branch->pivot->assigned_at ?? null,
        ]);
    }

    /**
     * Construye el mapa jerárquico: Compañía -> Región -> Sucursales
     */
    private function buildOrganizationMap($branches)
    {
        return $branches->groupBy(fn($b) => $b->region->team->name ?? 'Sin Compañía')
            ->map(fn($branchesInTeam) => 
                $branchesInTeam->groupBy(fn($b) => $b->region->name ?? 'Sin Región')
                    ->map(fn($group) => $this->formatCecosForCards($group))
            );
    }

    private function isAdminLevel(User $user): bool
    {
        return $user->isGlobalAdmin() 
            || ($user->global_role && in_array($user->global_role, ['gerente', 'supervisor']));
    }

    private function getStatsForUser(User $user, $activeBranches): array
    {
        return ($user->global_role === 'coordinador') 
            ? $this->getCoordinatorStats($user) 
            : $this->getEngineerStats($activeBranches);
    }

    private function getCoordinatorStats(User $user): array
    {
        $teamId = $user->current_team_id;

        return [
            'totalTiendas'    => Branch::where('team_id', $teamId)->count(),
            'totalRegiones'   => Region::where('team_id', $teamId)->count(),
            'tiendasActivas'  => Branch::where('team_id', $teamId)->where('status', 'active')->count(),
            'ingenierosAsignados' => $user->currentTeam ? $user->currentTeam->users()->where('global_role', 'ingeniero')->count() : 0,
        ];
    }

    private function getEngineerStats($activeBranches): array
    {
        return [
            'totalTiendas'    => $activeBranches->count(),
            'totalPrimarias'  => $activeBranches->filter(fn($b) => ($b->pivot->assignment_type ?? '') === 'primary')->count(),
            'totalSoporte'    => $activeBranches->filter(fn($b) => ($b->pivot->assignment_type ?? '') === 'support')->count(),
            'totalExternas'   => $activeBranches->filter(fn($b) => ($b->pivot->is_external ?? 0) == 1)->count(),
            'regionesActivas' => $activeBranches->pluck('region_id')->unique()->count(),
            'companiesActivas'=> $activeBranches->pluck('team_id')->unique()->count(),
        ];
    }

    public function admin(Request $request) 
    {
        $user = $request->user();
        if (!$this->isAdminLevel($user)) abort(403);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'companies'    => Team::count(),
                'regions'      => Region::count(),
                'branches'     => Branch::count(),
                'engineers'    => User::whereNotIn('global_role', ['admin', 'gerente', 'supervisor', 'coordinador'])->orWhereNull('global_role')->count(),
                'coordinators' => User::where('global_role', 'coordinador')->count(),
            ],
        ]);
    }
}