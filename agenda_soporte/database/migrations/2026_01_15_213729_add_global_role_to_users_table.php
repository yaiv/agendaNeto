<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ARQUITECTURA NIVEL 1: Rol Global de Usuario
            // Values esperados: 'supervisor', 'gerente', o NULL (para usuarios normales).
            $table->string('global_role')
                  ->nullable()
                  ->after('password') // Opcional: solo por orden visual en DB
                  ->comment('Rol de Nivel 1: supervisor/gerente. NULL para usuarios estándar.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('global_role');
        });
    }
};