<?php

namespace Database\Factories;

use App\Models\Membresia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membresia>
 */
class MembresiaFactory extends Factory
{
    protected $model = Membresia::class;

    public function definition(): array
    {
        $inicio = Carbon::now()->subDays(fake()->numberBetween(1, 30));
        $vencimiento = $inicio->copy()->addDays(30);
        return [
            'user_id' => User::factory(),
            'tipo_plan' => 'mensual',
            'precio' => fake()->randomFloat(2, 500, 3000),
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_vencimiento' => $vencimiento->toDateString(),
            'estado' => 'activo',
            'ultimo_pago' => $inicio->toDateString(),
            'metodo_pago' => fake()->randomElement(['efectivo', 'transferencia', 'mercadopago']),
            'notas' => null,
        ];
    }

    public function vencida(): static
    {
        return $this->state([
            'estado' => 'vencido',
            'fecha_vencimiento' => Carbon::now()->subDays(5)->toDateString(),
        ]);
    }

    public function porVencer(int $dias = 3): static
    {
        return $this->state([
            'estado' => 'por_vencer',
            'fecha_vencimiento' => Carbon::now()->addDays($dias)->toDateString(),
        ]);
    }
}
