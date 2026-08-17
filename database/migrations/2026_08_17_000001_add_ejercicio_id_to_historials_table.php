<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resuelve D2/D3 (decisión de producto): `historials.ejercicio_nombre` (string)
 * → FK a `ejercicios.id` vía `ejercicio_id`.
 *
 * Mismo patrón aplicado antes a `rutinas.ejercicio_id` (migración
 * 2026_08_16_000000). El string `ejercicio_nombre` se mantiene por compat
 * con datos legacy y como fallback para filas sin match.
 *
 * Plan:
 *   1) Agregar `ejercicio_id` (nullable, FK a ejercicios.id).
 *   2) Backfill: para cada historial, setear ejercicio_id matching por nombre.
 *   3) NO dropear `ejercicio_nombre` todavía (queda como columna legacy).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente
        if (!Schema::hasColumn('historials', 'ejercicio_id')) {
            Schema::table('historials', function (Blueprint $table) {
                $table->unsignedBigInteger('ejercicio_id')->nullable()->after('ejercicio_nombre');
                $table->index('ejercicio_id');
                $table->foreign('ejercicio_id')
                    ->references('id')->on('ejercicios')
                    ->onDelete('set null');
            });
        } else {
            \Illuminate\Support\Facades\Log::info(
                '[migration] historials.ejercicio_id ya existe. Saltando ALTER TABLE.'
            );
        }

        // Backfill: matchear por nombre (portable, no usa UPDATE ... FROM)
        $ejercicios = DB::table('ejercicios')->pluck('id', 'nombre');
        $totalUpdated = 0;

        foreach ($ejercicios as $nombre => $id) {
            $totalUpdated += DB::table('historials')
                ->where('ejercicio_nombre', $nombre)
                ->whereNull('ejercicio_id')
                ->update(['ejercicio_id' => $id]);
        }

        $total = DB::table('historials')->count();
        $unmatched = $total - $totalUpdated;

        if ($unmatched > 0) {
            $nombresSinMatch = DB::table('historials')
                ->whereNull('ejercicio_id')
                ->distinct()
                ->pluck('ejercicio_nombre')
                ->take(50)
                ->toArray();

            \Illuminate\Support\Facades\Log::warning(
                "[migration] {$unmatched} historiales sin match en ejercicios. " .
                "Primeros nombres: " . implode(', ', $nombresSinMatch)
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('historials', 'ejercicio_id')) {
            Schema::table('historials', function (Blueprint $table) {
                $table->dropForeign(['ejercicio_id']);
                $table->dropIndex(['ejercicio_id']);
                $table->dropColumn('ejercicio_id');
            });
        }
    }
};
