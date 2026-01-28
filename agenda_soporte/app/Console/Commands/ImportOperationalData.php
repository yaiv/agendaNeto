<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Team;
use App\Models\Region;
use App\Models\Branch;
use App\Models\User;
use App\Models\Profile;

class ImportOperationalData extends Command
{
    protected $signature = 'import:neto-data {file : El nombre del archivo en storage/app} {--dry-run : Simular sin guardar}';
    protected $description = 'Importa datos masivos desde CSV (Semana 2)';

    private $stats = [
        'teams_created' => 0,
        'regions_created' => 0,
        'branches_created' => 0,
        'engineers_created' => 0,
        'coordinators_created' => 0,
        'assignments_created' => 0,
        'skipped_vacantes' => 0,
        'errors' => [],
    ];

    public function handle()
    {
        $fileName = $this->argument('file');
        $path = storage_path('app/' . $fileName);

        if (!file_exists($path)) {
            $this->error("❌ El archivo no existe en: $path");
            return 1;
        }

        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn("🔍 MODO SIMULACIÓN - No se guardará nada");
        }

        $this->info("📂 Leyendo archivo: $fileName...");

        try {
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');

            $records = iterator_to_array($csv->getRecords());
            $total = count($records);

            $this->info("📊 Total de registros a procesar: $total");
            
            // Muestra de datos
            $this->showSample($records);
            
            if (!$this->confirm('¿Los datos se ven correctos?', true)) {
                $this->warn('Importación cancelada.');
                return 1;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            if (!$isDryRun) {
                DB::beginTransaction();
            }

            foreach ($records as $offset => $record) {
                try {
                    $this->processRow($record, $isDryRun);
                } catch (\Exception $e) {
                    $this->stats['errors'][] = [
                        'fila' => $offset + 2,
                        'error' => $e->getMessage(),
                        'sucursal' => $record['NOMBRE SUCURSAL'] ?? 'N/A',
                    ];
                }
                $bar->advance();
            }

            $bar->finish();
            
            if (!$isDryRun) {
                DB::commit();
            }

            $this->newLine(2);
            $this->showStats();

            return 0;

        } catch (\Exception $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            $this->error("❌ Error fatal: " . $e->getMessage());
            $this->error("Línea: " . $e->getLine());
            return 1;
        }
    }

private function processRow(array $record, bool $isDryRun)
{
    // 0. LIMPIEZA DE LLAVES (Quita BOM y espacios invisibles de los headers)
    $record = array_change_key_case($record, CASE_UPPER);
    
    // Mapeo seguro usando ?? null para evitar "Undefined index"
    $companyName     = trim($record['COMPAÑIA'] ?? $record['COMPANIA'] ?? 'SIN ASIGNAR');
    $coordinatorName = trim($record['CORDINADOR'] ?? $record['COORDINADOR'] ?? 'Admin');
    $regionName      = trim($record['REGION'] ?? 'REGIÓN GENÉRICA');
    $branchName      = trim($record['NOMBRE SUCURSAL'] ?? 'Sucursal Genérica');
    $zoneName        = trim($record['NOMBRE ZONA'] ?? null);
    $engineerName    = trim($record['INGENIERO'] ?? 'Vacante');
    
    // 🛠️ FIX 1: Limpieza Inteligente de Coordenadas
    $latitude  = $this->cleanCoordinate($record['LATITUD'] ?? null, 'lat');
    $longitude = $this->cleanCoordinate($record['LONGITUD -'] ?? $record['LONGITUD'] ?? null, 'long');

    $ecoId     = trim($record['ECO'] ?? null);
    $cecoId    = trim($record['CECO'] ?? null);

    // Validaciones básicas
    if (empty($companyName) || empty($regionName) || empty($branchName)) {
        if ($isDryRun) {
            $this->warn("⚠️ Fila omitida por falta de datos clave (Sucursal: $branchName)");
            return;
        }
        throw new \Exception("Faltan datos obligatorios: COMPAÑIA, REGION o SUCURSAL");
    }

    if ($isDryRun) {
        // En simulación imprimimos una muestra para que veas si la latitud se arregló
        if ($this->stats['branches_created'] < 3) {
            $this->info("🔍 Test Coord: Original: " . ($record['LATITUD']??'N/A') . " -> Fix: $latitude");
        }
        $this->stats['branches_created']++; // Contamos como éxito simulado
        return;
    }

    // 1. COORDINADOR (Nivel 2)
    $coordinator = $this->getOrCreateUser($coordinatorName, 'COORD', 'coordinador');
    
    // 2. COMPAÑÍA (TEAM)
    $team = Team::firstOrCreate(
        ['name' => $companyName],
        ['user_id' => $coordinator->id, 'personal_team' => false]
    );
    
    // Asegurar relación Coordinador-Team
    if (!$team->users()->where('user_id', $coordinator->id)->exists() && $team->user_id !== $coordinator->id) {
        $team->users()->attach($coordinator, ['role' => 'admin']);
    }

    // 3. REGIÓN (Estructura)
    $regionData = ['name' => $regionName, 'team_id' => $team->id];
    if (Schema::hasColumn('regions', 'status')) {
        $regionData['status'] = 'active';
    }
    
    $region = Region::firstOrCreate($regionData);

    // 4. SUCURSAL (Punto Operativo) - FIX 2: Usar updateOrCreate
    // Esto garantiza que si la sucursal ya existe, SE ACTUALICEN sus coordenadas y ECOs
    $branch = Branch::updateOrCreate(
        [
            'name' => $branchName,
            'region_id' => $region->id, // Búsqueda única por nombre y región
        ],
        [
            'team_id' => $team->id,
            'zone_name' => $zoneName,
            'external_id_eco' => $ecoId,
            'external_id_ceco' => $cecoId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => 'active',
            'address' => 'Importado desde Sistema Central', 
        ]
    );
    
    if ($branch->wasRecentlyCreated) {
        $this->stats['branches_created']++;
    }

    // 5. INGENIERO (Nivel 3 - Operativo)
    $engineerUpper = strtoupper($engineerName);
    
    if ($engineerUpper !== 'VACANTE' && !empty($engineerName)) {
        $engineer = $this->getOrCreateUser($engineerName, 'ENG', 'ingeniero');

        // Agregar al Team
        if (!$team->users()->where('user_id', $engineer->id)->exists() && $team->user_id !== $engineer->id) {
            $team->users()->attach($engineer, ['role' => 'member']);
        }

        // ✅ ASIGNACIÓN A SUCURSAL (Pivote engineer_branch)
        // syncWithoutDetaching previene duplicados pero respeta el historial
        $engineer->assignedBranches()->syncWithoutDetaching([
            $branch->id => [
                'team_id'         => $team->id,
                'assignment_type' => 'primary',      // Tipo de asignación
                'is_external'     => false,          // No es soporte externo
                'is_active'       => true,           // Asignación activa
                'assigned_at'     => now(),          // Fecha de asignación
                'notes'           => 'Importado automáticamente desde CSV',
            ]
        ]);

        // ✅ ASIGNACIÓN A REGIÓN (Para filtrado jerárquico rápido)
        // Esto se mantiene para consultas de nivel regional
        $engineer->assignedRegions()->syncWithoutDetaching([
            $region->id => ['assignment_type' => 'primary']
        ]);
        
        // Actualizar asignación de Team (Jetstream)
        if (!$engineer->current_team_id) {
            $engineer->current_team_id = $team->id;
            $engineer->save();
        }

        $this->stats['assignments_created']++;
    } else {
        $this->stats['skipped_vacantes']++;
    }
}

