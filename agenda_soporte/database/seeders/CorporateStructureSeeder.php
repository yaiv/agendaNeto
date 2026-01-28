<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Team;
use App\Models\User;
use App\Models\Profile;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use League\Csv\Reader;

class CorporateStructureSeeder extends Seeder
{
    private $credentials = []; 
    private $stats = [
        'companies' => 0,
        'regions' => 0,
        'branches' => 0,
        'engineers' => 0,
        'assignments' => 0,
    ];

    public function run(): void
    {
        User::flushEventListeners();
        Team::flushEventListeners();

        DB::transaction(function () {
            $this->command->info('👑 Gestionando Nivel 1 (Gerencia Global)...');
            $this->manageLevel1();

            $this->command->info('🏗️  Construyendo Estructura y Operación (Niveles 2 y 3)...');
            $this->buildStructureFromCSV();
        });

        $this->displayCredentials();
        $this->displayStats();
    }

    private function manageLevel1()
    {
        // 0. CREAR/OBTENER TEAM GLOBAL (Primero de todo)
        $globalTeam = $this->ensureGlobalTeam();

        // 1. NETO (El Intocable)
        $netoEmail = 'neto@global.com';
        $neto = User::where('email', $netoEmail)->first();
        
        if (!$neto) {
            $neto = User::create([
                'name' => 'Neto Admin',
                'email' => $netoEmail,
                'password' => Hash::make('password'),
                'global_role' => 'gerente',
            ]);
            $this->createProfile($neto, 'ADM-001');
            $this->credentials[] = [
                'Usuario' => 'Neto Admin', 
                'Email' => $netoEmail, 
                'Password' => 'password', 
                'Rol' => 'Gerente Global'
            ];
            $this->command->info("   ✅ Creado: $netoEmail");
        } else {
            $this->command->info("   ⏭️  Omitido (Ya existe): $netoEmail");
        }
        
        // Asignar a Team Global
        $this->assignToGlobalTeam($neto, $globalTeam);

        // 2. DANIEL VAZQUEZ CARRALES
        $danielEmail = 'daniel.vazquez@global.com';
        $daniel = User::firstOrCreate(
            ['email' => $danielEmail],
            [
                'name' => 'DANIEL VAZQUEZ CARRALES',
                'password' => Hash::make('password'),
                'global_role' => 'gerente',
            ]
        );
        
        if ($daniel->global_role !== 'gerente') {
            $daniel->update(['global_role' => 'gerente']);
        }
        
        $this->createProfile($daniel, 'ADM-002');
        
        if ($daniel->wasRecentlyCreated) {
            $this->credentials[] = [
                'Usuario' => 'Daniel Vazquez Carrales', 
                'Email' => $danielEmail, 
                'Password' => 'password', 
                'Rol' => 'Gerente Global'
            ];
        }
        
        // Asignar a Team Global
        $this->assignToGlobalTeam($daniel, $globalTeam);
        
        $this->command->info("   ✅ Verificado: Daniel Vazquez Carrales (Gerente)");
    }

    /**
     * Crea o recupera el Team Global
     */
    private function ensureGlobalTeam()
    {
        $globalTeam = Team::firstOrCreate(
            ['name' => 'Global'],
            [
                'user_id' => 1, // Temporalmente, lo actualizaremos después
                'personal_team' => false,
            ]
        );

        if ($globalTeam->wasRecentlyCreated) {
            $this->command->info("   🌍 Team Global creado");
        } else {
            $this->command->info("   🌍 Team Global ya existe");
        }

        return $globalTeam;
    }

    /**
     * Asigna un gerente al Team Global
     */
    private function assignToGlobalTeam($user, $globalTeam)
    {
        // Asegurar que esté en el team
        if (!$globalTeam->users()->where('user_id', $user->id)->exists()) {
            $globalTeam->users()->attach($user, ['role' => 'admin']);
            $this->command->info("      → {$user->name} agregado a Team Global");
        }

        // Asignar como current_team si no tiene uno
        if (!$user->current_team_id) {
            $user->current_team_id = $globalTeam->id;
            $user->saveQuietly();
            $this->command->info("      → current_team_id = Global ({$globalTeam->id})");
        }

        // Actualizar owner del team si es Neto
        if ($user->email === 'neto@global.com' && $globalTeam->user_id !== $user->id) {
            $globalTeam->user_id = $user->id;
            $globalTeam->saveQuietly();
            $this->command->info("      → Neto asignado como owner del Team Global");
        }
    }

