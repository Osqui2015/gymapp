<?php

namespace Database\Seeders;

use App\Models\Rutina;
use Illuminate\Database\Seeder;

/**
 * Rutina "DIA 3 FULL BODY" del catalogo comunitario.
 *
 * Estructura solicitada por Oscar (2026-08-28):
 *   Bloques progresivos: 2 series con RIR 2 + 2 series con RIR 1.
 *   Superseries en ejercicios 1+2 (pecho+apertura) y 5+6 (biceps+triceps).
 *
 * Como el modelo Rutina actual no tiene campos estructurados para RIR target
 * ni para bloques progresivos, esa informacion va en el campo `notas` (text).
 * El frontend puede parsearlo si quiere mostrar UI especifica, sino se muestra
 * como texto en el detalle del ejercicio.
 */
class RutinaDia3FullBodySeeder extends Seeder
{
    public function run(): void
    {
        // Borrar el seed previo de esta rutina (por si se re-corre el seeder)
        // sin tocar las otras rutinas del catalogo.
        Rutina::where('nivel', 'Intermedio')
            ->where('modalidad', '3 Días')
            ->where('dia', 'Día 3 (Full Body)')
            ->delete();

        $ejercicios = [
            // 1+2: SUPERSERIE pecho (press de banca + aperturas en polea)
            [
                'orden' => 1,
                'superserie_grupo' => 1,
                'series' => 4,
                'reps_min' => '10',
                'reps_max' => '12',
                'descanso_min' => 1.5,
                'ejercicio_nombre' => 'Press de banca',
                'notas' => 'Bloque progresivo: 2x12 RIR 2 + 2x10 RIR 1. Superserie con aperturas en polea alta (sin descanso entre ambos).',
            ],
            [
                'orden' => 2,
                'superserie_grupo' => 1,
                'series' => 4,
                'reps_min' => '10',
                'reps_max' => '12',
                'descanso_min' => 1.5,
                'ejercicio_nombre' => 'Aperturas / Cruces en polea alta',
                'notas' => 'Bloque progresivo: 2x12 RIR 2 + 2x10 RIR 1. Superserie con press de banca.',
            ],

            // 3: hombro lateral
            [
                'orden' => 3,
                'series' => 4,
                'reps_min' => '10',
                'reps_max' => '12',
                'descanso_min' => 1.0,
                'ejercicio_nombre' => 'Elevaciones laterales con polea',
                'notas' => 'Bloque progresivo: 2x12 RIR 2 + 2x10 RIR 1.',
            ],

            // 4: espalda
            [
                'orden' => 4,
                'series' => 4,
                'reps_min' => '12',
                'reps_max' => '15',
                'descanso_min' => 1.5,
                'ejercicio_nombre' => 'Jalón al pecho',
                'notas' => 'Bloque progresivo: 2x15 RIR 2 + 2x12 RIR 1.',
            ],

            // 5+6: SUPERSERIE brazos (curl de biceps + extension de triceps en polea alta)
            [
                'orden' => 5,
                'superserie_grupo' => 2,
                'series' => 4,
                'reps_min' => '8',
                'reps_max' => '10',
                'descanso_min' => 1.0,
                'ejercicio_nombre' => 'Curl de bíceps',
                'notas' => 'Bloque progresivo: 2x10 RIR 2 + 2x8 RIR 1. Superserie con extensión de tríceps sobre la cabeza (polea alta).',
            ],
            [
                'orden' => 6,
                'superserie_grupo' => 2,
                'series' => 4,
                'reps_min' => '8',
                'reps_max' => '10',
                'descanso_min' => 1.0,
                'ejercicio_nombre' => 'Extensión de tríceps sobre la cabeza',
                'notas' => 'Bloque progresivo: 2x10 RIR 2 + 2x8 RIR 1. Superserie con curl de bíceps.',
            ],

            // 7: pierna compuesto
            [
                'orden' => 7,
                'series' => 4,
                'reps_min' => '8',
                'reps_max' => '10',
                'descanso_min' => 2.0,
                'ejercicio_nombre' => 'Prensa de piernas 45 grados',
                'notas' => 'Bloque progresivo: 2x10 RIR 2 + 2x8 RIR 1.',
            ],

            // 8: pierna aislamiento
            [
                'orden' => 8,
                'series' => 3,
                'reps_min' => '10',
                'reps_max' => '10',
                'descanso_min' => 1.5,
                'ejercicio_nombre' => 'Extensión de cuádriceps',
                'notas' => '3x10 RIR 1.',
            ],
        ];

        foreach ($ejercicios as $ej) {
            Rutina::create(array_merge($ej, [
                'nivel' => 'Intermedio',
                'modalidad' => '3 Días',
                'dia' => 'Día 3 (Full Body)',
                'publica' => true,           // visible en el catalogo comunitario
                'created_by' => null,        // rutina del sistema
            ]));
        }

        $this->command->info('Seeder DIA 3 FULL BODY: ' . count($ejercicios) . ' ejercicios cargados.');
    }
}
