<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Region;
use App\Http\Requests\StoreBranchRequest; // <--- Usamos el Request que creamos
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BranchController extends Controller
{
    /**
     * Muestra el formulario para crear una nueva sucursal.
     */
    public function create(Request $request)
    {
        // 1. Autorización (opcional si usas middleware, pero recomendado)
        // Gate::authorize('create', Branch::class); 
        Gate::authorize('create', Branch::class);
        $user = Auth::user();

        // LÓGICA "MODO DIOS" PARA NIVEL 1
        // Si es Admin Global, traemos TODAS las regiones de TODAS las compañías.
        if ($user->is_global_admin || in_array($user->global_role, ['gerente', 'supervisor', 'admin'])) {
            $regions = Region::with('team:id,name') // Cargamos el nombre del Team para mostrarlo
                ->orderBy('team_id') // Ordenamos por empresa para agrupar visualmente
                ->orderBy('name')
                ->select('id', 'name', 'team_id') // Quitamos 'code' si no existe en BD aún
                ->get()
                ->map(function ($region) {
                    // Formato visual: "Región (Compañía)"
                    $region->name = $region->name . ' - [' . $region->team->name . ']';
                    return $region;
                });
        } else {

        // 2. Preparamos las Regiones para el Select
        // Solo enviamos las regiones de SU compañía actual.
        $regions = Region::where('team_id', $user->current_team_id)
            ->orderBy('name')
            ->select('id', 'name' ) // Optimizamos la consulta
            ->get();

        }

        // 3. Capturamos si viene una pre-selección desde la URL 
        // (Ej: click en "Agregar Sucursal" desde la tarjeta de la región BAJIO)
        $preselectedRegionId = $request->query('region_id');

        return Inertia::render('Branches/Create', [
            'regions' => $regions,
            'preselectedRegionId' => $preselectedRegionId,
        ]);
    }

    /**
     * Guarda la sucursal en BD.
     */
    public function store(StoreBranchRequest $request)
    {
        // El Request ya validó seguridad, unicidad y pertenencia.
        // Aquí solo guardamos.
        
        // El modelo Branch tiene un método 'booted' que rellenará 
        // automáticamente el 'team_id' basándose en la 'region_id'.
        Branch::create($request->validated());

        // Redirección inteligente:
        // Si vienes de gestionar una región específica, volvemos ahí? 
        // Por ahora, volvemos al listado de regiones que es el centro operativo.
        return redirect()->route('regions.index')
            ->with('flash.banner', 'Sucursal registrada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }
    
    // Aquí agregaremos edit/update/destroy más adelante...

    /**
     * LISTADO DE SUCURSALES (Por Región)
     * Ruta esperada: /regions/{region}/branches
     */
/**
     * LISTADO DE SUCURSALES (Híbrido: Por Región o Global)
     */
/**
 * LISTADO DE SUCURSALES (Híbrido: Por Región o Global)
 */
    /**
     * LISTADO DE SUCURSALES (Híbrido: Por Región o Global)
     */
    // 1. Hacemos que $region sea opcional (= null)
    public function index(Request $request, ?Region $region = null)
    {
        $user = Auth::user();
        $search = $request->input('search');

        // INICIO DEL QUERY
        // Cargamos la región y el team para poder mostrar "Sucursal X - Región Y [Empresa Z]"
        $query = Branch::with(['region.team']);

        // ESCENARIO A: VIENE UNA REGIÓN ESPECÍFICA (Ruta: /regions/{id}/branches)
        if ($region && $region->exists) {
            Gate::authorize('view', $region);
            $query->where('region_id', $region->id);
        } 
        // ESCENARIO B: VISTA GLOBAL (Ruta: /branches)
        else {
            // Nivel 1: Admin Global -> Ve TODO (No aplicamos filtro)
            if ($user->isGlobalAdmin() || in_array($user->global_role, ['gerente', 'supervisor'])) {
                // Pass (Query limpio)
            }
            // Nivel 2: Coordinador -> Ve todo lo de su EQUIPO
            elseif ($user->id === $user->currentTeam->user_id || $user->global_role === 'coordinador') {
                $query->whereHas('region', function ($q) use ($user) {
                    $q->where('team_id', $user->current_team_id);
                });
            }
            // Nivel 3: Ingeniero -> Ve solo lo de sus regiones ASIGNADAS
            else {
                $query->whereHas('region', function ($q) use ($user) {
                    $q->whereIn('id', $user->assignedRegions()->pluck('regions.id'));
                });
            }
        }

        // APLICAMOS BÚSQUEDA (Común para ambos casos)
        $branches = $query->when($search, function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('name', 'like', "%{$search}%")
                         ->orWhere('external_id_eco', 'like', "%{$search}%")
                         ->orWhere('zone_name', 'like', "%{$search}%")
                         // Búsqueda avanzada: Buscar también por nombre de Región o Compañía
                         ->orWhereHas('region', function($r) use ($search){
                             $r->where('name', 'like', "%{$search}%")
                               ->orWhereHas('team', fn($t) => $t->where('name', 'like', "%{$search}%"));
                         });
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Branches/Index', [
            'region' => $region, // Puede ser null
            'branches' => $branches,
            'filters' => $request->only(['search']),
            // Flag para que el frontend sepa si mostrar columnas extra
            'isGlobal' => is_null($region), 
        ]);
    }

    /**
     * EDICIÓN
     * Reutiliza la lógica de selección de regiones del Create
     */
    public function edit(Branch $branch)
    {
        Gate::authorize('update', $branch);
        
        $user = Auth::user();

        // REUTILIZAMOS LA LÓGICA DE OBTENCIÓN DE REGIONES
        // Esto permite mover una sucursal de región (y de Team si es Admin)
        if ($user->is_global_admin || in_array($user->global_role, ['gerente', 'supervisor', 'admin'])) {
            $regions = Region::with('team:id,name')
                ->orderBy('team_id')
                ->orderBy('name')
                ->select('id', 'name', 'team_id')
                ->get()
                ->map(function ($region) {
                    $region->name = $region->name . ' - [' . $region->team->name . ']';
                    return $region;
                });
        } else {
            $regions = Region::where('team_id', $user->current_team_id)
                ->orderBy('name')
                ->select('id', 'name')
                ->get();
        }

        return Inertia::render('Branches/Edit', [
            'branch' => $branch,
            'regions' => $regions,
        ]);
    }

    /**
     * ACTUALIZACIÓN
     */
    public function update(StoreBranchRequest $request, Branch $branch)
    {
        // Nota: Reutilizo StoreBranchRequest asumiendo que manejas la validación de unique
        // ignorando el ID actual. Si no, necesitarás un UpdateBranchRequest.
        
        Gate::authorize('update', $branch);

        $data = $request->validated();

        // 🛡️ LÓGICA DE SEGURIDAD PARA CAMBIO DE REGIÓN
        // Si se cambia la región, debemos asegurarnos que el team_id se actualice
        // para coincidir con la nueva región.
        if ($branch->region_id != $data['region_id']) {
            $newRegion = Region::find($data['region_id']);
            // Sobreescribimos el team_id para mantener consistencia
            $data['team_id'] = $newRegion->team_id; 
        }

        $branch->update($data);

        // Retornamos al index de la región a la que pertenece ACTUALMENTE la sucursal
        return redirect()->route('regions.branches.index', $branch->region_id)
            ->with('flash.banner', 'Sucursal actualizada correctamente.')
            ->with('flash.bannerStyle', 'success');
    }

    /**
     * ELIMINACIÓN (Soft Delete)
     */
    public function destroy(Branch $branch)
    {
        Gate::authorize('delete', $branch);

        $regionId = $branch->region_id; // Guardamos ID para el redirect
        $branch->delete();

        return redirect()->route('regions.branches.index', $regionId)
            ->with('flash.banner', 'Sucursal eliminada.')
            ->with('flash.bannerStyle', 'danger'); // Rojo para alertas de borrado
    }

}