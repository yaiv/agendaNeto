<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado.
     * Aquí verificamos que sea Nivel 1 (Global Admin).
     */
    public function authorize(): bool
    {
        // Asumiendo que usas un Gate o verificas el rol global
        // Ajusta 'manage-structure' a tu Gate real o lógica de rol
        return $this->user()->tokenCan('create') || $this->user()->is_global_admin; 
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            // Validación de la Compañía (Team)
            'name' => ['required', 'string', 'max:255'],
            
            // Validación del Dueño Inicial (Opcional: si permites asignar coordinador desde el inicio)
            'owner_id' => ['nullable', 'exists:users,id'],

            // Validación de Regiones (Array)
            // 'sometimes' permite que envíen la compañía sin regiones si así lo decides
            'regions' => ['sometimes', 'array', 'min:1'],
            
            // Validación de cada Región individual
            'regions.*.name' => [
                'required', 
                'string', 
                'max:255',
                // TRUCO PRO: 'distinct' evita que el usuario envíe 
                // "Norte" dos veces en el mismo formulario, 
                // protegiendo tu Unique Constraint de DB antes de intentar insertar.
                'distinct' 
            ],
            
            'regions.*.code' => ['nullable', 'string', 'max:10', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'regions.*.name.distinct' => 'No puedes crear dos regiones con el mismo nombre en la misma carga.',
            'regions.*.name.required' => 'El nombre de la región es obligatorio.',
        ];
    }
}