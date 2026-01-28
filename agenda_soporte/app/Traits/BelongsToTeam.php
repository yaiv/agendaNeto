<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTeam
{
    protected static function bootBelongsToTeam(): void
    {
        static::addGlobalScope('team_scope', function (Builder $builder) {
            $user = auth()->user();

            if ($user) {
                // NIVEL 1: Acceso Transversal
                if (in_array($user->global_role, ['supervisor', 'gerente'])) {
                    return; 
                }

                // 🔒 NIVEL 2 y 3: Filtro Estricto Cualificado
                // Usamos getTable() para que sea 'branches.team_id' o 'regions.team_id'
                $column = $builder->getModel()->getTable() . '.team_id';

                if ($user->current_team_id) {
                    $builder->where($column, $user->current_team_id);
                } else {
                    $builder->whereRaw('0=1');
                }
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && ! $model->team_id) {
                if (auth()->user()->current_team_id) {
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