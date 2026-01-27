<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Region;
use App\Models\Team;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class EngineerController extends Controller
{
    /**
     * 🛡️ GUARDIÁN DE SEGURIDAD
     * Verifica que el usuario sea Admin Global o Dueño del Equipo.
     */
    private function authorizeManager($user)
    {
        // 1. Si es Admin Global, pase.
        if ($user->isGlobalAdmin()) {
            return true;
        }

        // 2. Si tiene equipo Y es el dueño (user_id del team == user->id), pase.
        if ($user->currentTeam && $user->currentTeam->user_id === $user->id) {
            return true;
        }

        // 3. Si no, denegado.
        abort(403, 'ACCESO DENEGADO: Solo Coordinadores y Gerentes pueden gestionar ingenieros.');
    }

  public function index(Request $request)
{
    $currentUser = $request->user();
    $this->authorizeManager($currentUser); // 👈 CANDADO
    
    $search = $request->input('search');

    $query = User::query()
        ->with(['profile', 'assignedRegions', 'currentTeam:id,name'])
        ->whereHas('profile')
        ->whereNull('global_role');

    if ($currentUser->global_role) {
        // Admin ve todo
    } else {
        // Coordinador ve solo su equipo
        $query->whereHas('teams', function ($q) use ($currentUser) {
            $q->where('teams.id', $currentUser->current_team_id);
        });
    }

    // 🔍 BÚSQUEDA
    $query->when($search, function ($q, $search) {
        $q->where(function ($subQ) use ($search) {
            $subQ->where('name', 'like', "%{$search}%")
                 ->orWhere('email', 'like', "%{$search}%")
                 // Buscar por teléfono en profile
                 ->orWhereHas('profile', function($p) use ($search) {
                     $p->where('phone1', 'like', "%{$search}%")
                       ->orWhere('employee_code', 'like', "%{$search}%");
                 })
                 // Buscar por región asignada
                 ->orWhereHas('assignedRegions', function($r) use ($search) {
                     $r->where('name', 'like', "%{$search}%");
                 })
                 // Buscar por equipo/compañía
                 ->orWhereHas('currentTeam', function($t) use ($search) {
                     $t->where('name', 'like', "%{$search}%");
                 });
        });
    });

    // 📄 PAGINACIÓN (en lugar de get())
    $engineers = $query->latest()
        ->paginate(10)
        ->withQueryString();

    // Transformar la colección paginada
    $engineers->getCollection()->transform(function ($user) {
        $userTeam = $user->currentTeam; 
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->profile?->phone1 ?? 'N/A',
            'code' => $user->profile?->employee_code ?? 'N/A',
            'status' => $user->profile?->status ?? 'active',
            'team_name' => $userTeam?->name ?? 'Sin Asignar',
            'primary_region' => $user->assignedRegions->first(fn($r) => $r->pivot->assignment_type === 'primary')?->name ?? 'Sin Asignar',
            'support_regions' => $user->assignedRegions->filter(fn($r) => $r->pivot->assignment_type === 'support')->pluck('name')->join(', '),
        ];
    });

    return Inertia::render('Engineers/Index', [
        'engineers' => $engineers,
        'filters' => $request->only(['search']),
    ]);
}

