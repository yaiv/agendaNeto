<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Profile;
use App\Models\Region;
use App\Models\EngineerRegion;
use App\Models\Branch;
use App\Models\Activity;
use App\Models\Task;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'global_role',
        'status',
        'current_team_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isGlobalAdmin(): bool
    {
        return in_array($this->global_role, ['supervisor', 'gerente']);
    }

    // ========================================
    // RELACIONES
    // ========================================

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * ✅ REGIONES ASIGNADAS AL INGENIERO
     * Tabla pivote: engineer_region
     */
    public function assignedRegions()
    {
        return $this->belongsToMany(Region::class, 'engineer_region', 'user_id', 'region_id')
            ->withPivot(['assignment_type', 'team_id', 'assigned_at', 'unassigned_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * ✅ SUCURSALES ASIGNADAS AL INGENIERO
     * Tabla pivote: engineer_branch
     * Incluye is_external para soporte fuera de la compañía base
     */
    public function assignedBranches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'engineer_branch', 'user_id', 'branch_id')
            ->withPivot([
                'team_id',
                'assignment_type',
                'is_external',      // ← NUEVO: Soporte externo
                'is_active',
                'assigned_at',
                'unassigned_at',
                'notes'
            ])
            ->withTimestamps();
    }

    /**
     * ✅ SOLO SUCURSALES ACTIVAS
     */
public function activeBranches()
{
    return $this->assignedBranches()
        ->wherePivot('is_active', true)
        ->where('branches.team_id', $this->current_team_id);
}

    /**
     * ✅ SUCURSALES PRIMARIAS ACTIVAS
     */
    public function primaryBranches()
{
    return $this->activeBranches() // Reutilizamos el método base para mantener el filtro de team_id
        ->wherePivot('assignment_type', 'primary');
}

    /**
     * ✅ SUCURSALES DE APOYO ACTIVAS
     */
    public function supportBranches()
    {
        return $this->assignedBranches()
            ->wherePivot('assignment_type', 'support');
    }

    /**
     * ✅ SUCURSALES EXTERNAS (fuera de la compañía base)
     */
    public function externalBranches()
    {
        return $this->assignedBranches()
            ->wherePivot('is_external', true)
            ->wherePivot('is_active', true);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'user_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to_user_id');
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_by_user_id');
    }

    // ========================================
    // MÉTODOS DE AYUDA (SCOPES Y HELPERS)
    // ========================================

    public function activeAssignedRegions()
    {
        return $this->assignedRegions()->wherePivot('is_active', true);
    }

    public function activeAssignedBranches()
    {
        return $this->assignedBranches()->wherePivot('is_active', true);
    }

    public function hasRegion(int $regionId): bool
    {
        return $this->activeAssignedRegions()
            ->where('regions.id', $regionId)
            ->exists();
    }

    public function hasBranch(int $branchId): bool
    {
        return $this->activeAssignedBranches()
            ->where('branches.id', $branchId)
            ->exists();
    }

    public function primaryRegion()
    {
        return $this->assignedRegions()
            ->wherePivot('assignment_type', 'primary')
            ->wherePivot('is_active', true)
            ->first();
    }

    public function supportRegions()
    {
        return $this->assignedRegions()
            ->wherePivot('assignment_type', 'support')
            ->wherePivot('is_active', true)
            ->get();
    }
}