<?php

namespace Tests\Feature;

use App\Models\Rutina;
use App\Models\User;
use App\Services\RutinaPrescripcionService;
use Database\Seeders\Rutina3DiasV2Seeder;
use Database\Seeders\RutinaPrescripcionBackfillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Prescripción estructurada de la rutina "3 Días V2".
 *
 * `rutinas.prescripcion` (JSON) es la fuente de verdad para renderizar
 * bloques (RIR/RPE/FALLO), rest-pause y progresión. `rutinas.notas` sigue
 * siendo texto libre y el front actual lo parsea con regex, así que la
 * prescripción tiene que ser CONSISTENTE con esas notas.
 */
class RutinaPrescripcionTest extends TestCase
{
    use RefreshDatabase;

    /** Regex que usa el front actual para parsear los bloques de las notas. */
    private const REGEX_BLOQUES = '/(\d+)\s*[xX\*]\s*(\d+)\s*(?:R[I1][RP]\s*(\d+)|RPE\s*(\d+)|(?:AL\s+)?FALLO)/i';

    protected function setUp(): void
    {
        parent::setUp();

        // Destino del seeder (el default ya es 'osqui', lo fijamos para que
        // el test no dependa del .env local).
        $_ENV['RUTINA_V2_USER_NICK'] = 'osqui';
        $_SERVER['RUTINA_V2_USER_NICK'] = 'osqui';
        putenv('RUTINA_V2_USER_NICK=osqui');
    }

    protected function tearDown(): void
    {
        putenv('RUTINA_V2_USER_NICK');
        unset($_ENV['RUTINA_V2_USER_NICK'], $_SERVER['RUTINA_V2_USER_NICK']);

        parent::tearDown();
    }

    private function crearUsuarioOsqui(): User
    {
        return User::factory()->create([
            'nick' => 'osqui',
            'role' => User::ROLE_COMUN,
        ]);
    }

