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
        Schema::table('engineer_region', function (Blueprint $table) {
            // Verificar si las columnas no existen antes de agregarlas
            if (!Schema::hasColumn('engineer_region', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable()->after('region_id');
                $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            }

            if (!Schema::hasColumn('engineer_region', 'assigned_at')) {
                $table->date('assigned_at')->nullable()->after('assignment_type');
            }

            if (!Schema::hasColumn('engineer_region', 'unassigned_at')) {
                $table->date('unassigned_at')->nullable()->after('assigned_at');
            }

            if (!Schema::hasColumn('engineer_region', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('unassigned_at');
            }

            // Índice para mejorar performance
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('engineer_region', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn(['team_id', 'assigned_at', 'unassigned_at', 'is_active']);
            $table->dropIndex(['user_id', 'is_active']);
        });
    }
};