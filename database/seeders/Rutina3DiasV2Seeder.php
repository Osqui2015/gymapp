<?php

namespace Database\Seeders;

use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rutina "3 Días V2" — plan personal de 3 días para Oscar (nick: osqui).
 *
 * AGREGA el plan "Personalizada | 3 Días V2" — NO reemplaza nada.
 *
 * El plan con el que arrancó Oscar sigue intacto y visible en su perfil,
 * renombrado a "3 Días v1" (22 ejercicios) para poder diferenciarlo de la
 * versión mejorada (24 ejercicios, esta). Ambos conviven en la app: el
 * usuario activa la que quiera desde /rutinas (RutinasAccordion.vue ->
 * POST /api/user-rutina), que sólo mueve el puntero user_rutinas.rutina_id.
 *
 * La v1 se armó sin volumen ni progresión suficientes (4-6 series por sesión
 * en setiembre, 34-35 series de golpe en octubre) y el resultado fue 45% de
 * adherencia en 7 semanas.
 *
 * Criterios de diseño de la v2:
 *
 *  1. Volumen por sesión acotado a 26 series (la v1 llegaba a 34-35).
 *     Objetivo: sesión de 60-75 minutos, 3 veces por semana.
 *  2. Sentadilla + bisagra de cadera 2 veces por semana. En la v1 la
 *     sentadilla se tocó 1 sola vez en 7 semanas.
 *  3. Espalda real: jalón + remo + dominadas = 14 series/semana
 *     (la v1 dejaba la espalda en ~5).
 *  4. Técnicas (las pidió Oscar):
 *       - Bloques "2xN RIR 1 + 2xM RIR 0" en compuestos.
 *       - Rest-pause con mini-series de 5" y 10" entre ellas.
 *       - Fallo SOLO en aislamiento y en la última serie del bloque.
 *         Nunca al fallo en sentadilla, press de banca ni press militar.
 *  5. Abdominales los 3 días: un ejercicio por día con patrón distinto
 *     (crunch / bicicleta / polea), siempre al final de la sesión y con
 *     RIR 1 fijo. Nunca al fallo: la flexión repetida con fatiga es lo que
 *     factura la lumbar, y ese día carga sentadilla y bisagra de cadera.
 *     Se usan ejercicios por REPS (no plancha) porque el modelo guarda
 *     reps y el front los renderiza tal cual ("40 reps" para una plancha
 *     de 40 segundos sería un dato mentiroso).
 *
 * Las metas de esfuerzo viven en DOS lugares, y ambos se mantienen en sync:
 *
 *  1. `prescripcion` (JSON, columna estructurada) — fuente de verdad para el
 *     front nuevo. Por ejercicio:
 *       - `bloques`: lista ordenada, cada uno {series, reps, tipo, valor}
 *         con `tipo` = rir | rpe | fallo. La SUMA de `series` de los bloques
 *         tiene que ser EXACTAMENTE la columna `series` de la fila.
 *       - `rest_pause`: {minisets: [{reps, descanso_seg, tipo, valor}]} o
 *         null. `descanso_seg` es el descanso ANTES de ese mini-set.
 *       - `progresion` / `consejo` / `etiqueta`: texto libre (null si no aplica).
 *  2. `notas` (texto libre) — se mantiene porque el front actual
 *     (SetConfigCard.vue) parsea la prosa con el regex:
 *       /(\d+)\s*[xX\*]\s*(\d+)\s*(?:R[I1][RP]\s*(\d+)|RPE\s*(\d+)|(?:AL\s+)?FALLO(?:\s+T[ÉE]CNICO)?)/gi
 *     o sea: "2x6 RIR 1", "2*6 RIP 1", "2x8 FALLO", "2x8 AL FALLO", "1x20 AL FALLO TÉCNICO".
 *     Los bloques de `prescripcion` son los mismos que aparecen en las notas.
 *
 * El campo `series` debe coincidir con la SUMA de las series de los bloques
 * de la nota, si no el front reparte mal los objetivos por serie.
 *
 * Idempotente: borra las filas previas de esta modalidad para este usuario y
 * las vuelve a crear dentro de una transacción. Al final apunta
 * `user_rutinas.rutina_id` al primer ejercicio del plan, que es como la app
 * resuelve la rutina activa (UserRutinaController::ejerciciosActivos lee
 * nivel/modalidad/created_by desde esa fila).
 *
 * Uso:
 *   php artisan db:seed --class=Rutina3DiasV2Seeder
 *
 * Override del usuario destino (producción):
 *   RUTINA_V2_USER_NICK=osqui php artisan db:seed --class=Rutina3DiasV2Seeder
 */