    private function buildStructureFromCSV()
    {
        $path = storage_path('app/NetoSemana2.csv');

        if (!file_exists($path)) {
            $this->command->error("❌ Error: No se encontró el archivo en $path");
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        
        $structure = [];

        // 🔄 PASO 1: Agrupar datos por jerarquía Compañía -> Región -> Sucursal
        foreach ($csv->getRecords() as $record) {
            $record = array_change_key_case($record, CASE_UPPER);

            $companyName     = strtoupper(trim($record['COMPAÑIA'] ?? $record['COMPANIA'] ?? $record['EMPRESA'] ?? ''));
            $coordinatorName = strtoupper(trim($record['CORDINADOR'] ?? $record['COORDINADOR'] ?? ''));
            $regionName      = strtoupper(trim($record['REGION'] ?? $record['ZONA'] ?? ''));
            $branchName      = strtoupper(trim($record['NOMBRE SUCURSAL'] ?? $record['SUCURSAL'] ?? ''));
            $engineerName    = strtoupper(trim($record['INGENIERO'] ?? ''));

            if (empty($companyName) || empty($coordinatorName) || empty($regionName)) {
                continue;
            }

            // Si no hay nombre de sucursal, crear uno genérico basado en la región
            if (empty($branchName)) {
                $branchName = "SUCURSAL $regionName";
            }

            $structure[$companyName]['coordinator'] = $coordinatorName;
            $structure[$companyName]['regions'][$regionName]['branches'][$branchName]['engineers'][] = $engineerName;
        }

        // 🔄 PASO 2: Procesar cada compañía
        foreach ($structure as $companyName => $data) {
            $this->processCompany($companyName, $data['coordinator'], $data['regions']);
        }
    }

    private function processCompany($companyName, $coordinatorName, $regions)
    {
        $this->command->info("   🏢 Procesando: $companyName ($coordinatorName)");

        // 1. COORDINADOR (Nivel 2)
        $coordinator = $this->getOrCreateUser($coordinatorName, 'COORD', 'coordinador');
        
        // 2. COMPAÑÍA (TEAM)
        $team = Team::updateOrCreate(
            ['name' => $companyName],
            [
                'user_id' => $coordinator->id,
                'personal_team' => false,
            ]
        );
        
        if ($team->wasRecentlyCreated) {
            $this->stats['companies']++;
        }
        
        // Asegurar que el coordinador esté en el team como admin
        if (!$team->users()->where('user_id', $coordinator->id)->exists()) {
            $team->users()->attach($coordinator, ['role' => 'admin']);
        }
        
        // Asignar current_team al coordinador
        if ($coordinator->current_team_id !== $team->id) {
            $coordinator->current_team_id = $team->id;
            $coordinator->saveQuietly();
        }

        // 3. REGIONES, SUCURSALES E INGENIEROS
        foreach ($regions as $regionName => $regionData) {
            // 3.1 Crear Región (Estructura)
            $region = Region::firstOrCreate(
                ['team_id' => $team->id, 'name' => $regionName],
                ['status' => 'active']
            );
            
            if ($region->wasRecentlyCreated) {
                $this->stats['regions']++;
            }

            // 3.2 Procesar cada Sucursal dentro de la Región
            foreach ($regionData['branches'] as $branchName => $branchData) {
                // Crear Sucursal (Punto Operativo)
                $branch = Branch::firstOrCreate(
                    [
                        'name' => $branchName, 
                        'region_id' => $region->id
                    ],
                    [
                        'team_id' => $team->id, 
                        'status' => 'active',
                        'address' => 'Por definir - Seeder',
                    ]
                );
                
                if ($branch->wasRecentlyCreated) {
                    $this->stats['branches']++;
                }

                // 3.3 Procesar Ingenieros de la Sucursal
                $uniqueEngineers = array_unique(array_filter($branchData['engineers']));
                
                foreach ($uniqueEngineers as $engName) {
                    if (empty($engName) || strtoupper($engName) === 'VACANTE') {
                        continue;
                    }

                    $engineer = $this->getOrCreateUser($engName, 'ENG', 'ingeniero');
                    
                    if ($engineer->wasRecentlyCreated) {
                        $this->stats['engineers']++;
                    }

                    // Asegurar que el ingeniero esté en el Team
                    if (!$team->users()->where('user_id', $engineer->id)->exists()) {
                        $team->users()->attach($engineer, ['role' => 'member']);
                    }

                    // ✅ CRÍTICO: VINCULACIÓN DIRECTA A SUCURSAL
                    // Esto alimenta el Dashboard Operativo (Nivel 3)
                    $engineer->assignedBranches()->syncWithoutDetaching([
                        $branch->id => [
                            'team_id'         => $team->id,
                            'assignment_type' => 'primary',      // Ingeniero primario
                            'is_external'     => false,          // No es soporte externo
                            'is_active'       => true,           // Asignación activa
                            'assigned_at'     => now(),          // Fecha de asignación
                            'notes'           => 'Generado por CorporateStructureSeeder',
                        ]
                    ]);
                    
                    $this->stats['assignments']++;

                    // ✅ OPCIONAL: VINCULACIÓN A REGIÓN
                    // Útil para filtros jerárquicos rápidos
                    $engineer->assignedRegions()->syncWithoutDetaching([
                        $region->id => ['assignment_type' => 'primary']
                    ]);

                    // Asignar current_team si no tiene
                    if (!$engineer->current_team_id) {
                        $engineer->current_team_id = $team->id;
                        $engineer->saveQuietly();
                    }
                }
            }
        }
    }

    private function getOrCreateUser($name, $prefix, $role = null)
    {
        $email = Str::slug($name, '.') . '@corporativo.com';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'global_role' => null
            ]
        );

