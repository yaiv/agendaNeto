<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class RegionController extends Controller
{
    /**
     * Muestra el listado de regiones según el nivel jerárquico del usuario.
     */
public function index(Request $request)
{
    $user = Auth::user();
    Gate::authorize('viewAny', Region::class);

    $search = $request->input('search');
    $query = Region::query();

    // ========================================
    // 1. FILTRADO POR JERARQUÍA
    // ========================================
    
    if ($user->isGlobalAdmin() || in_array($user->global_role, ['gerente', 'supervisor'])) {
        // ✅ NIVEL 1: Ve todas las regiones de todas las compañías
        $query->with('team'); 
    } 
    elseif ($user->id === $user->currentTeam->user_id || $user->global_role === 'coordinador') {
        // ✅ NIVEL 2: Ve solo las regiones de su compañía
        $query->where('team_id', $user->current_team_id);
    } 
    else {
        // ✅ NIVEL 3 (Ingeniero): Ve solo las regiones que tiene asignadas activas
    $assignedRegionIds = DB::table('engineer_region')
        ->where('engineer_region.user_id', $user->id)
        ->where('engineer_region.is_active', true)
        ->pluck('engineer_region.region_id')
        ->toArray();
    
    if (empty($assignedRegionIds)) {
        // Si no tiene regiones asignadas, devolver query vacío
        $query->whereRaw('1 = 0');
    } else {
        $query->whereIn('id', $assignedRegionIds);
    }
    }

    // ========================================
    // 2. BUSCADOR
    // ========================================
    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhereHas('team', function($qTeam) use ($search) {
                  $qTeam->where('name', 'like', "%{$search}%");
              });
        });
    }

    // ========================================
    // 3. EJECUCIÓN
    // ========================================
    $regions = $query->withCount('branches')
        ->with('team:id,name')
        ->orderBy('name', 'asc')
        ->get();

    return Inertia::render('Regions/Index', [
        'regions' => $regions,
        'filters' => $request->only(['search']),
        'userRole' => $user->global_role,
    ]);
}

    /**
     * Muestra el formulario de creación.
     */
    public function create(Request $request)
    {
        Gate::authorize('create', Region::class);

        $user = Auth::user();
        $teams = [];

        // Si es Nivel 1, cargamos todas las compañías para que elija
        if ($user->isGlobalAdmin() || in_array($user->global_role, ['gerente', 'supervisor'])) {
            $teams = Team::where('personal_team', false) // 👈 Excluye equipos personales
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Regions/Create', [
            'teams' => $teams,
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
        ];

        // Lógica Nivel 1 vs Nivel 2
        if ($user->isGlobalAdmin() || in_array($user->global_role, ['gerente', 'supervisor'])) {
            // Nivel 1: DEBE seleccionar una compañía
            $rules['team_id'] = ['required', 'exists:teams,id'];
        } else {
            // Nivel 2: Se fuerza su compañía actual
            $request->merge(['team_id' => $user->current_team_id]);
            $rules['team_id'] = ['required', 'exists:teams,id']; // Validamos de todas formas
        }

        $validated = $request->validate($rules);

        // Validación adicional: El nombre debe ser único dentro de la compañía
        $existingRegion = Region::where('team_id', $validated['team_id'])
            ->where('name', $validated['name'])
            ->first();

        if ($existingRegion) {
            return back()->withErrors([
                'name' => 'Ya existe una región con este nombre en la compañía seleccionada.'
            ])->withInput();
        }

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
            $teams = Team::where('personal_team', false)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Regions/Edit', [
            'region' => $region->load('team:id,name'), // 👈 Carga la relación
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
            // Nivel 2: Forzamos que mantenga su compañía
            $request->merge(['team_id' => $region->team_id]);
            $rules['team_id'] = ['required', 'exists:teams,id'];
        }

        $validated = $request->validate($rules);

        // Validación de nombre único dentro de la compañía (excluyendo la región actual)
        $existingRegion = Region::where('team_id', $validated['team_id'])
            ->where('name', $validated['name'])
            ->where('id', '!=', $region->id)
            ->first();

        if ($existingRegion) {
            return back()->withErrors([
                'name' => 'Ya existe otra región con este nombre en la compañía.'
            ])->withInput();
        }

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

        // Validación: No permitir eliminar si tiene sucursales
        if ($region->branches()->count() > 0) {
            return back()->with('flash', [
                'banner' => 'No se puede eliminar la región porque tiene ' . $region->branches()->count() . ' sucursales activas.',
                'bannerStyle' => 'danger'
            ]);
        }

        // Validación: No permitir eliminar si tiene ingenieros asignados
        $assignedEngineers = $region->engineers()->wherePivot('is_active', true)->count();
        if ($assignedEngineers > 0) {
            return back()->with('flash', [
                'banner' => 'No se puede eliminar la región porque tiene ' . $assignedEngineers . ' ingeniero(s) asignado(s).',
                'bannerStyle' => 'danger'
            ]);
        }

        $region->delete();

        return redirect()->route('regions.index')
            ->with('flash', ['banner' => 'Región eliminada correctamente.', 'bannerStyle' => 'success']);
    }
}