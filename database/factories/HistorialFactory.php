<?php

namespace Database\Factories;

use App\Models\Historial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Historial>
 */
class HistorialFactory extends Factory
{
    protected $model = Historial::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fecha' => Carbon::now()->toDateString(),
            'rutina_nombre' => fake()->randomElement(['Push Intermedio', 'Pull Avanzado', 'Legs Principiante']),
            'dia' => 'Día ' . fake()->numberBetween(1, 5),
            'ejercicio_nombre' => ucfirst(fake()->words(2, true)),
            'series_numero' => fake()->numberBetween(1, 5),
            'series_completadas' => fake()->numberBetween(0, 5),
            'reps_min' => '8',
            'reps_max' => '12',
            'reps_realizadas' => fake()->numberBetween(6, 15),
            'descanso_min' => fake()->randomElement([1, 1.5, 2, 3]),
            'peso' => fake()->randomFloat(1, 5, 100),
            'completado' => fake()->boolean(80),
            'superserie_grupo' => null,
        ];
    }

    public function deHoy(): static
    {
        return $this->state(['fecha' => Carbon::now()->toDateString()]);
    }

    public function deFecha(string $fecha): static
    {
        return $this->state(['fecha' => $fecha]);
    }

    public function completado(): static
    {
        return $this->state(['completado' => true, 'series_completadas' => 3]);
    }
}
