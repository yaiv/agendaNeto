<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTeam
{
   /**
     * Boot the trait.
     */
    protected static function bootBelongsToTeam(): void
    {
        static::addGlobalScope('team_scope', function (Builder $builder) {
            // Obtenemos al usuario autenticado (si existe)
            $user = auth()->user();

            if ($user) {
                // NIVEL 1 (Supervisores y Gerentes)
                // Si tiene rol global, NO aplicamos el filtro. Ve todo.
                if (in_array($user->global_role, ['supervisor', 'gerente'])) {
                    return; 
                }

                // 🔒 NIVEL 2 y 3 (Coordinadores e Inges)
                // Si tiene un equipo seleccionado, filtramos estrictamente por él.
                if ($user->current_team_id) {
                    $builder->where('team_id', $user->current_team_id);
                }else{
                    // Si no tiene equipo seleccionado, no mostramos nada.
                    $builder->whereRaw('0=1');
                }
            }
        });

        // Al crear registros, asignamos el team_id automáticamente
        static::creating(function ($model) {
            // Si el usuario no especificó un team_id manual y está logueado...
            if (auth()->check() && ! $model->team_id) {
                if (auth()->user()->current_team_id) {
                // Asignamos su equipo actual (útil para Coordinadores)
                    $model->team_id = auth()->user()->current_team_id;
                }
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}