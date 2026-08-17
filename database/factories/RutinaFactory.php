<?php

namespace Database\Factories;

use App\Models\Rutina;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rutina>
 */
class RutinaFactory extends Factory
{
    protected $model = Rutina::class;

    public function definition(): array
    {
        return [
            'nivel' => fake()->randomElement(['Principiante', 'Intermedio', 'Avanzado']),
            'modalidad' => fake()->randomElement(['Push', 'Pull', 'Legs', 'Upper', 'Lower', 'Full body']),
            'dia' => 'Día ' . fake()->numberBetween(1, 5),
            'created_by' => null, // rutina default
            'series' => fake()->numberBetween(3, 5),
            'reps_min' => '8',
            'reps_max' => '12',
            'descanso_min' => fake()->randomFloat(2, 1, 3),
            'ejercicio_nombre' => ucfirst(fake()->unique()->words(2, true)),
            'orden' => fake()->numberBetween(1, 20),
            'superserie_grupo' => null,
        ];
    }

    /**
     * Rutina creada por un user específico (trainer/admin).
     */
    public function createdBy(User $user): static
    {
        return $this->state(['created_by' => $user->id]);
    }

    /**
     * Rutina default (created_by = null).
     */
    public function default(): static
    {
        return $this->state(['created_by' => null]);
    }
}
