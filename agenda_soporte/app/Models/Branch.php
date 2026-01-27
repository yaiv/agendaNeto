<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTeam; 

class Branch extends Model
{
    use HasFactory, SoftDeletes;
    use BelongsToTeam; 

    protected $fillable = [
        'team_id',          // Redundancia estratégica
        'region_id',        // La relación clave
        'name',             // Nombre Tienda
        'address',          // Dirección física
        'zone_name',        // Agrupación lógica (Zona)
        'external_id_eco',  // ID Operativo (ECO)
        'external_id_ceco', // Centro de Costos (CECO)
        'latitude',
        'longitude',
    ];

    /**
     * Conversión de tipos nativos.
     * Garantiza que lat/long se manejen como números y no strings.
     */
    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'team_id' => 'integer',
        'region_id' => 'integer',
    ];

    /**
     * EL CEREBRO DEL MODELO: Eventos de ciclo de vida.
     * * Al crear una sucursal, si el programador (o el Excel) solo manda la Region,
     * este método busca automáticamente el Team ID y lo rellena.
     * Esto evita que la sucursal quede huérfana de compañía.
     */
    protected static function booted(): void
    {
        static::creating(function (Branch $branch) {
            // Si falta el team_id pero tenemos region_id...
            if (empty($branch->team_id) && !empty($branch->region_id)) {
                
                // Buscamos la región padre para obtener su team_id
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
     * Relación Padre Inmediato: Región Geográfica.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Relación Abuelo: Compañía (Team).
     * Vital para la seguridad y el aislamiento de datos.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignedEngineers()
{
    return $this->belongsToMany(User::class, 'engineer_branch')
                ->withPivot(['assignment_type', 'is_active'])
                ->wherePivot('is_active', true)
                ->withTimestamps();
}
}