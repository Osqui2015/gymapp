<?php

namespace App\Http\Requests;

use App\Models\Membresia;
use Illuminate\Foundation\Http\FormRequest;

class StoreMembresiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Membresia::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'tipo_plan' => 'required|in:mensual,trimestral,semestral,anual',
            'precio' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_vencimiento' => 'required|date|after:fecha_inicio',
            'metodo_pago' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:500',
        ];
    }
}
