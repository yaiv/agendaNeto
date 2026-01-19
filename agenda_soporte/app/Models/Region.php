<?php

namespace App\Models;

use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\EngineerRegion;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{

    use HasFactory, BelongsToTeam, SoftDeletes; 


    protected $fillable = ['team_id', 'name'];

    // Relación con Sucursales
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    // Relación con Ingenieros (Nivel 3 - Operativo)
    // Permite saber qué ingenieros están asignados a esta región
    public function engineers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'engineer_region')
                    ->using(EngineerRegion::class)
                    ->withPivot('assignment_type')
                    ->withTimestamps();
    }
}