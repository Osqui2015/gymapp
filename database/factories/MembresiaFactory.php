<?php

namespace Database\Factories;

use App\Models\Membresia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membresia>
 */
class MembresiaFactory extends Factory
{
    protected $model = Membresia::class;

    public function definition(): array
    {
        $inicio = now()->subDays(5);

        return [
            'user_id' => User::factory(),
            'tipo_plan' => 'mensual',
            'precio' => 25000,
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_vencimiento' => $inicio->copy()->addMonth()->toDateString(),
            'estado' => 'activo',
            'ultimo_pago' => $inicio->toDateString(),
            'metodo_pago' => 'efectivo',
            'notas' => null,
            'es_trial' => false,
            'trial_ends_at' => null,
        ];
    }

    /**
     * Membresía vencida (fuera del período de gracia).
     */
    public function vencida(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_inicio' => now()->subMonths(2)->toDateString(),
            'fecha_vencimiento' => now()->subMonth()->toDateString(),
            'estado' => 'vencido',
        ]);
    }

    /**
     * Membresía de cortesía (sin vencimiento).
     */
    public function cortesia(): static
    {
        return $this->state(fn (array $attributes) => [
            'precio' => 0,
            'fecha_vencimiento' => null,
            'estado' => 'activo',
            'ultimo_pago' => null,
            'metodo_pago' => 'cortesia',
        ]);
    }

    /**
     * Trial en curso (Fase 0.5).
     */
    public function trial(int $dias = 14): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_inicio' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays($dias)->toDateString(),
            'estado' => 'activo',
            'es_trial' => true,
            'trial_ends_at' => now()->addDays($dias)->toDateString(),
        ]);
    }
}
