<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate; // <--- 1. IMPORTANTE: Agrega esto
use Inertia\Inertia;

class RegionController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 2. CORRECCIÓN: Usamos Gate en lugar de $this->authorize
        Gate::authorize('viewAny', Region::class);

        // 2. Consulta Inteligente (Scope)
        if ($user->id === $user->currentTeam->user_id) {
            // CASO A: Es COORDINADOR (Nivel 2) -> Ve TODO lo del Team
            $regions = $user->currentTeam->regions()
                ->withCount('branches')
                ->get();
        } else {
            // CASO B: Es INGENIERO (Nivel 3) -> Solo ve lo ASIGNADO
            $regions = $user->assignedRegions()
                ->where('team_id', $user->current_team_id)
                ->withCount('branches')
                ->get();
        }

        return Inertia::render('Regions/Index', [
            'regions' => $regions
        ]);
    }
}