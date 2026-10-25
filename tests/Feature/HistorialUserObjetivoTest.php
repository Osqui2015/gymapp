<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El ?user_id= (trainer/admin viendo un alumno) debe respetarse en TODOS los
 * endpoints de stats e historial. Antes se ignoraba en stats/resumen,
 * stats/heatmap, stats/esfuerzo y historial/comparar: el trainer veía sus
 * propios datos (o ceros) en vez de los del alumno.
 */
class HistorialUserObjetivoTest extends TestCase
{
    use RefreshDatabase;

    private function alumnoConSeries(User $user, int $dias = 3): void
    {
        foreach (range(1, $dias) as $i) {
            Historial::create([
                'user_id' => $user->id,
                'rutina_nombre' => 'Push',
                'dia' => 'Día 1',
                'ejercicio_nombre' => 'Press de banca',
                'series_numero' => $i,
                'reps_min' => '8', 'reps_max' => '12',
                'reps_realizadas' => 10,
                'descanso_min' => 2, 'peso' => 60,
                'completado' => true,
                'esfuerzo_tipo' => 'rir',
                'esfuerzo_valor' => 2,
                'fecha' => now()->subDays($i - 1)->toDateString(),
            ]);
        }
    }

    // === stats/resumen ===

    public function test_stats_resumen_devuelve_los_datos_del_alumno(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN, 'trainer_id' => $trainer->id]);
        $this->alumnoConSeries($alumno, 3);

        $response = $this->actingAs($trainer)->getJson("/api/stats/resumen?user_id={$alumno->id}");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertSame(3, $data['total_sets']);
        $this->assertSame(3, $data['total_workouts']);
        $this->assertSame(3, $data['last_30_days']);
    }

    public function test_stats_resumen_sin_user_id_devuelve_los_datos_propios(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $this->alumnoConSeries($user, 2);

        $response = $this->actingAs($user)->getJson('/api/stats/resumen');

        $response->assertStatus(200);
        $this->assertSame(2, $response->json('total_sets'));
    }

    public function test_stats_resumen_rechaza_usuario_no_autorizado(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $otro = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->actingAs($user)->getJson("/api/stats/resumen?user_id={$otro->id}")->assertStatus(403);
    }

    public function test_stats_resumen_rechaza_alumno_no_asignado_al_trainer(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->actingAs($trainer)->getJson("/api/stats/resumen?user_id={$alumno->id}")->assertStatus(403);
    }

    public function test_stats_resumen_admin_puede_ver_cualquier_alumno(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN]);
        $this->alumnoConSeries($alumno, 2);

        $response = $this->actingAs($admin)->getJson("/api/stats/resumen?user_id={$alumno->id}");

        $response->assertStatus(200);
        $this->assertSame(2, $response->json('total_sets'));
    }

    // === stats/heatmap ===

    public function test_stats_heatmap_devuelve_los_datos_del_alumno(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN, 'trainer_id' => $trainer->id]);
        $this->alumnoConSeries($alumno, 2);

        $response = $this->actingAs($trainer)->getJson("/api/stats/heatmap?user_id={$alumno->id}");

        $response->assertStatus(200);
        $this->assertNotEmpty(array_filter($response->json('days')));
    }

    public function test_stats_heatmap_rechaza_usuario_no_autorizado(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $otro = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->actingAs($user)->getJson("/api/stats/heatmap?user_id={$otro->id}")->assertStatus(403);
    }

    // === stats/esfuerzo ===

    public function test_stats_esfuerzo_devuelve_los_datos_del_alumno(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN, 'trainer_id' => $trainer->id]);
        $this->alumnoConSeries($alumno, 3);

        $response = $this->actingAs($trainer)->getJson("/api/stats/esfuerzo?user_id={$alumno->id}&window=30");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertSame(3, $data['total_sets']);
        $this->assertSame(3, $data['sets_with_esfuerzo']);
    }

    public function test_stats_esfuerzo_rechaza_usuario_no_autorizado(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $otro = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->actingAs($user)->getJson("/api/stats/esfuerzo?user_id={$otro->id}")->assertStatus(403);
    }

    // === historial/comparar ===

    public function test_comparar_devuelve_los_datos_del_alumno(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN, 'trainer_id' => $trainer->id]);

        Historial::create([
            'user_id' => $alumno->id, 'rutina_nombre' => 'Push', 'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca', 'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12', 'reps_realizadas' => 10,
            'descanso_min' => 2, 'peso' => 80, 'completado' => true,
            'fecha' => now()->subDays(3)->toDateString(),
        ]);

        $response = $this->actingAs($trainer)->getJson(
            '/api/historial/comparar?user_id='.$alumno->id
            .'&ejercicio='.rawurlencode('Press de banca')
            .'&desde='.now()->subDays(30)->toDateString()
            .'&hasta='.now()->toDateString()
        );

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('desde_stats.sets'));
    }

    public function test_comparar_rechaza_usuario_no_autorizado(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $otro = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->actingAs($user)->getJson(
            '/api/historial/comparar?user_id='.$otro->id
            .'&ejercicio=Press&desde='.now()->subDays(30)->toDateString()
            .'&hasta='.now()->toDateString()
        )->assertStatus(403);
    }

    // === cache: no se puede servir el dato de otro usuario ===

    public function test_la_cache_de_stats_no_mezcla_usuarios(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumnoA = User::factory()->create(['role' => User::ROLE_COMUN, 'trainer_id' => $trainer->id]);
        $alumnoB = User::factory()->create(['role' => User::ROLE_COMUN, 'trainer_id' => $trainer->id]);

        $this->alumnoConSeries($alumnoA, 3);
        $this->alumnoConSeries($alumnoB, 1);

        $a = $this->actingAs($trainer)->getJson("/api/stats/resumen?user_id={$alumnoA->id}")->json();
        $b = $this->actingAs($trainer)->getJson("/api/stats/resumen?user_id={$alumnoB->id}")->json();

        $this->assertSame(3, $a['total_sets']);
        $this->assertSame(1, $b['total_sets']);
    }
}
