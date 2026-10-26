<?php

namespace App\Services;

/**
 * RutinaPrescripcionService
 *
 * Deriva la prescripción ESTRUCTURADA de un ejercicio a partir de sus `notas`
 * en texto libre.
 *
 * Es el espejo en PHP de `resources/js/utils/rutinaPrescripcion.js`: ambos
 * implementan el mismo contrato para que el front (que parsea `notas` como
 * fallback de rutinas viejas) y el back (que genera la columna
 * `rutinas.prescripcion`) produzcan exactamente la misma estructura.
 *
 *   {
 *     "bloques": [
 *       { "series": 2, "reps": 6, "tipo": "rir", "valor": 1 },
 *       { "series": 2, "reps": 4, "tipo": "rir", "valor": 0 }
 *     ],
 *     "rest_pause": {
 *       "minisets": [
 *         { "reps": 8, "descanso_seg": 5, "tipo": "fallo", "valor": null }
 *       ]
 *     },
 *     "progresion": "Al completar 3x8, sumar 2,5 kg de lastre.",
 *     "consejo": "Bajada controlada, sin rebote.",
 *     "etiqueta": "Bloque progresivo"
 *   }
 *
 * Reglas:
 * - `tipo` siempre es rir | rpe | fallo. Para `fallo`, `valor` es null.
 * - `rest_pause.minisets[i].descanso_seg` es el descanso ANTES de ese mini-set.
 * - Si las notas no tienen bloques (ej. "3x8 (estocadas, sin RIR objetivo)"),
 *   se devuelve null: no hay prescripción que derivar.
 */
class RutinaPrescripcionService
{
    /** Bloques de esfuerzo: "2x6 RIR 1", "2*6 RIP 1", "2x6 RPE 8", "2x8 FALLO". */
    protected const RE_BLOQUE =
        '/(\d+)\s*[xX*]\s*(\d+)\s*(?:R[I1][RP]\s*(\d+)|RPE\s*(\d+)|(?:AL\s+)?FALLO(?:\s+T[ÉE]CNICO)?)/i';

    /** Minutos de descanso intra-set: 'descanso 5" y fallo', '5" fallo'. */
    protected const RE_SEGUNDOS_FALLO = '/(\d+)\s*(?:"|″|\'\')?\s*(?:y\s*)?fallo/i';

    /** Minutos de descanso intra-set sin "fallo": '10" descanso'. */
    protected const RE_SEGUNDOS_DESCANSO = '/(\d+)\s*(?:"|″|\'\')?\s*descanso/i';

    /** Reps del mini-set: '5 reps por serie', '8 reps → 5" fallo'. */
    protected const RE_REPS_MINISET = '/(\d+)\s*reps?\s*(?:por\s*serie|→|->)/i';

    /** Frases que describen progresión de carga. */
    protected const RE_PROGRESION =
        '/\b(sumar|subir|agregar|aumentar|progresa\w*|progresi[oó]n)\b|al\s+completar/i';

    /**
     * Prescripción completa derivada de las notas.
     *
     * @return array<string, mixed>|null null si las notas no tienen bloques.
     */
    public static function fromNotas(?string $notas): ?array
    {
        $notas = $notas !== null ? trim($notas) : '';
        if ($notas === '') {
            return null;
        }

        $bloques = self::bloques($notas);
        if (! $bloques) {
            return null;
        }

        return [
            'bloques' => $bloques,
            'rest_pause' => self::restPause($notas, $bloques),
            'progresion' => self::progresion($notas),
            'consejo' => self::consejo($notas),
            'etiqueta' => self::etiqueta($notas),
        ];
    }

    /**
     * Bloques de esfuerzo detectados en las notas.
     *
     * @return array<int, array{series:int, reps:int, tipo:string, valor:int|null}>
     */
    public static function bloques(?string $notas): array
    {
        if (! $notas) {
            return [];
        }

        $bloques = [];
        if (preg_match_all(self::RE_BLOQUE, $notas, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                if (($match[3] ?? '') !== '') {
                    $bloques[] = [
                        'series' => (int) $match[1],
                        'reps' => (int) $match[2],
                        'tipo' => 'rir',
                        'valor' => (int) $match[3],
                    ];
                } elseif (($match[4] ?? '') !== '') {
                    $bloques[] = [
                        'series' => (int) $match[1],
                        'reps' => (int) $match[2],
                        'tipo' => 'rpe',
                        'valor' => (int) $match[4],
                    ];
                } else {
                    $bloques[] = [
                        'series' => (int) $match[1],
                        'reps' => (int) $match[2],
                        'tipo' => 'fallo',
                        'valor' => null,
                    ];
                }
            }
        }

        return $bloques;
    }

    /**
     * Mini-sets del rest-pause (si las notas los describen).
     *
     * @param  array<int, array<string, mixed>>  $bloques
     * @return array{minisets: array<int, array<string, mixed>>}|null
     */
    public static function restPause(?string $notas, array $bloques = []): ?array
    {
        if (! $notas || ! preg_match('/rest[\s-]?pause/i', $notas)) {
            return null;
        }

        $segundos = [];
        if (preg_match_all(self::RE_SEGUNDOS_FALLO, $notas, $m)) {
            foreach ($m[1] as $s) {
                $segundos[] = (int) $s;
            }
        }
        if (! $segundos && preg_match_all(self::RE_SEGUNDOS_DESCANSO, $notas, $m)) {
            foreach ($m[1] as $s) {
                $segundos[] = (int) $s;
            }
        }
        if (! $segundos) {
            return null;
        }

        // Reps del mini-set: explícitas ("5 reps por serie") o, por defecto,
        // las del set principal.
        $repsMini = $bloques ? (int) $bloques[0]['reps'] : 8;
        if (preg_match(self::RE_REPS_MINISET, $notas, $m)) {
            $repsMini = (int) $m[1];
        }

        $minisets = [];
        foreach ($segundos as $seg) {
            $minisets[] = [
                'reps' => $repsMini,
                'descanso_seg' => $seg,
                'tipo' => 'fallo',
                'valor' => null,
            ];
        }

        return ['minisets' => $minisets];
    }

