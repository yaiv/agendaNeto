<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EngineerRegion extends Pivot
{

    protected $table = 'engineer_region';

    // Indicamos que el ID es autoincremental 
    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'region_id',
        'assignment_type', // 'primary' o 'support'
    ];

    // Helper para saber si es la región principal del ingeniero
    public function isPrimary(): bool
    {
        return $this->assignment_type === 'primary';
    }

    // Helper para saber si es apoyo
    public function isSupport(): bool
    {
        return $this->assignment_type === 'support';
    }
}