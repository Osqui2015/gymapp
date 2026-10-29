<?php

namespace Database\Seeders;

use App\Models\Rutina;
use App\Services\RutinaPrescripcionService;
use Illuminate\Database\Seeder;

/**
 * RutinaPrescripcionBackfillSeeder
 *
 * Genera la prescripción ESTRUCTURADA (`rutinas.prescripcion`) para todas las
 * rutinas que sólo tienen `notas` en texto libre.
 *
 * Sirve para:
 * - La rutina "3 Días v1" de Oscar (22 ejercicios, 7 formatos de notas
 *   distintos: "2x6 RIR 1 + 2x4 RIR 0", "3x8 RIR 1 + rest-pause final
 *   (8 reps → 5" fallo → 10" fallo)", "1x40 (Pull-up australiana...)", etc.).
 * - Las rutinas oficiales y comunitarias que nunca se editaron a mano.
 *
 * Es IDEMPOTENTE: sólo escribe en las filas que todavía no tienen
 * prescripción. Para forzar el recálculo:
 *
 *   php artisan tinker --execute="App\Models\Rutina::whereNotNull('notas')->update(['prescripcion' => null]);"
 *   php artisan db:seed --class=RutinaPrescripcionBackfillSeeder
 */
class RutinaPrescripcionBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $total = 0;
        $actualizadas = 0;
        $sinBloques = 0;
        $inconsistentes = [];

        Rutina::query()
            ->whereNotNull('notas')
            ->where('notas', '!=', '')
            ->orderBy('id')
            ->chunk(200, function ($rutinas) use (&$total, &$actualizadas, &$sinBloques, &$inconsistentes) {
                foreach ($rutinas as $rutina) {
                    $total++;

                    // Ya tiene prescripción: no tocar (idempotente).
                    if (! empty($rutina->prescripcion)) {
                        continue;
                    }

                    $prescripcion = RutinaPrescripcionService::fromNotas($rutina->notas);

                    if ($prescripcion === null) {
                        // Las notas no describen bloques (ej. "3x8 (estocadas,
                        // sin RIR objetivo prescrito)"): no hay prescripción
                        // que derivar y el front parsea las notas igual.
                        $sinBloques++;

                        continue;
                    }

                    if (! RutinaPrescripcionService::esConsistente($prescripcion, (int) $rutina->series)) {
                        $inconsistentes[] = "{$rutina->modalidad} / {$rutina->dia} / {$rutina->ejercicio_nombre}";
                    }

                    $rutina->prescripcion = $prescripcion;
                    $rutina->saveQuietly();
                    $actualizadas++;
                }
            });

        $this->command->info("Prescripción backfill: {$actualizadas} de {$total} rutinas con notas actualizadas.");
        if ($sinBloques > 0) {
            $this->command->line("  {$sinBloques} rutinas sin bloques de esfuerzo (no se les puede derivar prescripción).");
        }
        if ($inconsistentes) {
            $this->command->warn('  Inconsistentes (suma de bloques != series):');
            foreach ($inconsistentes as $fila) {
                $this->command->warn('    - '.$fila);
            }
        }
    }
}
