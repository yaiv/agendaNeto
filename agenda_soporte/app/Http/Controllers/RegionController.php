<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Team; // Importante para que Nivel 1 elija compañía
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RegionController extends Controller
{
    /**
     * Muestra el listado de regiones (Ya lo tenías, lo dejo igual).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        Gate::authorize('viewAny', Region::class);

        $search = $request->input('search');
        $query = Region::query();

        // 1. FILTRADO POR JERARQUÍA
        if ($user->isGlobalAdmin() || in_array($user->global_role, ['gerente', 'supervisor'])) {
            $query->with('team'); 
        } 
        elseif ($user->id === $user->currentTeam->user_id || $user->global_role === 'coordinador') {
            $query->where('team_id', $user->current_team_id);
        } 
        else {
            $query->whereIn('id', $user->assignedRegions()->pluck('regions.id'))
                  ->where('team_id', $user->current_team_id);
        }

        // 2. BUSCADOR
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('team', function($qTeam) use ($search) {
                      $qTeam->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // 3. EJECUCIÓN
        $regions = $query->withCount('branches')
            ->orderBy('name', 'asc')
            ->get(); // 👈 Solo get(), sin nada más después.

        return Inertia::render('Regions/Index', [
            'regions' => $regions,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create(Request $request)
    {
        // 1. Validamos permiso (Policy: create)
        // El 'before' del Policy deja pasar a Nivel 1.
        // El método 'create' del Policy deja pasar solo a Owners (Nivel 2).
        Gate::authorize('create', Region::class);

        $user = Auth::user();
        $teams = [];

        // Si es Nivel 1, cargamos todas las compañías para que elija
        if ($user->isGlobalAdmin() || in_array($user->global_role, ['gerente', 'supervisor'])) {
            $teams = Team::select('id', 'name')->orderBy('name')->get();
        }

        return Inertia::render('Regions/Create', [
            'teams' => $teams, // Será array vacío si es Nivel 2 (usaremos su current_team_id)
            'preselectedTeamId' => $request->query('team_id'),
        ]);
    }

    /**
     * Guarda la nueva región en base de datos.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Region::class);
        $user = Auth::user();

        // Reglas de validación base
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            // Validaremos team_id dinámicamente abajo
        ];

        // Lógica Nivel 1 vs Nivel 2
        if ($user->isGlobalAdmin() || in_array($user->global_role, ['gerente', 'supervisor'])) {
            // Nivel 1: DEBE seleccionar una compañía
            $rules['team_id'] = ['required', 'exists:teams,id'];
        } else {
            // Nivel 2: Se fuerza su compañía actual, no se valida input
            $request->merge(['team_id' => $user->current_team_id]);
        }

        $validated = $request->validate($rules);

        // Crear Región
        Region::create($validated);

        return redirect()->route('regions.index')
            ->with('flash', ['banner' => 'Región creada correctamente.', 'bannerStyle' => 'success']);
    }

    /**
     * Muestra el formulario de edición.
     */
    public function edit(Region $region)
    {
        Gate::authorize('update', $region);

        $user = Auth::user();
        $teams = [];

        if ($user->isGlobalAdmin() || in_array($user->global_role, ['gerente', 'supervisor'])) {
            $teams = Team::select('id', 'name')->orderBy('name')->get();
        }

        return Inertia::render('Regions/Edit', [
            'region' => $region,
            'teams' => $teams,
        ]);
    }

    /**
     * Actualiza la región.
     */
    public function update(Request $request, Region $region)
    {
        Gate::authorize('update', $region);
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
        ];

        // Solo Nivel 1 puede cambiar una región de compañía
        if ($user->isGlobalAdmin() || in_array($user->global_role, ['gerente', 'supervisor'])) {
            $rules['team_id'] = ['required', 'exists:teams,id'];
        } else {
            // Nivel 2: Ignoramos cualquier intento de cambiar team_id
            unset($request['team_id']);
        }

        $validated = $request->validate($rules);

        $region->update($validated);

        return redirect()->route('regions.index')
            ->with('flash', ['banner' => 'Región actualizada correctamente.', 'bannerStyle' => 'success']);
    }

    /**
     * Elimina la región.
     */
    public function destroy(Region $region)
    {
        Gate::authorize('delete', $region);

        // Opcional: Validar si tiene sucursales antes de borrar
        if ($region->branches()->count() > 0) {
            return back()->with('flash', [
                'banner' => 'No se puede eliminar la región porque tiene sucursales activas.',
                'bannerStyle' => 'danger'
            ]);
        }

        $region->delete();

        return redirect()->route('regions.index')
            ->with('flash', ['banner' => 'Región eliminada.', 'bannerStyle' => 'success']);
    }
}