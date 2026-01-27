<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'global_role',
        'status',
        'current_team_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Verifica si el usuario es administrador global
     */
    public function isGlobalAdmin(): bool
    {
        return in_array($this->global_role, ['supervisor', 'gerente']);
    }

    // ========================================
    // RELACIONES
    // ========================================

    /**
     * Perfil del usuario (datos personales)
     */
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
     * ✅ TIENDAS/SUCURSALES ASIGNADAS AL INGENIERO
     * Tabla pivote: engineer_branch
     */
    public function assignedBranches()
    {
        return $this->belongsToMany(Branch::class, 'engineer_branch', 'user_id', 'branch_id')
            ->withPivot(['assignment_type', 'team_id', 'assigned_at', 'unassigned_at', 'is_active', 'notes'])
            ->withTimestamps();
    }

    /**
     * Actividades realizadas por el usuario
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, 'user_id');
    }

    /**
     * Tareas asignadas al usuario
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to_user_id');
    }

    /**
     * Tareas que el usuario ha asignado a otros
     */
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_by_user_id');
    }

    // ========================================
    // MÉTODOS DE AYUDA (SCOPES Y HELPERS)
    // ========================================

    /**
     * Obtiene solo las regiones activas asignadas
     */
    public function activeAssignedRegions()
    {
        return $this->assignedRegions()->wherePivot('is_active', true);
    }

    /**
     * Obtiene solo las tiendas activas asignadas
     */
    public function activeAssignedBranches()
    {
        return $this->assignedBranches()->wherePivot('is_active', true);
    }

    /**
     * Verifica si el usuario tiene asignada una región específica
     */
    public function hasRegion(int $regionId): bool
    {
        return $this->activeAssignedRegions()
            ->where('regions.id', $regionId)
            ->exists();
    }

    /**
     * Verifica si el usuario tiene asignada una tienda específica
     */
    public function hasBranch(int $branchId): bool
    {
        return $this->activeAssignedBranches()
            ->where('branches.id', $branchId)
            ->exists();
    }

    /**
     * Obtiene la región primaria del ingeniero
     */
    public function primaryRegion()
    {
        return $this->assignedRegions()
            ->wherePivot('assignment_type', 'primary')
            ->wherePivot('is_active', true)
            ->first();
    }

    /**
     * Obtiene las regiones de apoyo del ingeniero
     */
    public function supportRegions()
    {
        return $this->assignedRegions()
            ->wherePivot('assignment_type', 'support')
            ->wherePivot('is_active', true)
            ->get();
    }
}