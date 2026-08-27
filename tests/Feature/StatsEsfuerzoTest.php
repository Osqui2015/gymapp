<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Fase 3 — RIR/RPE tracking: tests del endpoint de stats y del guardado
 * del esfuerzo en sets individuales.
 */
class StatsEsfuerzoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_historial_guardar_acepta_esfuerzo_rir(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->postJson('/api/historial/guardar', [
            'rutina_nombre' => 'Test',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 1,
            'reps_min' => '8',
            'reps_max' => '12',
            'descanso_min' => 2,
            'peso' => 60,
            'completado' => true,
            'esfuerzo_tipo' => 'rir',
            'esfuerzo_valor' => 2,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('historials', [
            'user_id' => $user->id,
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 1,
            'esfuerzo_tipo' => 'rir',
            'esfuerzo_valor' => 2,
        ]);
    }

    public function test_historial_guardar_acepta_esfuerzo_rpe(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->postJson('/api/historial/guardar', [
            'rutina_nombre' => 'Test',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Sentadilla',
            'series_numero' => 1,
            'reps_min' => '5',
            'reps_max' => '8',
            'descanso_min' => 3,
            'esfuerzo_tipo' => 'rpe',
            'esfuerzo_valor' => 8,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('historials', [
            'user_id' => $user->id,
            'esfuerzo_tipo' => 'rpe',
            'esfuerzo_valor' => 8,
        ]);
    }

    public function test_historial_guardar_rechaza_esfuerzo_invalido(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->postJson('/api/historial/guardar', [
            'rutina_nombre' => 'Test',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Curl',
            'series_numero' => 1,
            'reps_min' => '10',
            'reps_max' => '12',
            'descanso_min' => 1,
            'esfuerzo_tipo' => 'foo', // inválido
            'esfuerzo_valor' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['esfuerzo_tipo']);
    }

    public function test_historial_guardar_rechaza_valor_fuera_de_rango(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->postJson('/api/historial/guardar', [
            'rutina_nombre' => 'Test',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Curl',
            'series_numero' => 1,
            'reps_min' => '10',
            'reps_max' => '12',
            'descanso_min' => 1,
            'esfuerzo_tipo' => 'rir',
            'esfuerzo_valor' => 11, // fuera de rango (0..5 RIR, 6..10 RPE)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['esfuerzo_valor']);
    }

    public function test_historial_guardar_sin_esfuerzo_sigue_funcionando(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->postJson('/api/historial/guardar', [
            'rutina_nombre' => 'Test',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press militar',
            'series_numero' => 1,
            'reps_min' => '8',
            'reps_max' => '10',
            'descanso_min' => 2,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('historials', [
            'user_id' => $user->id,
            'ejercicio_nombre' => 'Press militar',
            'esfuerzo_tipo' => null,
            'esfuerzo_valor' => null,
        ]);
    }

    public function test_stats_esfuerzo_sin_data_devuelve_ceros(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo');

        $response->assertStatus(200)
            ->assertJson([
                'total_sets' => 0,
                'sets_with_esfuerzo' => 0,
                'avg_por_tipo' => ['rir' => null, 'rpe' => null],
            ]);
    }

    public function test_stats_esfuerzo_calcula_promedios_y_distribucion(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        // 3 sets con RIR 1, 2, 3 → avg 2
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Rutina A',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => 60, 'completado' => true,
            'fecha' => now()->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 1,
        ]);
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Rutina A',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 2,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => 60, 'completado' => true,
            'fecha' => now()->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 2,
        ]);
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Rutina A',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 3,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => 60, 'completado' => true,
            'fecha' => now()->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 3,
        ]);
        // 1 set con RPE 9
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Rutina A',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Sentadilla',
            'series_numero' => 1,
            'reps_min' => '5', 'reps_max' => '8',
            'descanso_min' => 3, 'peso' => 80, 'completado' => true,
            'fecha' => now()->toDateString(),
            'esfuerzo_tipo' => 'rpe', 'esfuerzo_valor' => 9,
        ]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(4, $data['sets_with_esfuerzo']);
        $this->assertEquals(2, $data['avg_por_tipo']['rir']);
        $this->assertEquals(9, $data['avg_por_tipo']['rpe']);
        $this->assertEquals(1, $data['distribucion']['rir'][1]);
        $this->assertEquals(1, $data['distribucion']['rir'][2]);
        $this->assertEquals(1, $data['distribucion']['rir'][3]);
        $this->assertEquals(1, $data['distribucion']['rpe'][9]);
        $this->assertCount(2, $data['por_ejercicio']);
    }

    public function test_stats_esfuerzo_ignora_sets_sin_esfuerzo(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        // 2 sets sin esfuerzo (no deben contar)
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Rutina A',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => 60, 'completado' => true,
            'fecha' => now()->toDateString(),
        ]);
        // 1 set con RIR 4
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Rutina A',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 2,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => 60, 'completado' => true,
            'fecha' => now()->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 4,
        ]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2, $data['total_sets']); // incluye los sin esfuerzo
        $this->assertEquals(1, $data['sets_with_esfuerzo']); // solo 1
        $this->assertEquals(4, $data['avg_por_tipo']['rir']);
    }

    public function test_stats_esfuerzo_ignora_historial_viejo_mayor_a_30_dias(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Rutina A',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => 60, 'completado' => true,
            'fecha' => now()->subDays(60)->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['sets_with_esfuerzo']);
    }

    public function test_stats_esfuerzo_ignora_sets_no_completados(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Rutina A',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => 60, 'completado' => false,
            'fecha' => now()->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 2,
        ]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['sets_with_esfuerzo']);
    }

    public function test_stats_esfuerzo_requiere_auth(): void
    {
        $response = $this->getJson('/api/stats/esfuerzo');
        $response->assertStatus(401);
    }
}
