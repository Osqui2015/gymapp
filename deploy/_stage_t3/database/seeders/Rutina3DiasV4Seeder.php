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
 * Rutina "3 Días V4" — V3 ajustada al perfil real de Oscar (26 filas, 75 series).
 *
 * ORIGEN: la V3 salió del PDF "Programa de Entrenamiento 3 Días (Actualizado)"
 * del 25/10/2026. Esta V4 es la V3 con 8 ajustes, acordados el 27/10/2026 a
 * partir del historial real de 2 meses (03/09 → 24/10/2026) y del perfil:
 * 34 años, 70.4 kg, 22% de grasa, skinny fat funcional, fútbol + pádel.
 *
 * AJUSTES (V3 → V4):
 *   1. Laterales del Día 3: `2x15 RIR 1 + 1x15 AL FALLO` → `3x15 RIR 1`.
 *      Con 5 kg de polea el fallo no aporta estímulo, sólo compensación
 *      (trapecio y elevadores del hombro).
 *   2. Curl de bíceps del Día 1: 3 → 4 series
 *      (`2x10 RIR 1 + 1x8 RIR 0 + 1x8 FALLO`). Compensa la pérdida de volumen
 *      de bíceps: es el grupo que más le importa (brazos flabby, 22% grasa).
 *   3. Dominadas del Día 1: `3x8 RIR 1` → `3x5 RIR 2` de arranque, con el
 *      objetivo de 8 reps por serie en la nota (venía de 1x34 al fallo).
 *   4. Extensión de cuádriceps del Día 2: `3x12` → `3x10` (con 25-26 kg
 *     no llegaba a 12 reps con carga útil).
 *   5. Superserie en el Día 3: Curl martillo + Extensión de tríceps sobre la
 *      cabeza (3 series c/u, mismo grupo). Es el único lugar razonable: son
 *      ejercicios de aislamiento, con las mismas series, y no toca los
 *      ejercicios grandes (sentadilla, press, remo), donde la fatiga de la
 *      primera parte hace perder reps del segundo.
 *   6. Core: 2 ejercicios por día (movimiento + estabilidad), 15 series en
 *      lugar de 9. La V1 no tenía NINGÚN core; 9 series de flexión pura ya
 *      era un salto grande, así que la estabilidad entra como protección
 *      lumbar (anti-extensión, anti-lateral, anti-rotación).
 *   7. Prensa de piernas del Día 2: 3 → 2 series (ya hay 13 series de
 *      cuádriceps en la semana y 100 kg en esa máquina).
 *   8. Sentadilla frontal del Día 3: 3 → 2 series (complemento de la
 *      sentadilla libre del Día 2, no una segunda sesión de piernas).
 *
 * DÍAS:
 *   DÍA 1 (Torso + Movilidad + Core)  — 9 filas (1 bloque + 8 ejercicios), 25 series
 *   DÍA 2 (Pierna + Core)             — 8 ejercicios, 23 series
 *   DÍA 3 (Full Body + Core)          — 9 ejercicios, 27 series
 *   Semana: 75 series (25 + 23 + 27).
 *
 * Se crea como modalidad nueva ("Personalizada | 3 Días V4"): NO borra ni pisa
 * la V1 ("3 Días v1"), la V2 ("3 Días V2") ni la V3 ("3 Días V3"), que quedan
 * disponibles para volver atrás desde /rutinas.
 *
 * DOBLE FUENTE DE VERDAD, ambas en sincronía (verificado por el propio seeder):
 *   1. `prescripcion` (JSON estructurado) — la que usa el front.
 *      La SUMA de `series` de los bloques tiene que ser EXACTAMENTE la
 *      columna `series` de la fila.
 *   2. `notas` (texto libre) — el front también la parsea con regex.
 *
 * El seeder VALIDA al correr que:
 *   - la suma de los bloques == la columna `series`, y
 *   - `RutinaPrescripcionService::fromNotas($notas)` dé EXACTAMENTE lo mismo
 *     que la columna `prescripcion` (si no, revienta con RuntimeException).
 *
 * Idempotente: borra las filas previas de ESTA modalidad para este usuario y
 * las vuelve a crear dentro de una transacción.
 *
 * Uso:
 *   php artisan db:seed --class=Rutina3DiasV4Seeder
 *
 * Override del usuario destino:
 *   RUTINA_V4_USER_NICK=osqui php artisan db:seed --class=Rutina3DiasV4Seeder
 *
 * Activar la V4 como plan activo:
 *   RUTINA_V4_ACTIVAR=1 php artisan db:seed --class=Rutina3DiasV4Seeder
 */