class Rutina3DiasV2Seeder extends Seeder
{
    /** Modalidad del plan (se muestra como "Personalizada | 3 Días V2"). */
    public const MODALIDAD = '3 Días V2';

    /** Días del plan, en orden. */
    private const DIAS = [
        'Día 1 (Torso)',
        'Día 2 (Pierna)',
        'Día 3 (Full Body)',
    ];

    /**
     * Ejercicios del plan.
     *
     * Cada día: [orden, nombre, series, reps_min, reps_max, descanso_min, notas]
     */
    private function ejercicios(): array
    {
        return [
            'Día 1 (Torso)' => [
                [
                    'orden' => 1,
                    'ejercicio_nombre' => 'Press de banca',
                    'series' => 4,
                    'reps_min' => '4',
                    'reps_max' => '6',
                    'descanso_min' => 2.0,
                    'notas' => 'Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 6, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 2, 'reps' => 4, 'tipo' => 'rir', 'valor' => 0],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque progresivo',
                    ],
                ],
                [
                    'orden' => 2,
                    'ejercicio_nombre' => 'Press inclinado',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '8',
                    'descanso_min' => 1.5,
                    'notas' => 'Rest-pause: 3x8 RIR 1. Cada serie = 1 set de 8 reps, descanso 5" y fallo, descanso 10" y fallo.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 8, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => [
                            'minisets' => [
                                ['reps' => 8, 'descanso_seg' => 5, 'tipo' => 'fallo', 'valor' => null],
                                ['reps' => 8, 'descanso_seg' => 10, 'tipo' => 'fallo', 'valor' => null],
                            ],
                        ],
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Rest-pause',
                    ],
                ],
                [
                    'orden' => 3,
                    'ejercicio_nombre' => 'Jalón al pecho',
                    'series' => 4,
                    'reps_min' => '8',
                    'reps_max' => '10',
                    'descanso_min' => 1.5,
                    'notas' => 'Bloque progresivo: 2x10 RIR 1 + 2x8 RIR 0.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 2, 'reps' => 8, 'tipo' => 'rir', 'valor' => 0],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque progresivo',
                    ],
                ],
                [
                    'orden' => 4,
                    'ejercicio_nombre' => 'Remo sentado',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '8',
                    'descanso_min' => 1.5,
                    'notas' => 'Rest-pause: 3x8 RIR 1. Cada serie = 1 set de 8 reps, descanso 5" y fallo, descanso 10" y fallo.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 8, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => [
                            'minisets' => [
                                ['reps' => 8, 'descanso_seg' => 5, 'tipo' => 'fallo', 'valor' => null],
                                ['reps' => 8, 'descanso_seg' => 10, 'tipo' => 'fallo', 'valor' => null],
                            ],
                        ],
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Rest-pause',
                    ],
                ],
                [
                    'orden' => 5,
                    'ejercicio_nombre' => 'Dominadas',
                    'series' => 3,
                    'reps_min' => '6',
                    'reps_max' => '8',
                    'descanso_min' => 1.5,
                    'notas' => 'Bloque progresivo: 3x8 RIR 1. Al completar 3x8 con bodyweight, sumar 2,5 kg de lastre.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 8, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Al completar 3x8 con bodyweight, sumar 2,5 kg de lastre.',
                        'consejo' => null,
                        'etiqueta' => 'Bloque progresivo',
                    ],
                ],
                [
                    'orden' => 6,
                    'ejercicio_nombre' => 'Curl de bíceps',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '10',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x8 FALLO.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 8, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 7,
                    'ejercicio_nombre' => 'Extensión de tríceps sobre la cabeza',
                    'series' => 3,
                    'reps_min' => '10',
                    'reps_max' => '12',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x12 RIR 1 + 1x12 FALLO.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 12, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 12, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 8,
                    'ejercicio_nombre' => 'Crunch abdominal',
                    'series' => 3,
                    'reps_min' => '12',
                    'reps_max' => '15',
                    'descanso_min' => 0.5,
                    'notas' => '3x15 RIR 1. RIR fijo, no busques el fallo: flexión repetida con fatiga es lo que factura la lumbar.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 15, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => 'RIR fijo, no busques el fallo: flexión repetida con fatiga es lo que factura la lumbar.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
            ],

            'Día 2 (Pierna)' => [
                [
                    'orden' => 1,
                    'ejercicio_nombre' => 'Sentadilla con barra libre',
                    'series' => 4,
                    'reps_min' => '4',
                    'reps_max' => '6',
                    'descanso_min' => 2.5,
                    'notas' => 'Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 6, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 2, 'reps' => 4, 'tipo' => 'rir', 'valor' => 0],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque progresivo',
                    ],
                ],
                [
                    'orden' => 2,
                    'ejercicio_nombre' => 'Peso muerto rumano',
                    'series' => 4,
                    'reps_min' => '6',
                    'reps_max' => '8',
                    'descanso_min' => 2.0,
                    'notas' => 'Bloque progresivo: 2x8 RIR 1 + 2x6 RIR 0. Si esa semana jugaste fútbol con golpeo fuerte, reduci la carga 20%.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 8, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 2, 'reps' => 6, 'tipo' => 'rir', 'valor' => 0],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => 'Si esa semana jugaste fútbol con golpeo fuerte, reduci la carga 20%.',
                        'etiqueta' => 'Bloque progresivo',
                    ],
                ],
                [
                    'orden' => 3,
                    'ejercicio_nombre' => 'Prensa de piernas 45 grados',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '10',
                    'descanso_min' => 1.5,
                    'notas' => 'Bloque progresivo: 3x10 RIR 1.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque progresivo',
                    ],
                ],
                [
                    'orden' => 4,
                    'ejercicio_nombre' => 'Hip thrust',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '10',
                    'descanso_min' => 1.5,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x10 FALLO.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 10, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 5,
                    'ejercicio_nombre' => 'Extensión de cuádriceps',
                    'series' => 3,
                    'reps_min' => '12',
                    'reps_max' => '12',
                    'descanso_min' => 1.0,
                    'notas' => 'Rest-pause: 3x12 RIR 1. Cada serie = 1 set de 12 reps, descanso 5" y fallo, descanso 10" y fallo.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 12, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => [
                            'minisets' => [
                                ['reps' => 12, 'descanso_seg' => 5, 'tipo' => 'fallo', 'valor' => null],
                                ['reps' => 12, 'descanso_seg' => 10, 'tipo' => 'fallo', 'valor' => null],
                            ],
                        ],
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Rest-pause',
                    ],
                ],
                [
                    'orden' => 6,
                    'ejercicio_nombre' => 'Curl de isquiotibiales acostado',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '10',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x10 FALLO.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 10, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 7,
                    'ejercicio_nombre' => 'Elevación de talones de pie',
                    'series' => 3,
                    'reps_min' => '15',
                    'reps_max' => '15',
                    'descanso_min' => 1.0,
                    'notas' => '3x15 RIR 1.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 15, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 8,
                    'ejercicio_nombre' => 'Crunch bicicleta',
                    'series' => 3,
                    'reps_min' => '12',
                    'reps_max' => '16',
                    'descanso_min' => 0.5,
                    'notas' => '3x16 RIR 1 (12 por lado). RIR fijo: flexión con fatiga es lo que factura la lumbar.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 16, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => 'RIR fijo: flexión con fatiga es lo que factura la lumbar.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
            ],

            'Día 3 (Full Body)' => [
                [
                    'orden' => 1,
                    'ejercicio_nombre' => 'Press militar de pie',
                    'series' => 4,
                    'reps_min' => '4',
                    'reps_max' => '6',
                    'descanso_min' => 2.0,
                    'notas' => 'Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 6, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 2, 'reps' => 4, 'tipo' => 'rir', 'valor' => 0],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque progresivo',
                    ],
                ],
                [
                    'orden' => 2,
                    'ejercicio_nombre' => 'Sentadilla Goblet',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '10',
                    'descanso_min' => 1.5,
                    'notas' => '3x10 RIR 1. Bajada controlada, sin rebote.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => 'Bajada controlada, sin rebote.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 3,
                    'ejercicio_nombre' => 'Remo en polea baja',
                    'series' => 4,
                    'reps_min' => '8',
                    'reps_max' => '10',
                    'descanso_min' => 1.5,
                    'notas' => 'Bloque progresivo: 2x10 RIR 1 + 2x8 RIR 0.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 2, 'reps' => 8, 'tipo' => 'rir', 'valor' => 0],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque progresivo',
                    ],
                ],
                [
                    'orden' => 4,
                    'ejercicio_nombre' => 'Press de banca',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '8',
                    'descanso_min' => 1.5,
                    'notas' => '3x8 RIR 1.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 8, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 5,
                    'ejercicio_nombre' => 'Elevaciones laterales con polea',
                    'series' => 3,
                    'reps_min' => '12',
                    'reps_max' => '15',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x15 RIR 1 + 1x15 FALLO.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 15, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 15, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 6,
                    'ejercicio_nombre' => 'Curl martillo',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '10',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x10 FALLO.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 10, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 7,
                    'ejercicio_nombre' => 'Extensión de tríceps sobre la cabeza',
                    'series' => 3,
                    'reps_min' => '12',
                    'reps_max' => '12',
                    'descanso_min' => 1.0,
                    'notas' => 'Rest-pause: 3x12 RIR 1. Cada serie = 1 set de 12 reps, descanso 5" y fallo, descanso 10" y fallo.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 12, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => [
                            'minisets' => [
                                ['reps' => 12, 'descanso_seg' => 5, 'tipo' => 'fallo', 'valor' => null],
                                ['reps' => 12, 'descanso_seg' => 10, 'tipo' => 'fallo', 'valor' => null],
                            ],
                        ],
                        'progresion' => null,
                        'consejo' => null,
                        'etiqueta' => 'Rest-pause',
                    ],
                ],
                [
                    'orden' => 8,
                    'ejercicio_nombre' => 'Crunch abdominal con polea',
                    'series' => 3,
                    'reps_min' => '10',
                    'reps_max' => '15',
                    'descanso_min' => 1.0,
                    'notas' => '3x12 RIR 1. Es el que progresa con carga: subir 2,5 kg al completar 3x12 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 12, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Es el que progresa con carga: subir 2,5 kg al completar 3x12 con técnica limpia.',
                        'consejo' => null,
                        'etiqueta' => 'Bloque',
                    ],
                ],
            ],
        ];
    }

    public function run(): void
    {
        $nick = env('RUTINA_V2_USER_NICK', 'osqui');
        $user = User::where('nick', $nick)->first();

        if (! $user) {
            $this->command->error("No se encontró el usuario con nick '{$nick}'. Abortando.");
            $this->command->line('Override con: RUTINA_V2_USER_NICK=<nick> php artisan db:seed --class=Rutina3DiasV2Seeder');

            return;
        }

        $ejerciciosPorDia = $this->ejercicios();

        $resultado = DB::transaction(function () use ($user, $ejerciciosPorDia) {
            // Limpieza idempotente del plan anterior (sólo esta modalidad).
            Rutina::where('created_by', $user->id)
                ->where('nivel', 'Personalizada')
                ->where('modalidad', self::MODALIDAD)
                ->delete();

            $total = 0;

            foreach ($ejerciciosPorDia as $dia => $ejercicios) {
                foreach ($ejercicios as $ejercicio) {
                    Rutina::create(array_merge($ejercicio, [
                        'nivel' => 'Personalizada',
                        'modalidad' => self::MODALIDAD,
                        'dia' => $dia,
                        'created_by' => $user->id,
                        'publica' => false,
                    ]));
                    $total++;
                }
            }

            // Activar el plan: el puntero user_rutinas.rutina_id resuelve
            // nivel/modalidad/created_by de la fila a la que apunta.
            $primera = Rutina::where('created_by', $user->id)
                ->where('nivel', 'Personalizada')
                ->where('modalidad', self::MODALIDAD)
                ->where('dia', self::DIAS[0])
                ->orderBy('orden')
                ->first();

            UserRutina::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'rutina_id' => $primera->id,
                    'assigned_by' => null,
                    'dia_actual' => self::DIAS[0],
                    'ciclo_inicio' => now()->toDateString(),
                ]
            );

            return ['total' => $total, 'rutina_id' => $primera->id];
        });

        $this->command->info("Seeder 3 DÍAS V2: {$resultado['total']} ejercicios cargados para '{$nick}' (user {$user->id}).");
        $this->command->info("Plan activo: {$resultado['rutina_id']} | ".self::MODALIDAD.' | '.self::DIAS[0]);
        $this->command->line('La rutina anterior NO se borró ni se renombró: sigue disponible en tu perfil para volver atrás.');
    }
}
