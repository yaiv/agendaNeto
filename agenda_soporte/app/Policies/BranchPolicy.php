<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BranchPolicy
{
    use HandlesAuthorization;

    /**
     *  Para Nivel 1
     */
    public function before(User $user, $ability)
    {
        if ($user->is_global_admin || in_array($user->global_role, ['gerente', 'supervisor', 'admin'])) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Branch $branch): bool
    {
        // Usamos la política de la región padre para decidir
        // Si puedes ver la región, puedes ver la sucursal.
        return $user->can('view', $branch->region);
    }

    public function create(User $user): bool
    {
        // Nivel 2: Coordinador puede crear en su equipo
        return $user->id === $user->currentTeam->user_id;
    }

    public function update(User $user, Branch $branch): bool
    {
        // Nivel 2: Solo el dueño del equipo al que pertenece la sucursal
        return $user->id === $branch->region->team->user_id;
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->id === $branch->region->team->user_id;
    }
}