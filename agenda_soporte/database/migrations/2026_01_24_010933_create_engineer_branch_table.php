<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engineer_branch', function (Blueprint $table) {
            $table->id();
            
            // Relaciones Principales
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade'); // Aislamiento Corporativo
            
            // Atributos de Asignación [cite: 66, 69]
            $table->string('assignment_type')->default('primary'); // primary, support, temporary
            $table->date('assigned_at');
            $table->date('unassigned_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();

            // Índices de Integridad y Rendimiento
            // 1. Evita que un ingeniero se asigne a la misma tienda dos veces si está activo
            $table->unique(['user_id', 'branch_id', 'is_active'], 'uid_bid_active_unique');
            
            // 2. Optimización para filtrado por compañía y estado
            $table->index(['team_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engineer_branch');
    }
};