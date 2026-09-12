<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: setea `ciclo_inicio = hoy` para usuarios existentes que ya
 * terminaron su primer ciclo antes de que se implementara el flag.
 *
 * Detección de "estado cycle reset":
 *   1. `ciclo_inicio` está NULL
 *   2. `dia_actual` empieza con "Día 1" (cubre "Día 1", "Día 1 (Torso)", etc.)
 *   3. El usuario tiene AL MENOS un set completado para el ÚLTIMO día
 *      de su rutina (señal de que cerró el ciclo)
 *
 * Para usuarios que nunca cerraron un ciclo, el backfill es no-op aunque
 * estén en Día 1 (la condición 3 los descarta).
 *
 * Esto es one-shot: corre una vez al deploy. Después, `finalizarRutinaDia`
 * setea el flag automáticamente para nuevos cierres de ciclo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $today = now()->toDateString();

        // Candidatos: ciclo_inicio null + dia_actual empieza con "Día 1"
        $candidatos = DB::table('user_rutinas as ur')
            ->leftJoin('rutinas as r', 'ur.rutina_id', '=', 'r.id')
            ->whereNull('ur.ciclo_inicio')
            ->where('ur.dia_actual', 'LIKE', 'Día 1%')
            ->select('ur.id', 'ur.user_id', 'r.nivel', 'r.modalidad')
            ->get();

        foreach ($candidatos as $c) {
            if (! $c->nivel || ! $c->modalidad) {
                continue;
            }

            $rutinaNombre = $c->nivel.' '.$c->modalidad;

            // Último día (alfabéticamente) de la rutina
            $ultimoDia = DB::table('rutinas')
                ->where('nivel', $c->nivel)
                ->where('modalidad', $c->modalidad)
                ->orderBy('dia', 'desc')
                ->value('dia');

            if (! $ultimoDia) {
                continue;
            }

            // ¿El usuario tiene algún set completado del último día?
            $tieneUltimoDia = DB::table('historials')
                ->where('user_id', $c->user_id)
                ->where('rutina_nombre', $rutinaNombre)
                ->where('dia', $ultimoDia)
                ->where('completado', true)
                ->exists();

            if ($tieneUltimoDia) {
                DB::table('user_rutinas')
                    ->where('id', $c->id)
                    ->update(['ciclo_inicio' => $today]);
            }
        }
    }

    public function down(): void
    {
        // No revertimos: si el backfill asignó mal, se corrige en el próximo
        // ciclo. Borrar el flag sería peor (romperíamos el filtro para
        // usuarios que sí están en cycle reset).
    }
};
