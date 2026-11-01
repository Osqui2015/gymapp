<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\User;
use App\Services\CacheHistorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * El cache de stats/body map/1RM se invalida al escribir en `historials`.
 *
 * Sin esto el usuario veía KPIs de hasta 10 minutos de antigüedad justo
 * después de guardar la serie (el peor momento para que se note).
 */
class CacheHistorialTest extends TestCase
{
    use RefreshDatabase;

    private function guardarSerie(User $user, bool $completado = true): void
    {
        $this->actingAs($user)->postJson('/api/historial/guardar', [
            'series' => [[
                'rutina_nombre' => 'Push',
                'dia' => 'Día 1',
                'ejercicio_nombre' => 'Press de banca',
                'series_numero' => 1,
                'reps_min' => '8',
                'reps_max' => '12',
                'reps_realizadas' => 10,
                'descanso_min' => 2,
                'peso' => 60,
                'completado' => $completado,
                'fecha' => now()->toDateString(),
            ]],
        ])->assertStatus(200);
    }

    public function test_guardar_una_serie_invalida_el_cache_del_usuario(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $antes = CacheHistorial::version($user->id);
        $this->guardarSerie($user);

        $this->assertGreaterThan($antes, CacheHistorial::version($user->id));
    }

    public function test_el_resumen_refleja_la_serie_recien_guardada(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $antes = $this->actingAs($user)->getJson('/api/stats/resumen')->json();
        $this->assertSame(0, $antes['total_sets']);

        $this->guardarSerie($user);

        $despues = $this->actingAs($user)->getJson('/api/stats/resumen')->json();
        $this->assertSame(1, $despues['total_sets']);
        $this->assertSame(1, $despues['total_workouts']);
    }

    public function test_marcar_completado_invalida_el_cache(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $this->guardarSerie($user, completado: false);

        $antes = CacheHistorial::version($user->id);

        $this->actingAs($user)->postJson('/api/historial/completar', [
            'rutina_nombre' => 'Push',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca',
            'series_numero' => 1,
        ])->assertStatus(200);

        $this->assertGreaterThan($antes, CacheHistorial::version($user->id));
    }

    public function test_editar_una_serie_invalida_el_cache(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $this->guardarSerie($user);

        $serie = Historial::where('user_id', $user->id)->firstOrFail();
        $antes = CacheHistorial::version($user->id);

        $this->actingAs($user)->putJson("/api/historial/{$serie->id}", [
            'peso' => 65,
        ])->assertStatus(200);

        $this->assertGreaterThan($antes, CacheHistorial::version($user->id));
    }

    public function test_borrar_una_serie_invalida_el_cache(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $this->guardarSerie($user);

        $serie = Historial::where('user_id', $user->id)->firstOrFail();
        $antes = CacheHistorial::version($user->id);

        $this->actingAs($user)->deleteJson("/api/historial/{$serie->id}")->assertStatus(200);

        $this->assertGreaterThan($antes, CacheHistorial::version($user->id));
    }

    public function test_la_invalidadcion_no_toca_el_cache_de_otro_usuario(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $otro = User::factory()->create(['role' => User::ROLE_COMUN]);

        $antesOtro = CacheHistorial::version($otro->id);
        $this->guardarSerie($user);

        $this->assertSame($antesOtro, CacheHistorial::version($otro->id));
    }

    public function test_la_clave_incluye_la_version(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $v1 = CacheHistorial::key($user->id, 'stats:resumen');
        CacheHistorial::invalidate($user->id);
        $v2 = CacheHistorial::key($user->id, 'stats:resumen');

        $this->assertNotSame($v1, $v2);
        $this->assertStringContainsString((string) CacheHistorial::version($user->id), $v2);
    }
}
