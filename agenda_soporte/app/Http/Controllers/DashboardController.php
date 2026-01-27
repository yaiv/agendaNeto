<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Region;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($this->isAdminLevel($user)) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Dashboard/Index', [
            'stats' => $this->getStatsForUser($user),
            'userRole' => $user->global_role,
        ]);
    }

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

private function isAdminLevel(User $user): bool
{
    return $user->isGlobalAdmin()
        || (
            $user->global_role &&
            in_array($user->global_role, ['gerente', 'supervisor'])
        );
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

   private function getEngineerStats(User $user): array
{
    $totalTiendas = DB::table('engineer_branch')
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->count();

    $totalRegiones = DB::table('engineer_region')
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->count();

    return [
        'totalTiendas' => $totalTiendas,
        'totalRegiones' => $totalRegiones,

        // 🔕 Pendientes hasta definir estructura
        'tareasActivas' => 0,
        'actividadesHoy' => 0,
    ];
}
}
