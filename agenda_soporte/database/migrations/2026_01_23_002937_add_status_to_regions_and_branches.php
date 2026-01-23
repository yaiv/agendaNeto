<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ---- REGIONS ----
        Schema::table('regions', function (Blueprint $table) {
            $table
                ->enum('status', ['active', 'inactive'])
                ->nullable() 
                ->after('name');
        });

        // Setear status a 'active' para registros existentes
        DB::table('regions')
            ->whereNull('status')
            ->update(['status' => 'active']);

        // Quitar nullable y dejar default
        Schema::table('regions', function (Blueprint $table) {
            $table
                ->enum('status', ['active', 'inactive'])
                ->default('active')
                ->nullable(false)
                ->change();
        });

        // ---- BRANCHES ----
        Schema::table('branches', function (Blueprint $table) {
            $table
                ->enum('status', ['active', 'inactive'])
                ->nullable()
                ->after('address');
        });

        DB::table('branches')
            ->whereNull('status')
            ->update(['status' => 'active']);

        Schema::table('branches', function (Blueprint $table) {
            $table
                ->enum('status', ['active', 'inactive'])
                ->default('active')
                ->nullable(false)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
