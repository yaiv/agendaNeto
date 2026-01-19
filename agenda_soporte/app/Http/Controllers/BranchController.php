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
}