    /**
     * Frase de progresión ("sumar 2,5 kg al completar 3x8").
     */
    public static function progresion(?string $notas): ?string
    {
        if (! $notas) {
            return null;
        }

        $frases = [];
        foreach (self::frases($notas) as $frase) {
            if (preg_match(self::RE_PROGRESION, $frase)) {
                $frases[] = $frase;
            }
        }

        return $frases ? implode(' ', $frases) : null;
    }

    /**
     * Texto que sobra de las notas una vez extraídos bloques, progresión y
     * mecánica del rest-pause. Es el "consejo" (técnica / condiciones).
     */
    public static function consejo(?string $notas): ?string
    {
        if (! $notas) {
            return null;
        }

        $progresion = self::progresion($notas);

        $limpio = $notas;
        $limpio = self::reemplazar('/\([^)]*(?:fallo|descanso|reps)[^)]*\)/iu', $limpio);
        $limpio = self::reemplazar(self::RE_BLOQUE, $limpio);
        $limpio = self::reemplazar(self::RE_SEGUNDOS_FALLO, $limpio);
        $limpio = self::reemplazar(self::RE_SEGUNDOS_DESCANSO, $limpio);
        $limpio = self::reemplazar(self::RE_REPS_MINISET, $limpio);
        $limpio = self::reemplazar('/\b\d+\s*reps?\b/iu', $limpio);
        $limpio = self::reemplazar('/rest[\s-]?pause\s*(?:final|intra-set)?\s*:?/iu', $limpio);
        $limpio = self::reemplazar('/cada\s+serie\s*=\s*1\s*set\s+de/iu', $limpio);
        $limpio = self::reemplazar('/\(\s*\d+\s*por\s+lado\s*\)/iu', $limpio);
        $limpio = self::reemplazar('/\bdescanso\b/iu', $limpio);
        $limpio = self::reemplazar('/\b(bloques?|progresiv[oa])\b\s*:?/iu', $limpio);
        $limpio = self::reemplazar('/[+·|→()]/u', $limpio);
        $limpio = self::reemplazar('/\s{2,}/u', $limpio);
        // Limpia el espacio SÓLO antes de la puntuación (" . " → "."),
        // preservando el espacio después ("RIR fijo, no busques").
        $limpio = self::reemplazar('/\s+([.,;:])/u', $limpio, '$1');
        $limpio = trim($limpio);

        if ($progresion !== null) {
            $limpio = str_ireplace($progresion, ' ', $limpio);
            $limpio = self::reemplazar('/\s{2,}/u', $limpio);
            $limpio = trim($limpio);
        }

        // Limpia la puntuación suelta de los bordes, pero conserva el punto
        // final de la frase ("... la lumbar.").
        $limpio = preg_replace('/^[.·\s,;:]+/u', '', $limpio);
        $limpio = preg_replace('/[\s,;:]+$/u', '', $limpio);
        $limpio = trim((string) $limpio);

        if ($limpio === '' || mb_strlen($limpio) < 4) {
            return null;
        }
        if (preg_match('/^(rir\s*fijo|n\/?a|sin\s+rir|final)$/iu', $limpio)) {
            return null;
        }

        return $limpio;
    }

    /**
     * Etiqueta corta del tipo de bloque.
     */
    public static function etiqueta(?string $notas): ?string
    {
        if (! $notas) {
            return null;
        }

        if (preg_match('/bloque\s+progresivo/i', $notas)) {
            return 'Bloque progresivo';
        }
        if (preg_match('/rest[\s-]?pause/i', $notas)) {
            return 'Rest-pause';
        }

        return 'Bloque';
    }

    /**
     * ¿La prescripción es coherente con el número de series del ejercicio?
     * (suma de bloques == series)
     */
    public static function esConsistente(?array $prescripcion, int $series): bool
    {
        if (! $prescripcion || empty($prescripcion['bloques'])) {
            return false;
        }

        $suma = 0;
        foreach ($prescripcion['bloques'] as $bloque) {
            $suma += (int) ($bloque['series'] ?? 0);
        }

        return $suma === $series;
    }

    /**
     * Divide las notas en frases.
     *
     * @return array<int, string>
     */
    protected static function frases(string $notas): array
    {
        $partes = preg_split('/(?<=[.!?])\s+/u', $notas) ?: [];

        return array_values(array_filter(array_map('trim', $partes), fn ($f) => $f !== ''));
    }

    /**
     * preg_replace que devuelve string (nunca null) y respeta el modificador /u.
     */
    protected static function reemplazar(string $pattern, string $subject, string $reemplazo = ' '): string
    {
        $resultado = preg_replace($pattern, $reemplazo, $subject);

        return $resultado === null ? $subject : $resultado;
    }
}
