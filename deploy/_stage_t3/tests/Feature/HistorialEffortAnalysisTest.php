<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Análisis de esfuerzo (RIR/RPE) del historial — endpoint que alimenta la
 * card "Esfuerzo" (EffortCard.vue).
 *
 * Cubre:
 *   - el contrato que consume el front (summary / distribution / weekly_trend / by_exercise)
 *   - la normalización de RPE y "al fallo" a una escala RIR única
 *   - el filtrado por rango temporal
 *   - la autorización (dueño, trainer con alumno asignado, admin)
 *   - regresión del 405: la ruta debe existir como GET y no caer en /historial/{id}
 */
class HistorialEffortAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private function makeSet($user, string $fecha, ?string $tipo = null, ?int $valor = null, string $ejercicio = 'Press banca', bool $completado = true, int $serie = 1)
    {
        return Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Rutina A',
            'dia' => 'Día 1',
            'ejercicio_nombre' => $ejercicio,
            'series_numero' => $serie,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => 60, 'completado' => $completado,
            'fecha' => $fecha,
            'esfuerzo_tipo' => $tipo,
            'esfuerzo_valor' => $valor,
        ]);
    }

    public function test_requiere_auth(): void
    {
        $response = $this->getJson('/api/historial/effort-analysis');

        $response->assertStatus(401);
    }

    public function test_la_ruta_existe_y_no_devuelve_405(): void
    {
        // Regresión: /historial/effort-analysis caía en el wildcard
        // PUT/DELETE /historial/{id} y respondía 405 Method Not Allowed.
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/historial/effort-analysis');

        $response->assertStatus(200);
    }

    public function test_sin_datos_devuelve_estructura_vacia(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/historial/effort-analysis');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertSame('30d', $data['range']);
        $this->assertSame(0, $data['summary']['total_sets']);
        $this->assertSame(0, $data['summary']['sets_with_effort']);
        $this->assertNull($data['summary']['avg_rir']);
        $this->assertSame(0, $data['summary']['high_effort_pct']);
        $this->assertSame(0, $data['summary']['coverage_pct']);
        $this->assertSame([0, 0, 0, 0, 0], array_values($data['distribution']));
        $this->assertSame([], $data['weekly_trend']);
        $this->assertSame([], $data['by_exercise']);

        // El front accede con distribution['0'], distribution['1'], ...:
        // tiene que serializarse como objeto, no como lista indexada.
        $this->assertStringContainsString(
            '"distribution":{"0":0,"1":0,"2":0,"3":0,"4":0}',
            $response->getContent()
        );
    }

    public function test_calcula_promedio_rir_y_distribucion(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $hoy = now()->toDateString();

        $this->makeSet($user, $hoy, 'rir', 1, 'Press banca', true, 1);
        $this->makeSet($user, $hoy, 'rir', 2, 'Press banca', true, 2);
        $this->makeSet($user, $hoy, 'rir', 3, 'Sentadilla', true, 1);

        $response = $this->actingAs($user)->getJson('/api/historial/effort-analysis?range=30d');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertSame(3, $data['summary']['sets_with_effort']);
        $this->assertEquals(2.0, $data['summary']['avg_rir']);
        // RIR 1 y 2 son "alta intensidad" (RIR <= 2) → 2 de 3
        $this->assertSame(67, $data['summary']['high_effort_pct']);
        $this->assertSame(1, $data['distribution']['1']);
        $this->assertSame(1, $data['distribution']['2']);
        $this->assertSame(1, $data['distribution']['3']);
        $this->assertSame(0, $data['distribution']['0']);
    }

    public function test_normaliza_rpe_a_rir(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $hoy = now()->toDateString();

        // RPE 10 → RIR 0, RPE 8 → RIR 2, RPE 6 → RIR 4 (bucket "4+")
        $this->makeSet($user, $hoy, 'rpe', 10, 'Press banca', true, 1);
        $this->makeSet($user, $hoy, 'rpe', 8, 'Press banca', true, 2);
        $this->makeSet($user, $hoy, 'rpe', 6, 'Sentadilla', true, 1);

        $data = $this->actingAs($user)->getJson('/api/historial/effort-analysis')->json();

        $this->assertSame(3, $data['summary']['sets_with_effort']);
        $this->assertEquals(2.0, $data['summary']['avg_rir']); // (0 + 2 + 4) / 3
        $this->assertSame(1, $data['distribution']['0']);
        $this->assertSame(1, $data['distribution']['2']);
        $this->assertSame(1, $data['distribution']['4']);
        // RIR 0 y 2 son alta intensidad
        $this->assertSame(67, $data['summary']['high_effort_pct']);
    }

    public function test_al_fallo_cuenta_como_rir_cero_sin_valor(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->makeSet($user, now()->toDateString(), 'fallo', null, 'Press banca');

        $data = $this->actingAs($user)->getJson('/api/historial/effort-analysis')->json();

        $this->assertSame(1, $data['summary']['sets_with_effort']);
        $this->assertEquals(0.0, $data['summary']['avg_rir']);
        $this->assertSame(1, $data['distribution']['0']);
        $this->assertSame(100, $data['summary']['high_effort_pct']);
    }

    public function test_cobertura_ignora_sets_sin_esfuerzo_para_el_promedio(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $hoy = now()->toDateString();

        $this->makeSet($user, $hoy, null, null, 'Press banca', true, 1);
        $this->makeSet($user, $hoy, null, null, 'Press banca', true, 2);
        $this->makeSet($user, $hoy, 'rir', 2, 'Press banca', true, 3);

        $data = $this->actingAs($user)->getJson('/api/historial/effort-analysis')->json();

        $this->assertSame(3, $data['summary']['total_sets']);
        $this->assertSame(1, $data['summary']['sets_with_effort']);
        $this->assertSame(33, $data['summary']['coverage_pct']); // 1 de 3
        $this->assertEquals(2.0, $data['summary']['avg_rir']);
    }

    public function test_ignora_sets_no_completados(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->makeSet($user, now()->toDateString(), 'rir', 1, 'Press banca', false);

        $data = $this->actingAs($user)->getJson('/api/historial/effort-analysis')->json();

        $this->assertSame(0, $data['summary']['total_sets']);
        $this->assertSame(0, $data['summary']['sets_with_effort']);
    }

    public function test_rango_30d_excluye_historico_mas_viejo(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->makeSet($user, now()->subDays(60)->toDateString(), 'rir', 1);

        $data = $this->actingAs($user)->getJson('/api/historial/effort-analysis?range=30d')->json();

        $this->assertSame(0, $data['summary']['sets_with_effort']);
    }

    public function test_rango_all_incluye_todo_el_historico(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->makeSet($user, now()->subYears(3)->toDateString(), 'rir', 2);

        $data = $this->actingAs($user)->getJson('/api/historial/effort-analysis?range=all')->json();

        $this->assertSame('all', $data['range']);
        $this->assertSame(1, $data['summary']['sets_with_effort']);
    }

    public function test_rango_invalido_cae_a_30d(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $data = $this->actingAs($user)->getJson('/api/historial/effort-analysis?range=invalido')->json();

        $this->assertSame('30d', $data['range']);
    }

    public function test_tendencia_semanal_agrupa_por_semana_lunes(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $lunes = now()->startOfWeek(Carbon::MONDAY);

        $this->makeSet($user, $lunes->toDateString(), 'rir', 2, 'Press banca', true, 1);
        $this->makeSet($user, $lunes->copy()->addDays(2)->toDateString(), 'rir', 4, 'Press banca', true, 2);
        $this->makeSet($user, $lunes->copy()->subDays(7)->toDateString(), 'rir', 1, 'Press banca', true, 1);

        $data = $this->actingAs($user)->getJson('/api/historial/effort-analysis?range=90d')->json();

        $this->assertCount(2, $data['weekly_trend']);
        $this->assertSame($lunes->copy()->subDays(7)->toDateString(), $data['weekly_trend'][0]['week_start']);
        $this->assertSame(1, $data['weekly_trend'][0]['sets']);
        $this->assertSame($lunes->toDateString(), $data['weekly_trend'][1]['week_start']);
        $this->assertSame(2, $data['weekly_trend'][1]['sets']);
        $this->assertEquals(3.0, $data['weekly_trend'][1]['avg_rir']); // (2 + 4) / 2
    }

    public function test_lista_ejercicios_mas_trackeados(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $hoy = now()->toDateString();

        $this->makeSet($user, $hoy, 'rir', 1, 'Press banca', true, 1);
        $this->makeSet($user, $hoy, 'rir', 2, 'Press banca', true, 2);
        $this->makeSet($user, $hoy, 'rir', 3, 'Sentadilla', true, 1);

        $data = $this->actingAs($user)->getJson('/api/historial/effort-analysis')->json();

        $this->assertCount(2, $data['by_exercise']);
        $this->assertSame('Press banca', $data['by_exercise'][0]['ejercicio']);
        $this->assertSame(2, $data['by_exercise'][0]['sets']);
        $this->assertSame(1.5, $data['by_exercise'][0]['avg_rir']);
        $this->assertSame('Sentadilla', $data['by_exercise'][1]['ejercicio']);
    }

    public function test_usuario_comun_no_puede_ver_esfuerzo_de_otro(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $otro = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson("/api/historial/effort-analysis?user_id={$otro->id}");

        $response->assertStatus(403);
    }

    public function test_trainer_puede_ver_esfuerzo_de_alumno_asignado(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN, 'trainer_id' => $trainer->id]);

        $this->makeSet($alumno, now()->toDateString(), 'rir', 2);

        $response = $this->actingAs($trainer)->getJson("/api/historial/effort-analysis?user_id={$alumno->id}");

        $response->assertStatus(200);
        $this->assertSame(1, $response->json()['summary']['sets_with_effort']);
    }

    public function test_trainer_no_puede_ver_alumno_no_asignado(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($trainer)->getJson("/api/historial/effort-analysis?user_id={$alumno->id}");

        $response->assertStatus(403);
    }

    public function test_admin_puede_ver_esfuerzo_de_cualquier_alumno(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->makeSet($alumno, now()->toDateString(), 'rir', 3);

        $response = $this->actingAs($admin)->getJson("/api/historial/effort-analysis?user_id={$alumno->id}");

        $response->assertStatus(200);
        $this->assertSame(1, $response->json()['summary']['sets_with_effort']);
    }
}
