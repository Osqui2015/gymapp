<?php

namespace App\Http\Requests;

use App\Models\Membresia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembresiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membresia = $this->route('membresia');

        return $membresia instanceof Membresia && ($this->user()?->can('update', $membresia) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo_plan' => 'sometimes|in:mensual,trimestral,semestral,anual',
            'precio' => 'sometimes|numeric|min:0',
            'fecha_inicio' => 'sometimes|date',
            'fecha_vencimiento' => [
                'sometimes',
                'date',
                Rule::when(
                    $this->filled('fecha_inicio'),
                    'after:fecha_inicio'
                ),
            ],
            'estado' => 'sometimes|in:activo,por_vencer,vencido,cancelado',
            'metodo_pago' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:500',
        ];
    }
}
