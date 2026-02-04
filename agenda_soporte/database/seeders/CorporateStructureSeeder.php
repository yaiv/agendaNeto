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
        'companies'   => 0,
        'regions'     => 0,
        'branches'    => 0,
        'engineers'   => 0,
        'assignments' => 0,
    ];

    public function run(): void
    {
        User::flushEventListeners();
        Team::flushEventListeners();

        DB::transaction(function () {
            $this->command->info('👑 Gestionando Nivel 1 (Gerencia Global)...');
            $this->manageLevel1();

            $this->command->info('🏗️  Construyendo Estructura (CSV)...');
            $this->buildStructureFromCSV();
        });

        $this->displayCredentials();
        $this->displayStats();
    }

    /* ============================================================
     | NIVEL 1 – GERENCIA GLOBAL
     * ============================================================*/
    private function manageLevel1()
    {
        $globalTeam = $this->ensureGlobalTeam();

        // NETO
        $neto = User::firstOrCreate(
            ['email' => 'neto@global.com'],
            [
                'name'        => 'Neto Admin',
                'password'    => Hash::make('password'),
                'global_role' => 'gerente',
            ]
        );

        if ($neto->global_role !== 'gerente') {
            $neto->updateQuietly(['global_role' => 'gerente']);
        }

        $this->createProfile($neto, 'ADM');
        $this->assignToGlobalTeam($neto, $globalTeam);

        // DANIEL
        $daniel = User::firstOrCreate(
            ['email' => 'daniel.vazquez@global.com'],
            [
                'name'        => 'DANIEL VAZQUEZ CARRALES',
                'password'    => Hash::make('password'),
                'global_role' => 'gerente',
            ]
        );

        if ($daniel->global_role !== 'gerente') {
            $daniel->updateQuietly(['global_role' => 'gerente']);
        }

        $this->createProfile($daniel, 'ADM');
        $this->assignToGlobalTeam($daniel, $globalTeam);
    }

    private function ensureGlobalTeam(): Team
    {
        return Team::firstOrCreate(
            ['name' => 'Global'],
            [
                'user_id'       => 1,
                'personal_team' => false,
            ]
        );
    }

    private function assignToGlobalTeam(User $user, Team $team): void
    {
        if (!$team->users()->where('user_id', $user->id)->exists()) {
            $team->users()->attach($user, ['role' => 'admin']);
        }

        if (!$user->current_team_id) {
            $user->updateQuietly(['current_team_id' => $team->id]);
        }

        if ($user->email === 'neto@global.com' && $team->user_id !== $user->id) {
            $team->updateQuietly(['user_id' => $user->id]);
        }
    }

    /* ============================================================
     | CSV → ESTRUCTURA
     * ============================================================*/
    private function buildStructureFromCSV()
    {
        $path = storage_path('app/NetoSemana2.csv');

        if (!file_exists($path)) {
            $this->command->error("❌ CSV no encontrado: $path");
            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        $structure = [];

        foreach ($csv->getRecords() as $record) {
            $r = array_change_key_case($record, CASE_UPPER);

            $company     = strtoupper(trim($r['COMPAÑIA'] ?? ''));
            $coordinator = strtoupper(trim($r['CORDINADOR'] ?? $r['COORDINADOR'] ?? ''));
            $region      = strtoupper(trim($r['REGION'] ?? ''));
            $branch      = strtoupper(trim($r['NOMBRE SUCURSAL'] ?? ''));
            $engineer    = strtoupper(trim($r['INGENIERO'] ?? ''));

            if (!$company || !$coordinator || !$region) {
                continue;
            }

            $branch = $branch ?: "SUCURSAL $region";

            $structure[$company]['coordinator'] = $coordinator;
            $structure[$company]['regions'][$region]['branches'][$branch]['engineers'][] = $engineer;
        }

        foreach ($structure as $company => $data) {
            $this->processCompany($company, $data['coordinator'], $data['regions']);
        }
    }

    /* ============================================================
     | COMPAÑÍA → TEAM
     * ============================================================*/
    private function processCompany(string $company, string $coordinatorName, array $regions)
    {
        $this->command->info("🏢 $company");

        // COORDINADOR (OWNER DEL TEAM)
        $coordinator = $this->getOrCreateUser($coordinatorName, 'COORD', 'coordinador');

        // 🔐 FORZAR ROL GLOBAL
        if ($coordinator->global_role !== 'coordinador') {
            $coordinator->updateQuietly(['global_role' => 'coordinador']);
        }

        $team = Team::updateOrCreate(
            ['name' => $company],
            [
                'user_id'       => $coordinator->id,
                'personal_team' => false,
            ]
        );

        if (!$team->users()->where('user_id', $coordinator->id)->exists()) {
            $team->users()->attach($coordinator, ['role' => 'admin']);
        }

        $coordinator->updateQuietly(['current_team_id' => $team->id]);

        foreach ($regions as $regionName => $regionData) {
            $region = Region::firstOrCreate(
                ['team_id' => $team->id, 'name' => $regionName],
                ['status' => 'active']
            );

            foreach ($regionData['branches'] as $branchName => $branchData) {
                $branch = Branch::firstOrCreate(
                    ['region_id' => $region->id, 'name' => $branchName],
                    [
                        'team_id' => $team->id,
                        'status'  => 'active',
                        'address' => 'Seeder',
                    ]
                );

                foreach (array_unique($branchData['engineers']) as $engName) {
                    if (!$engName || $engName === 'VACANTE') {
                        continue;
                    }

                    $engineer = $this->getOrCreateUser($engName, 'ENG', 'ingeniero');

                    // 🔐 FORZAR ROL GLOBAL
                    if ($engineer->global_role !== 'ingeniero') {
                        $engineer->updateQuietly(['global_role' => 'ingeniero']);
                    }

                    if (!$team->users()->where('user_id', $engineer->id)->exists()) {
                        $team->users()->attach($engineer, ['role' => 'member']);
                    }

                    $engineer->assignedBranches()->syncWithoutDetaching([
                        $branch->id => [
                            'team_id'         => $team->id,
                            'assignment_type' => 'primary',
                            'is_active'       => true,
                            'assigned_at'     => now(),
                        ]
                    ]);

                    if (!$engineer->current_team_id) {
                        $engineer->updateQuietly(['current_team_id' => $team->id]);
                    }

                    $this->stats['assignments']++;
                }
            }
        }
    }

    /* ============================================================
     | USUARIOS
     * ============================================================*/
    private function getOrCreateUser(string $name, string $prefix, string $role): User
    {
        $email = Str::slug($name, '.') . '@corporativo.com';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'        => $name,
                'password'    => Hash::make('password'),
                'global_role' => $role,
            ]
        );

        // 🔐 BLINDAJE TOTAL
        if ($user->global_role !== $role) {
            $user->updateQuietly(['global_role' => $role]);
        }

        $this->createProfile($user, $prefix);

        return $user;
    }

    private function createProfile(User $user, string $prefix): void
    {
        if (!$user->profile) {
            Profile::create([
                'user_id'       => $user->id,
                'employee_code' => $prefix . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                'phone1' => '555-0000',
                'status'        => 'active',
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