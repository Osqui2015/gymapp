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
        $this->assertEquals(1, $data['distribucion']['rir'][1]['count']);
        $this->assertEquals(1, $data['distribucion']['rir'][2]['count']);
        $this->assertEquals(1, $data['distribucion']['rir'][3]['count']);
        $this->assertEquals(9, $data['distribucion']['rpe'][3]['valor']);
        $this->assertEquals(1, $data['distribucion']['rpe'][3]['count']);
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

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo?window=30');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['sets_with_esfuerzo']);
    }

    public function test_stats_esfuerzo_window_90_incluye_historico_mas_viejo(): void
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
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 2,
        ]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo?window=90');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['sets_with_esfuerzo']);
    }

    public function test_stats_esfuerzo_window_365_incluye_historico_muy_viejo(): void
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
            'fecha' => now()->subDays(200)->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 3,
        ]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo?window=365');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['sets_with_esfuerzo']);
    }

    public function test_stats_esfuerzo_window_all_devuelve_todo_el_historico(): void
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
            'fecha' => now()->subYears(3)->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 2,
        ]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo?window=all');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['sets_with_esfuerzo']);
    }

    public function test_stats_esfuerzo_window_invalido_cae_a_30(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo?window=invalido');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('30', $data['window']['key']);
        $this->assertEquals('30 días', $data['window']['label']);
    }

    public function test_stats_esfuerzo_devuelve_window_info(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo?window=90');
        $data = $response->json();
        $this->assertEquals('90', $data['window']['key']);
        $this->assertEquals(90, $data['window']['days']);
        $this->assertEquals('90 días', $data['window']['label']);
    }

    public function test_stats_esfuerzo_calcula_avg_hard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        // 3 sets hard (RIR <= 2) y 1 easy (RIR 4)
        for ($i = 0; $i < 3; $i++) {
            Historial::create([
                'user_id' => $user->id,
                'rutina_nombre' => 'R',
                'dia' => 'Día 1',
                'ejercicio_nombre' => 'Press',
                'series_numero' => $i + 1,
                'reps_min' => '8', 'reps_max' => '12',
                'descanso_min' => 2, 'peso' => 60, 'completado' => true,
                'fecha' => now()->toDateString(),
                'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 1,
            ]);
        }
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'R',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press',
            'series_numero' => 4,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => 60, 'completado' => true,
            'fecha' => now()->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 4,
        ]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo?window=30');
        $data = $response->json();
        $this->assertEquals(75, $data['avg_hard']);  // 3/4 = 75%
    }

    public function test_stats_esfuerzo_calcula_avg_hard_con_rpe(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        // 2 sets hard (RPE >= 8) y 1 easy (RPE 6)
        foreach ([[8, 1], [9, 2], [6, 3]] as [$val, $num]) {
            Historial::create([
                'user_id' => $user->id,
                'rutina_nombre' => 'R',
                'dia' => 'Día 1',
                'ejercicio_nombre' => 'Press',
                'series_numero' => $num,
                'reps_min' => '8', 'reps_max' => '12',
                'descanso_min' => 2, 'peso' => 60, 'completado' => true,
                'fecha' => now()->toDateString(),
                'esfuerzo_tipo' => 'rpe', 'esfuerzo_valor' => $val,
            ]);
        }

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo?window=30');
        $data = $response->json();
        $this->assertEquals(67, $data['avg_hard']);  // 2/3 = 67%
    }

    public function test_stats_esfuerzo_tendencia_agrupa_por_semana(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        // Forzar 2 sets esta semana (lun-mié) y 1 set la semana pasada (hace 10 días, debería ser dom-lun anterior)
        $hoy = now();
        $esta_semana_lun = $hoy->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $semana_pasada = $esta_semana_lun->copy()->subDays(10);

        Historial::create([
            'user_id' => $user->id, 'rutina_nombre' => 'R', 'dia' => 'D',
            'ejercicio_nombre' => 'Press', 'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 2,
            'peso' => 60, 'completado' => true,
            'fecha' => $esta_semana_lun->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 2,
        ]);
        Historial::create([
            'user_id' => $user->id, 'rutina_nombre' => 'R', 'dia' => 'D',
            'ejercicio_nombre' => 'Press', 'series_numero' => 2,
            'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 2,
            'peso' => 60, 'completado' => true,
            'fecha' => $esta_semana_lun->copy()->addDays(2)->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 3,
        ]);
        Historial::create([
            'user_id' => $user->id, 'rutina_nombre' => 'R', 'dia' => 'D',
            'ejercicio_nombre' => 'Press', 'series_numero' => 3,
            'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 2,
            'peso' => 60, 'completado' => true,
            'fecha' => $semana_pasada->toDateString(),
            'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => 4,
        ]);

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo?window=90');
        $data = $response->json();
        fwrite(STDERR, "\n[DEBUG] now=" . $hoy . " lun_esta=" . $esta_semana_lun . " semana_pasada=" . $semana_pasada . " tendencia=" . json_encode($data['tendencia']) . "\n");
        $this->assertCount(2, $data['tendencia']);
    }

    public function test_stats_esfuerzo_tendencia_orden_cronologico(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $lun = now()->startOfWeek(\Carbon\Carbon::MONDAY);
        // 2 sets en la misma semana
        foreach ([[$lun, 2], [$lun->copy()->addDays(2), 4]] as [$fecha, $val]) {
            Historial::create([
                'user_id' => $user->id, 'rutina_nombre' => 'R', 'dia' => 'D',
                'ejercicio_nombre' => 'Press', 'series_numero' => 1,
                'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 2,
                'peso' => 60, 'completado' => true,
                'fecha' => $fecha->toDateString(),
                'esfuerzo_tipo' => 'rir', 'esfuerzo_valor' => $val,
            ]);
        }

        $response = $this->actingAs($user)->getJson('/api/stats/esfuerzo?window=30');
        $data = $response->json();
        // Misma semana → 1 bucket
        $this->assertCount(1, $data['tendencia']);
        $this->assertEquals(2, $data['tendencia'][0]['sets']);
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
