<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    /**
     * Verificación temprana para Nivel 1 (Admin global)
     * Si retorna true, se saltan todas las demás verificaciones
     */
    public function before(User $user, $ability)
    {
        // Admins globales tienen acceso total a TODO
        if (in_array($user->global_role, ['admin', 'gerente', 'supervisor'])) {
            return true;
        }
    }

    /**
     * Determina si el usuario puede ver CUALQUIER sucursal
     */
    public function viewAny(User $user): bool
    {
        // Todos los usuarios autenticados pueden ver la lista
        // (el scope accessibleBy filtrará qué sucursales específicas)
        return true;
    }

    /**
     * Determina si el usuario puede ver UNA sucursal específica
     */
    public function view(User $user, Branch $branch): bool
    {
        // Nivel 2: Coordinador (Restricción por Team)
        if ($user->global_role === 'coordinador') {
            return $user->current_team_id === $branch->team_id;
        }

        // Nivel 3: Ingeniero (Restricción por Asignación Explícita)
        // Usa el helper 'hasEngineer' que verifica asignación ACTIVA
        return $branch->hasEngineer($user->id);
    }

    /**
     * Determina si el usuario puede CREAR sucursales
     */
    public function create(User $user): bool
    {
        // Solo coordinadores (dueños de equipos) pueden crear
        // Admin/Gerente/Supervisor ya tienen acceso por el before()
        return $user->global_role === 'coordinador' 
            && $user->currentTeam 
            && $user->id === $user->currentTeam->user_id;
    }

    /**
     * Determina si el usuario puede ACTUALIZAR una sucursal
     */
    public function update(User $user, Branch $branch): bool
    {
        // Solo coordinadores del mismo team pueden editar
        // Admin/Gerente/Supervisor ya tienen acceso por el before()
        return $user->global_role === 'coordinador' 
            && $user->current_team_id === $branch->team_id
            && $user->id === $user->currentTeam->user_id;
    }

    /**
     * Determina si el usuario puede ELIMINAR una sucursal
     */
    public function delete(User $user, Branch $branch): bool
    {
        // Solo coordinadores del mismo team pueden eliminar
        // Admin/Gerente/Supervisor ya tienen acceso por el before()
        return $user->global_role === 'coordinador' 
            && $user->current_team_id === $branch->team_id
            && $user->id === $user->currentTeam->user_id;
    }

    /**
     * Determina si el usuario puede ASIGNAR INGENIEROS a la sucursal
     */
    public function assignEngineers(User $user, Branch $branch): bool
    {
        // Solo coordinadores del mismo team pueden asignar ingenieros
        // Admin/Gerente/Supervisor ya tienen acceso por el before()
        return $user->global_role === 'coordinador' 
            && $user->current_team_id === $branch->team_id;
    }

    /**
     * Determina si el usuario puede VER EL HISTORIAL de asignaciones
     */
    public function viewHistory(User $user, Branch $branch): bool
    {
        // Coordinadores del mismo team pueden ver historial
        // Ingenieros asignados también pueden ver el historial de sus sucursales
        if ($user->global_role === 'coordinador') {
            return $user->current_team_id === $branch->team_id;
        }

        // Ingenieros pueden ver historial de sucursales donde están asignados
        return $branch->hasEngineer($user->id);
    }
}