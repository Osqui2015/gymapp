<?php

namespace Database\Factories;

use App\Models\Membresia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nick' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_COMUN,
            'trainer_id' => null,
            'altura' => null,
            'peso_objetivo' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * El usuario tiene una membresía activa (Fase 0.6).
     *
     * Desde la Fase 0.6 la regla de acceso exige membresía o trial para
     * entrar al área de entrenamiento. Los tests que esperan un 200 en
     * `/dashboard` o `/profile` tienen que crear el usuario CON membresía:
     * sin ella el middleware redirige a la pantalla de renovación (302),
     * que es exactamente el comportamiento nuevo que se quiere verificar.
     *
     *   $user = User::factory()->conMembresia()->create();
     */
    public function conMembresia(): static
    {
        return $this->afterCreating(function (User $user) {
            Membresia::factory()->create(['user_id' => $user->id]);
        });
    }

    /**
     * El usuario tiene una membresía de cortesía (sin vencimiento).
     */
    public function conCortesia(): static
    {
        return $this->afterCreating(function (User $user) {
            Membresia::factory()->cortesia()->create(['user_id' => $user->id]);
        });
    }

    /**
     * El usuario tiene un trial en curso.
     */
    public function conTrial(int $dias = 14): static
    {
        // `use ($dias)` es obligatorio: el closure de `afterCreating` se
        // ejecuta DESPUÉS de que el método retorne, así que sin la
        // captura explícita la variable no existe en ese scope.
        return $this->afterCreating(function (User $user) use ($dias) {
            Membresia::factory()->trial($dias)->create(['user_id' => $user->id]);
        });
    }

    /**
     * El usuario tiene una membresía vencida (fuera del período de gracia).
     */
    public function conMembresiaVencida(): static
    {
        return $this->afterCreating(function (User $user) {
            Membresia::factory()->vencida()->create(['user_id' => $user->id]);
        });
    }
}
