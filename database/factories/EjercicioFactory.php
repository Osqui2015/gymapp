<?php

namespace Database\Factories;

use App\Models\Ejercicio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ejercicio>
 */
class EjercicioFactory extends Factory
{
    protected $model = Ejercicio::class;

    public function definition(): array
    {
        $nombre = ucfirst(fake()->unique()->words(2, true));
        return [
            'nombre' => $nombre,
            'equipamiento' => fake()->randomElement(['Mancuerna', 'Barra', 'Máquina', 'Peso corporal', 'Polea']),
            'grupo_muscular' => fake()->randomElement(['Pecho', 'Espalda', 'Pierna', 'Hombro', 'Bíceps', 'Tríceps', 'Abdomen']),
            'url_img' => null,
            'url_video' => 'https://www.youtube.com/watch?v=' . Str::random(11),
            'visibilidad' => true,
            'descripcion' => fake()->sentence(),
        ];
    }
}
