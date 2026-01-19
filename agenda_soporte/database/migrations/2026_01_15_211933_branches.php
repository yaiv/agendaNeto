<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            
            // Jerarquía y Seguridad
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade'); 
            
            // Agrupación lógica
            $table->string('zone_name')->nullable()->index(); 
            
            // Identificadores Externos (Integraciones)
            $table->string('external_id_eco')->nullable()->index(); 
            $table->string('external_id_ceco')->nullable();
            
            $table->string('name');
            
            // Geolocalización (Precision 10,7 según ER)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            $table->string('address')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};