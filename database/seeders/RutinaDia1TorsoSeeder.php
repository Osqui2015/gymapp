<?php

namespace Database\Seeders;

use App\Models\Rutina;
use Illuminate\Database\Seeder;

/**
 * Rutina "DIA 1 TORSO" del catalogo comunitario.
 *
 * Estructura solicitada por Oscar (2026-08-28):
 *   Bloques progresivos: 2 series con RIR 1 + 2 series con RIR 0 (cerca del fallo).
 *   Tecnicas especiales (rest-pause, drop-set) documentadas en `notas`.
 */
class RutinaDia1TorsoSeeder extends Seeder
{
    public function run(): void
    {
        // Borrar el seed previo de esta rutina
        Rutina::where('nivel', 'Intermedio')
            ->where('modalidad', '3 Días')
            ->where('dia', 'Día 1 (Torso)')
            ->delete();

        $ejercicios = [
            // 1) Press plano: bloque progresivo RIR 1 -> RIR 0
            [
                'orden' => 1,
                'series' => 4,
                'reps_min' => '4',
                'reps_max' => '6',
                'descanso_min' => 2.0,
                'ejercicio_nombre' => 'Press de banca',
                'notas' => 'Bloque progresivo: 2x6 RIR 1 + 2x4 RIR 0.',
            ],

            // 2) Press inclinado con mancuerna (REST-PAUSE)
            [
                'orden' => 2,
                'series' => 3,
                'reps_min' => '8',
                'reps_max' => '8',
                'descanso_min' => 1.5,
                'ejercicio_nombre' => 'Press inclinado',
                'notas' => 'Rest-pause: 3x8 RIR 1. Cada serie = 1 set de 8 reps, descanso 5" y fallo, descanso 10" y fallo.',
            ],

            // 3) Press hombro (con barra o Smith)
            [
                'orden' => 3,
                'series' => 4,
                'reps_min' => '8',
                'reps_max' => '8',
                'descanso_min' => 1.5,
                'ejercicio_nombre' => 'Press militar de pie',
                'notas' => '4x8 RIR 1. Con barra o Smith.',
            ],

            // 4) Remo sentado (REST-PAUSE al fallo)
            [
                'orden' => 4,
                'series' => 3,
                'reps_min' => '8',
                'reps_max' => '8',
                'descanso_min' => 1.5,
                'ejercicio_nombre' => 'Remo sentado',
                'notas' => 'Rest-pause: 3x8 RIR 0. Cada serie = 1 set de 8 reps al fallo (5"), luego 10" descanso y fallo. Total = 3 series RP.',
            ],

            // 5) Pull up australiana: 40 reps (alto rep range, hasta el fallo o volumen)
            [
                'orden' => 5,
                'series' => 1,
                'reps_min' => '40',
                'reps_max' => '40',
                'descanso_min' => 1.5,
                'ejercicio_nombre' => 'Dominadas',
                'notas' => 'Pull up australiana (en barra baja o anillo). Meta: 40 reps totales. Se puede romper en las sets que quieras con poco descanso.',
            ],

            // 6) Curl 45°: bloque progresivo
            [
                'orden' => 6,
                'series' => 4,
                'reps_min' => '6',
                'reps_max' => '8',
                'descanso_min' => 1.0,
                'ejercicio_nombre' => 'Curl de bíceps',
                'notas' => 'Curl a 45° (predicador style). Bloque progresivo: 2x8 RIR 1 + 2x6 RIR 0.',
            ],

            // 7) Jalón tríceps (DROP-SET) - 3 series con técnica
            [
                'orden' => 7,
                'series' => 3,
                'reps_min' => '20',
                'reps_max' => '20',
                'descanso_min' => 1.0,
                'ejercicio_nombre' => 'Extensión de tríceps con barra recta',
                'notas' => 'Jalón tríceps polea alta. Drop-set x3 series: 10 reps al fallo (RIR 0), 10" descanso +5 reps, 10" descanso +5 reps. Total ~20 reps por serie.',
            ],
        ];

        foreach ($ejercicios as $ej) {
            Rutina::create(array_merge($ej, [
                'nivel' => 'Intermedio',
                'modalidad' => '3 Días',
                'dia' => 'Día 1 (Torso)',
                'publica' => true,
                'created_by' => null,
            ]));
        }

        $this->command->info('Seeder DIA 1 TORSO: ' . count($ejercicios) . ' ejercicios cargados.');
    }
}
