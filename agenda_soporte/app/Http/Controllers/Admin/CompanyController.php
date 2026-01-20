<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Companies/Index', [
            'companies' => Team::query()
                ->with('owner:id,name,email,profile_photo_path')
                ->withCount('regions')
                ->latest()
                ->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Companies/Create', [
            // Filtramos para no mostrar al mismo admin global en la lista, solo usuarios elegibles
            'potentialCoordinators' => User::select('id', 'name', 'email')
                ->where('id', '!=', auth()->id()) // Excluirse a sí mismo
                ->orderBy('name')
                ->get()
        ]);
    }

    public function store(StoreCompanyRequest $request)
    {
        DB::transaction(function () use ($request) {
            
            // A. Determinar el Dueño
            $user = $request->user();
            $owner = $request->filled('owner_id') 
                ? User::find($request->owner_id) 
                : $user;

            // B. Crear la Compañía (Team)
            // Al poner 'user_id' => $owner->id, Jetstream ya sabe que él es el dueño.
            $team = Team::forceCreate([
                'user_id' => $owner->id,
                'name' => $request->name,
                'personal_team' => false,
            ]);

            // C. Lógica de Asignación (CORREGIDA)
            
            // Si asignaste a OTRO usuario como dueño (Coordinador):
            if ($owner->id !== $user->id) {
                // 1. Forzamos que el Coordinador "entre" a esta compañía la próxima vez que se loguee
                $owner->forceFill([
                    'current_team_id' => $team->id,
                ])->save();

                // 2. (OPCIONAL) ¿Quieres que TÚ (Admin Global) quedes como miembro "admin" del equipo?
                // Esto te permite ver el equipo en tu selector de equipos dropdown.
                // Si no lo haces, solo podrás acceder vía tus permisos globales, no el dropdown.
                // $team->users()->attach($user, ['role' => 'admin']); 
            }
            
            // ❌ BORRADO: $team->users()->attach($owner...); 
            // El dueño NUNCA va en la tabla pivote team_user en Jetstream estándar.

            // D. Crear las Regiones
            if ($request->has('regions')) {
                // Aseguramos que los nombres de regiones vengan limpios
                $regionsData = collect($request->regions)->map(function ($region) {
                    return ['name' => $region['name']]; // Asegura estructura correcta
                })->toArray();
                
                $team->regions()->createMany($regionsData);
            }
        });

        return redirect()->route('admin.companies.index')
            ->with('flash', [
                'banner' => 'Compañía creada. El coordinador asignado ahora es el dueño.',
                'bannerStyle' => 'success'
            ]);
    }


public function show(Team $company)
{
    // Carga ansiosa (Eager Loading) de las relaciones definidas en tu modelo
    $company->load(['regions.branches']); 
    
    // O si solo necesitas el conteo como tenías antes:
    $company->loadCount('regions');

    return Inertia::render('Admin/Companies/Show', [
        'company' => $company
    ]);
}

/**
     * Elimina la compañía y su estructura.
     */
public function destroy(Team $company)
    {
        $user = auth()->user();

        // 🛡️ BLINDAJE CRÍTICO: ID 8 es Corporativo Global
        if ($company->id === 8) { 
            return back()->with('flash', [
                'banner' => '⛔ ACCIÓN DENEGADA: El "Corporativo Global" es el núcleo del sistema y no puede ser eliminado.',
                'bannerStyle' => 'danger'
            ]);
        }

        // 🛡️ BLINDAJE DE SESIÓN: No borrar equipo actual
        if ($user->current_team_id === $company->id) {
            return back()->with('flash', [
                'banner' => '⚠ No puedes eliminar la compañía activa. Cambia de equipo primero.',
                'bannerStyle' => 'danger'
            ]);
        }

        try {
            $company->delete();

            return redirect()->route('admin.companies.index')
                ->with('flash', [
                    'banner' => 'Compañía eliminada correctamente.',
                    'bannerStyle' => 'success'
                ]);

        } catch (\Exception $e) {
            return back()->with('flash', [
                'banner' => 'Error al eliminar: ' . $e->getMessage(),
                'bannerStyle' => 'danger'
            ]);
        }
    }

}