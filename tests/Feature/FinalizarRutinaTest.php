<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tanda 3 (hotfix produccion) — regresion del 500 en "Finalizar rutina".
 *
 * La clave UNIQUE de `historials` es:
 *   (user_id, rutina_nombre, dia, ejercicio_nombre, series_numero, fecha)
 *
 * `HistorialService::finalizarRutinaDia()` usaba `firstOrNew()` SIN la fecha.
 * Como la clave ahora incluye la fecha, ese lookup no matcheaba la fila
 * existente y el codigo intentaba insertar una fila duplicada -> violacion
 * del indice UNIQUE -> HTTP 500.
 *
 * OJO: este bug lo introdujo la migracion del P0
 * (`2026_10_29_000000_fix_historials_idempotencia_y_fecha`), que agrego `fecha`
 * a la clave unica. No lo detecto ningun test porque no habia cobertura de
 * "finalizar rutina dos veces el mismo dia".
 */
class FinalizarRutinaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Rutina $rutina;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'alumno']);

        // La rutina tiene que existir en la tabla `rutinas` (nivel + modalidad
        // + dia + ejercicio).
        $this->rutina = Rutina::create([
            'nivel' => 'Intermedio',
            'modalidad' => 'Full Body',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press banca',
            'series' => 3,
            'reps_min' => '8',
            'reps_max' => '12',
            'descanso_min' => 90,
            'orden' => 1,
        ]);

        UserRutina::create([
            'user_id' => $this->user->id,
            'rutina_id' => $this->rutina->id,
            'dia_actual' => 'Día 1',
        ]);
    }

    public function test_finalizar_rutina_responde_200(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/historial/finalizar-rutina');

        $response->assertOk();
    }

    public function test_finalizar_rutina_marca_las_series_completadas(): void
    {
        $this->actingAs($this->user)->postJson('/api/historial/finalizar-rutina')
            ->assertOk();

        $series = Historial::where('user_id', $this->user->id)->get();

        $this->assertCount(3, $series);
        foreach ($series as $s) {
            $this->assertTrue((bool) $s->completado);
            $this->assertSame('Press banca', $s->ejercicio_nombre);
        }
    }

    /**
     * LA REGRESION: finalizar dos veces el mismo dia no puede romper.
     *
     * Con el `firstOrNew()` viejo, la segunda llamada intentaba insertar
     * filas duplicadas (la clave UNIQUE incluye la fecha) -> 500.
     */
    public function test_finalizar_rutina_dos_veces_el_mismo_dia_no_falla(): void
    {
        // Primera vez.
        $this->actingAs($this->user)->postJson('/api/historial/finalizar-rutina')
            ->assertOk();

        // Segunda vez (mismo dia): tiene que seguir funcionando.
        $response = $this->actingAs($this->user)
            ->postJson('/api/historial/finalizar-rutina');

        $response->assertOk();

        // Y no debe duplicar filas: la clave incluye la fecha, asi que la
        // segunda llamada actualiza las mismas 3 filas.
        $this->assertSame(3, Historial::where('user_id', $this->user->id)->count());
    }

    public function test_finalizar_rutina_tres_veces_seguidas_no_falla(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->user)->postJson('/api/historial/finalizar-rutina')
                ->assertOk();
        }

        $this->assertSame(3, Historial::where('user_id', $this->user->id)->count());
    }

    public function test_finalizar_rutina_conserva_los_datos_ya_registrados(): void
    {
        // El usuario ya habia cargado sus series a mano (peso y reps reales).
        Historial::create([
            'user_id' => $this->user->id,
            'rutina_nombre' => 'Intermedio Full Body',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 1,
            'series_completadas' => 1,
            'reps_min' => '8',
            'reps_max' => '12',
            'reps_realizadas' => 10,
            'peso' => 60.0,
            'descanso_min' => 90,
            'completado' => true,
            'fecha' => Carbon::now()->toDateString(),
        ]);

        $this->actingAs($this->user)->postJson('/api/historial/finalizar-rutina')
            ->assertOk();

        // Finalizar la rutina NO debe pisar el peso/reps que el usuario cargo.
        $serie = Historial::where('user_id', $this->user->id)
            ->where('series_numero', 1)
            ->first();

        $this->assertNotNull($serie);
        $this->assertSame(60.0, (float) $serie->peso);
        $this->assertSame(10, $serie->reps_realizadas);
    }

    public function test_finalizar_rutina_avanza_al_dia_siguiente(): void
    {
        // Segunda rutina (dia 2) para verificar el avance.
        Rutina::create([
            'nivel' => 'Intermedio',
            'modalidad' => 'Full Body',
            'dia' => 'Día 2',
            'ejercicio_nombre' => 'Sentadilla',
            'series' => 2,
            'reps_min' => '8',
            'reps_max' => '10',
            'descanso_min' => 120,
            'orden' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/historial/finalizar-rutina');

        $response->assertOk();
        $response->assertJson(['dia_actual' => 'Día 2']);
    }

    public function test_finalizar_rutina_sin_rutina_seleccionada_no_falla(): void
    {
        $userSinRutina = User::factory()->create(['role' => 'alumno']);

        $response = $this->actingAs($userSinRutina)
            ->postJson('/api/historial/finalizar-rutina');

        // Puede ser 200 con error o 422: lo importante es que NO sea 500.
        $this->assertNotSame(500, $response->status());
    }
}
