<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Region;
use App\Models\Team;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CorporateStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedCompanies();
        });
    }

    /**
     * Procesa todas las compañías con sus coordinadores, regiones e ingenieros
     */
    private function seedCompanies()
    {
        // Definición de las 5 compañías con sus coordinadores
        $companies = [
            [
                'name' => 'CENTRO',
                'coordinator' => 'ALEXANDER VARGAS GARCIA',
                'regions' => $this->getCentroRegions(),
                'engineers' => $this->getCentroEngineers()
            ],
            [
                'name' => 'ORIENTE',
                'coordinator' => 'HECTOR ISRAEL RAMIREZ GONZALEZ',
                'regions' => $this->getOrienteRegions(),
                'engineers' => $this->getOrienteEngineers()
            ],
            [
                'name' => 'PONIENTE',
                'coordinator' => 'LUIS JESUS SANTOS GALEANA',
                'regions' => $this->getPonienteRegions(),
                'engineers' => $this->getPonienteEngineers()
            ],
            [
                'name' => 'SURESTE',
                'coordinator' => 'IVAN GUERRA GARCIA',
                'regions' => $this->getSuresteRegions(),
                'engineers' => $this->getSuresteEngineers()
            ],
            [
                'name' => 'VERACRUZ',
                'coordinator' => 'HECTOR ALBERTO CORTES CONTRERAS',
                'regions' => $this->getVeracruzRegions(),
                'engineers' => $this->getVeracruzEngineers()
            ],
        ];

        foreach ($companies as $companyData) {
            $this->processCompany($companyData);
        }
    }

    private function processCompany(array $companyData)
    {
        $this->command->info("Procesando Compañía: {$companyData['name']}");

        // 1. Crear al Coordinador (Team Owner)
        $coordinator = User::firstOrCreate(
            ['email' => $this->generateEmail($companyData['coordinator'])],
            [
                'name' => $companyData['coordinator'],
                'password' => Hash::make('password'),
                'global_role' => null,
            ]
        );

        // Usamos firstOrCreate para el perfil para evitar duplicados estrictos
        Profile::firstOrCreate(
            ['user_id' => $coordinator->id],
            [
                'employee_code' => 'COORD-' . strtoupper(Str::random(5)),
                'phone1' => '555-0000',
                'status' => 'active'
            ]
        );

        // 2. Crear el Team (Compañía)
        $team = Team::firstOrCreate(
            ['name' => $companyData['name']],
            ['user_id' => $coordinator->id, 'personal_team' => false]
        );
        
        // Aseguramos que el coordinador vea este equipo
        $coordinator->forceFill(['current_team_id' => $team->id])->save();

        // 3. Crear las Regiones y guardarlas en un Mapa para búsqueda rápida
        $regionsMap = [];
        foreach ($companyData['regions'] as $regionName) {
            $regionsMap[$regionName] = Region::firstOrCreate(
                ['team_id' => $team->id, 'name' => $regionName]
            );
        }

        // 4. Procesar Ingenieros
        $this->seedEngineers($team, $regionsMap, $companyData['engineers']);

        $this->command->info("--- Fin Compañía {$companyData['name']} ---\n");
    }

    private function seedEngineers(Team $team, array $regionsMap, array $engineersData)
    {
        foreach ($engineersData as $engineerData) {
            // Crear Usuario Ingeniero
            $engineer = User::firstOrCreate(
                ['email' => $this->generateEmail($engineerData['name'])],
                ['name' => $engineerData['name'], 'password' => Hash::make('password')]
            );
            
            Profile::firstOrCreate(
                ['user_id' => $engineer->id],
                [
                    'employee_code' => 'ENG-' . strtoupper(Str::random(5)),
                    'phone1' => '555-' . rand(1000, 9999),
                    'status' => 'active'
                ]
            );

            // Agregarlo al Team como miembro si no está
            if (!$team->hasUser($engineer)) {
                $team->users()->attach($engineer, ['role' => 'member']);
                // OJO: Si el ingeniero pertenece a varios equipos, esto setea el último como default
                $engineer->forceFill(['current_team_id' => $team->id])->save();
            }

            // --- Asignar Región Primaria ---
            $primaryName = $engineerData['primary'];
            if (isset($regionsMap[$primaryName])) {
                $regionId = $regionsMap[$primaryName]->id;
                $engineer->assignedRegions()->syncWithoutDetaching([
                    $regionId => ['assignment_type' => 'primary']
                ]);
            } else {
                // Alerta visual si escribiste mal el nombre de la región en el array de ingenieros
                $this->command->warn("⚠️  Región primaria no encontrada: '$primaryName' para {$engineerData['name']} en {$team->name}");
            }
            
            // --- Asignar Región de Soporte ---
            // Solo si existe la key 'support', tiene valor, existe en el mapa y es distinta a la primaria
            if (!empty($engineerData['support'])) {
                $supportName = $engineerData['support'];
                
                if ($supportName !== $primaryName) {
                    if (isset($regionsMap[$supportName])) {
                        $regionId = $regionsMap[$supportName]->id;
                        $engineer->assignedRegions()->syncWithoutDetaching([
                            $regionId => ['assignment_type' => 'support']
                        ]);
                    } else {
                        $this->command->warn("⚠️  Región soporte no encontrada: '$supportName' para {$engineerData['name']} en {$team->name}");
                    }
                }
            }
        }
    }

    private function generateEmail(string $name): string
    {
        // Limpieza básica de acentos y caracteres raros para el email
        $cleanName = Str::ascii($name); 
        return strtolower(str_replace(' ', '.', $cleanName)) . '@example.com';
    }

    // ==================== DATOS ====================
    // NOTA: Asegúrate que los nombres en 'primary' y 'support' coincidan EXACTAMENTE 
    // con los definidos en los arrays de regiones.

    // --- CENTRO ---
    private function getCentroRegions(): array
    {
        return ['BAJIO', 'BAJIO 2', 'CENTRO', 'MICHOACAN', 'METRO NORTE', 'NUEVO ECATEPEC', 
                'NORTE', 'METRO SUR', 'NEZA', 'TOLUCA', 'VALLE'];
    }

    private function getCentroEngineers(): array
    {
        return [
            ['name' => 'VICTOR OLAVIDE', 'primary' => 'BAJIO 2', 'support' => 'BAJIO 2'],
            ['name' => 'VICENTE CORDOBA', 'primary' => 'BAJIO', 'support' => 'BAJIO'],
            ['name' => 'HORACIO', 'primary' => 'MICHOACAN', 'support' => 'MICHOACAN'],
            ['name' => 'ABRAHAM', 'primary' => 'METRO NORTE', 'support' => 'METRO NORTE'],
            ['name' => 'CRISTIAN URIEL', 'primary' => 'NORTE', 'support' => 'NORTE'],
            ['name' => 'JOSUE', 'primary' => 'CENTRO', 'support' => 'CENTRO'],
            ['name' => 'JUAN CARLOS', 'primary' => 'TOLUCA', 'support' => 'TOLUCA'],
            ['name' => 'KEVIN ABOYTES', 'primary' => 'METRO SUR', 'support' => 'METRO SUR'],
            ['name' => 'JUAN EDUARDO', 'primary' => 'NEZA', 'support' => 'NEZA'],
            // OJO: Aqui corregí 'ORIENTE' porque no existía como región, puse null en support para evitar error
            // Si realmente existe la región "ORIENTE" agrégala en getCentroRegions()
            ['name' => 'RICARDO PACHECO', 'primary' => 'VALLE', 'support' => null], 
        ];
    }

    // --- ORIENTE ---
    private function getOrienteRegions(): array
    {
        return ['VALLE', 'NEZA', 'PUEBLA'];
    }

    private function getOrienteEngineers(): array
    {
        return [
            // Ricardo aparece en dos empresas (CENTRO y ORIENTE). Esto es válido, tendrá dos equipos.
            ['name' => 'RICARDO PACHECO', 'primary' => 'VALLE', 'support' => null],
            ['name' => 'JUAN EDUARDO', 'primary' => 'NEZA', 'support' => 'NEZA'],
            ['name' => 'GERARDO DIAZ', 'primary' => 'PUEBLA', 'support' => 'PUEBLA'],
        ];
    }

    // --- PONIENTE ---
    private function getPonienteRegions(): array
    {
        return ['ACAPULCO MONTAÑA', 'ACAPULCO ORIENTE', 'ACAPULCO PONIENTE', 
                'MORELOS', 'OAXACA', 'OAXACA COSTA'];
    }

    private function getPonienteEngineers(): array
    {
        return [
            ['name' => 'ANGEL OLIVARES', 'primary' => 'ACAPULCO MONTAÑA', 'support' => 'ACAPULCO MONTAÑA'],
            ['name' => 'LENIS ESPINOZA', 'primary' => 'ACAPULCO ORIENTE', 'support' => 'ACAPULCO ORIENTE'],
            ['name' => 'IVAN CUEVAS', 'primary' => 'ACAPULCO PONIENTE', 'support' => 'ACAPULCO PONIENTE'],
            ['name' => 'ENRIQUE DE JESUS', 'primary' => 'MORELOS', 'support' => 'MORELOS'],
            ['name' => 'FRANCISCO ALONSO', 'primary' => 'OAXACA', 'support' => 'OAXACA'],
            ['name' => 'ERICK REYES', 'primary' => 'OAXACA COSTA', 'support' => 'OAXACA COSTA'],
        ];
    }

    // --- SURESTE ---
    private function getSuresteRegions(): array
    {
        return ['CHIAPAS', 'COATZA-CHIAPAS', 'TABASCO', 'YUCATAN'];
    }

    private function getSuresteEngineers(): array
    {
        return [
            ['name' => 'JUAN ANDRES', 'primary' => 'CHIAPAS', 'support' => 'CHIAPAS'],
            ['name' => 'OMAR SANCHEZ', 'primary' => 'COATZA-CHIAPAS', 'support' => 'COATZA-CHIAPAS'],
            ['name' => 'LUIS ENRIQUE', 'primary' => 'TABASCO', 'support' => 'TABASCO'],
            ['name' => 'VACANTE', 'primary' => 'YUCATAN', 'support' => 'YUCATAN'],
        ];
    }

    // --- VERACRUZ ---
    private function getVeracruzRegions(): array
    {
        return ['VERACRUZ CENTRO', 'VERACRUZ NORTE', 'VERACRUZ SUR'];
    }

    private function getVeracruzEngineers(): array
    {
        return [
            ['name' => 'RAFAEL FLORES MARTIN DEL CAMPO', 'primary' => 'VERACRUZ CENTRO', 'support' => 'VERACRUZ CENTRO'],
            ['name' => 'OMAR HAZAEL OCAMPO OSORIO', 'primary' => 'VERACRUZ CENTRO', 'support' => 'VERACRUZ CENTRO'],
            ['name' => 'CRISTIAN CRUZ', 'primary' => 'VERACRUZ NORTE', 'support' => 'VERACRUZ NORTE'],
            ['name' => 'NEFTALI OCAMPO OSORIO', 'primary' => 'VERACRUZ SUR', 'support' => 'VERACRUZ SUR'],
        ];
    }
}