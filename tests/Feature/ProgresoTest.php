<?php

namespace Tests\Feature;

use App\Models\Progreso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgresoTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_progresos(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        Progreso::create([
            'user_id' => $user->id,
            'fecha' => now()->toDateString(),
            'peso' => 75.5,
            'altura' => 1.75,
            'edad' => 30,
            'sexo' => 'masculino',
        ]);

        $response = $this->actingAs($user)->getJson('/api/progreso');
        $response->assertStatus(200)
            ->assertJsonStructure(['progresos', 'ultimo', 'puede_registrar']);
    }

    public function test_user_can_save_first_progreso_with_required_fields(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->postJson('/api/progreso', [
            'peso' => 75.5,
            'altura' => 1.75,
            'edad' => 30,
            'sexo' => 'masculino',
            'pecho' => 95,
            'cintura' => 80,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('progresos', [
            'user_id' => $user->id,
            'peso' => 75.5,
        ]);
    }

    public function test_user_cannot_save_progreso_without_required_initial_data(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->postJson('/api/progreso', [
            'pecho' => 95, // sin peso/altura/edad/sexo
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['peso', 'altura', 'edad', 'sexo']);
    }

    public function test_progreso_validates_numeric_ranges(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->postJson('/api/progreso', [
            'peso' => 75,
            'altura' => 1.75,
            'edad' => 30,
            'sexo' => 'masculino',
            'cintura' => 5, // fuera de rango (min:30)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cintura']);
    }

    public function test_user_cannot_register_progreso_within_15_days(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        Progreso::create([
            'user_id' => $user->id,
            'fecha' => now()->subDays(3)->toDateString(),
            'peso' => 75,
            'altura' => 1.75,
            'edad' => 30,
            'sexo' => 'masculino',
        ]);

        $response = $this->actingAs($user)->getJson('/api/progreso');
        $response->assertStatus(200)
            ->assertJsonPath('puede_registrar', false);
    }

    public function test_unauthenticated_cannot_access_progreso(): void
    {
        $response = $this->getJson('/api/progreso');
        $response->assertStatus(401);
    }
}