class Rutina3DiasV4Seeder extends Seeder
{
    /** Modalidad del plan (se muestra como "Personalizada | 3 Días V4"). */
    public const MODALIDAD = '3 Días V4';

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
     *                  descanso_min, notas, prescripcion, superserie_grupo]
     */
    private function ejercicios(): array
    {
        return [
            // =============================================================
            // DÍA 1 — TORSO + MOVILIDAD + CORE
            // =============================================================
            'Día 1 (Torso)' => [
                [
                    // BLOQUE informativo: la movilidad NO es una serie de
                    // trabajo, es un protocolo de 5 a 10 minutos. Por eso va
                    // con series = 0: el front lo muestra como "Bloque de
                    // calentamiento", no cuenta para el total de series del
                    // día (25) y no bloquea el avance: al registrar el
                    // calentamiento la app pasa al siguiente ejercicio.
                    'orden' => 1,
                    'ejercicio_nombre' => 'Movilidad articular y activación',
                    'series' => 0,
                    'reps_min' => '1',
                    'reps_max' => '1',
                    'descanso_min' => 0.0,
                    'notas' => 'Bloque de movilidad articular y activación (agregado inicial): 5 a 10 minutos continuos, fluido y sin fatiga muscular. Enfoque en el complejo articular del hombro, la columna torácica y las escápulas. Dislocaciones de hombro con banda elástica o bastón, rotaciones torácicas en cuadrupedia, retracciones escapulares colgado en barra (scapular pulls) y rotaciones externas para el manguito rotador antes de tocar la barra. No es una serie de trabajo: hacelo antes de la barra.',
                    'prescripcion' => null,
                    'superserie_grupo' => null,
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
                    'superserie_grupo' => null,
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
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 4,
                    'ejercicio_nombre' => 'Dominadas',
                    'series' => 3,
                    'reps_min' => '5',
                    'reps_max' => '5',
                    'descanso_min' => 1.5,
                    'notas' => '3x5 RIR 2. En suspensión en barra fija con manos separadas en pronación, elevar el cuerpo hasta que la barbilla supere la barra y descender con control. Es el primer ejercicio de espalda tras los empujes para ejecutarlo con los dorsales frescos. Pasá a ocho por serie cuando completes las tres series con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 5, 'tipo' => 'rir', 'valor' => 2],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => 'En suspensión en barra fija con manos separadas en pronación, elevar el cuerpo hasta que la barbilla supere la barra y descender con control. Es el primer ejercicio de espalda tras los empujes para ejecutarlo con los dorsales frescos. Pasá a ocho por serie cuando completes las tres series con técnica limpia.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => null,
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
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 6,
                    'ejercicio_nombre' => 'Curl de bíceps',
                    'series' => 4,
                    'reps_min' => '8',
                    'reps_max' => '10',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x8 RIR 0 + 1x8 FALLO. De pie o sentado, tronco erguido y codos pegados a los costados, flexionar codos iniciando con manos en semipronación y realizando supinación durante la elevación, evitando el balanceo del torso. Arrancá con 15 kg y llegá a 20 kg cuando llegues a diez repeticiones limpias. Sumar una repetición por serie antes de pasar de peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 8, 'tipo' => 'rir', 'valor' => 0],
                            ['series' => 1, 'reps' => 8, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar una repetición por serie antes de pasar de peso.',
                        'consejo' => 'De pie o sentado, tronco erguido y codos pegados a los costados, flexionar codos iniciando con manos en semipronación y realizando supinación durante la elevación, evitando el balanceo del torso. Arrancá con 15 kg y llegá a 20 kg cuando llegues a diez repeticiones limpias.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 7,
                    'ejercicio_nombre' => 'Extensión de tríceps sobre la cabeza',
                    'series' => 3,
                    'reps_min' => '12',
                    'reps_max' => '12',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x12 RIR 1 + 1x12 FALLO. Sentado o de pie, brazos fijos en posición vertical junto a la cabeza, flexionar codos descendiendo la carga por detrás de la nuca para lograr el estiramiento de la porción larga y extender codos hacia arriba. Sumar una repetición por serie antes de pasar de peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 12, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 12, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar una repetición por serie antes de pasar de peso.',
                        'consejo' => 'Sentado o de pie, brazos fijos en posición vertical junto a la cabeza, flexionar codos descendiendo la carga por detrás de la nuca para lograr el estiramiento de la porción larga y extender codos hacia arriba.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 8,
                    'ejercicio_nombre' => 'Crunch abdominal',
                    'series' => 3,
                    'reps_min' => '15',
                    'reps_max' => '15',
                    'descanso_min' => 0.5,
                    'notas' => '3x15 RIR 1. Decúbito supino, rodillas flexionadas a 90°. Espirar e incurvar la columna aproximando el esternón al pubis mediante flexión de tronco. Despegar únicamente escápulas del suelo sin tirar de la nuca. Sin ir al fallo: la flexión repetida con fatiga es lo que factura la lumbar. Sumar una repetición por serie antes de pasar de peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 15, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar una repetición por serie antes de pasar de peso.',
                        'consejo' => 'Decúbito supino, rodillas flexionadas a 90°. Espirar e incurvar la columna aproximando el esternón al pubis mediante flexión de tronco. Despegar únicamente escápulas del suelo sin tirar de la nuca. Sin ir al fallo: la flexión repetida con fatiga es lo que factura la lumbar.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => null,
                ],
                [
                    // CORE 2 del Día 1: anti-extensión. No es una flexión más:
                    // el objetivo es que la pelvis no se despegue, no flexionar
                    // la columna. Por eso no lleva bloques de esfuerzo: se
                    // registra por tiempo (una repetición por serie) y la
                    // duración va en la nota de la serie.
                    'orden' => 9,
                    'ejercicio_nombre' => 'Plancha abdominal',
                    'series' => 2,
                    'reps_min' => '1',
                    'reps_max' => '1',
                    'descanso_min' => 0.5,
                    'notas' => '2 series de 40 segundos. Decúbito prono, antebrazos apoyados bajo los hombros y cuerpo en línea recta de la cabeza a los pies. Mantené la cadera alineada sin arquear la zona lumbar ni dejarla caer, respirá de forma continua y sostené hasta el final del tiempo. Registrá una repetición por serie y anotá los segundos en la nota de la serie.',
                    'prescripcion' => null,
                    'superserie_grupo' => null,
                ],
            ],

            // =============================================================
            // DÍA 2 — PIERNA + CORE
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
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 2,
                    'ejercicio_nombre' => 'Prensa de piernas 45 grados',
                    'series' => 2,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.5,
                    'notas' => '2x10 RIR 1. Espalda y pelvis totalmente apoyadas en el respaldo, pies a anchura media en la plataforma. Flexionar rodillas hasta llevar los muslos cerca de la caja torácica sin despegar la pelvis y empujar la plataforma hasta la extensión casi completa. Arrancá con 100 kg en tu máquina. Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                        'consejo' => 'Espalda y pelvis totalmente apoyadas en el respaldo, pies a anchura media en la plataforma. Flexionar rodillas hasta llevar los muslos cerca de la caja torácica sin despegar la pelvis y empujar la plataforma hasta la extensión casi completa. Arrancá con 100 kg en tu máquina.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 3,
                    'ejercicio_nombre' => 'Hip thrust',
                    'series' => 3,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.5,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x10 FALLO. Espalda apoyada en banco a nivel escapular y barra acolchada sobre la cresta ilíaca, flexionar cadera descendiendo la pelvis y empujando fuertemente con los talones hasta alcanzar extensión completa de cadera con retroversión pélvica. Sumar una repetición por serie antes de pasar de peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 10, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar una repetición por serie antes de pasar de peso.',
                        'consejo' => 'Espalda apoyada en banco a nivel escapular y barra acolchada sobre la cresta ilíaca, flexionar cadera descendiendo la pelvis y empujando fuertemente con los talones hasta alcanzar extensión completa de cadera con retroversión pélvica.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 4,
                    'ejercicio_nombre' => 'Extensión de cuádriceps',
                    'series' => 3,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.0,
                    'notas' => 'Rest-pause: 3x10 RIR 1. Cada serie = 1 set de 10 reps, descanso 5" y fallo, descanso 10" y fallo. Piezas con acolchado en la pantorrilla, extender rodillas hasta la contracción cuádriceps sin hiperflexionar la rodilla. Ajustá la carga para llegar a diez repeticiones por serie con técnica limpia, alrededor de 30 kg. Subir 2,5 kg al completar las 3 series en RIR 1 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => [
                            'minisets' => [
                                ['reps' => 10, 'descanso_seg' => 5, 'tipo' => 'fallo', 'valor' => null],
                                ['reps' => 10, 'descanso_seg' => 10, 'tipo' => 'fallo', 'valor' => null],
                            ],
                        ],
                        'progresion' => 'Subir 2,5 kg al completar las 3 series en RIR 1 con técnica limpia.',
                        'consejo' => 'Piezas con acolchado en la pantorrilla, extender rodillas hasta la contracción cuádriceps sin hiperflexionar la rodilla. Ajustá la carga para llegar a diez repeticiones por serie con técnica limpia, alrededor de 30 kg.',
                        'etiqueta' => 'Rest-pause',
                    ],
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 5,
                    'ejercicio_nombre' => 'Curl de isquiotibiales acostado',
                    'series' => 3,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x10 FALLO. Decúbito prono con la rodilla flexionada a 90°, curling lento sin rebote y con la cadera pegada al banco. Sumar una repetición por serie antes de pasar de peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 10, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar una repetición por serie antes de pasar de peso.',
                        'consejo' => 'Decúbito prono con la rodilla flexionada a 90°, curling lento sin rebote y con la cadera pegada al banco.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 6,
                    'ejercicio_nombre' => 'Elevación de talones de pie',
                    'series' => 3,
                    'reps_min' => '15',
                    'reps_max' => '15',
                    'descanso_min' => 1.0,
                    'notas' => '3x15 RIR 1. De pie con los pies apoyados en el borde de un escalón, elevar los talones hasta la extensión completa de tobillo y bajar despacio hasta sentir el estiramiento. Arrancá con 20 kg y llegá a 25 kg cuando llegues a quince repeticiones limpias.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 15, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => 'De pie con los pies apoyados en el borde de un escalón, elevar los talones hasta la extensión completa de tobillo y bajar despacio hasta sentir el estiramiento. Arrancá con 20 kg y llegá a 25 kg cuando llegues a quince repeticiones limpias.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 7,
                    'ejercicio_nombre' => 'Crunch bicicleta',
                    'series' => 3,
                    'reps_min' => '16',
                    'reps_max' => '16',
                    'descanso_min' => 0.5,
                    'notas' => '3x16 RIR 1. Decúbito supino con las manos detrás de la cabeza y las piernas en el aire, rotar el tronco llevando el codo hacia la rodilla opuesta sin tirar del cuello. Movimiento controlado y sin velocidad: la rotación es lo que trabaja el oblicuo. Sumar una repetición por serie antes de pasar de peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 16, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar una repetición por serie antes de pasar de peso.',
                        'consejo' => 'Decúbito supino con las manos detrás de la cabeza y las piernas en el aire, rotar el tronco llevando el codo hacia la rodilla opuesta sin tirar del cuello. Movimiento controlado y sin velocidad: la rotación es lo que trabaja el oblicuo.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => null,
                ],
                [
                    // CORE 2 del Día 2: anti-lateral (oblicuos).
                    'orden' => 8,
                    'ejercicio_nombre' => 'Plancha lateral',
                    'series' => 2,
                    'reps_min' => '1',
                    'reps_max' => '1',
                    'descanso_min' => 0.5,
                    'notas' => '2 series de 30 segundos por lado. Decúbito lateral, antebrazo apoyado bajo el hombro y piernas extendidas, elevar la cadera hasta formar una línea recta de la cabeza a los pies. Mantené la cadera alta sin dejar caer el pie del piso y respirá de forma continua. Registrá una repetición por lado y anotá los segundos en la nota de la serie.',
                    'prescripcion' => null,
                    'superserie_grupo' => null,
                ],
            ],

            // =============================================================
            // DÍA 3 — FULL BODY + CORE
            // =============================================================
            'Día 3 (Full Body)' => [
                [
                    'orden' => 1,
                    'ejercicio_nombre' => 'Press militar de pie',
                    'series' => 4,
                    'reps_min' => '4',
                    'reps_max' => '6',
                    'descanso_min' => 2.0,
                    'notas' => 'Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0. De pie con la barra a la altura de los ojos, agarre prono un poco más ancho que los hombros, llevar la barra hasta la extensión de codos sin inclinar el torso hacia atrás. Bajada controlada de 2 segundos hasta la frente. Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 6, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 2, 'reps' => 4, 'tipo' => 'rir', 'valor' => 0],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                        'consejo' => 'De pie con la barra a la altura de los ojos, agarre prono un poco más ancho que los hombros, llevar la barra hasta la extensión de codos sin inclinar el torso hacia atrás. Bajada controlada de 2 segundos hasta la frente.',
                        'etiqueta' => 'Bloque progresivo',
                    ],
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 2,
                    'ejercicio_nombre' => 'Sentadilla frontal',
                    'series' => 2,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.5,
                    'notas' => '2x10 RIR 1. Barra apoyada sobre los deltoides anteriores y codos altos, parados con los pies apenas más abiertos que los hombros. Mantené el torso totalmente vertical y la cadera a la misma altura en todo el recorrido, descendiendo hasta que el muslo llegue a la horizontal. Arrancá con 30 kg y ajustá hasta 40 kg cuando llegues a diez repeticiones limpias. Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Subir 2,5 kg al completar todas las series en RIR 1 con técnica limpia.',
                        'consejo' => 'Barra apoyada sobre los deltoides anteriores y codos altos, parados con los pies apenas más abiertos que los hombros. Mantené el torso totalmente vertical y la cadera a la misma altura en todo el recorrido, descendiendo hasta que el muslo llegue a la horizontal. Arrancá con 30 kg y ajustá hasta 40 kg cuando llegues a diez repeticiones limpias.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => null,
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
                    'superserie_grupo' => null,
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
                    'superserie_grupo' => null,
                ],
                [
                    'orden' => 5,
                    'ejercicio_nombre' => 'Elevaciones laterales con polea',
                    'series' => 3,
                    'reps_min' => '15',
                    'reps_max' => '15',
                    'descanso_min' => 1.0,
                    'notas' => '3x15 RIR 1. De pie, agarrar polea baja cruzando el cable por delante o detrás del cuerpo, elevar el brazo lateralmente hasta la horizontal con codo de inflexión imperceptible y hombro deprimido. Sin series al fallo: con la polea baja el fallo se paga con trapecio y elevadores del hombro. Arrancá con 7,5 kg y llegá a 10 kg cuando llegues a quince repeticiones limpias.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 3, 'reps' => 15, 'tipo' => 'rir', 'valor' => 1],
                        ],
                        'rest_pause' => null,
                        'progresion' => null,
                        'consejo' => 'De pie, agarrar polea baja cruzando el cable por delante o detrás del cuerpo, elevar el brazo lateralmente hasta la horizontal con codo de inflexión imperceptible y hombro deprimido. Sin series al fallo: con la polea baja el fallo se paga con trapecio y elevadores del hombro. Arrancá con 7,5 kg y llegá a 10 kg cuando llegues a quince repeticiones limpias.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => null,
                ],
                [
                    // SUPERSERIE (grupo 1) con la extensión de tríceps: par de
                    // aislamiento. Ambos con 3 series para que las rondas
                    // calcen. No se hace superserie con los ejercicios grandes
                    // (sentadilla, press, remo): ahí la fatiga de la primera
                    // parte hace perder reps del segundo.
                    'orden' => 6,
                    'ejercicio_nombre' => 'Curl martillo',
                    'series' => 3,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.0,
                    'notas' => 'Bloque: 2x10 RIR 1 + 1x10 FALLO. De pie o sentado con mancuernas en agarre neutro, flexionar codos sin rotar las muñecas y mantener los codos inmóviles junto al torso. Sumar una repetición por serie antes de pasar de peso.',
                    'prescripcion' => [
                        'bloques' => [
                            ['series' => 2, 'reps' => 10, 'tipo' => 'rir', 'valor' => 1],
                            ['series' => 1, 'reps' => 10, 'tipo' => 'fallo', 'valor' => null],
                        ],
                        'rest_pause' => null,
                        'progresion' => 'Sumar una repetición por serie antes de pasar de peso.',
                        'consejo' => 'De pie o sentado con mancuernas en agarre neutro, flexionar codos sin rotar las muñecas y mantener los codos inmóviles junto al torso.',
                        'etiqueta' => 'Bloque',
                    ],
                    'superserie_grupo' => 1,
                ],
                [
                    'orden' => 7,
                    'ejercicio_nombre' => 'Extensión de tríceps sobre la cabeza',
                    'series' => 3,
                    'reps_min' => '12',
                    'reps_max' => '12',
                    'descanso_min' => 1.0,
                    'notas' => 'Rest-pause: 3x12 RIR 1. Cada serie = 1 set de 12 reps, descanso 5" y fallo, descanso 10" y fallo. Sujetar cuerda en polea baja sobre la cabeza, extender codos hacia arriba desde la posición de estiramiento posterior. Sumar una repetición por serie antes de pasar de peso.',
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
                        'progresion' => 'Sumar una repetición por serie antes de pasar de peso.',
                        'consejo' => 'Sujetar cuerda en polea baja sobre la cabeza, extender codos hacia arriba desde la posición de estiramiento posterior.',
                        'etiqueta' => 'Rest-pause',
                    ],
                    'superserie_grupo' => 1,
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
                    'superserie_grupo' => null,
                ],
                [
                    // CORE 2 del Día 3: anti-rotación (hanging leg raise).
                    'orden' => 9,
                    'ejercicio_nombre' => 'Elevación de piernas colgado',
                    'series' => 2,
                    'reps_min' => '10',
                    'reps_max' => '10',
                    'descanso_min' => 1.0,
                    'notas' => '2 series de 10 repeticiones. Colgado de la barra con agarre prono y piernas extendidas, elevar las piernas hasta la horizontal sin balanceo y bajar de forma controlada sin dejar caer la lumbar. Sentate despacio al final de cada serie para que no se genere rebote. Arrancá con ocho repeticiones y llegá a doce cuando las dos series salgan limpias.',
                    'prescripcion' => null,
                    'superserie_grupo' => null,
                ],
            ],
        ];
    }

    public function run(): void
    {
        $nick = env('RUTINA_V4_USER_NICK', 'osqui');
        $user = User::where('nick', $nick)->first();

        if (! $user) {
            $this->command->error("No se encontró el usuario con nick '{$nick}'. Abortando.");
            $this->command->line('Override con: RUTINA_V4_USER_NICK=<nick> php artisan db:seed --class=Rutina3DiasV4Seeder');

            return;
        }

        $ejerciciosPorDia = $this->ejercicios();

        // Validación previa (doble fuente de verdad):
        //  a) la suma de las series de los bloques == la columna `series`
        //  b) `notas` deriva EXACTAMENTE lo mismo que la columna `prescripcion`
        //     (si el front parsea otra cosa, el ejercicio se muestra distinto
        //      según de dónde venga el dato).
        foreach ($ejerciciosPorDia as $dia => $ejercicios) {
            foreach ($ejercicios as $ejercicio) {
                $nombre = $ejercicio['ejercicio_nombre'];

                if (! empty($ejercicio['prescripcion'])
                    && ! RutinaPrescripcionService::esConsistente($ejercicio['prescripcion'], (int) $ejercicio['series'])) {
                    throw new \RuntimeException(
                        "Prescripción inconsistente en '{$dia} / {$nombre}': ".
                        'la suma de los bloques no coincide con series = '.$ejercicio['series']
                    );
                }

                $derivada = RutinaPrescripcionService::fromNotas($ejercicio['notas']);
                $guardada = $ejercicio['prescripcion'];

                if (json_encode($this->normalizar($derivada)) !== json_encode($this->normalizar($guardada))) {
                    throw new \RuntimeException(
                        "Las notas y la prescripción no coinciden en '{$dia} / {$nombre}'.\n".
                        '  derivada de notas: '.json_encode($derivada, JSON_UNESCAPED_UNICODE)."\n".
                        '  prescripcion:     '.json_encode($guardada, JSON_UNESCAPED_UNICODE)
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
            $totales = ['rir1' => 0, 'rir2' => 0, 'rir0' => 0, 'fallo' => 0, 'rest_pause' => 0, 'core' => 0];

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
                        } elseif ((int) $bloque['valor'] === 1) {
                            $totales['rir1'] += (int) $bloque['series'];
                        } else {
                            $totales['rir2'] += (int) $bloque['series'];
                        }
                    }

                    if (! empty($ejercicio['prescripcion']['rest_pause'])) {
                        $totales['rest_pause'] += (int) $ejercicio['series'];
                    }

                    // Core: ejercicios de flexión/rotación (con bloques) más
                    // los de estabilidad (sin bloques, trabajo por tiempo).
                    if ($this->esCore($nombre)) {
                        $totales['core'] += (int) $ejercicio['series'];
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
        $activar = filter_var(env('RUTINA_V4_ACTIVAR', false), FILTER_VALIDATE_BOOLEAN);

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
            "Seeder 3 DÍAS V4: {$resultado['total']} ejercicios cargados para '{$nick}' (user {$user->id})."
        );
        foreach ($resultado['seriePorDia'] as $dia => $series) {
            $this->command->line("  {$dia}: {$series} series");
        }
        $this->command->line('  Total semana: '.$resultado['series'].' series');
        $this->command->line(
            "  Carácter de esfuerzo: {$resultado['totales']['rir1']} s RIR 1 · ".
            "{$resultado['totales']['rir2']} s RIR 2 · ".
            "{$resultado['totales']['rir0']} s RIR 0 · ".
            "{$resultado['totales']['fallo']} s al fallo · ".
            "{$resultado['totales']['rest_pause']} s rest-pause (5\"/10\") · ".
            "{$resultado['totales']['core']} s de core"
        );
        $this->command->line('  Superserie: curl martillo + extensión de tríceps sobre la cabeza (Día 3).');

        if ($sinMatch) {
            $this->command->warn('  Ejercicios sin match en la biblioteca (quedaron con ejercicio_id NULL):');
            foreach (array_unique($sinMatch) as $nombre) {
                $this->command->warn('    - '.$nombre);
            }
        }

        if (! $activar) {
            $this->command->line('  El plan activo NO se modificó: la V4 aparece en /rutinas → "Mis Rutinas".');
            $this->command->line('  Para activar la V4: RUTINA_V4_ACTIVAR=1 php artisan db:seed --class='.self::class);
        }
    }

    /**
     * ¿Este ejercicio es trabajo de core?
     *
     * OJO: el patrón va SIN acentos a propósito. En PCRE sin el flag `u` una
     * clase de caracteres matchea UN byte, no un carácter multibyte: un
     * `[oó]` no encuentra nunca una "ó" (2 bytes). Por eso se matchea
     * "piernas colgado" en vez de "elevación de piernas colgado".
     */
    private function esCore(string $nombre): bool
    {
        return (bool) preg_match(
            '/crunch|plancha|piernas colgado|dead bug|bird dog/i',
            $nombre
        );
    }

    /**
     * Normaliza una estructura para comparar dos arrays ignorando el ORDEN
     * de las claves (sólo el orden de la lista `bloques` cuenta).
     */
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