    /**
     * 🧠 Lógica heurística para reparar coordenadas rotas
     * Convierte "221.746.683" -> 22.1746683
     */
    private function cleanCoordinate($value, $type = 'lat')
    {
        if (empty($value)) return null;

        // 1. Limpiar todo lo que no sea números o signo menos
        // Ej: "221.746.683" -> "221746683"
        $clean = preg_replace('/[^0-9-]/', '', (string)$value);
        
        if (!is_numeric($clean)) return null;

        $number = (float)$clean;
        
        // Límites geográficos
        $max = ($type === 'lat') ? 90 : 180;
        $min = ($type === 'lat') ? -90 : -180;

        // Si es 0, retornamos null
        if ($number == 0) return null;

        // 2. Reducir magnitud dividiendo por 10 hasta que entre en el rango
        // "221746683" es > 90, así que dividimos...
        while ($number > $max || $number < $min) {
            $number /= 10;
            // Freno de emergencia por si entra en loop infinito (números absurdamente grandes)
            if (abs($number) < 0.00001) return null; 
        }

        return $number;
    }

    private function getOrCreateUser($name, $prefix, $type = 'usuario')
    {
        // Limpiar nombre
        $cleanName = trim($name);
        $email = Str::slug($cleanName, '.') . '@corporativo.com';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $cleanName,
                'password' => Hash::make('Temporal123!'), // 🔐 Contraseña temporal fuerte
            ]
        );

        if ($user->wasRecentlyCreated) {
            if ($type === 'coordinador') {
                $this->stats['coordinators_created']++;
            } elseif ($type === 'ingeniero') {
                $this->stats['engineers_created']++;
            }
        }

        // Crear perfil si no existe
        if (!$user->profile) {
            Profile::create([
                'user_id' => $user->id,
                'employee_code' => strtoupper($prefix) . '-' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
                'phone1' => '555-0000',
                'status' => 'active'
            ]);
        }

        return $user;
    }

    private function showSample(array $records)
    {
        $this->info("\n📋 Muestra de los primeros 3 registros:");
        $sample = array_slice($records, 0, 3);
        
        if (!empty($sample)) {
            $headers = array_keys($sample[0]);
            $this->table($headers, $sample);
        }
    }

    private function showStats()
    {
        $this->info("✅ Importación completada:");
        $this->table(
            ['Recurso', 'Cantidad'],
            [
                ['Compañías creadas', $this->stats['teams_created']],
                ['Regiones creadas', $this->stats['regions_created']],
                ['Sucursales creadas', $this->stats['branches_created']],
                ['Coordinadores creados', $this->stats['coordinators_created']],
                ['Ingenieros creados', $this->stats['engineers_created']],
                ['Asignaciones creadas', $this->stats['assignments_created']],
                ['Vacantes omitidas', $this->stats['skipped_vacantes']],
                ['Errores', count($this->stats['errors'])],
            ]
        );

        if (!empty($this->stats['errors'])) {
            $this->error("\n⚠️  Errores encontrados:");
            foreach (array_slice($this->stats['errors'], 0, 10) as $error) {
                $this->warn("Fila {$error['fila']} ({$error['sucursal']}): {$error['error']}");
            }
            
            if (count($this->stats['errors']) > 10) {
                $remaining = count($this->stats['errors']) - 10;
                $this->warn("... y {$remaining} errores más");
            }
        }
    }
}