public function create(Request $request)
{
    $user = $request->user();
    $this->authorizeManager($user);

    $isGlobalAdmin = $user->isGlobalAdmin();

    // ===============================
    // EQUIPOS (teams)
    // ===============================
    if ($isGlobalAdmin) {
        // Admin puede elegir cualquier compañía
        $teams = Team::where('personal_team', false)
            ->orderBy('name')
            ->get(['id', 'name']);
    } else {
        // Coordinador solo su compañía
        $teams = collect([$user->currentTeam]);
    }

    // ===============================
    // REGIONES
    // ===============================
    if ($isGlobalAdmin) {
        // Admin ve todas
        $regions = Region::orderBy('name')
            ->get(['id', 'name', 'team_id']);
    } else {
        // Coordinador solo las suyas
        $regions = Region::where('team_id', $user->current_team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'team_id']);
    }

    // ===============================
    // TIENDAS (branches)
    // ===============================
    $all_branches = Branch::query()
        ->when(!$isGlobalAdmin, fn ($q) =>
            $q->where('team_id', $user->current_team_id)
        )
        ->get(['id', 'name', 'region_id', 'external_id_eco']);

    return Inertia::render('Engineers/Create', [
        'teams' => $teams,
        'all_regions' => $regions,
        'all_branches' => $all_branches,
        'is_global_admin' => $isGlobalAdmin,
    ]);
}

   public function store(Request $request)
{
    $currentUser = $request->user();
    $this->authorizeManager($currentUser);
    $isGlobalAdmin = $currentUser->isGlobalAdmin();

    $targetTeamId = $isGlobalAdmin ? $request->input('team_id') : $currentUser->current_team_id;

    if (!$targetTeamId) {
        abort(403, 'Error: No se ha definido un equipo.');
    }

    $validated = $request->validate([
        'team_id' => $isGlobalAdmin ? ['required', 'exists:teams,id'] : ['nullable'],
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'string', 'min:8'],
        'employee_code' => ['required', 'string', 'max:20', 'unique:profiles'],
        'phone1' => ['required', 'string', 'max:20'],
        'primary_region_id' => ['required', 'exists:regions,id'],
        'support_region_ids' => ['array', 'nullable'],
        'support_region_ids.*' => ['exists:regions,id'],
        'selected_branch_ids' => ['array', 'nullable'], // Validación de tiendas
    ]);

    // 🛡️ VALIDACIÓN: La región primaria debe pertenecer al equipo
    $primaryRegion = Region::find($validated['primary_region_id']);
    
    if ($primaryRegion->team_id != $targetTeamId) {
        return back()->withErrors([
            'primary_region_id' => 'La región principal debe pertenecer a la compañía seleccionada.'
        ])->withInput();
    }
    // ⚠️ Las regiones de apoyo NO se validan (pueden ser de otras compañías)

    DB::transaction(function () use ($validated, $targetTeamId) {
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'current_team_id' => $targetTeamId,
        ]);

        $user->profile()->create([
            'employee_code' => $validated['employee_code'],
            'phone1' => $validated['phone1'],
            'status' => 'active',
        ]);

        $team = Team::find($targetTeamId);
        $team->users()->attach($user, ['role' => 'member']);

        $user->assignedRegions()->attach($validated['primary_region_id'], [
            'assignment_type' => 'primary'
        ]);

        if (!empty($validated['support_region_ids'])) {
            $supportIds = collect($validated['support_region_ids'])
                ->reject(fn($id) => $id == $validated['primary_region_id'])
                ->unique();
            
            foreach ($supportIds as $regionId) {
                $user->assignedRegions()->attach($regionId, [
                    'assignment_type' => 'support'
                ]);
            }
        }
    });

    if (!empty($validated['selected_branch_ids'])) {
    $branchSyncData = [];
    foreach ($validated['selected_branch_ids'] as $branchId) {
        $branch = \App\Models\Branch::find($branchId);
        $branchSyncData[$branchId] = [
            'team_id'         => $branch->team_id,
            'assignment_type' => 'primary',
            'assigned_at'     => now(),
            'is_active'       => true,
        ];
    }
    $user->assignedBranches()->sync($branchSyncData);
}

    return redirect()->route('engineers.index')
        ->with('flash.banner', 'Ingeniero creado correctamente.');
}

 public function edit(Request $request, User $engineer)
{
    // AUTO-CORRECCIÓN: Si el usuario tiene equipo asignado en pivote pero current_team_id es null
    if (is_null($engineer->current_team_id)) {
        $firstTeam = $engineer->teams()->first();
        if ($firstTeam) {
            $engineer->current_team_id = $firstTeam->id;
            $engineer->save(); // Lo arreglamos en BD silenciosamente
        }
    }

    $currentUser = $request->user();
    $this->authorizeManager($currentUser);

    // Seguridad estricta para Coordinadores
    if (!$currentUser->isGlobalAdmin() && $engineer->current_team_id !== $currentUser->current_team_id) {
        abort(403, 'No tienes permiso para editar a este ingeniero.');
    }

    // 👇 ACTUALIZADO: Cargar también assignedBranches
    $engineer->load(['profile', 'assignedRegions', 'assignedBranches']);

    // DATOS PARA LA VISTA
    $teams = [];
    $regions = []; // Regiones filtradas (para coordinador)
    $all_regions = []; // Todas las regiones (para admin)

    if ($currentUser->isGlobalAdmin()) {
        // Admin: Necesita todos los equipos para poder mover al usuario
        $teams = Team::where('personal_team', false)->orderBy('name')->get();
        // Y todas las regiones para el filtro dinámico
        $all_regions = Region::orderBy('name')->get(['id', 'name', 'team_id']);
    } else {
        // Coordinador: Solo ve su equipo y sus regiones
        $teams = [$currentUser->currentTeam];
        $regions = Region::where('team_id', $currentUser->current_team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'team_id']);
    }

    // IDs actuales de asignaciones de regiones
    $currentSupportIds = $engineer->assignedRegions
        ->filter(fn($r) => $r->pivot->assignment_type === 'support')
        ->pluck('id')
        ->toArray();

    $currentPrimaryId = $engineer->assignedRegions
        ->first(fn($r) => $r->pivot->assignment_type === 'primary')?->id;

    // ========================================
    // 👇 NUEVA LÓGICA: Asignación de Tiendas
    // ========================================
    
    // 1. Obtener los IDs de todas las regiones vinculadas (Base + Apoyo)
    $allLinkedRegionIds = $engineer->assignedRegions->pluck('id')->toArray();
    
    // 2. Traer todas las TIENDAS que pertenecen a esas regiones
    // Esto es para que el Coordinador elija cuáles le tocan al ingeniero
    $availableBranches = \App\Models\Branch::whereIn('region_id', $allLinkedRegionIds)
        ->orderBy('name')
        ->get(['id', 'name', 'region_id', 'external_id_eco']);
    
    // 3. IDs de las tiendas que YA tiene asignadas actualmente (solo las activas)
    $currentBranchIds = $engineer->assignedBranches
        ->where('pivot.esta_activo', true) // 👈 Solo las asignaciones activas
        ->pluck('id')
        ->toArray();

    return Inertia::render('Engineers/Edit', [
        'engineer' => [
            'id' => $engineer->id,
            'name' => $engineer->name,
            'email' => $engineer->email,
            'employee_code' => $engineer->profile?->employee_code,
            'phone1' => $engineer->profile?->phone1,
            'status' => $engineer->profile?->status ?? 'active',
            'current_team_id' => $engineer->current_team_id, // Vital para el select
            'primary_region_id' => $currentPrimaryId,
            'support_region_ids' => $currentSupportIds,
            'selected_branch_ids' => $currentBranchIds, // 👈 NUEVO: Tiendas seleccionadas
        ],
        'teams' => $teams,
        'regions' => $regions,         // Para Coordinador
        'all_regions' => $all_regions, // Para Admin
        'available_branches' => $availableBranches, // 👈 NUEVO: Para el listado de selección
        'is_global_admin' => $currentUser->isGlobalAdmin(),
    ]);
}

