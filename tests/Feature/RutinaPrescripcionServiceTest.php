<?php

namespace Tests\Feature;

use App\Services\RutinaPrescripcionService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tests del parser de notas → prescripción estructurada (lado PHP).
 *
 * Son el espejo de `resources/js/utils/rutinaPrescripcion.spec.js`: ambos
 * implementan el mismo contrato, así que los casos usan las notas REALES de
 * la rutina "3 Días V2" y de la v1.
 */
class RutinaPrescripcionServiceTest extends TestCase
{
    public function test_parsea_un_bloque_progresivo(): void
    {
        $p = RutinaPrescripcionService::fromNotas('Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.');

        $this->assertSame([
            ['series' => 2, 'reps' => 6, 'tipo' => 'rir', 'valor' => 1],
            ['series' => 2, 'reps' => 4, 'tipo' => 'rir', 'valor' => 0],
        ], $p['bloques']);
        $this->assertSame('Bloque progresivo', $p['etiqueta']);
        $this->assertNull($p['rest_pause']);
        $this->assertNull($p['consejo']);
    }

    public function test_parsea_el_bloque_con_serie_al_fallo(): void
    {
        $p = RutinaPrescripcionService::fromNotas('Bloque: 2x10 RIR 1 + 1x8 FALLO.');

        $this->assertCount(2, $p['bloques']);
        $this->assertSame('fallo', $p['bloques'][1]['tipo']);
        $this->assertNull($p['bloques'][1]['valor']);
    }

    public function test_acepta_rpe_rip_y_al_fallo_tecnico(): void
    {
        $this->assertSame('rpe', RutinaPrescripcionService::fromNotas('2x6 RPE 8')['bloques'][0]['tipo']);
        $this->assertSame(8, RutinaPrescripcionService::fromNotas('2x6 RPE 8')['bloques'][0]['valor']);
        $this->assertSame('rir', RutinaPrescripcionService::fromNotas('2*6 RIP 1')['bloques'][0]['tipo']);
        $this->assertSame(1, RutinaPrescripcionService::fromNotas('2*6 RIP 1')['bloques'][0]['valor']);

        $fallo = RutinaPrescripcionService::fromNotas('1x20 AL FALLO TÉCNICO');
        $this->assertSame('fallo', $fallo['bloques'][0]['tipo']);
        $this->assertNull($fallo['bloques'][0]['valor']);
    }

    /**
     * Hay notas que escriben el bloque al fallo SIN repeticiones:
     * "2x8 RIR 1 + 2X al fallo técnico (RIR 0)". Las reps del bloque se
     * heredan del bloque anterior (8), y así la suma de series coincide con
     * la columna `series` (4).
     */
    public function test_parsea_el_bloque_al_fallo_sin_reps(): void
    {
        $p = RutinaPrescripcionService::fromNotas('Bloque: 2x8 RIR 1 + 2X al fallo tecnico (RIR 0).');

        $this->assertCount(2, $p['bloques']);
        $this->assertSame(['series' => 2, 'reps' => 8, 'tipo' => 'rir', 'valor' => 1], $p['bloques'][0]);
        $this->assertSame(['series' => 2, 'reps' => 8, 'tipo' => 'fallo', 'valor' => null], $p['bloques'][1]);
        $this->assertTrue(RutinaPrescripcionService::esConsistente($p, 4));

        // El "RIR 0" que quedó del paréntesis no es un consejo.
        $this->assertNull($p['consejo']);
    }

    public function test_el_bloque_al_fallo_sin_reps_usa_8_reps_por_defecto(): void
    {
        $p = RutinaPrescripcionService::fromNotas('3X AL FALLO');

        $this->assertCount(1, $p['bloques']);
        $this->assertSame(['series' => 3, 'reps' => 8, 'tipo' => 'fallo', 'valor' => null], $p['bloques'][0]);
    }

    public function test_devuelve_null_si_no_hay_bloques(): void
    {
        $this->assertNull(RutinaPrescripcionService::fromNotas(null));
        $this->assertNull(RutinaPrescripcionService::fromNotas(''));
        $this->assertNull(RutinaPrescripcionService::fromNotas('1x40 (Pull-up australiana, completar todas las reps)'));
        $this->assertNull(RutinaPrescripcionService::fromNotas('3x8 (estocadas/zancadas, sin RIR objetivo prescrito)'));
    }

    public function test_detecta_el_rest_pause_con_sus_dos_minisets(): void
    {
        $notas = 'Rest-pause: 3x8 RIR 1. Cada serie = 1 set de 8 reps, descanso 5" y fallo, descanso 10" y fallo.';
        $p = RutinaPrescripcionService::fromNotas($notas);

        $this->assertNotNull($p['rest_pause']);
        $this->assertCount(2, $p['rest_pause']['minisets']);
        $this->assertSame(5, $p['rest_pause']['minisets'][0]['descanso_seg']);
        $this->assertSame(10, $p['rest_pause']['minisets'][1]['descanso_seg']);
        $this->assertSame(8, $p['rest_pause']['minisets'][0]['reps']);
        $this->assertSame('fallo', $p['rest_pause']['minisets'][0]['tipo']);
        $this->assertSame('Rest-pause', $p['etiqueta']);
    }

    public function test_detecta_el_rest_pause_de_la_v1(): void
    {
        $p = RutinaPrescripcionService::fromNotas('3x8 RIR 1 + rest-pause final (8 reps → 5" fallo → 10" fallo)');

        $this->assertCount(1, $p['bloques']);
        $this->assertCount(2, $p['rest_pause']['minisets']);
        $this->assertSame(5, $p['rest_pause']['minisets'][0]['descanso_seg']);
        $this->assertSame(10, $p['rest_pause']['minisets'][1]['descanso_seg']);
        $this->assertSame(8, $p['rest_pause']['minisets'][0]['reps']);
    }

