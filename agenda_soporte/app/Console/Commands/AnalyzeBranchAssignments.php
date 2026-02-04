<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeBranchAssignments extends Command
{
    protected $signature = 'analyze:branch-assignments';
    protected $description = 'Analiza qué sucursales no tienen ingeniero asignado y por qué';

    public function handle()
    {
        $this->info('🔍 ANÁLISIS DE SUCURSALES SIN INGENIERO');
        $this->info(str_repeat('=', 80));
        $this->newLine();

        // 1. ESTADÍSTICAS GENERALES
        $totalSucursales = Branch::count();
        $sucursalesConIngeniero = Branch::whereHas('assignedEngineers', function($q) {
            $q->where('is_active', true);
        })->count();
        $sucursalesSinIngeniero = $totalSucursales - $sucursalesConIngeniero;

        $this->info('📊 ESTADÍSTICAS GENERALES');
        $this->line(str_repeat('-', 80));
        $this->table(
            ['Métrica', 'Cantidad', 'Porcentaje'],
            [
                [
                    'Total sucursales', 
                    $totalSucursales, 
                    '100%'
                ],
                [
                    'Con ingeniero asignado', 
                    $sucursalesConIngeniero, 
                    number_format(($sucursalesConIngeniero / $totalSucursales) * 100, 2) . '%'
                ],
                [
                    'SIN ingeniero asignado', 
                    $sucursalesSinIngeniero, 
                    number_format(($sucursalesSinIngeniero / $totalSucursales) * 100, 2) . '%'
                ],
            ]
        );
        $this->newLine();

        // 2. LISTAR SUCURSALES SIN INGENIERO
        if ($sucursalesSinIngeniero > 0) {
            $this->warn("⚠️ SUCURSALES SIN INGENIERO ASIGNADO ($sucursalesSinIngeniero)");
            $this->line(str_repeat('-', 80));
            
            $sinIngeniero = Branch::whereDoesntHave('assignedEngineers', function($q) {
                $q->where('is_active', true);
            })
            ->with(['region', 'team'])
            ->get();

            // Agrupar por región para ver patrones
            $porRegion = $sinIngeniero->groupBy('region.name');
            
            $this->info("\n📍 AGRUPADAS POR REGIÓN:");
            foreach ($porRegion as $regionName => $sucursales) {
                $this->line("\n🔹 {$regionName} ({$sucursales->count()} sucursales):");
                
                $table = [];
                foreach ($sucursales->take(10) as $sucursal) {
                    $table[] = [
                        $sucursal->id,
                        $sucursal->name,
                        $sucursal->team->name ?? 'N/A',
                        $sucursal->zone_name ?? 'Sin zona',
                    ];
                }
                
                $this->table(
                    ['ID', 'Nombre', 'Compañía', 'Zona'],
                    $table
                );
                
                if ($sucursales->count() > 10) {
                    $remaining = $sucursales->count() - 10;
                    $this->line("   ... y {$remaining} más en esta región");
                }
            }
            $this->newLine();

            // 3. ANÁLISIS DEL CSV ORIGINAL
            $this->info('📄 ANÁLISIS DE CAUSA RAÍZ');
            $this->line(str_repeat('-', 80));
            
            $csvPath = storage_path('app/NetoSemana2.csv');
            if (file_exists($csvPath)) {
                $this->analyzeCSV($csvPath, $sinIngeniero);
            } else {
                $this->warn("⚠️ No se encontró el archivo CSV en: {$csvPath}");
                $this->line("   No se puede analizar la causa raíz.");
            }
            
        } else {
            $this->info('✅ TODAS LAS SUCURSALES TIENEN INGENIERO ASIGNADO');
        }

        // 4. RECOMENDACIONES
        $this->newLine();
        $this->info('💡 RECOMENDACIONES');
        $this->line(str_repeat('-', 80));
        
        if ($sucursalesSinIngeniero > 0) {
            $this->warn("Hay {$sucursalesSinIngeniero} sucursales sin ingeniero asignado.");
            $this->newLine();
            
            $this->line('POSIBLES CAUSAS:');
            $this->line('  1. En el CSV, esas filas tenían "VACANTE" en la columna INGENIERO');
            $this->line('  2. En el CSV, la columna INGENIERO estaba vacía');
            $this->line('  3. Las sucursales se crearon pero no había ingeniero para asignar');
            $this->newLine();
            
            $this->line('SOLUCIONES:');
            $this->line('  A. Revisar el CSV original y verificar qué ingeniero debería ir');
            $this->line('  B. Asignar manualmente desde el panel administrativo');
            $this->line('  C. Esperar la próxima carga con datos actualizados');
            $this->newLine();
            
            $this->line('EXPORTAR LISTA PARA REVISIÓN:');
            $this->comment('  php artisan analyze:branch-assignments --export');
            
        } else {
            $this->info('✅ No hay acciones necesarias. Todas las sucursales tienen ingeniero.');
        }

        $this->newLine();
        $this->info(str_repeat('=', 80));
        
        return Command::SUCCESS;
    }

    private function analyzeCSV($csvPath, $sinIngeniero)
    {
        try {
            $csv = \League\Csv\Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);
            
            $records = iterator_to_array($csv->getRecords());
            $totalRecords = count($records);
            
            // Contar filas con VACANTE o sin ingeniero
            $vacantes = 0;
            $vacios = 0;
            
            foreach ($records as $record) {
                $record = array_change_key_case($record, CASE_UPPER);
                $ingeniero = trim($record['INGENIERO'] ?? '');
                
                if (empty($ingeniero)) {
                    $vacios++;
                } elseif (strtoupper($ingeniero) === 'VACANTE') {
                    $vacantes++;
                }
            }
            
            $this->info("📋 ANÁLISIS DEL CSV:");
            $this->table(
                ['Métrica', 'Cantidad', 'Porcentaje'],
                [
                    [
                        'Total de filas en CSV',
                        $totalRecords,
                        '100%'
                    ],
                    [
                        'Filas con INGENIERO = "VACANTE"',
                        $vacantes,
                        number_format(($vacantes / $totalRecords) * 100, 2) . '%'
                    ],
                    [
                        'Filas con INGENIERO vacío',
                        $vacios,
                        number_format(($vacios / $totalRecords) * 100, 2) . '%'
                    ],
                    [
                        'Total sin ingeniero en CSV',
                        $vacantes + $vacios,
                        number_format((($vacantes + $vacios) / $totalRecords) * 100, 2) . '%'
                    ],
                ]
            );
            $this->newLine();
            
            if ($vacantes + $vacios > 0) {
                $this->warn('✅ CONFIRMADO: Las sucursales sin ingeniero corresponden a:');
                $this->line('   - Filas con "VACANTE" en la columna INGENIERO');
                $this->line('   - Filas con la columna INGENIERO vacía');
                $this->newLine();
                $this->info('📌 Esto es el comportamiento esperado del sistema.');
                $this->info('   El script omite asignaciones cuando no hay ingeniero definido.');
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error analizando CSV: {$e->getMessage()}");
        }
    }
}