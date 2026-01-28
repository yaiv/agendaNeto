<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Columna generada VIRTUAL (no almacenada físicamente)
        DB::statement("
            ALTER TABLE engineer_branch 
            ADD COLUMN active_assignment VARCHAR(100) 
            AS (IF(is_active = 1, CONCAT(user_id, '-', branch_id), NULL)) VIRTUAL
        ");
        
        // Índice único sobre la columna generada
        DB::statement("
            CREATE UNIQUE INDEX unique_active_assignment 
            ON engineer_branch (active_assignment)
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX unique_active_assignment ON engineer_branch");
        DB::statement("ALTER TABLE engineer_branch DROP COLUMN active_assignment");
    }
};