    public function test_detecta_el_rest_pause_intra_set_con_descanso_y_reps(): void
    {
        $p = RutinaPrescripcionService::fromNotas('3x10 RIR 0 + rest-pause intra-set (10" descanso + 5 reps por serie)');

        $this->assertCount(1, $p['rest_pause']['minisets']);
        $this->assertSame(10, $p['rest_pause']['minisets'][0]['descanso_seg']);
        $this->assertSame(5, $p['rest_pause']['minisets'][0]['reps']);
    }

    public function test_extrae_la_progresion(): void
    {
        $this->assertSame(
            'Al completar 3x8 con bodyweight, sumar 2,5 kg de lastre.',
            RutinaPrescripcionService::progresion('Bloque progresivo: 3x8 RIR 1. Al completar 3x8 con bodyweight, sumar 2,5 kg de lastre.')
        );

        $this->assertSame(
            'Es el que progresa con carga: subir 2,5 kg al completar 3x12 con técnica limpia.',
            RutinaPrescripcionService::progresion('3x12 RIR 1. Es el que progresa con carga: subir 2,5 kg al completar 3x12 con técnica limpia.')
        );

        // La etiqueta "Bloque progresivo" NO es una progresión.
        $this->assertNull(RutinaPrescripcionService::progresion('Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.'));
    }

    public function test_extrae_el_consejo(): void
    {
        $this->assertSame(
            'Si esa semana jugaste fútbol con golpeo fuerte, reduci la carga 20%.',
            RutinaPrescripcionService::consejo('Bloque progresivo: 2x8 RIR 1 + 2x6 RIR 0. Si esa semana jugaste fútbol con golpeo fuerte, reduci la carga 20%.')
        );

        $this->assertSame(
            'RIR fijo, no busques el fallo: flexión repetida con fatiga es lo que factura la lumbar.',
            RutinaPrescripcionService::consejo('3x15 RIR 1. RIR fijo, no busques el fallo: flexión repetida con fatiga es lo que factura la lumbar.')
        );

        $this->assertSame(
            'RIR fijo: flexión con fatiga es lo que factura la lumbar.',
            RutinaPrescripcionService::consejo('3x16 RIR 1 (12 por lado). RIR fijo: flexión con fatiga es lo que factura la lumbar.')
        );

        $this->assertSame(
            'Bajada controlada, sin rebote.',
            RutinaPrescripcionService::consejo('3x10 RIR 1. Bajada controlada, sin rebote.')
        );

        // Sin consejo cuando sólo hay plan.
        $this->assertNull(RutinaPrescripcionService::consejo('Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.'));
        $this->assertNull(RutinaPrescripcionService::consejo('3x10 RIR 1.'));
    }

    /**
     * Todas las notas reales de la v1 (7 formatos distintos) deben parsear
     * sin romper y, cuando tienen bloques, ser consistentes con `series`.
     */
    #[DataProvider('notasV1')]
    public function test_las_notas_de_la_v1_se_parsean_bien(string $notas, int $series, ?int $bloques): void
    {
        $p = RutinaPrescripcionService::fromNotas($notas);

        if ($bloques === null) {
            $this->assertNull($p, "Las notas no deberían generar prescripción: {$notas}");

            return;
        }

        $this->assertNotNull($p, "Las notas deberían generar prescripción: {$notas}");
        $this->assertCount($bloques, $p['bloques']);
        $this->assertTrue(
            RutinaPrescripcionService::esConsistente($p, $series),
            "La suma de bloques no coincide con series: {$notas}"
        );
    }

    public static function notasV1(): array
    {
        return [
            'bloque progresivo' => ['2x6 RIR 1 + 2x4 RIR 0', 4, 2],
            'bloque con fallo' => ['2x10 RIR 1 + 1x8 FALLO', 3, 2],
            'serie simple' => ['4x8 RIR 1', 4, 1],
            'rest-pause final' => ['3x8 RIR 1 + rest-pause final (8 reps → 5" fallo → 10" fallo)', 3, 1],
            'rest-pause intra-set' => ['3x8 RIR 0 + rest-pause intra-set (5" fallo + 10" fallo)', 3, 1],
            'domingas a tiempo' => ['1x40 (Pull-up australiana, completar todas las reps)', 1, null],
            'zancadas sin rir' => ['3x8 (estocadas/zancadas, sin RIR objetivo prescrito)', 3, null],
            'descanso + reps' => ['3x10 RIR 0 + rest-pause intra-set (10" descanso + 5 reps por serie)', 3, 1],
            'talones sin rir' => ['4x15 (gemelos parado, sin RIR objetivo prescrito)', 4, null],
            'superserie' => ['2x12 RIR 2 + 2x10 RIR 1 (Superserie con aperturas en polea alta)', 4, 2],
            'fallo sin reps' => ['2x8 RIR 1 + 2X al fallo tecnico (RIR 0)', 4, 2],
        ];
    }

    public function test_es_consistente_valida_la_suma_de_series(): void
    {
        $p = RutinaPrescripcionService::fromNotas('2x6 RIR 1 + 2x4 RIR 0');

        $this->assertTrue(RutinaPrescripcionService::esConsistente($p, 4));
        $this->assertFalse(RutinaPrescripcionService::esConsistente($p, 3));
        $this->assertFalse(RutinaPrescripcionService::esConsistente(null, 4));
    }
}
