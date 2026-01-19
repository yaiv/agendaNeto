<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            
            // Pertenencia al Team (Compañía)
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            
            $table->timestamps();
            
            
            $table->unique(['team_id', 'name']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};