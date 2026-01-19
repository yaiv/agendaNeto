<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Region;
use Illuminate\Support\Facades\Auth;

class StoreBranchRequest extends FormRequest
{
    /**
     * Autorización: ¿Quién puede crear sucursales?
     * Nivel 1 (Global) y Nivel 2 (Coordinadores/Dueños).
     */
    public function authorize(): bool
    {
        // Puedes refinar esto con un Gate, pero por ahora:
        // Si es Admin Global O es el Dueño del equipo actual -> Pasa.
        $user = $this->user();
        return $user->is_global_admin || $user->id === $user->currentTeam->user_id;
    }

    /**
     * Reglas de validación alineadas a tu Excel y BD.
     */
    public function rules(): array
    {
        $user = $this->user();
        
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'zone_name' => ['nullable', 'string', 'max:100'],
            'external_id_ceco' => ['nullable', 'string', 'max:50'],
            
            // Coordenadas (Validación numérica estricta)
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // VALIDACIÓN DE SEGURIDAD 1: La Región (Mejorada con lógica Cross-Company)
            'region_id' => [
                'required',
                'integer',
                // Validación Cross-Company Inteligente
                function ($attribute, $value, $fail) use ($user) {
                    $region = Region::find($value);
                    
                    if (!$region) {
                        $fail('La región seleccionada no existe.');
                        return;
                    }

                    // SI NO ES ADMIN GLOBAL, aplicamos el bloqueo de equipo
                    $isGlobal = $user->is_global_admin || in_array($user->global_role, ['gerente', 'supervisor', 'admin']);
                    
                    if (!$isGlobal && $region->team_id !== $user->current_team_id) {
                        $fail('No tienes permiso para crear sucursales en esta compañía.');
                    }
                },
            ],

            // VALIDACIÓN DE SEGURIDAD 2: El ECO (Mejorada para Admins Cross-Company)
            'external_id_eco' => [
                'required',
                'string',
                'max:50',
                // Unique compuesto mejorado: Para Admins, valida contra la compañía de destino
                Rule::unique('branches')->where(function ($query) use ($user) {
                    // Si es Admin, el ECO debe ser único DENTRO DE LA COMPAÑÍA DE LA REGIÓN SELECCIONADA
                    // (No de la compañía actual del Admin)
                    $targetRegion = Region::find($this->region_id);
                    $targetTeamId = $targetRegion ? $targetRegion->team_id : $user->current_team_id;
                    
                    return $query->where('team_id', $targetTeamId);
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'external_id_eco.unique' => 'Ya existe una sucursal con este ECO en la compañía seleccionada.',
            'region_id.required' => 'Debes asignar una región operativa.',
        ];
    }
}