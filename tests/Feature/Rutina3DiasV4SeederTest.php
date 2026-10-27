<?php

namespace Tests\Feature;

use App\Models\Rutina;
use App\Models\User;
use App\Services\RutinaPrescripcionService;
use Database\Seeders\Rutina3DiasV4Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La "3 Días V4" es la V3 ajustada al perfil real de Oscar (26 filas,
 * 75 series). Este test fija las invariantes del plan para que no se rompan
 * con un cambio futuro.
 */
class Rutina3DiasV4SeederTest extends TestCase
{
    use RefreshDatabase;

    private function cargar(): void
    {
        User::factory()->create(['nick' => 'osqui']);
        $this->seed(Rutina3DiasV4Seeder::class);
    }

    public function test_crea_26_filas_por_dia_con_el_total_de_series_correcto(): void
    {
        $this->cargar();

        $v4 = Rutina::where('modalidad', '3 Días V4')->get();
        $this->assertCount(26, $v4);

        $this->assertSame(25, $v4->where('dia', 'Día 1 (Torso)')->sum('series'));
        $this->assertSame(23, $v4->where('dia', 'Día 2 (Pierna)')->sum('series'));
        $this->assertSame(27, $v4->where('dia', 'Día 3 (Full Body)')->sum('series'));
        $this->assertSame(75, $v4->sum('series'));
    }

    /**
     * REGLA DURA: la suma de `series` de los bloques tiene que coincidir
     * EXACTAMENTE con la columna `series` de cada fila.
     */
    public function test_la_suma_de_los_bloques_coincide_con_la_columna_series(): void
    {
        $this->cargar();

        foreach (Rutina::where('modalidad', '3 Días V4')->get() as $rutina) {
            $bloques = $rutina->prescripcion['bloques'] ?? [];

            if (empty($bloques)) {
                // Sin bloques de esfuerzo (bloque de movilidad o trabajo por
                // tiempo) no hay suma que comparar.
                $this->assertContains(
                    $rutina->ejercicio_nombre,
                    ['Movilidad articular y activación', 'Plancha abdominal', 'Plancha lateral', 'Elevación de piernas colgado']
                );
                continue;
            }

            $suma = 0;
            foreach ($bloques as $bloque) {
                $suma += (int) $bloque['series'];
            }

            $this->assertSame(
                (int) $rutina->series,
                $suma,
                "Series inconsistentes en {$rutina->dia} / {$rutina->ejercicio_nombre}"
            );
        }
    }

    /**
     * DOBLE FUENTE DE VERDAD: las notas (texto libre, que parsea el front con
     * regex) tienen que derivar EXACTAMENTE lo mismo que la columna
     * `prescripcion` (JSON estructurado).
     */
    public function test_las_notas_derivan_lo_mismo_que_la_prescripcion(): void
    {
        $this->cargar();

        foreach (Rutina::where('modalidad', '3 Días V4')->get() as $rutina) {
            $derivada = RutinaPrescripcionService::fromNotas($rutina->notas);
            $guardada = $rutina->prescripcion;

            $this->assertEquals(
                $this->normalizar($guardada),
                $this->normalizar($derivada),
                "Notas y prescripción no coinciden en {$rutina->dia} / {$rutina->ejercicio_nombre}"
            );
        }
    }

    public function test_la_superserie_del_dia_3_agrupa_curl_martillo_y_triceps(): void
    {
        $this->cargar();

        $ss = Rutina::where('modalidad', '3 Días V3')->orWhere('modalidad', '3 Días V4')
            ->whereNotNull('superserie_grupo')
            ->orderBy('orden')
            ->get();

        $this->assertCount(2, $ss);
        $this->assertSame('Curl martillo', $ss[0]->ejercicio_nombre);
        $this->assertSame('Extensión de tríceps sobre la cabeza', $ss[1]->ejercicio_nombre);
        $this->assertSame($ss[0]->superserie_grupo, $ss[1]->superserie_grupo);
        // Las dos tienen 3 series para que las rondas calcen.
        $this->assertSame(3, (int) $ss[0]->series);
        $this->assertSame(3, (int) $ss[1]->series);
    }

    public function test_hay_dos_ejercicios_de_core_por_dia(): void
    {
        $this->cargar();

        $core = ['Crunch', 'Plancha', 'Elevación de piernas colgado'];

        foreach (['Día 1 (Torso)', 'Día 2 (Pierna)', 'Día 3 (Full Body)'] as $dia) {
            $delDia = Rutina::where('modalidad', '3 Días V4')
                ->where('dia', $dia)
                ->get()
                ->filter(fn ($r) => (bool) preg_match('/Crunch|Plancha|piernas colgado/i', $r->ejercicio_nombre));

            $this->assertCount(2, $delDia, "Se esperaban 2 ejercicios de core en {$dia}");
        }
    }

    public function test_el_bloque_de_movilidad_no_cuenta_como_serie(): void
    {
        $this->cargar();

        $movilidad = Rutina::where('modalidad', '3 Días V4')
            ->where('ejercicio_nombre', 'Movilidad articular y activación')
            ->firstOrFail();

        $this->assertSame(0, (int) $movilidad->series);
        $this->assertNull($movilidad->prescripcion);
    }

    public function test_es_idempotente_y_no_toca_las_otras_modalidades(): void
    {
        $this->cargar();

        // Una rutina de la V3 que NO se debe tocar.
        $v3 = Rutina::create([
            'nivel' => 'Personalizada',
            'modalidad' => '3 Días V3',
            'dia' => 'Día 1 (Torso)',
            'series' => 4,
            'reps_min' => '4',
            'reps_max' => '6',
            'descanso_min' => 2,
            'ejercicio_nombre' => 'Press de banca',
            'orden' => 1,
            'created_by' => 1,
        ]);

        $this->seed(Rutina3DiasV4Seeder::class);

        $this->assertSame(26, Rutina::where('modalidad', '3 Días V4')->count());
        $this->assertSame(1, Rutina::where('modalidad', '3 Días V3')->count());
        $this->assertNotNull(Rutina::find($v3->id));
    }

    public function test_las_cargas_de_referencia_quedan_en_las_notas(): void
    {
        $this->cargar();

        $curl = Rutina::where('modalidad', '3 Días V4')->where('ejercicio_nombre', 'Curl de bíceps')->firstOrFail();
        $this->assertStringContainsString('15 kg', $curl->notas);
        $this->assertStringContainsString('20 kg', $curl->notas);

        // La serie de fallo del curl de bíceps (el ajuste #2 de la V4).
        $this->assertSame(4, (int) $curl->series);
        $this->assertCount(3, $curl->prescripcion['bloques']);
    }

    private function normalizar(?array $valor): array
    {
        if ($valor === null) {
            return [];
        }

        foreach ($valor as $k => $v) {
            if (is_array($v)) {
                $valor[$k] = $this->normalizar($v);
            }
        }

        if (! array_is_list($valor)) {
            ksort($valor);
        }

        return $valor;
    }
}
