<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CompanyController extends Controller
{

    public function index()
{
    return Inertia::render('Admin/Companies/Index', [
        'companies' => Team::query()
            ->with('owner:id,name,email,profile_photo_path') // Traemos datos del dueño
            ->withCount('regions') // Contamos las regiones automáticamente
            ->withCount('users') // Opcional: Contamos miembros
            ->latest()
            ->get()
    ]);
}

    public function create()
    {
        return Inertia::render('Admin/Companies/Create', [
        // Enviamos la lista de candidatos a la vista.
        // OJO: Aquí podrías filtrar users que NO sean admins globales si quisieras.
        'potentialCoordinators' => User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
    ]);
    }

    public function store(StoreCompanyRequest $request)
    {
        // 1. Iniciamos la transacción para integridad total
        DB::transaction(function () use ($request) {
            
            // A. Determinar el Dueño (Team Owner)
            // Si el request trae un 'owner_id' (ej. un Coordinador pre-seleccionado), úsalo.
            // Si no, el Admin Global (tú) asume la propiedad temporalmente.
            $owner = $request->filled('owner_id') 
                ? User::find($request->owner_id) 
                : $request->user();

            // B. Crear la Compañía (Team)
            // forceCreate evita protecciones de mass-assignment si es necesario,
            // pero con Team::create y fillable configurado es suficiente.
            $team = Team::forceCreate([
                'user_id' => $owner->id,
                'name' => $request->name,
                'personal_team' => false, // Es una compañía corporativa, no personal
            ]);

            // C. Si el Admin NO es el dueño, agregamos al Admin al equipo
            // para garantizar que no pierda acceso inmediato (aunque sea Global Admin).
if ($owner->id !== $request->user()->id) {
    $owner->forceFill([
        'current_team_id' => $team->id,
    ])->save();

}

$team->users()->attach(
        $owner, 
        ['role' => 'admin'] 
    );

            // D. Crear las Regiones (Batch Insert)
            // Solo si vienen en el request
            if ($request->has('regions')) {
                // Preparamos el array para createMany
                // createMany maneja automáticamente el 'team_id' gracias a la relación
                $team->regions()->createMany($request->regions);
            }

            // Opcional: Disparar evento de "Nueva Compañía Corporativa Creada"
            // CompanyCreated::dispatch($team);
        });

        // 2. Redirección con Feedback
        return redirect()->route('admin.companies.index')
            ->with('flash.banner', 'Compañía y estructura regional creadas correctamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function show(Team $company)
{
    // Cargamos la relación de regiones y, para cada región, contamos sus sucursales
    $company->load(['owner', 'regions' => function ($query) {
        $query->withCount('branches'); // Para saber si la región está vacía u operativa
    }]);

    return Inertia::render('Admin/Companies/Show', [
        'company' => $company
    ]);
}
}