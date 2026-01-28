<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Branch;
use App\Models\Region;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseDatabaseCommand extends Command
{
    protected $signature = 'diagnose:database';
    protected $description = 'Diagnostica el estado actual de la base de datos y las asignaciones';

    public function handle()
    {
        $this->info('🔍 DIAGNÓSTICO DE BASE DE DATOS');
        $this->info(str_repeat('=', 80));
        $this->newLine();

        // 1. Usuarios
        $this->info('👥 USUARIOS');
        $this->line(str_repeat('-', 80));
        
        $totalUsers = User::count();
        $gerentes = User::where('global_role', 'gerente')->count();
        
        // Detectar si usa Spatie Permission o campo directo
        $hasRolesTable = Schema::hasTable('roles');
        $hasRoleColumn = Schema::hasColumn('users', 'role');
        
        if ($hasRolesTable) {
            // Usando Spatie Permission
            $coordinadores = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'coordinador')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->count();
            
            $ingenieros = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'ingeniero')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->count();
        } elseif ($hasRoleColumn) {
            // Usando columna 'role' directo en users
            $coordinadores = User::where('role', 'coordinador')->count();
            $ingenieros = User::where('role', 'ingeniero')->count();
        } else {
            // Sin sistema de roles
            $coordinadores = 0;
            $ingenieros = 0;
            $this->warn('⚠️ No se detectó sistema de roles (ni Spatie ni columna role)');
        }
        
        $this->table(
            ['Tipo', 'Cantidad'],
            [
                ['Total usuarios', $totalUsers],
                ['Gerentes (global_role)', $gerentes],
                ['Coordinadores', $coordinadores],
                ['Ingenieros', $ingenieros],
            ]
        );
        $this->newLine();

        // 2. Estructura Organizacional
        $this->info('🏢 ESTRUCTURA ORGANIZACIONAL');
        $this->line(str_repeat('-', 80));
        
        $totalTeams = Team::count();
        $totalRegions = Region::count();
        $totalBranches = Branch::count();
        
        $this->table(
            ['Elemento', 'Cantidad'],
            [
                ['Compañías (Teams)', $totalTeams],
                ['Regiones', $totalRegions],
                ['Sucursales', $totalBranches],
            ]
        );
        $this->newLine();

        // 3. Asignaciones (LO CRÍTICO)
        $this->info('🔗 ASIGNACIONES (CRÍTICO)');
        $this->line(str_repeat('-', 80));
        
        // Verificar si existen las tablas pivote
        $hasEngineerRegion = Schema::hasTable('engineer_region');
        $hasEngineerBranch = Schema::hasTable('engineer_branch');
        
        if (!$hasEngineerRegion) {
            $this->error('🔴 Tabla engineer_region NO EXISTE');
        }
        
        if (!$hasEngineerBranch) {
            $this->error('🔴 Tabla engineer_branch NO EXISTE - CRÍTICO');
            $this->newLine();
            $this->warn('⚠️ Esta tabla es esencial para el Dashboard Operativo');
            $this->warn('⚠️ Ejecuta: php artisan migrate');
            $this->newLine();
        }
        
        // Asignaciones a regiones
        $ingenierosConRegion = 0;
        $totalAsignacionesRegion = 0;
        if ($hasEngineerRegion) {
            $totalAsignacionesRegion = DB::table('engineer_region')->count();
            $ingenierosConRegion = DB::table('engineer_region')
                ->distinct('user_id')
                ->count('user_id');
        }
        
        // Asignaciones a sucursales (LO IMPORTANTE)
        $ingenierosConSucursal = 0;
        $totalAsignacionesSucursal = 0;
        $totalAsignacionesSucursalHistorial = 0;
        
        if ($hasEngineerBranch) {
            // Verificar si tiene columna is_active
            $hasIsActive = Schema::hasColumn('engineer_branch', 'is_active');
            
            if ($hasIsActive) {
                $totalAsignacionesSucursal = DB::table('engineer_branch')
                    ->where('is_active', true)
                    ->count();
                
                $ingenierosConSucursal = DB::table('engineer_branch')
                    ->where('is_active', true)
                    ->distinct('user_id')
                    ->count('user_id');
            } else {
                $this->warn('⚠️ Tabla engineer_branch no tiene columna is_active');
                $totalAsignacionesSucursal = DB::table('engineer_branch')->count();
                $ingenierosConSucursal = DB::table('engineer_branch')
                    ->distinct('user_id')
                    ->count('user_id');
            }
            
            $totalAsignacionesSucursalHistorial = DB::table('engineer_branch')->count();
        }
        
        $this->table(
            ['Métrica', 'Cantidad', 'Estado'],
            [
                [
                    'Ingenieros con regiones asignadas', 
                    $ingenierosConRegion,
                    $ingenierosConRegion > 0 ? '✅' : '⚠️'
                ],
                [
                    'Total asignaciones a regiones', 
                    $totalAsignacionesRegion,
                    $totalAsignacionesRegion > 0 ? '✅' : '⚠️'
                ],
                [
                    'Ingenieros con sucursales asignadas (ACTIVAS)', 
                    $ingenierosConSucursal,
                    $ingenierosConSucursal > 0 ? '✅' : '🔴 PROBLEMA'
                ],
                [
                    'Total asignaciones activas a sucursales', 
                    $totalAsignacionesSucursal,
                    $totalAsignacionesSucursal > 0 ? '✅' : '🔴 PROBLEMA'
                ],
                [
                    'Total asignaciones (con historial)', 
                    $totalAsignacionesSucursalHistorial,
                    ''
                ],
            ]
        );
        $this->newLine();

        // 4. Verificar campos de engineer_branch
        if ($hasEngineerBranch) {
            $this->info('📋 CAMPOS DE engineer_branch');
            $this->line(str_repeat('-', 80));
            
            $requiredFields = [
                'user_id' => true,
                'branch_id' => true,
                'team_id' => false,
                'assignment_type' => false,
                'is_external' => false,
                'is_active' => false,
                'assigned_at' => false,
                'unassigned_at' => false,
                'notes' => false,
            ];
            
            $fieldsStatus = [];
            foreach ($requiredFields as $field => $required) {
                $exists = Schema::hasColumn('engineer_branch', $field);
                $status = $exists ? '✅' : ($required ? '🔴 FALTA' : '⚠️ FALTA');
                $fieldsStatus[] = [$field, $exists ? 'Presente' : 'Ausente', $status];
            }
            
            $this->table(['Campo', 'Estado', 'Crítico'], $fieldsStatus);
            $this->newLine();
        }

        // 5. Análisis de Problemas
        $this->info('🚨 ANÁLISIS DE PROBLEMAS');
        $this->line(str_repeat('-', 80));
        
        $problemas = [];
        $advertencias = [];
        $todo_bien = [];
        
        // Verificar tabla engineer_branch
        if (!$hasEngineerBranch) {
            $problemas[] = '🔴 CRÍTICO: Tabla engineer_branch NO EXISTE';
            $problemas[] = '   → Ejecuta: php artisan migrate';
        } else {
            $todo_bien[] = "✅ Tabla engineer_branch existe";
        }
        
        // Verificar campos esenciales
        if ($hasEngineerBranch && !Schema::hasColumn('engineer_branch', 'is_active')) {
            $problemas[] = '🔴 Falta columna is_active en engineer_branch';
            $problemas[] = '   → Ejecuta la migración de refactorización';
        }
        
        if ($hasEngineerBranch && !Schema::hasColumn('engineer_branch', 'is_external')) {
            $advertencias[] = '⚠️ Falta columna is_external en engineer_branch';
            $advertencias[] = '   → Esta columna se agregó en la refactorización';
        }
        
        // Verificar si hay sucursales
        if ($totalBranches === 0) {
            $problemas[] = '🔴 NO HAY SUCURSALES CREADAS - Esto es crítico';
            $problemas[] = '   → Ejecuta el seeder o importa datos';
        } else {
            $todo_bien[] = "✅ Hay {$totalBranches} sucursales creadas";
        }
        
        // Verificar asignaciones a sucursales
        if ($hasEngineerBranch && $ingenierosConSucursal === 0 && $ingenieros > 0) {
            $problemas[] = '🔴 NINGÚN INGENIERO TIENE SUCURSALES ASIGNADAS';
            $problemas[] = '   → El Dashboard Operativo NO funcionará';
            $problemas[] = '   → Causa probable: Corriste el script ANTES de la corrección';
        } elseif ($ingenierosConSucursal < $ingenieros && $ingenieros > 0) {
            $advertencias[] = "⚠️ Solo {$ingenierosConSucursal} de {$ingenieros} ingenieros tienen sucursales";
        } elseif ($ingenierosConSucursal > 0) {
            $todo_bien[] = "✅ {$ingenierosConSucursal} ingenieros tienen sucursales asignadas";
        }
        
        // Verificar consistencia
        if ($hasEngineerBranch && $totalAsignacionesSucursal === 0 && $totalAsignacionesRegion > 0) {
            $problemas[] = '🔴 INCONSISTENCIA DETECTADA:';
            $problemas[] = "   → Hay {$totalAsignacionesRegion} asignaciones a regiones";
            $problemas[] = "   → Pero 0 asignaciones a sucursales";
            $problemas[] = '   → Esto confirma que usaste el script VIEJO (sin la corrección)';
        }
        
        // Mostrar resultados
        if (!empty($problemas)) {
            $this->error('PROBLEMAS CRÍTICOS DETECTADOS:');
            foreach ($problemas as $problema) {
                $this->line($problema);
            }
            $this->newLine();
        }
        
        if (!empty($advertencias)) {
            $this->warn('ADVERTENCIAS:');
            foreach ($advertencias as $advertencia) {
                $this->line($advertencia);
            }
            $this->newLine();
        }
        
        if (!empty($todo_bien)) {
            $this->info('ASPECTOS CORRECTOS:');
            foreach ($todo_bien as $tb) {
                $this->line($tb);
            }
            $this->newLine();
        }

        // 6. Recomendaciones
        $this->info('💡 RECOMENDACIONES');
        $this->line(str_repeat('-', 80));
        $this->newLine();
        
        if (!empty($problemas)) {
            if (!$hasEngineerBranch) {
                $this->warn('PASO 1: Ejecutar migraciones');
                $this->line('  php artisan migrate');
                $this->newLine();
            }
            
            if ($totalBranches === 0 || $ingenierosConSucursal === 0) {
                $this->warn('PASO 2: Decidir estrategia según tu situación:');
                $this->newLine();
                
                $this->line('OPCIÓN A: Empezar de CERO (si no hay datos importantes)');
                $this->comment('  php artisan migrate:fresh');
                $this->comment('  php artisan db:seed --class=CorporateStructureSeeder');
                $this->newLine();
                
                $this->line('OPCIÓN B: CORREGIR datos existentes (si hay datos importantes)');
                $this->comment('  php artisan db:seed --class=FixEngineerBranchAssignments');
                $this->newLine();
                
                $this->line('OPCIÓN C: Re-importar con script corregido');
                $this->comment('  php artisan import:neto-data NetoSemana2.csv');
                $this->newLine();
            }
            
        } else {
            $this->info('✅ La base de datos parece estar en buen estado!');
            $this->newLine();
            
            $this->line('Puedes verificar el Dashboard:');
            $this->comment('  → Inicia sesión como ingeniero');
            $this->comment('  → Verifica que se muestren las sucursales asignadas');
            $this->newLine();
        }

        // 7. Información del sistema
        $this->info('ℹ️  INFORMACIÓN DEL SISTEMA');
        $this->line(str_repeat('-', 80));
        
        $this->table(
            ['Componente', 'Estado'],
            [
                ['Tabla engineer_branch', $hasEngineerBranch ? '✅ Existe' : '🔴 No existe'],
                ['Tabla engineer_region', $hasEngineerRegion ? '✅ Existe' : '⚠️ No existe'],
                ['Tabla roles (Spatie)', $hasRolesTable ? '✅ Detectado' : '⚠️ No detectado'],
                ['Columna users.role', $hasRoleColumn ? '✅ Existe' : '⚠️ No existe'],
                ['Total usuarios', $totalUsers],
                ['Total sucursales', $totalBranches],
                ['Asignaciones activas', $totalAsignacionesSucursal],
            ]
        );
        $this->newLine();

        // Resumen final
        $this->info(str_repeat('=', 80));
        if (empty($problemas) && empty($advertencias)) {
            $this->info('✅ DIAGNÓSTICO COMPLETO - TODO EN ORDEN');
        } elseif (!empty($problemas)) {
            $this->error('🔴 DIAGNÓSTICO COMPLETO - ACCIÓN REQUERIDA');
            $this->newLine();
            $this->warn('Lee las recomendaciones arriba y decide tu estrategia.');
        } else {
            $this->warn('⚠️ DIAGNÓSTICO COMPLETO - REVISAR ADVERTENCIAS');
        }
        $this->info(str_repeat('=', 80));

        return Command::SUCCESS;
    }
}