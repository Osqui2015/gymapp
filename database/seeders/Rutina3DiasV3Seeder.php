<?php

namespace Database\Seeders;

use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use App\Services\RutinaPrescripcionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rutina "3 Días V3" — plan COMPLETO (Día 1 + Día 2 + Día 3).
 *
 * Origen: "Programa de Entrenamiento 3 Días (Actualizado).pdf" (25/10/2026).
 * Transcripción fiel de las tablas de prescripción del PDF:
 *
 *   DÍA 1 (Torso + Movilidad)  — 8 filas (movilidad + 7 ejercicios), 22 series
 *   DÍA 2 (Pierna + Core)      — 7 ejercicios, 22 series (SIN Peso Muerto Rumano)
 *   DÍA 3 (Full Body)          — 8 ejercicios, 26 series (Front Squat en vez de Goblet)
 *   Semana: 70 series (22 + 22 + 26), el objetivo del PDF.
 *
 * NOTA SOBRE EL DÍA 1: la tabla del PDF lista 6 ejercicios (19 series) pero su
 * propio encabezado dice "7 ejercicios + Movilidad (22 series)" y el dashboard
 * del documento totaliza abdomen 9 s (3 s por día). El core del Día 1 (Crunch
 * Abdominal 3x15 RIR 1 fijo, sin ir al fallo, al cierre de la sesión) estaba
 * fuera de la tabla y fue confirmado por Oscar el 26/10/2026: con él el Día 1
 * cierra en 22, la semana en 70 y el abdomen en 9, que son los números que
 * pide el propio PDF.
 *
 * Se crea como modalidad nueva ("Personalizada | 3 Días V3"): NO borra ni pisa
 * la V1 ("3 Días v1") ni la V2 ("3 Días V2"), que quedan disponibles para
 * volver atrás desde /rutinas.
 *
 * AJUSTES DE ESTA V3 RESPECTO DE LA V2 (los que pide el PDF):
 *   Día 1: se elimina el Jalón al Pecho, las Dominadas se adelantan al 3º
 *          lugar (3º ejercicio) y se agrega un bloque de movilidad de 5-10'.
 *   Día 2: se elimina el Peso Muerto Rumano (4 series) y se mantiene el resto.
 *   Día 3: la Sentadilla Goblet se reemplaza por la Sentadilla con Barra al
 *          Frente (Front Squat) 3x10 RIR 1.
 *
 * DOBLE FUENTE DE VERDAD (igual que la V2), ambas se mantienen en sincronía:
 *
 *  1. `prescripcion` (JSON, columna estructurada) — fuente de verdad para el
 *     front. Por ejercicio:
 *       - `bloques`: lista ordenada, cada uno {series, reps, tipo, valor}
 *         con `tipo` = rir | rpe | fallo. La SUMA de `series` de los bloques
 *         tiene que ser EXACTAMENTE la columna `series` de la fila.
 *       - `rest_pause`: {minisets: [{reps, descanso_seg, tipo, valor}]} o
 *         null. `descanso_seg` es el descanso ANTES de ese mini-set.
 *       - `progresion` / `consejo` / `etiqueta`: texto libre (null si no aplica).
 *  2. `notas` (texto libre) — el front también parsea la prosa con el regex
 *       /(\d+)\s*[xX\*]\s*(\d+)\s*(?:R[I1][RP]\s*(\d+)|RPE\s*(\d+)|(?:AL\s+)?FALLO(?:\s+T[ÉE]CNICO)?)/gi
 *     o sea: "2x6 RIR 1", "2x8 FALLO", "1x12 AL FALLO".
 *
 * El texto de `notas` está escrito para que el parseo dé EXACTAMENTE lo mismo
 * que la columna `prescripcion` (verificado con RutinaPrescripcionService).
 *
 * Idempotente: borra las filas previas de ESTA modalidad para este usuario y
 * las vuelve a crear dentro de una transacción.
 *
 * Uso:
 *   php artisan db:seed --class=Rutina3DiasV3Seeder
 *
 * Override del usuario destino:
 *   RUTINA_V3_USER_NICK=osqui php artisan db:seed --class=Rutina3DiasV3Seeder
 *
 * Activar la V3 como plan activo:
 *   RUTINA_V3_ACTIVAR=1 php artisan db:seed --class=Rutina3DiasV3Seeder
 */
