<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Agregar columna a profiles
            //Schema::table('profiles', function (Blueprint $table) {
            //$table->string('status')->default('active')->after('user_id');
        //});

        // 2. INICIALIZAR todos los perfiles existentes como 'active'
        // Ya que no hay datos previos que migrar
        DB::table('profiles')->update(['status' => 'active']);
    }

    public function down()
    {
        // Revertir: simplemente borrar la columna de profiles
     //   Schema::table('profiles', function (Blueprint $table) {
     //       $table->dropColumn('status');
     //   });
    }
};