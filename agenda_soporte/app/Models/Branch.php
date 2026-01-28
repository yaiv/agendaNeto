<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Builder;

class Branch extends Model
{
    use HasFactory, SoftDeletes;
    use BelongsToTeam;

    protected $fillable = [
        'team_id',
        'region_id',
        'name',
        'address',
        'zone_name',
        'external_id_eco',
        'external_id_ceco',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'team_id' => 'integer',
        'region_id' => 'integer',
    ];


    /**
 * Scope para filtrar sucursales según el nivel de acceso del usuario.
 * Este es el "Single Point of Truth" para la visibilidad de datos.
 */

    public function scopeAccessibleBy($query, User $user)
{
    // Nivel 1: Global (Admin, Gerente, Supervisor)
    // Ellos ven TODO el universo de sucursales.
    if (in_array($user->global_role, ['admin', 'gerente', 'supervisor'])) {
        return $query;
    }

    // Nivel 2: Coordinador
    // Solo ve sucursales que pertenecen a su Team actual.
    if ($user->global_role === 'coordinador') {
        return $query->where('branches.team_id', $user->current_team_id);
    }

    // Nivel 3: Ingeniero (
    // Solo ve donde existe una relación ACTIVA en la tabla pivote.
    return $query->whereHas('activeEngineers', function ($q) use ($user) {
        $q->where('user_id', $user->id);
    });
}

    /**
     * EL CEREBRO DEL MODELO: Eventos de ciclo de vida.
     * Al crear una sucursal, si solo se proporciona region_id,
     * busca automáticamente el team_id de la región padre.
     */
    protected static function booted(): void
    {
        static::creating(function (Branch $branch) {
            if (empty($branch->team_id) && !empty($branch->region_id)) {
                $region = Region::find($branch->region_id);
                
                if ($region) {
                    $branch->team_id = $region->team_id;
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Relación Padre Inmediato: Región Geográfica
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Relación Abuelo: Compañía (Team)
     * Vital para la seguridad y el aislamiento de datos
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     *  INGENIEROS ASIGNADOS A ESTA SUCURSAL
     * Tabla pivote: engineer_branch
     * NO filtra por is_active para mantener flexibilidad
     */
    public function assignedEngineers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'engineer_branch', 'branch_id', 'user_id')
            ->withPivot([
                'team_id',
                'assignment_type',
                'is_external',
                'is_active',
                'assigned_at',
                'unassigned_at',
                'notes'
            ])
            ->withTimestamps();
    }

    /**
     * ✅ SOLO INGENIEROS ACTIVOS
     */
    public function activeEngineers()
    {
        return $this->assignedEngineers()->wherePivot('is_active', true);
    }

    /**
     *  INGENIERO PRIMARIO ACTIVO
     * Debe haber solo uno gracias al constraint unique_active_assignment
     */
    public function primaryEngineer()
    {
        return $this->assignedEngineers()
            ->wherePivot('assignment_type', 'primary')
            ->wherePivot('is_active', true)
            ->first();
    }

    /**
     *  INGENIEROS DE APOYO ACTIVOS
     */
    public function supportEngineers()
    {
        return $this->assignedEngineers()
            ->wherePivot('assignment_type', 'support')
            ->wherePivot('is_active', true)
            ->get();
    }

    /**
     *  INGENIEROS EXTERNOS ACTIVOS
     * (Ingenieros que dan soporte pero no son de la compañía base)
     */
    public function externalEngineers()
    {
        return $this->assignedEngineers()
            ->wherePivot('is_external', true)
            ->wherePivot('is_active', true)
            ->get();
    }

    /**
     * HISTORIAL DE INGENIEROS (incluye inactivos)
     */
    public function engineerHistory()
    {
        return $this->assignedEngineers()
            ->orderByPivot('assigned_at', 'desc')
            ->get();
    }

    /**
     *  Verifica si tiene un ingeniero específico asignado activamente
     */
    public function hasEngineer(int $userId): bool
    {
        return $this->activeEngineers()
            ->where('users.id', $userId)
            ->exists();
    }
}