class Rutina3DiasV3Seeder extends Seeder
{
    /** Modalidad del plan (se muestra como "Personalizada | 3 Días V3"). */
    public const MODALIDAD = '3 Días V3';

    /** Nivel: plan personal (no entra al catálogo comunitario). */
    public const NIVEL = 'Personalizada';

    /** Días del plan, en orden. */
    public const DIAS = [
        'Día 1 (Torso)',
        'Día 2 (Pierna)',
        'Día 3 (Full Body)',
    ];

    /**
     * Ejercicios del plan, agrupados por día.
     *
     * Cada ejercicio: [orden, ejercicio_nombre, series, reps_min, reps_max,
     *                  descanso_min, notas, prescripcion]
     */
    private function ejercicios(): array
    {
        return [
            // =============================================================
            // DÍA 1 — TORSO + MOVILIDAD
            // =============================================================
            'Día 1 (Torso)' => [
                [
                    // BLOQUE informative: la movilidad NO es una serie de
                    // trabajo, es un protocolo de 5 a 10 minutos. Por eso va
                    // con series = 0: el front lo muestra como "Bloque de
                    // calentamiento", no cuenta para el total de series del
                    // día (22, como el PDF) y no bloquea el avance: al
                    // registrar el calentamiento la app pasa al siguiente
                    // ejercicio.
                    'orden' => 1,
                    'ejercicio_nombre' => 'Movilidad articular y activación',
                    'series' => 0,
                    'reps_min' => '1',
                    'reps_max' => '1',
                    'descanso_min' => 0.0,
                    'notas' => 'Bloque de movilidad articular y activación (agregado inicial): 5 a 10 minutos continuos, fluido y sin fatiga muscular. Enfoque en el complejo articular del hombro, la columna torácica y las escápulas. Dislocaciones de hombro con banda elástica o bastón, rotaciones torácicas en cuadrupedia, retracciones escapulares colgado en barra (scapular pulls) y rotaciones externas para el manguito rotador antes de tocar la barra. No es una serie de trabajo: hacelo antes de la barra.',
                    'prescripcion' => null,
                ],
                [
                    'orden' => 2,
                    'ejercicio_nombre' => 'Press de banca',
                    'series' => 4,
                    'reps_min' => '4',
                    'reps_max' => '6',
                    'descanso_min' => 2.0,
                    'notas' => 'Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0. Decúbito supino en banco plano, glúteos fijos y pies firmes en el suelo, agarre en pronación a una anchura superior a los hombros. Inspirar y bajar la barra de forma controlada hasta el esternón ensanchando el tórax, y empujar verticalmente espirando al final sin perder estabilidad escapular. Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 6, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 2, 'reps' => 4, 'tipo' => 'rir', 'valor' => 0],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                        'consejo' => 'Decúbito supino en banco plano, glúteos fijos y pies firmes en el suelo, agarre en pronación a una anchura superior a los hombros. Inspirar y bajar la barra de forma controlada hasta el esternón ensanchando el tórax, y empujar verticalmente espirando al final sin perder estabilidad escapular.',
                        'etiqueta' => 'Bloque progresivo',
                    ],
                ],
                [
                    'orden' => 3,
                    'ejercicio_nombre' => 'Press inclinado',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '8',
                    'descanso_min' => 1.5,
                    'notas' => 'Rest-pause: 3x8 RIR 1. Cada serie = 1 set de 8 reps, descanso 5" y fallo, descanso 10" y fallo. Sentado en banco inclinado a 45°-60°, agarre pronado, bajar la carga hacia la parte superior del pecho manteniendo codos en ángulo de ~45° respecto al torso y empujar hacia arriba extendiendo brazos sin hiperextender los hombros. Subir 2,5 kg al completar las 3 series en RIR 1 con técnica limpia.',
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
                        'progresion' => 'Subir 2,5 kg al completar las 3 series en RIR 1 con técnica limpia.',
                        'consejo' => 'Sentado en banco inclinado a 45°-60°, agarre pronado, bajar la carga hacia la parte superior del pecho manteniendo codos en ángulo de ~45° respecto al torso y empujar hacia arriba extendiendo brazos sin hiperextender los hombros.',
                        'etiqueta' => 'Rest-pause',
                    ],
                ],
                [
                    'orden' => 4,
                    'ejercicio_nombre' => 'Dominadas',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '8',
                    'descanso_min' => 1.5,
                    'notas' => '3x8 RIR 1. En suspensión en barra fija con manos separadas en pronación, elevar el cuerpo hasta que la barbilla supere la barra y descender con control. Es el primer ejercicio de espalda tras los empujes, se eliminó el jalón al pecho, para ejecutarlo con los dorsales frescos. Sumar 2,5 kg de lastre al completar las 3 series con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 8, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar 2,5 kg de lastre al completar las 3 series con técnica limpia.',
                        'consejo' => 'En suspensión en barra fija con manos separadas en pronación, elevar el cuerpo hasta que la barbilla supere la barra y descender con control. Es el primer ejercicio de espalda tras los empujes, se eliminó el jalón al pecho, para ejecutarlo con los dorsales frescos.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 5,
                    'ejercicio_nombre' => 'Remo sentado',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '8',
                    'descanso_min' => 1.5,
                    'notas' => 'Rest-pause: 3x8 RIR 1. Cada serie = 1 set de 8 reps, descanso 5" y fallo, descanso 10" y fallo. Sentado con pies apoyados y tronco erguido, traccionar el agarre hacia la base del abdomen inferior dirigiendo los codos hacia atrás, juntar omóplatos con retracción escapular al final sin arquear excesivamente la zona lumbar. Subir 2,5 kg al completar las 3 series en RIR 1 con técnica limpia.',
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
                        'progresion' => 'Subir 2,5 kg al completar las 3 series en RIR 1 con técnica limpia.',
                        'consejo' => 'Sentado con pies apoyados y tronco erguido, traccionar el agarre hacia la base del abdomen inferior dirigiendo los codos hacia atrás, juntar omóplatos con retracción escapular al final sin arquear excesivamente la zona lumbar.',
                        'etiqueta' => 'Rest-pause',
                    ],
                ],
                [
                    'orden' => 6,
                    'ejercicio_nombre' => 'Curl de bíceps',
                    'series' => 3,
                    'reps_min' => '8',
                    'reps_max' => '10',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x8 FALLO. De pie o sentado, tronco erguido y codos pegados a los costados, flexionar codos iniciando con manos en semipronación y realizando supinación durante la elevación, evitando el balanceo del torso. Sumar 1 repetición por serie antes de subir peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 8, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar 1 repetición por serie antes de subir peso.',
                        'consejo' => 'De pie o sentado, tronco erguido y codos pegados a los costados, flexionar codos iniciando con manos en semipronación y realizando supinación durante la elevación, evitando el balanceo del torso.',
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
                    'notas' => 'Bloque: 2x12 RIR 1 + 1x12 FALLO. Sentado o de pie, brazos fijos en posición vertical junto a la cabeza, flexionar codos descendiendo la carga por detrás de la nuca para lograr el estiramiento de la porción larga y extender codos hacia arriba. Sumar 1 repetición por serie antes de subir peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 12, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 12, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar 1 repetición por serie antes de subir peso.',
                        'consejo' => 'Sentado o de pie, brazos fijos en posición vertical junto a la cabeza, flexionar codos descendiendo la carga por detrás de la nuca para lograr el estiramiento de la porción larga y extender codos hacia arriba.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    // Core del Día 1, AL CIERRE de la sesión (3x15 RIR 1 fijo,
                    // sin ir al fallo). Confirmado por Oscar el 26/10/2026 con
                    // la técnica de la tabla del programa.
                    //
                    // La tabla del PDF NO lo lista, pero el resto del documento
                    // lo exige: el encabezado del Día 1 dice "7 ejercicios +
                    // Movilidad (22 series)" y la tabla tiene 6 (19 series);
                    // el dashboard totaliza abdomen 9 s (3 s por día) y el
                    // documento fija el trabajo abdominal "al cierre de cada
                    // sesión". Con este core el Día 1 cierra en 22, la semana
                    // en 70 y el abdomen en 9, los tres números del PDF.
                    'orden' => 8,
                    'ejercicio_nombre' => 'Crunch abdominal',
                    'series' => 3,
                    'reps_min' => '15',
                    'reps_max' => '15',
                    'descanso_min' => 0.5,
                    'notas' => '3x15 RIR 1. Decúbito supino, rodillas flexionadas a 90°. Espirar e incurvar la columna aproximando el esternón al pubis mediante flexión de tronco. Despegar únicamente escápulas del suelo sin tirar de la nuca. Sin ir al fallo: la flexión repetida con fatiga es lo que factura la lumbar. Sumar 1 repetición por serie antes de subir peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 15, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar 1 repetición por serie antes de subir peso.',
                        'consejo' => 'Decúbito supino, rodillas flexionadas a 90°. Espirar e incurvar la columna aproximando el esternón al pubis mediante flexión de tronco. Despegar únicamente escápulas del suelo sin tirar de la nuca. Sin ir al fallo: la flexión repetida con fatiga es lo que factura la lumbar.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
            ],

