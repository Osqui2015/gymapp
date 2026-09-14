<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorialUltimoTest extends TestCase
{
    use RefreshDatabase;

    private function makeSerie(User $user, array $overrides = []): Historial
    {
        return Historial::create(array_merge([
            'user_id' => $user->id,
            'rutina_nombre' => 'Test',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press Banca',
            'series_numero' => 1,
            'series_completadas' => 1,
            'reps_min' => '8',
            'reps_max' => '10',
            'reps_realizadas' => 10,
            'descanso_min' => 2,
            'peso' => 80,
            'completado' => true,
            'fecha' => now()->toDateString(),
        ], $overrides));
    }

    public function test_unauthenticated_user_cannot_access_ultimo(): void
    {
        $response = $this->getJson('/api/historial/ultimo?ejercicio=Press%20Banca');
        $response->assertStatus(401);
    }

    public function test_returns_not_found_when_user_has_no_history(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        $response = $this->actingAs($user)
            ->getJson('/api/historial/ultimo?ejercicio=Press%20Banca');

        $response->assertOk()
            ->assertJson([
                'encontrado' => false,
                'ejercicio' => 'Press Banca',
            ]);
    }

    public function test_validation_requires_ejercicio(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        $response = $this->actingAs($user)
            ->getJson('/api/historial/ultimo');

        $response->assertStatus(422);
    }

    public function test_returns_last_session_for_user_with_top_set(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $hace10dias = now()->subDays(10)->toDateString();

        // Sesión vieja: 50kg x 8
        $this->makeSerie($user, [
            'fecha' => $hace10dias,
            'series_numero' => 1,
            'peso' => 50,
            'reps_realizadas' => 8,
        ]);

        // Sesión reciente: 60kg x 6 (debe ser la que devuelva)
        $this->makeSerie($user, [
            'fecha' => now()->toDateString(),
            'series_numero' => 1,
            'peso' => 60,
            'reps_realizadas' => 8,
            'tipo_serie' => 'calentamiento',
        ]);
        $this->makeSerie($user, [
            'fecha' => now()->toDateString(),
            'series_numero' => 2,
            'peso' => 60,
            'reps_realizadas' => 6,
            'esfuerzo_tipo' => 'rir',
            'esfuerzo_valor' => 2,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/historial/ultimo?ejercicio=Press%20Banca');

        $response->assertOk()
            ->assertJson([
                'encontrado' => true,
                'ejercicio' => 'Press Banca',
                'peso_top' => 60.0,
                'reps_en_peso_top' => 6,
                'ultimo_esfuerzo' => ['tipo' => 'rir', 'valor' => 2],
            ])
            ->assertJsonStructure([
                'encontrado',
                'ejercicio',
                'fecha',
                'series' => [
                    '*' => ['peso', 'reps', 'tipo_serie', 'esfuerzo_tipo', 'esfuerzo_valor'],
                ],
                'peso_top',
                'reps_en_peso_top',
                'ultimo_esfuerzo',
            ]);
    }

    public function test_ignores_warmup_sets_when_computing_top_weight(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $fecha = now()->subDays(2)->toDateString();

        // Calentamiento de 40kg x 10 (NO debe ser el peso top)
        $this->makeSerie($user, [
            'fecha' => $fecha,
            'series_numero' => 1,
            'peso' => 40,
            'reps_realizadas' => 10,
            'tipo_serie' => 'calentamiento',
        ]);
        // Efectiva 60kg x 6 (debe ser el peso top)
        $this->makeSerie($user, [
            'fecha' => $fecha,
            'series_numero' => 2,
            'peso' => 60,
            'reps_realizadas' => 6,
            'tipo_serie' => 'efectiva',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/historial/ultimo?ejercicio=Press%20Banca');

        $response->assertOk()
            ->assertJson([
                'peso_top' => 60.0,
                'reps_en_peso_top' => 6,
            ]);
    }

    public function test_returns_only_last_session_not_history(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        // Sesión de hace 1 mes: 80kg (no debe aparecer)
        $this->makeSerie($user, [
            'fecha' => now()->subDays(30)->toDateString(),
            'series_numero' => 1,
            'peso' => 80,
            'reps_realizadas' => 5,
        ]);
        // Sesión de hoy: 60kg (debe ser la devuelta)
        $this->makeSerie($user, [
            'fecha' => now()->toDateString(),
            'series_numero' => 1,
            'peso' => 60,
            'reps_realizadas' => 7,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/historial/ultimo?ejercicio=Press%20Banca');

        $response->assertOk()
            ->assertJson([
                'peso_top' => 60.0,
                'reps_en_peso_top' => 7,
            ]);

        $this->assertCount(1, $response->json('series'));
    }

    public function test_user_cannot_see_other_user_history(): void
    {
        $user1 = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $user2 = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        $this->makeSerie($user2, [
            'peso' => 999,
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/api/historial/ultimo?ejercicio=Press%20Banca');

        $response->assertOk()
            ->assertJson(['encontrado' => false]);
    }
}