    /**
     * Parsea las notas con el MISMO regex que usa el front y devuelve los
     * bloques normalizados a la forma de `prescripcion.bloques`.
     */
    private function bloquesDesdeNotas(?string $notas): array
    {
        preg_match_all(self::REGEX_BLOQUES, (string) $notas, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        return array_map(function (array $m): array {
            if ($m[3] !== null) {
                return ['series' => (int) $m[1], 'reps' => (int) $m[2], 'tipo' => 'rir', 'valor' => (int) $m[3]];
            }
            if ($m[4] !== null) {
                return ['series' => (int) $m[1], 'reps' => (int) $m[2], 'tipo' => 'rpe', 'valor' => (int) $m[4]];
            }

            return ['series' => (int) $m[1], 'reps' => (int) $m[2], 'tipo' => 'fallo', 'valor' => null];
        }, $matches);
    }

    private function filasV2(): \Illuminate\Support\Collection
    {
        return Rutina::where('nivel', 'Personalizada')
            ->where('modalidad', Rutina3DiasV2Seeder::MODALIDAD)
            ->orderBy('dia')
            ->orderBy('orden')
            ->get();
    }

    public function test_la_migracion_agrega_la_columna_prescripcion(): void
    {
        $this->assertTrue(Schema::hasColumn('rutinas', 'prescripcion'));
    }

    public function test_el_modelo_castea_prescripcion_a_array(): void
    {
        $rutina = Rutina::create([
            'nivel' => 'Personalizada',
            'modalidad' => 'Test prescripcion',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca',
            'series' => 2,
            'reps_min' => '6',
            'reps_max' => '6',
            'descanso_min' => 2.0,
            'orden' => 1,
            'prescripcion' => [
                'bloques' => [
                    ['series' => 2, 'reps' => 6, 'tipo' => 'rir', 'valor' => 1],
                ],
                'rest_pause' => null,
                'progresion' => null,
                'consejo' => null,
                'etiqueta' => 'Bloque',
            ],
        ]);

        $this->assertIsArray($rutina->prescripcion);
        $this->assertSame('rir', $rutina->prescripcion['bloques'][0]['tipo']);
        $this->assertSame(1, $rutina->prescripcion['bloques'][0]['valor']);

        // En la DB queda serializado como JSON.
        $raw = DB::table('rutinas')->where('id', $rutina->id)->value('prescripcion');
        $this->assertIsString($raw);
        $this->assertSame('rir', json_decode($raw, true)['bloques'][0]['tipo']);
    }

    public function test_seeder_carga_24_ejercicios_con_prescripcion_no_nula(): void
    {
        $this->crearUsuarioOsqui();
        $this->seed(Rutina3DiasV2Seeder::class);

        $filas = $this->filasV2();
        $this->assertCount(24, $filas);

        foreach ($filas as $fila) {
            $this->assertNotNull($fila->prescripcion, "{$fila->ejercicio_nombre} ({$fila->dia}) sin prescripcion");
            $this->assertIsArray($fila->prescripcion);
            $this->assertArrayHasKey('bloques', $fila->prescripcion);
            $this->assertNotEmpty($fila->prescripcion['bloques']);
            $this->assertArrayHasKey('rest_pause', $fila->prescripcion);
        }
    }

    public function test_la_suma_de_series_de_los_bloques_coincide_con_la_columna_series(): void
    {
        $this->crearUsuarioOsqui();
        $this->seed(Rutina3DiasV2Seeder::class);

        foreach ($this->filasV2() as $fila) {
            $suma = array_sum(array_column($fila->prescripcion['bloques'], 'series'));
            $this->assertSame(
                (int) $fila->series,
                $suma,
                "Suma de bloques != series en {$fila->ejercicio_nombre} ({$fila->dia})"
            );
        }
    }

    public function test_los_bloques_estructurados_coinciden_con_el_parseo_de_las_notas(): void
    {
        $this->crearUsuarioOsqui();
        $this->seed(Rutina3DiasV2Seeder::class);

        foreach ($this->filasV2() as $fila) {
            $esperados = $this->bloquesDesdeNotas($fila->notas);
            $bloques = $fila->prescripcion['bloques'];

            $this->assertCount(
                count($esperados),
                $bloques,
                "Cantidad de bloques != notas en {$fila->ejercicio_nombre} ({$fila->dia})"
            );

            foreach ($esperados as $i => $esperado) {
                $this->assertSame($esperado['series'], $bloques[$i]['series'], "series bloque {$i}");
                $this->assertSame($esperado['reps'], $bloques[$i]['reps'], "reps bloque {$i}");
                $this->assertSame($esperado['tipo'], $bloques[$i]['tipo'], "tipo bloque {$i}");
                $this->assertSame($esperado['valor'], $bloques[$i]['valor'], "valor bloque {$i}");
            }
        }
    }

    public function test_los_ejercicios_con_rest_pause_tienen_los_dos_minisets(): void
    {
        $this->crearUsuarioOsqui();
        $this->seed(Rutina3DiasV2Seeder::class);

        $conRestPause = $this->filasV2()->filter(fn ($f) => str_contains((string) $f->notas, 'Rest-pause'));

        // Press inclinado, Remo sentado (Día 1), Extensión de cuádriceps (Día 2)
        // y Extensión de tríceps (Día 3).
        $this->assertCount(4, $conRestPause);

        foreach ($conRestPause as $fila) {
            $restPause = $fila->prescripcion['rest_pause'];
            $this->assertNotNull($restPause, "{$fila->ejercicio_nombre} deberia tener rest_pause");
            $this->assertCount(2, $restPause['minisets']);

            $this->assertSame(5, $restPause['minisets'][0]['descanso_seg']);
            $this->assertSame(10, $restPause['minisets'][1]['descanso_seg']);

            foreach ($restPause['minisets'] as $miniset) {
                $this->assertSame('fallo', $miniset['tipo']);
                $this->assertNull($miniset['valor']);
                // Los mini-sets usan las mismas reps del set principal.
                $this->assertSame($fila->prescripcion['bloques'][0]['reps'], $miniset['reps']);
            }
        }
    }

    public function test_los_ejercicios_sin_rest_pause_lo_dejan_en_null(): void
    {
        $this->crearUsuarioOsqui();
        $this->seed(Rutina3DiasV2Seeder::class);

        $sinRestPause = $this->filasV2()->filter(fn ($f) => ! str_contains((string) $f->notas, 'Rest-pause'));
        $this->assertCount(20, $sinRestPause);

        foreach ($sinRestPause as $fila) {
            $this->assertNull($fila->prescripcion['rest_pause'], "{$fila->ejercicio_nombre} deberia tener rest_pause null");
        }
    }

    public function test_el_endpoint_de_ejercicios_actuales_devuelve_prescripcion(): void
    {
        $user = $this->crearUsuarioOsqui();
        $this->seed(Rutina3DiasV2Seeder::class);

        $response = $this->actingAs($user)->getJson('/api/user-rutina/ejercicios-actuales');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertSame('Día 1 (Torso)', $data['dia']);
        $this->assertCount(8, $data['ejercicios']);

        foreach ($data['ejercicios'] as $ejercicio) {
            $this->assertArrayHasKey('prescripcion', $ejercicio);
            $this->assertNotNull($ejercicio['prescripcion'], "{$ejercicio['nombre']} sin prescripcion");
            $this->assertNotEmpty($ejercicio['prescripcion']['bloques']);
        }
    }

    public function test_store_acepta_y_guarda_la_prescripcion(): void
    {
        $user = $this->crearUsuarioOsqui();

        $response = $this->actingAs($user)->postJson('/api/rutinas', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Test prescripcion',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca',
            'series' => 3,
            'reps_min' => '8',
            'reps_max' => '8',
            'descanso_min' => 2,
            'orden' => 1,
            'notas' => 'Bloque: 2x8 RIR 1 + 1x8 FALLO.',
            'prescripcion' => [
                'bloques' => [
                    ['series' => 2, 'reps' => 8, 'tipo' => 'rir', 'valor' => 1],
                    ['series' => 1, 'reps' => 8, 'tipo' => 'fallo', 'valor' => null],
                ],
                'rest_pause' => null,
                'progresion' => null,
                'consejo' => null,
                'etiqueta' => 'Bloque',
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('prescripcion.bloques.0.tipo', 'rir');
        $response->assertJsonPath('prescripcion.bloques.1.tipo', 'fallo');

        $rutina = Rutina::where('modalidad', 'Test prescripcion')->firstOrFail();
        $this->assertSame('fallo', $rutina->prescripcion['bloques'][1]['tipo']);
        $this->assertNull($rutina->prescripcion['bloques'][1]['valor']);
    }

    public function test_store_rechaza_una_prescripcion_con_tipo_invalido(): void
    {
        $user = $this->crearUsuarioOsqui();

        $response = $this->actingAs($user)->postJson('/api/rutinas', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Test prescripcion',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca',
            'series' => 2,
            'reps_min' => '8',
            'reps_max' => '8',
            'descanso_min' => 2,
            'orden' => 1,
            'prescripcion' => [
                'bloques' => [
                    ['series' => 2, 'reps' => 8, 'tipo' => 'rp', 'valor' => 1],
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prescripcion.bloques.0.tipo']);
    }

    public function test_update_guarda_la_prescripcion(): void
    {
        $user = $this->crearUsuarioOsqui();

        $rutina = Rutina::create([
            'nivel' => 'Personalizada',
            'modalidad' => 'Test prescripcion',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca',
            'series' => 2,
            'reps_min' => '8',
            'reps_max' => '8',
            'descanso_min' => 2.0,
            'orden' => 1,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->putJson('/api/rutinas/'.$rutina->id, [
            'prescripcion' => [
                'bloques' => [
                    ['series' => 2, 'reps' => 8, 'tipo' => 'rir', 'valor' => 2],
                ],
                'rest_pause' => null,
                'progresion' => 'Subir 2,5 kg al completar 3x12.',
                'consejo' => null,
                'etiqueta' => 'Bloque progresivo',
            ],
        ]);

        $response->assertStatus(200);

        $rutina->refresh();
        $this->assertSame(2, $rutina->prescripcion['bloques'][0]['valor']);
        $this->assertSame('Subir 2,5 kg al completar 3x12.', $rutina->prescripcion['progresion']);
    }

    public function test_store_deriva_la_prescripcion_de_las_notas_cuando_no_viene_una(): void
    {
        $user = $this->crearUsuarioOsqui();

        $response = $this->actingAs($user)->postJson('/api/rutinas', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Test derivacion',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca',
            'series' => 3,
            'reps_min' => '8',
            'reps_max' => '8',
            'descanso_min' => 2,
            'orden' => 1,
            'notas' => 'Bloque: 2x8 RIR 1 + 1x8 FALLO.',
        ]);

        $response->assertStatus(201);

        $rutina = Rutina::where('modalidad', 'Test derivacion')->firstOrFail();
        $this->assertNotNull($rutina->prescripcion);
        $this->assertCount(2, $rutina->prescripcion['bloques']);
        $this->assertSame('rir', $rutina->prescripcion['bloques'][0]['tipo']);
        $this->assertSame('fallo', $rutina->prescripcion['bloques'][1]['tipo']);
    }

    public function test_update_regenera_la_prescripcion_cuando_cambian_las_notas(): void
    {
        $user = $this->crearUsuarioOsqui();

        $rutina = Rutina::create([
            'nivel' => 'Personalizada',
            'modalidad' => 'Test desfasaje',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca',
            'series' => 2,
            'reps_min' => '8',
            'reps_max' => '8',
            'descanso_min' => 2.0,
            'orden' => 1,
            'notas' => 'Bloque: 2x8 RIR 1 + 1x8 FALLO.',
            'created_by' => $user->id,
        ]);

        $this->assertSame('fallo', $rutina->prescripcion['bloques'][1]['tipo']);

        $response = $this->actingAs($user)->putJson('/api/rutinas/'.$rutina->id, [
            'notas' => 'Bloque: 3x10 RIR 2.',
        ]);

        $response->assertStatus(200);

        $rutina->refresh();
        // La prescripción se regeneró: ya no está el bloque al fallo.
        $this->assertCount(1, $rutina->prescripcion['bloques']);
        $this->assertSame(3, $rutina->prescripcion['bloques'][0]['series']);
        $this->assertSame(2, $rutina->prescripcion['bloques'][0]['valor']);
    }

    public function test_importar_copia_las_notas_y_la_prescripcion(): void
    {
        $user = $this->crearUsuarioOsqui();

        $original = Rutina::create([
            'nivel' => 'Oficial',
            'modalidad' => '3 Días',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca',
            'series' => 3,
            'reps_min' => '8',
            'reps_max' => '8',
            'descanso_min' => 2.0,
            'orden' => 1,
            'notas' => 'Bloque: 2x8 RIR 1 + 1x8 FALLO.',
            'publica' => true,
            'created_by' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/api/rutinas/importar', [
            'nivel' => 'Oficial',
            'modalidad' => '3 Días',
            'created_by' => null,
        ]);

        $response->assertStatus(200);

        $copia = Rutina::where('created_by', $user->id)
            ->where('modalidad', '3 Días')
            ->firstOrFail();

        $this->assertSame('Bloque: 2x8 RIR 1 + 1x8 FALLO.', $copia->notas);
        $this->assertNotNull($copia->prescripcion);
        $this->assertCount(2, $copia->prescripcion['bloques']);
        $this->assertSame('fallo', $copia->prescripcion['bloques'][1]['tipo']);
        $this->assertNotSame($original->id, $copia->id);
    }

    public function test_duplicar_dia_copia_las_notas_y_la_prescripcion(): void
    {
        $user = $this->crearUsuarioOsqui();

        $rutina = Rutina::create([
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi rutina',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca',
            'series' => 3,
            'reps_min' => '8',
            'reps_max' => '8',
            'descanso_min' => 2.0,
            'orden' => 1,
            'notas' => 'Rest-pause: 3x8 RIR 1. Cada serie = 1 set de 8 reps, descanso 5" y fallo, descanso 10" y fallo.',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/rutinas/duplicar-dia', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi rutina',
            'dia_origen' => 'Día 1',
            'dia_destino' => 'Día 2',
        ]);

        $response->assertStatus(201);

        $copia = Rutina::where('modalidad', 'Mi rutina')->where('dia', 'Día 2')->firstOrFail();
        $this->assertSame($rutina->notas, $copia->notas);
        $this->assertNotNull($copia->prescripcion);
        $this->assertCount(2, $copia->prescripcion['rest_pause']['minisets']);
        $this->assertSame(5, $copia->prescripcion['rest_pause']['minisets'][0]['descanso_seg']);
    }

    public function test_el_backfill_deriva_la_prescripcion_de_las_rutinas_solo_con_notas(): void
    {
        $user = $this->crearUsuarioOsqui();

        Rutina::create([
            'nivel' => 'Personalizada',
            'modalidad' => 'v1',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca',
            'series' => 4,
            'reps_min' => '6',
            'reps_max' => '6',
            'descanso_min' => 2.0,
            'orden' => 1,
            'notas' => '2x6 RIR 1 + 2x4 RIR 0',
            'created_by' => $user->id,
        ]);

        Rutina::create([
            'nivel' => 'Personalizada',
            'modalidad' => 'v1',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Dominadas',
            'series' => 1,
            'reps_min' => '40',
            'reps_max' => '40',
            'descanso_min' => 1.5,
            'orden' => 2,
            'notas' => '1x40 (Pull-up australiana, completar todas las reps)',
            'created_by' => $user->id,
        ]);

        $this->seed(RutinaPrescripcionBackfillSeeder::class);

        $press = Rutina::where('ejercicio_nombre', 'Press de banca')->firstOrFail();
        $this->assertNotNull($press->prescripcion);
        $this->assertCount(2, $press->prescripcion['bloques']);
        $this->assertTrue(RutinaPrescripcionService::esConsistente($press->prescripcion, 4));

        // Sin bloques de esfuerzo: no hay prescripción que derivar.
        $dominadas = Rutina::where('ejercicio_nombre', 'Dominadas')->firstOrFail();
        $this->assertNull($dominadas->prescripcion);
    }
}