            // =============================================================
            // DÍA 2 — PIERNA + CORE (SIN Peso Muerto Rumano)
            // =============================================================
            'Día 2 (Pierna)' => [
                [
                    'orden' => 1,
                    'ejercicio_nombre' => 'Sentadilla con barra libre',
                    'series' => 4,
                    'reps_min' => '4',
                    'reps_max' => '6',
                    'descanso_min' => 2.5,
                    'notas' => 'Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0. Barra apoyada sobre los trapecios y pies a anchura de hombros. Inspirar profundamente, mantener presión intraabdominal y flexionar cadera y rodillas descendiendo con tronco recto hasta la horizontal, empujando desde el suelo extendiendo rodillas y cadera. Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 6, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 2, 'reps' => 4, 'tipo' => 'rir', 'valor' => 0],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                        'consejo' => 'Barra apoyada sobre los trapecios y pies a anchura de hombros. Inspirar profundamente, mantener presión intraabdominal y flexionar cadera y rodillas descendiendo con tronco recto hasta la horizontal, empujando desde el suelo extendiendo rodillas y cadera.',
                        'etiqueta' => 'Bloque progresivo',
                    ],
                ],
                [
                    'orden' => 2,
                    'ejercicio_nombre' => 'Prensa de piernas 45 grados',
                    'series' => 3,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.5,
                    'notas' => '3x10 RIR 1. Espalda y pelvis totalmente apoyadas en el respaldo, pies a anchura media en la plataforma. Flexionar rodillas hasta llevar los muslos cerca de la caja torácica sin despegar la pelvis y empujar la plataforma hasta la extensión casi completa. Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                        'consejo' => 'Espalda y pelvis totalmente apoyadas en el respaldo, pies a anchura media en la plataforma. Flexionar rodillas hasta llevar los muslos cerca de la caja torácica sin despegar la pelvis y empujar la plataforma hasta la extensión casi completa.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 3,
                    'ejercicio_nombre' => 'Hip thrust',
                    'series' => 3,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.5,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x10 FALLO. Espalda apoyada en banco a nivel escapular y barra acolchada sobre la cresta ilíaca, flexionar cadera descendiendo la pelvis y empujar fuertemente con los talones hasta alcanzar extensión completa de cadera con retroversión pélvica. Sumar 1 repetición por serie antes de subir peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 10, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar 1 repetición por serie antes de subir peso.',
                        'consejo' => 'Espalda apoyada en banco a nivel escapular y barra acolchada sobre la cresta ilíaca, flexionar cadera descendiendo la pelvis y empujar fuertemente con los talones hasta alcanzar extensión completa de cadera con retroversión pélvica.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 4,
                    'ejercicio_nombre' => 'Extensión de cuádriceps',
                    'series' => 3,
                    'reps_min' => '12',
                    'reps_max' => '12',
                    'descanso_min' => 1.0,
                    'notas' => 'Rest-pause: 3x12 RIR 1. Cada serie = 1 set de 12 reps, descanso 5" y fallo, descanso 10" y fallo. Sentado en máquina con espalda fija y tobillos bajo el rodillo, extender rodillas hasta la horizontal manteniendo 1 segundo de contracción isométrica arriba y bajar de forma controlada. Sumar 1 repetición por serie antes de subir peso.',
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
                        'progresion' => 'Sumar 1 repetición por serie antes de subir peso.',
                        'consejo' => 'Sentado en máquina con espalda fija y tobillos bajo el rodillo, extender rodillas hasta la horizontal manteniendo 1 segundo de contracción isométrica arriba y bajar de forma controlada.',
                        'etiqueta' => 'Rest-pause',
                    ],
                ],
                [
                    'orden' => 5,
                    'ejercicio_nombre' => 'Curl de isquiotibiales acostado',
                    'series' => 3,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x10 FALLO. Decúbito prono con apófisis maleolares por debajo del rodillo, flexionar ambas rodillas intentando llevar los talones hacia los glúteos sin levantar la pelvis del banco. Espirar en flexión y controlar la fase negativa. Sumar 1 repetición por serie antes de subir peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 10, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar 1 repetición por serie antes de subir peso.',
                        'consejo' => 'Decúbito prono con apófisis maleolares por debajo del rodillo, flexionar ambas rodillas intentando llevar los talones hacia los glúteos sin levantar la pelvis del banco. Espirar en flexión y controlar la fase negativa.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 6,
                    'ejercicio_nombre' => 'Elevación de talones de pie',
                    'series' => 3,
                    'reps_min' => '15',
                    'reps_max' => '15',
                    'descanso_min' => 1.0,
                    'notas' => '3x15 RIR 1. De pie con apófisis metatarsiana apoyada en escalón y hombros bajo cojines, realizar flexión plantar completa elevando los talones al máximo y bajar de forma controlada permitiendo estiramiento completo. Sumar 1 repetición por serie antes de subir peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 15, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar 1 repetición por serie antes de subir peso.',
                        'consejo' => 'De pie con apófisis metatarsiana apoyada en escalón y hombros bajo cojines, realizar flexión plantar completa elevando los talones al máximo y bajar de forma controlada permitiendo estiramiento completo.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 7,
                    'ejercicio_nombre' => 'Crunch bicicleta',
                    'series' => 3,
                    'reps_min' => '16',
                    'reps_max' => '16',
                    'descanso_min' => 0.5,
                    'notas' => '3x16 RIR 1 (8 por lado). Decúbito supino con manos tras la nuca, alternar flexión y rotación de tronco acercando el codo a la rodilla contraria mientras la otra pierna se extiende casi paralela al suelo, con movimiento fluido y sin tirones. Sin ir al fallo: la flexión repetida con fatiga es lo que factura la lumbar. Sumar 1 repetición por serie antes de subir peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 16, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar 1 repetición por serie antes de subir peso.',
                        'consejo' => 'Decúbito supino con manos tras la nuca, alternar flexión y rotación de tronco acercando el codo a la rodilla contraria mientras la otra pierna se extiende casi paralela al suelo, con movimiento fluido y sin tirones. Sin ir al fallo: la flexión repetida con fatiga es lo que factura la lumbar.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
            ],

            // =============================================================
            // DÍA 3 — FULL BODY (Front Squat en vez de Goblet)
            // =============================================================
            'Día 3 (Full Body)' => [
                [
                    'orden' => 1,
                    'ejercicio_nombre' => 'Press militar de pie',
                    'series' => 4,
                    'reps_min' => '4',
                    'reps_max' => '6',
                    'descanso_min' => 2.0,
                    'notas' => 'Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0. Barra apoyada en la parte alta del pecho con agarre algo mayor al ancho de hombros, abdomen y glúteos contraídos, desarrollo vertical hasta extensión de codos. Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 6, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 2, 'reps' => 4, 'tipo' => 'rir', 'valor' => 0],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                        'consejo' => 'Barra apoyada en la parte alta del pecho con agarre algo mayor al ancho de hombros, abdomen y glúteos contraídos, desarrollo vertical hasta extensión de codos.',
                        'etiqueta' => 'Bloque progresivo',
                    ],
                ],
                [
                    'orden' => 2,
                    'ejercicio_nombre' => 'Sentadilla frontal',
                    'series' => 3,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.5,
                    'notas' => '3x10 RIR 1. Reemplaza a la Sentadilla Goblet: barra apoyada sobre los deltoides anteriores, codos altos y paralelos al suelo, torso totalmente vertical en la sentadilla profunda. Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                        'consejo' => 'Reemplaza a la Sentadilla Goblet: barra apoyada sobre los deltoides anteriores, codos altos y paralelos al suelo, torso totalmente vertical en la sentadilla profunda.',
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
                    'notas' => 'Bloque progresivo: 2x10 RIR 1 + 2x8 RIR 0. Sentado con rodillas ligeramente flexionadas y tronco recto, traccionar el manubrio hacia la región umbilical juntando omóplatos y manteniendo el torso estable. Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 2, 'reps' => 8, 'tipo' => 'rir', 'valor' => 0],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                        'consejo' => 'Sentado con rodillas ligeramente flexionadas y tronco recto, traccionar el manubrio hacia la región umbilical juntando omóplatos y manteniendo el torso estable.',
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
                    'notas' => '3x8 RIR 1. Decúbito supino en banco plano, pies firmes en el suelo, escápulas retraídas, descenso controlado de la barra hasta el esternón ensanchando el tórax y empuje vertical sin perder estabilidad escapular. Frecuencia 2 veces por semana, acumulativa con el Día 1. Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 8, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                        'consejo' => 'Decúbito supino en banco plano, pies firmes en el suelo, escápulas retraídas, descenso controlado de la barra hasta el esternón ensanchando el tórax y empuje vertical sin perder estabilidad escapular. Frecuencia 2 veces por semana, acumulativa con el Día 1.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 5,
                    'ejercicio_nombre' => 'Elevaciones laterales con polea',
                    'series' => 3,
                    'reps_min' => '15',
                    'reps_max' => '15',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x15 RIR 1 + 1x15 FALLO. De pie, agarrar polea baja cruzando el cable por delante o detrás del cuerpo, elevar el brazo lateralmente hasta la horizontal con codo de inflexión imperceptible y hombro deprimido. Sumar 1 repetición por serie antes de subir peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 15, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 15, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar 1 repetición por serie antes de subir peso.',
                        'consejo' => 'De pie, agarrar polea baja cruzando el cable por delante o detrás del cuerpo, elevar el brazo lateralmente hasta la horizontal con codo de inflexión imperceptible y hombro deprimido.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
                [
                    'orden' => 6,
                    'ejercicio_nombre' => 'Curl martillo',
                    'series' => 3,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x10 FALLO. De pie o sentado con mancuernas en agarre neutro, flexionar codos sin rotar las muñecas y mantener los codos inmóviles junto al torso. Sumar 1 repetición por serie antes de subir peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 10, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar 1 repetición por serie antes de subir peso.',
                        'consejo' => 'De pie o sentado con mancuernas en agarre neutro, flexionar codos sin rotar las muñecas y mantener los codos inmóviles junto al torso.',
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
                    'notas' => 'Rest-pause: 3x12 RIR 1. Cada serie = 1 set de 12 reps, descanso 5" y fallo, descanso 10" y fallo. Sujetar cuerda en polea baja sobre la cabeza, extender codos hacia arriba desde la posición de estiramiento posterior. Sumar 1 repetición por serie antes de subir peso.',
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
                        'progresion' => 'Sumar 1 repetición por serie antes de subir peso.',
                        'consejo' => 'Sujetar cuerda en polea baja sobre la cabeza, extender codos hacia arriba desde la posición de estiramiento posterior.',
                        'etiqueta' => 'Rest-pause',
                    ],
                ],
                [
                    'orden' => 8,
                    'ejercicio_nombre' => 'Crunch abdominal con polea',
                    'series' => 3,
                    'reps_min' => '12',
                    'reps_max' => '12',
                    'descanso_min' => 1.0,
                    'notas' => '3x12 RIR 1. De rodillas en polea alta sosteniendo la cuerda junto a las orejas, flexionar e incurvar la columna intentando aproximar el esternón al pubis manteniendo la cadera bloqueada. Subir 2,5 kg al completar 3x12 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 12, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Subir 2,5 kg al completar 3x12 con técnica limpia.',
                        'consejo' => 'De rodillas en polea alta sosteniendo la cuerda junto a las orejas, flexionar e incurvar la columna intentando aproximar el esternón al pubis manteniendo la cadera bloqueada.',
                        'etiqueta' => 'Bloque',
                    ],
                ],
            ],
        ];
    }

    public function run(): void
    {
        $nick = env('RUTINA_V3_USER_NICK', 'osqui');
        $user = User::where('nick', $nick)->first();

        if (! $user) {
            $this->command->error("No se encontró el usuario con nick '{$nick}'. Abortando.");
            $this->command->line('Override con: RUTINA_V3_USER_NICK=<nick> php artisan db:seed --class=Rutina3DiasV3Seeder');

            return;
        }

        $ejerciciosPorDia = $this->ejercicios();

        // Validación previa: la suma de las series de los bloques tiene que
        // coincidir EXACTAMENTE con la columna `series` (si no, el front
        // reparte mal los objetivos por serie). Los bloques sin prescripción
        // (el bloque de movilidad) se saltean.
        foreach ($ejerciciosPorDia as $dia => $ejercicios) {
            foreach ($ejercicios as $ejercicio) {
                if (empty($ejercicio['prescripcion'])) {
                    continue;
                }
                if (! RutinaPrescripcionService::esConsistente($ejercicio['prescripcion'], (int) $ejercicio['series'])) {
                    throw new \RuntimeException(
                        "Prescripción inconsistente en '{$dia} / {$ejercicio['ejercicio_nombre']}': ".
                        'la suma de los bloques no coincide con series = '.$ejercicio['series']
                    );
                }
            }
        }

        // Resolver el FK contra la biblioteca de ejercicios por nombre EXACTO.
        $catalogo = Ejercicio::pluck('id', 'nombre');
        $sinMatch = [];

        $resultado = DB::transaction(function () use ($user, $ejerciciosPorDia, $catalogo, &$sinMatch) {
            // Limpieza idempotente del plan anterior (sólo esta modalidad).
            Rutina::where('created_by', $user->id)
                ->where('nivel', self::NIVEL)
                ->where('modalidad', self::MODALIDAD)
                ->delete();

            $total = 0;
            $seriePorDia = [];
            $totales = ['rir1' => 0, 'rir0' => 0, 'fallo' => 0, 'rest_pause' => 0];

            foreach ($ejerciciosPorDia as $dia => $ejercicios) {
                $seriePorDia[$dia] = 0;

                foreach ($ejercicios as $ejercicio) {
                    $nombre = $ejercicio['ejercicio_nombre'];
                    $ejercicioId = $catalogo[$nombre] ?? null;

                    if (! $ejercicioId) {
                        $sinMatch[] = $nombre;
                    }

                    Rutina::create(array_merge($ejercicio, [
                        'ejercicio_id' => $ejercicioId,
                        'nivel' => self::NIVEL,
                        'modalidad' => self::MODALIDAD,
                        'dia' => $dia,
                        'created_by' => $user->id,
                        'publica' => false,
                    ]));

                    $total++;
                    $seriePorDia[$dia] += (int) $ejercicio['series'];

                    // Carácter de esfuerzo (para el reporte de consola).
                    foreach ($ejercicio['prescripcion']['bloques'] ?? [] as $bloque) {
                        if ($bloque['tipo'] === 'fallo') {
                            $totales['fallo'] += (int) $bloque['series'];
                        } elseif ((int) $bloque['valor'] === 0) {
                            $totales['rir0'] += (int) $bloque['series'];
                        } else {
                            $totales['rir1'] += (int) $bloque['series'];
                        }
                    }

                    if (! empty($ejercicio['prescripcion']['rest_pause'])) {
                        $totales['rest_pause'] += (int) $ejercicio['series'];
                    }
                }
            }

            return [
                'total' => $total,
                'seriePorDia' => $seriePorDia,
                'series' => array_sum($seriePorDia),
                'totales' => $totales,
            ];
        });

        // Activación opcional del plan (opt-in explícito).
        $activar = filter_var(env('RUTINA_V3_ACTIVAR', false), FILTER_VALIDATE_BOOLEAN);

        if ($activar) {
            $primera = Rutina::where('created_by', $user->id)
                ->where('nivel', self::NIVEL)
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

            $this->command->info('Plan activo: '.$primera->id.' | '.self::NIVEL.' | '.self::MODALIDAD.' | '.self::DIAS[0]);
        }

        $this->command->info(
            "Seeder 3 DÍAS V3: {$resultado['total']} ejercicios cargados para '{$nick}' (user {$user->id})."
        );
        foreach ($resultado['seriePorDia'] as $dia => $series) {
            $this->command->line("  {$dia}: {$series} series");
        }
        $this->command->line('  Total semana: '.$resultado['series'].' series');
        $this->command->line(
            "  Carácter de esfuerzo: {$resultado['totales']['rir1']} s RIR 1 · ".
            "{$resultado['totales']['rir0']} s RIR 0 · {$resultado['totales']['fallo']} s al fallo · ".
            "{$resultado['totales']['rest_pause']} s rest-pause (5\"/10\")"
        );

        if ($sinMatch) {
            $this->command->warn('  Ejercicios sin match en la biblioteca (quedaron con ejercicio_id NULL):');
            foreach (array_unique($sinMatch) as $nombre) {
                $this->command->warn('    - '.$nombre);
            }
        }

        if (! $activar) {
            $this->command->line('  El plan activo NO se modificó: la V3 aparece en /rutinas → "Mis Rutinas".');
            $this->command->line('  Para activar la V3: RUTINA_V3_ACTIVAR=1 php artisan db:seed --class='.self::class);
        }
    }
}