        // Registrar credenciales si es nuevo
        if ($user->wasRecentlyCreated) {
            $this->credentials[] = [
                'Usuario' => $name,
                'Email' => $email,
                'Password' => 'password',
                'Rol' => ucfirst($role ?? 'usuario')
            ];
        }

        $this->createProfile($user, $prefix);

        return $user;
    }

    private function createProfile($user, $prefix)
    {
        if (!$user->profile) {
            Profile::create([
                'user_id' => $user->id,
                'employee_code' => $prefix . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                'phone1' => '555-0000',
                'status' => 'active'
            ]);
        }
    }

    /**
     * Mostrar tabla de credenciales generadas
     */
    private function displayCredentials()
    {
        if (empty($this->credentials)) {
            $this->command->info("\n📋 No se crearon usuarios nuevos (todos ya existían)");
            return;
        }

        $this->command->info("\n" . str_repeat('=', 90));
        $this->command->info("🔐 CREDENCIALES DE ACCESO GENERADAS");
        $this->command->info(str_repeat('=', 90));
        
        $this->command->table(
            ['Usuario', 'Email', 'Password', 'Rol'],
            array_map(function($cred) {
                return [
                    $cred['Usuario'],
                    $cred['Email'],
                    $cred['Password'],
                    $cred['Rol']
                ];
            }, $this->credentials)
        );

        $this->command->warn("\n⚠️  IMPORTANTE: Cambia estas contraseñas en producción");
        $this->command->info("💡 Todos los usuarios pueden ingresar con su email y password 'password'\n");
    }

    /**
     * Mostrar estadísticas de la importación
     */
    private function displayStats()
    {
        $this->command->info("\n" . str_repeat('=', 90));
        $this->command->info("📊 ESTADÍSTICAS DE IMPORTACIÓN");
        $this->command->info(str_repeat('=', 90));
        
        $this->command->table(
            ['Elemento', 'Cantidad'],
            [
                ['Compañías (Teams) creadas', $this->stats['companies']],
                ['Regiones creadas', $this->stats['regions']],
                ['Sucursales creadas', $this->stats['branches']],
                ['Ingenieros creados', $this->stats['engineers']],
                ['Asignaciones a Sucursales', $this->stats['assignments']],
            ]
        );
        
        $this->command->info("\n✅ Importación completada exitosamente\n");
    }
}