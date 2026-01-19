<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engineer_region', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            
            // "primary" o "support"
            $table->string('assignment_type'); 
            
            $table->timestamps();

            $table->unique(['user_id', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engineer_region');
    }
};