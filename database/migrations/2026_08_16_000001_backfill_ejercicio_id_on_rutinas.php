<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill de `rutinas.ejercicio_id` (corre después de la migración
 * 2026_08_16_000000 que ya agregó la columna + FK).
 *
 * ¿Por qué una migración separada?
 *   La migración original mezclaba el ALTER TABLE con el UPDATE de backfill.
 *   El UPDATE con `... SET ejercicio_id = e.id` (UPDATE ... FROM) NO funciona
 *   en SQLite. Lo separamos para que:
 *     1) La migración original quede portable (la voy a arreglar).
 *     2) Esta migración SOLO hace el backfill, también portable (UPDATE por
 *        cada nombre de ejercicio, en lugar de UPDATE ... FROM).
 *
 * Es idempotente: solo actualiza filas con `ejercicio_id IS NULL`, así que
 * se puede correr varias veces sin daño.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Verificar que la columna existe (sanity check)
        if (!\Illuminate\Support\Facades\Schema::hasColumn('rutinas', 'ejercicio_id')) {
            throw new \RuntimeException(
                'La columna rutinas.ejercicio_id no existe. ' .
                'Corré primero la migración 2026_08_16_000000_add_ejercicio_id_to_rutinas_table'
            );
        }

        $ejercicios = DB::table('ejercicios')->pluck('id', 'nombre');
        $totalUpdated = 0;

        foreach ($ejercicios as $nombre => $id) {
            $totalUpdated += DB::table('rutinas')
                ->where('ejercicio_nombre', $nombre)
                ->whereNull('ejercicio_id')
                ->update(['ejercicio_id' => $id]);
        }

        $total = DB::table('rutinas')->count();
        $matched = DB::table('rutinas')->whereNotNull('ejercicio_id')->count();
        $unmatched = $total - $matched;

        \Illuminate\Support\Facades\Log::info(
            "[backfill R1] {$matched}/{$total} rutinas con ejercicio_id seteado. " .
            "{$unmatched} sin match (queda para revisión manual)."
        );
    }

    public function down(): void
    {
        // No revertimos el backfill: si el user quiere, puede correr
        // UPDATE rutinas SET ejercicio_id = NULL manualmente.
        // El rollback semántico acá sería "deshacer lo que el backfill hizo",
        // pero eso es destructivo (perderíamos el mapeo) y el user puede
        // elegir cuándo hacerlo.
    }
};
