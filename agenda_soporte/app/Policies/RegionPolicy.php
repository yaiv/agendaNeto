<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RegionPolicy
{
    /**
     * Determina si el usuario puede ver el listado de regiones.
     */
    public function viewAny(User $user): bool
    {
        // Regla 1: Si es el DUEÑO del equipo actual (Nivel 2) -> VE TODO
        if ($user->id === $user->currentTeam->user_id) {
            return true;
        }

        // Regla 2: Si es Admin Global (Nivel 1) -> VE TODO
        if ($user->is_global_admin) {
            return true;
        }

        // Regla 3: Si es Ingeniero (Nivel 3)
        // Solo puede ver si tiene regiones asignadas en este equipo.
        // (Esta lógica la refinaremos en el controlador con scopes, 
        // pero la Policy da luz verde general).
        return true; 
    }

    /**
     * Determina si el usuario puede VER una región específica.
     * AQUÍ ESTÁ LA CLAVE DE TU DUDA.
     */
    public function view(User $user, Region $region): bool
    {
        // 1. Verificar que la región sea del equipo actual (Seguridad básica)
        if ($user->current_team_id !== $region->team_id && !$user->is_global_admin) {
            return false;
        }

        // 2. EL DUEÑO MANDA (Nivel 2)
        // Si el usuario es el dueño del team, ACCESO AUTOMÁTICO a todas las regiones.
        if ($user->id === $region->team->user_id) {
            return true;
        }

        // 3. EL INGENIERO OBDECE (Nivel 3)
        // Si no es dueño, verificamos si está asignado explícitamente en la tabla pivote.
        return $user->assignedRegions()->where('region_id', $region->id)->exists();
    }

    // ... create, update, delete solo para Dueños y Global Admin
    public function create(User $user): bool
    {
        return $user->id === $user->currentTeam->user_id || $user->is_global_admin;
    }
}