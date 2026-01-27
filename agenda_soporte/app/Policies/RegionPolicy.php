<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    /**
     * Se ejecuta ANTES de cualquier método.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Nivel 1 - Admin Global
        if ($user->isGlobalAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Ver listado
     */
    public function viewAny(User $user): bool
    {
        // Todos los autenticados pueden ver el índice
        return true;
    }

    /**
     * Ver región individual
     */
    public function view(User $user, Region $region): bool
    {
        // Coordinador → solo su compañía
        if ($user->global_role === 'coordinador') {
            return $region->team_id === $user->current_team_id;
        }

        // Ingeniero → solo regiones asignadas
        return $user->hasRegion($region->id);
    }

    /**
     * Crear región
     */
    public function create(User $user): bool
    {
        return $user->isGlobalAdmin() || $user->global_role === 'coordinador';
    }

    /**
     * Actualizar región
     */
    public function update(User $user, Region $region): bool
    {
        if ($user->global_role === 'coordinador') {
            return $region->team_id === $user->current_team_id;
        }

        return false;
    }

    /**
     * Eliminar región
     */
    public function delete(User $user, Region $region): bool
    {
        if ($user->global_role === 'coordinador') {
            return $region->team_id === $user->current_team_id;
        }

        return false;
    }
}
