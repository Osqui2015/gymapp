<?php

namespace Database\Seeders;

use App\Models\Rutina;
use Illuminate\Database\Seeder;

/**
 * Rutina "DIA 2 PIERNA" del catalogo comunitario.
 *
 * Estructura solicitada por Oscar (2026-08-28):
 *   Bloques progresivos + series al fallo tecnico (RIR 0).
 */
class RutinaDia2PiernaSeeder extends Seeder
{
    public function run(): void
    {
        // Borrar el seed previo de esta rutina
        Rutina::where('nivel', 'Intermedio')
            ->where('modalidad', '3 Días')
            ->where('dia', 'Día 2 (Pierna)')
            ->delete();

        $ejercicios = [
            // 1) Sentadilla: bloque progresivo RIR 1 -> RIR 0
            [
                'orden' => 1,
                'series' => 4,
                'reps_min' => '4',
                'reps_max' => '6',
                'descanso_min' => 2.5,
                'ejercicio_nombre' => 'Sentadilla',
                'notas' => 'Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.',
            ],

            // 2) Estocada
            [
                'orden' => 2,
                'series' => 3,
                'reps_min' => '8',
                'reps_max' => '8',
                'descanso_min' => 1.5,
                'ejercicio_nombre' => 'Zancadas con mancuernas',
                'notas' => '3x8 por pierna.',
            ],

            // 3) Buenos dias (Good Mornings)
            [
                'orden' => 3,
                'series' => 4,
                'reps_min' => '8',
                'reps_max' => '8',
                'descanso_min' => 1.5,
                'ejercicio_nombre' => 'Buenos días (Good Mornings)',
                'notas' => '4x8 RIR 1. Enfocarse en isquiotibiales y espalda baja neutra.',
            ],

            // 4) Hip thrust: bloque progresivo
            [
                'orden' => 4,
                'series' => 4,
                'reps_min' => '6',
                'reps_max' => '8',
                'descanso_min' => 2.0,
                'ejercicio_nombre' => 'Hip thrust',
                'notas' => 'Bloque progresivo: 2x8 RIR 1 + 2x6 RIR 0. Apoyar escapulas en banco, drive de talones.',
            ],

            // 5) Sillon cuadriceps (extension): bloque progresivo al fallo
            [
                'orden' => 5,
                'series' => 4,
                'reps_min' => '8',
                'reps_max' => '8',
                'descanso_min' => 1.0,
                'ejercicio_nombre' => 'Extensión de cuádriceps',
                'notas' => 'Bloque: 2x8 RIR 1 + 2X al fallo tecnico (RIR 0).',
            ],

            // 6) Camilla femorales (curl femoral): al fallo
            [
                'orden' => 6,
                'series' => 4,
                'reps_min' => '8',
                'reps_max' => '8',
                'descanso_min' => 1.0,
                'ejercicio_nombre' => 'Curl de isquiotibiales acostado',
                'notas' => 'Bloque: 2x8 RIR 0 + 2X al fallo tecnico. Camilla femorales acostado.',
            ],

            // 7) Gemelo parado
            [
                'orden' => 7,
                'series' => 4,
                'reps_min' => '15',
                'reps_max' => '15',
                'descanso_min' => 1.0,
                'ejercicio_nombre' => 'Elevación de talones de pie',
                'notas' => '4x15 reps.',
            ],
        ];

        foreach ($ejercicios as $ej) {
            Rutina::create(array_merge($ej, [
                'nivel' => 'Intermedio',
                'modalidad' => '3 Días',
                'dia' => 'Día 2 (Pierna)',
                'publica' => true,
                'created_by' => null,
            ]));
        }

        $this->command->info('Seeder DIA 2 PIERNA: ' . count($ejercicios) . ' ejercicios cargados.');
    }
}
