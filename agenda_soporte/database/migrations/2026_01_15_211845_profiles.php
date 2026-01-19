<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            
            // FK y UK: Relación 1:1 estricta con users
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique();
            
            // Identificación única corporativa
            $table->string('employee_code')->unique(); 
            
            // Datos de contacto
            $table->string('phone1');
            $table->string('phone2')->nullable();
            
            // Fechas administrativas
            $table->date('birth_date')->nullable();
            $table->date('hire_date')->nullable();
            
            $table->text('address')->nullable();
            
            // Estado del perfil (Default: active)
            $table->string('status')->default('active');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};