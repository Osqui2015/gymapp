<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\SesionEntrenamiento;
use App\Models\User;
use App\Services\WorkoutSessionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tanda 3 / T3-C — tests unitarios de lógica pura: WorkoutSessionService.
 *
 * El servicio maneja el ciclo de vida de una sesión de entrenamiento:
 * iniciar (idempotente por UUID), finalizar (volumen, duración, PRs) y
 * descartar. Es la lógica que calcula lo que el usuario ve en el resumen
 * del entrenamiento, así que un error acá muestra números equivocados.
 */
class WorkoutSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    private WorkoutSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WorkoutSessionService;
    }

    private function user(): User
    {
        return User::factory()->create();
    }

    /**
     * Crea un set de historial completado.
     *
     * `series_numero` se autoincrementa porque la clave única de `historials`
     * es (user_id, rutina_nombre, dia, ejercicio_nombre, series_numero, fecha):
     * dos sets del mismo ejercicio el mismo día necesitan números distintos.
     */
    private function set(User $user, array $attrs = []): Historial
    {
        if (! array_key_exists('series_numero', $attrs)) {
            $attrs['series_numero'] = (int) Historial::where('user_id', $user->id)->count() + 1;
        }

        return Historial::create(array_merge([
            'user_id' => $user->id,
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'ejercicio_nombre' => 'Press banca',
            'peso' => 60.0,
            'reps_min' => 8,
            'reps_max' => 12,
            'descanso_min' => 90,
            'reps_realizadas' => 10,
            'completado' => true,
            'fecha' => now()->toDateString(),
        ], $attrs));
    }

    // ------------------------------------------------------------- iniciar

    public function test_iniciar_crea_la_sesion_con_los_datos_del_usuario(): void
    {
        $user = $this->user();

        $sesion = $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'series_totales' => 12,
        ]);

        $this->assertDatabaseHas('sesiones_entrenamiento', [
            'id' => $sesion->id,
            'user_id' => $user->id,
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'series_totales' => 12,
        ]);
    }

    public function test_iniciar_es_idempotente_con_el_mismo_uuid(): void
    {
        $user = $this->user();

        $primera = $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'series_totales' => 12,
        ]);

        $segunda = $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'series_totales' => 12,
        ]);

        $this->assertSame($primera->id, $segunda->id);
        $this->assertSame(1, SesionEntrenamiento::where('uuid', 'uuid-1')->count());
    }

    public function test_iniciar_respeta_el_started_at_proporcionado(): void
    {
        $user = $this->user();

        $sesion = $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'started_at' => '2026-10-01 08:30:00',
        ]);

        $this->assertSame('2026-10-01', $sesion->started_at->toDateString());
        $this->assertSame('08:30:00', $sesion->started_at->toTimeString());
    }

    public function test_iniciar_usa_series_totales_cero_si_no_vienen(): void
    {
        $user = $this->user();

        $sesion = $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
        ]);

        $this->assertSame(0, (int) $sesion->series_totales);
    }

    // ------------------------------------------------------- obtenerActiva

    public function test_obtener_activa_devuelve_la_sesion_sin_finalizar(): void
    {
        $user = $this->user();

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
        ]);

        $activa = $this->service->obtenerActiva($user);

        $this->assertNotNull($activa);
        $this->assertSame('uuid-1', $activa->uuid);
    }

    public function test_obtener_activa_devuelve_null_si_no_hay_sesiones(): void
    {
        $this->assertNull($this->service->obtenerActiva($this->user()));
    }

    public function test_obtener_activa_ignora_las_sesiones_ya_finalizadas(): void
    {
        $user = $this->user();

        $sesion = $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
        ]);
        $sesion->update(['ended_at' => now()]);

        $this->assertNull($this->service->obtenerActiva($user));
    }

    public function test_obtener_activa_no_devuelve_la_sesion_de_otro_usuario(): void
    {
        $userA = $this->user();
        $userB = $this->user();

        $this->service->iniciar($userA, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
        ]);

        $this->assertNull($this->service->obtenerActiva($userB));
    }

    // ----------------------------------------------------------- finalizar

    public function test_finalizar_calcula_el_volumen_total(): void
    {
        $user = $this->user();

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'started_at' => '2026-10-01 10:00:00',
        ]);

        $this->set($user, ['peso' => 60.0, 'reps_realizadas' => 10, 'sesion_uuid' => 'uuid-1']);
        $this->set($user, ['peso' => 80.0, 'reps_realizadas' => 8, 'sesion_uuid' => 'uuid-1']);

        $resultado = $this->service->finalizar($user, 'uuid-1', [
            'ended_at' => '2026-10-01 11:00:00',
        ]);

        // 60×10 + 80×8 = 600 + 640 = 1240
        $this->assertEqualsWithDelta(1240.0, $resultado['volumen_total'], 0.01);
        $this->assertSame(2, $resultado['series_completadas']);
    }

    public function test_finalizar_calcula_la_duracion_en_segundos(): void
    {
        $user = $this->user();

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'started_at' => '2026-10-01 10:00:00',
        ]);

        $resultado = $this->service->finalizar($user, 'uuid-1', [
            'ended_at' => '2026-10-01 10:45:00',
        ]);

        $this->assertEquals(2700, $resultado['duracion_segundos']);
    }

    public function test_la_duracion_nunca_es_negativa(): void
    {
        $user = $this->user();

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'started_at' => '2026-10-01 10:00:00',
        ]);

        // Finalizada ANTES de empezar (reloj del cliente desfasado).
        $resultado = $this->service->finalizar($user, 'uuid-1', [
            'ended_at' => '2026-10-01 09:00:00',
        ]);

        $this->assertEquals(0, $resultado['duracion_segundos']);
    }

    public function test_los_sets_no_completados_no_cuentan_en_el_volumen(): void
    {
        $user = $this->user();

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
        ]);

        $this->set($user, ['peso' => 60.0, 'reps_realizadas' => 10, 'sesion_uuid' => 'uuid-1']);
        $this->set($user, [
            'peso' => 100.0,
            'reps_realizadas' => 10,
            'completado' => false,
            'sesion_uuid' => 'uuid-1',
            'series_numero' => 2,
        ]);

        $resultado = $this->service->finalizar($user, 'uuid-1');

        $this->assertEqualsWithDelta(600.0, $resultado['volumen_total'], 0.01);
        $this->assertSame(1, $resultado['series_completadas']);
    }

    public function test_los_sets_sin_peso_o_sin_reps_no_cuentan_en_el_volumen(): void
    {
        $user = $this->user();

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
        ]);

        $this->set($user, ['peso' => 60.0, 'reps_realizadas' => 10, 'sesion_uuid' => 'uuid-1']);
        $this->set($user, ['peso' => null, 'reps_realizadas' => 10, 'sesion_uuid' => 'uuid-1', 'series_numero' => 2]);
        $this->set($user, ['peso' => 60.0, 'reps_realizadas' => 0, 'sesion_uuid' => 'uuid-1', 'series_numero' => 3]);

        $resultado = $this->service->finalizar($user, 'uuid-1');

        $this->assertEqualsWithDelta(600.0, $resultado['volumen_total'], 0.01);
    }

    public function test_finalizar_hace_backfill_del_sesion_uuid_en_los_sets_sin_asignar(): void
    {
        $user = $this->user();

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'started_at' => now()->toDateTimeString(),
        ]);

        // Set sin sesion_uuid, pero de la misma rutina/día/fecha.
        $set = $this->set($user, ['sesion_uuid' => null]);

        $this->service->finalizar($user, 'uuid-1');

        $this->assertSame('uuid-1', $set->fresh()->sesion_uuid);
    }

    public function test_finalizar_no_toca_los_sets_de_otra_rutina(): void
    {
        $user = $this->user();

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'started_at' => now()->toDateTimeString(),
        ]);

        // Misma fecha pero otra rutina: no pertenece a esta sesión.
        $otro = $this->set($user, [
            'rutina_nombre' => 'Pull B',
            'dia' => 2,
            'sesion_uuid' => null,
        ]);

        $this->service->finalizar($user, 'uuid-1');

        $this->assertNull($otro->fresh()->sesion_uuid);
    }

    public function test_finalizar_detecta_un_pr_cuando_se_supera_el_maximo_historico(): void
    {
        $user = $this->user();

        // Histórico: 80 kg fue el máximo anterior.
        $this->set($user, [
            'ejercicio_nombre' => 'Press banca',
            'peso' => 80.0,
            'fecha' => now()->subDays(10)->toDateString(),
            'sesion_uuid' => 'uuid-viejo',
        ]);

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'started_at' => now()->toDateTimeString(),
        ]);

        $this->set($user, [
            'ejercicio_nombre' => 'Press banca',
            'peso' => 85.0,
            'sesion_uuid' => 'uuid-1',
        ]);

        $resultado = $this->service->finalizar($user, 'uuid-1');

        $this->assertSame(1, $resultado['prs_superados']);
    }

    public function test_finalizar_no_cuenta_pr_si_no_se_supera_el_maximo_historico(): void
    {
        $user = $this->user();

        $this->set($user, [
            'ejercicio_nombre' => 'Press banca',
            'peso' => 80.0,
            'fecha' => now()->subDays(10)->toDateString(),
            'sesion_uuid' => 'uuid-viejo',
        ]);

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'started_at' => now()->toDateTimeString(),
        ]);

        // Igual que el máximo histórico: no es PR.
        $this->set($user, [
            'ejercicio_nombre' => 'Press banca',
            'peso' => 80.0,
            'sesion_uuid' => 'uuid-1',
        ]);

        $resultado = $this->service->finalizar($user, 'uuid-1');

        $this->assertSame(0, $resultado['prs_superados']);
    }

    public function test_finalizar_no_puede_operar_sobre_la_sesion_de_otro_usuario(): void
    {
        $userA = $this->user();
        $userB = $this->user();

        $this->service->iniciar($userA, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
        ]);

        $this->expectException(ModelNotFoundException::class);

        $this->service->finalizar($userB, 'uuid-1');
    }

    public function test_finalizar_actualiza_la_sesion_en_la_base(): void
    {
        $user = $this->user();

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'started_at' => '2026-10-01 10:00:00',
        ]);

        $this->set($user, ['peso' => 60.0, 'reps_realizadas' => 10, 'sesion_uuid' => 'uuid-1']);

        $this->service->finalizar($user, 'uuid-1', ['ended_at' => '2026-10-01 10:30:00']);

        $this->assertDatabaseHas('sesiones_entrenamiento', [
            'uuid' => 'uuid-1',
            'volumen_total' => 600,
            'series_completadas' => 1,
        ]);

        $this->assertEquals(1800, (int) SesionEntrenamiento::where('uuid', 'uuid-1')->first()->duracion_segundos);
    }

    public function test_las_series_totales_nunca_bajan_de_las_completadas(): void
    {
        $user = $this->user();

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
            'series_totales' => 3,
        ]);

        // Se completaron 5 series (más de las 3 planificadas).
        for ($i = 1; $i <= 5; $i++) {
            $this->set($user, ['peso' => 60.0, 'reps_realizadas' => 10, 'sesion_uuid' => 'uuid-1']);
        }

        $resultado = $this->service->finalizar($user, 'uuid-1');

        $this->assertSame(5, $resultado['series_completadas']);
        $this->assertSame(5, $resultado['series_totales']);
    }

    // ----------------------------------------------------------- descartar

    public function test_descartar_elimina_la_sesion_activa(): void
    {
        $user = $this->user();

        $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
        ]);

        $this->assertTrue($this->service->descartar($user, 'uuid-1'));
        $this->assertDatabaseMissing('sesiones_entrenamiento', ['uuid' => 'uuid-1']);
    }

    public function test_descartar_devuelve_false_si_la_sesion_no_existe(): void
    {
        $this->assertFalse($this->service->descartar($this->user(), 'uuid-inexistente'));
    }

    public function test_descartar_devuelve_false_si_la_sesion_ya_esta_finalizada(): void
    {
        $user = $this->user();

        $sesion = $this->service->iniciar($user, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
        ]);
        $sesion->update(['ended_at' => now()]);

        $this->assertFalse($this->service->descartar($user, 'uuid-1'));
        $this->assertDatabaseHas('sesiones_entrenamiento', ['uuid' => 'uuid-1']);
    }

    public function test_descartar_no_puede_tocar_la_sesion_de_otro_usuario(): void
    {
        $userA = $this->user();
        $userB = $this->user();

        $this->service->iniciar($userA, [
            'uuid' => 'uuid-1',
            'rutina_nombre' => 'Push A',
            'dia' => 1,
        ]);

        $this->assertFalse($this->service->descartar($userB, 'uuid-1'));
        $this->assertDatabaseHas('sesiones_entrenamiento', ['uuid' => 'uuid-1']);
    }
}
