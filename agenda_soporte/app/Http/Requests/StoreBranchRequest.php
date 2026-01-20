<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Region;
// use Illuminate\Support\Facades\Auth; // No es necesario, usamos $this->user()

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        // CORRECCIÓN 1: Consistencia con Nivel 1 (Supervisores/Gerentes)
        // Permitimos si es Admin Global, si tiene rol global de alto nivel, 
        // o si es el Dueño del equipo actual.
        $isGlobal = $user->is_global_admin || in_array($user->global_role, ['gerente', 'supervisor', 'admin']);
        
        return $isGlobal || $user->id === $user->currentTeam->user_id;
    }

    public function rules(): array
    {
        $user = $this->user();
        
        // Detectamos si estamos en modo Edición obteniendo el parámetro de la ruta
        // Asumiendo que tu ruta es regions/{region}/branches/{branch} o similar, 
        // o resource 'branches'. Laravel suele llamar al parámetro 'branch'.
        $currentBranch = $this->route('branch'); 

        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'zone_name' => ['nullable', 'string', 'max:100'],
            'external_id_ceco' => ['nullable', 'string', 'max:50'],
            
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'region_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($user) {
                    $region = Region::find($value);
                    
                    if (!$region) {
                        $fail('La región seleccionada no existe.');
                        return;
                    }

                    $isGlobal = $user->is_global_admin || in_array($user->global_role, ['gerente', 'supervisor', 'admin']);
                    
                    if (!$isGlobal && $region->team_id !== $user->current_team_id) {
                        $fail('No tienes permiso para asignar esta compañía.');
                    }
                },
            ],

            'external_id_eco' => [
                'required',
                'string',
                'max:50',
                // CORRECCIÓN 2: Ignorar ID al editar y manejo de nulos
                Rule::unique('branches')
                    ->ignore($currentBranch) // Si estamos editando, ignora este ID
                    ->where(function ($query) use ($user) {
                        
                        // CORRECCIÓN 3: Prevención de Crash si region_id es inválido
                        if (!$this->region_id) {
                             return $query->whereRaw('1 = 0'); // Falla silenciosa segura
                        }

                        $targetRegion = Region::find($this->region_id);
                        
                        // Si la región no existe (manipulación de DOM), usamos el team actual por defecto
                        // para que falle la validación principal de region_id, no esta.
                        $targetTeamId = $targetRegion ? $targetRegion->team_id : $user->current_team_id;
                        
                        return $query->where('team_id', $targetTeamId);
                    }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'external_id_eco.unique' => 'Ya existe una sucursal con este ECO en la compañía destino.',
            'region_id.required' => 'Debes asignar una región operativa.',
        ];
    }
}