public function update(Request $request, User $engineer)
{
    $currentUser = $request->user();
    $this->authorizeManager($currentUser);
    $isGlobalAdmin = $currentUser->isGlobalAdmin();

    if (!$isGlobalAdmin && $engineer->current_team_id !== $currentUser->current_team_id) {
        abort(403, 'No autorizado.');
    }

    $validated = $request->validate([
        'team_id' => $isGlobalAdmin ? ['required', 'exists:teams,id'] : ['nullable'],
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$engineer->id],
        'employee_code' => ['required', 'string', 'max:20', 'unique:profiles,employee_code,'.($engineer->profile->id ?? 'null')],
        'phone1' => ['required', 'string', 'max:20'],
        'primary_region_id' => ['required', 'exists:regions,id'],
        'support_region_ids' => ['array', 'nullable'],
        'support_region_ids.*' => ['exists:regions,id'],
        'selected_branch_ids' => ['array', 'nullable'], // 👈 NUEVO: Validación de tiendas
        'selected_branch_ids.*' => ['exists:branches,id'], // 👈 NUEVO: Validación individual
        'status' => ['required', 'in:active,inactive'],
    ]);

    // 🛡️ VALIDACIÓN: Solo la región primaria debe pertenecer al equipo
    $targetTeamId = $validated['team_id'] ?? $engineer->current_team_id;
    $primaryRegion = Region::find($validated['primary_region_id']);
    
    if ($primaryRegion->team_id != $targetTeamId) {
        return back()->withErrors([
            'primary_region_id' => 'La región principal debe pertenecer a la compañía del ingeniero.'
        ])->withInput();
    }
    // ⚠️ Las regiones de apoyo NO se validan porque pueden ser de otras compañías

    // 🛡️ NUEVA VALIDACIÓN: Las tiendas deben pertenecer a las regiones asignadas
    if (!empty($validated['selected_branch_ids'])) {
        $allAssignedRegionIds = array_merge(
            [$validated['primary_region_id']],
            $validated['support_region_ids'] ?? []
        );

        $invalidBranches = \App\Models\Branch::whereIn('id', $validated['selected_branch_ids'])
            ->whereNotIn('region_id', $allAssignedRegionIds)
            ->exists();

        if ($invalidBranches) {
            return back()->withErrors([
                'selected_branch_ids' => 'Algunas tiendas no pertenecen a las regiones asignadas al ingeniero.'
            ])->withInput();
        }
    }

    DB::transaction(function () use ($validated, $engineer, $isGlobalAdmin, $targetTeamId) {
        // 1. Actualizar Datos Básicos
        $engineer->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        // 2. CAMBIO DE EQUIPO (Solo Admin)
        if ($isGlobalAdmin && $targetTeamId != $engineer->current_team_id) {
            // Detach del equipo anterior
            if ($engineer->current_team_id) {
                $oldTeam = Team::find($engineer->current_team_id);
                if ($oldTeam) {
                    $oldTeam->users()->detach($engineer->id);
                }
            }

            // Actualizar el team_id
            $engineer->update(['current_team_id' => $targetTeamId]);

            // Attach al nuevo equipo
            $newTeam = Team::find($targetTeamId);
            if (!$newTeam->users()->where('user_id', $engineer->id)->exists()) {
                $newTeam->users()->attach($engineer, ['role' => 'member']);
            }
        }

        // 3. Actualizar Perfil
        $engineer->profile()->updateOrCreate(
            ['user_id' => $engineer->id],
            [
                'employee_code' => $validated['employee_code'],
                'phone1' => $validated['phone1'],
                'status' => $validated['status'],
            ]
        );

        // 4. Sincronizar Regiones (OPTIMIZADO con sync)
        $regionSyncData = [];
        
        // Región primaria
        $regionSyncData[$validated['primary_region_id']] = [
            'assignment_type' => 'primary',
            'team_id' => $targetTeamId, // 🛡️ Multitenancy
            'assigned_at' => now(),
            'is_active' => true,
        ];
        
        // Regiones de apoyo (pueden ser de cualquier compañía)
        if (!empty($validated['support_region_ids'])) {
            foreach ($validated['support_region_ids'] as $regionId) {
                if ($regionId != $validated['primary_region_id']) {
                    $supportRegion = Region::find($regionId);
                    $regionSyncData[$regionId] = [
                        'assignment_type' => 'support',
                        'team_id' => $supportRegion->team_id, // 🛡️ Usa el team_id de la región de apoyo
                        'assigned_at' => now(),
                        'is_active' => true,
                    ];
                }
            }
        }
        
        $engineer->assignedRegions()->sync($regionSyncData);

        // ========================================
        // 👇 5. SINCRONIZAR TIENDAS ESPECÍFICAS (engineer_branch)
        // ========================================
        $branchSyncData = [];
        
        if (!empty($validated['selected_branch_ids'])) {
            foreach ($validated['selected_branch_ids'] as $branchId) {
                $branch = \App\Models\Branch::find($branchId);
                
                $branchSyncData[$branchId] = [
                    'team_id' => $branch->team_id, // 🛡️ Usa el team_id de la tienda
                    'assignment_type' => 'primary', // Por defecto primario, puedes ajustar según lógica
                    'assigned_at' => now(),
                    'is_active' => true,
                ];
            }
        }
        
        // Sincroniza: elimina asignaciones viejas, crea nuevas, mantiene existentes
        $engineer->assignedBranches()->sync($branchSyncData);
    });

    return redirect()->route('engineers.index')
        ->with('flash.banner', 'Ingeniero y sus tiendas actualizados correctamente.');
}
}