<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegionPolicy
{
    use HandlesAuthorization;

    /**
     * Si eres Nivel 1, tienes permiso total automático.
     */
    public function before(User $user, $ability)
    {
        if ($user->is_global_admin || in_array($user->global_role, ['gerente', 'supervisor', 'admin'])) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        // El Nivel 1 ya pasó por el 'before', así que aquí solo validamos Nivel 2 y 3.
        return true; 
    }

    public function view(User $user, Region $region): bool
    {
        // Nivel 2: Coordinador (Ve todo lo de su equipo)
        if ($user->id === $region->team->user_id || $user->global_role === 'coordinador') {
             return $user->current_team_id === $region->team_id;
        }

        // Nivel 3: Ingeniero (Solo asignaciones explícitas)
        return $user->assignedRegions()->where('region_id', $region->id)->exists();
    }

    // create, update, delete: Solo Admin Global (vía 'before') o Coordinador
    public function create(User $user): bool
    {
        return $user->id === $user->currentTeam->user_id;
    }

    public function update(User $user, Region $region): bool
    {
        return $user->id === $region->team->user_id;
    }

    public function delete(User $user, Region $region): bool
    {
        return $user->id === $region->team->user_id;
    }
}