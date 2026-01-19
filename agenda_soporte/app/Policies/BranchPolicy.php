<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BranchPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // 1. NIVEL 1: Admin Global (Pasa siempre)
        if ($user->is_global_admin || in_array($user->global_role, ['gerente', 'supervisor', 'admin'])) {
            return true;
        }

        // 2. NIVEL 2: Dueño del Equipo Actual (Pasa)
        if ($user->id === $user->currentTeam->user_id) {
            return true;
        }

        // 3. NIVEL 3: Ingenieros / Miembros (BLOQUEADO)
        return false;
    }

    // ... Puedes dejar los otros métodos vacíos o con return false por ahora
}