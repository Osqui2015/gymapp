<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la FK `ejercicio_id` a la tabla `rutinas`, manteniendo la
     * columna legacy `ejercicio_nombre` (string) por compat.
     *
     * Plan:
     *   1) Agregar `ejercicio_id` (nullable, FK a ejercicios.id).
     *   2) Backfill: para cada rutina, setear ejercicio_id matching por nombre.
     *   3) NO dropear `ejercicio_nombre` todavía (queda como columna legacy).
     *
     * Próximos pasos (NO incluidos en esta migración):
     *   - Actualizar todos los call sites para usar la relación.
     *   - Crear una migración futura que drope `ejercicio_nombre` una vez
     *     verificado que nada lo lee.
     */
    public function up(): void
    {
        // Idempotente: si la columna ya existe (porque una corrida anterior
        // falló a mitad), no intentamos crearla de nuevo.
        if (!Schema::hasColumn('rutinas', 'ejercicio_id')) {
            Schema::table('rutinas', function (Blueprint $table) {
                $table->unsignedBigInteger('ejercicio_id')->nullable()->after('ejercicio_nombre');
                $table->index('ejercicio_id');
                $table->foreign('ejercicio_id')
                    ->references('id')->on('ejercicios')
                    ->onDelete('set null');
            });
        } else {
            // La columna ya existe (probablemente de una migración fallida a
            // mitad). Solo nos aseguramos de que la FK esté bien.
            // SQLite no soporta ADD CONSTRAINT, pero si la migración anterior
            // falló en el UPDATE (no en el ALTER), la FK YA está aplicada.
            // Si solo se creó la columna sin FK, esto no la puede agregar.
            // En ese caso, logueamos para que el user lo arregle manualmente.
            \Illuminate\Support\Facades\Log::info(
                '[migration] rutinas.ejercicio_id ya existe. ' .
                'Saltando ALTER TABLE. Si la FK no se aplicó, ver migración manual.'
            );
        }

        // Backfill: matchear por nombre.
        //
        // IMPORTANTE: NO usamos `update rutinas r set ejercicio_id = e.id from ejercicios e where ...`
        // porque **SQLite no lo soporta** (la sintaxis `UPDATE ... FROM` es propia de MySQL/Postgres).
        // La forma portable es iterar por cada nombre de ejercicio y hacer UPDATE individuales.
        // Es más lento para tablas grandes, pero funciona en todos los drivers.
        //
        // Es idempotente: solo actualiza filas con `ejercicio_id IS NULL`.
        $ejercicios = DB::table('ejercicios')->pluck('id', 'nombre');
        $totalUpdated = 0;

        foreach ($ejercicios as $nombre => $id) {
            $totalUpdated += DB::table('rutinas')
                ->where('ejercicio_nombre', $nombre)
                ->whereNull('ejercicio_id')
                ->update(['ejercicio_id' => $id]);
        }

        $total = DB::table('rutinas')->count();
        $unmatched = $total - $totalUpdated;

        if ($unmatched > 0) {
            $nombresSinMatch = DB::table('rutinas')
                ->whereNull('ejercicio_id')
                ->distinct()
                ->pluck('ejercicio_nombre')
                ->take(50)
                ->toArray();

            \Illuminate\Support\Facades\Log::warning(
                "[migration] {$unmatched} rutinas sin match en ejercicios. " .
                "Primeros nombres: " . implode(', ', $nombresSinMatch)
            );
        }
    }

    public function down(): void
    {
        // Solo dropear si la columna existe (rollback idempotente)
        if (Schema::hasColumn('rutinas', 'ejercicio_id')) {
            Schema::table('rutinas', function (Blueprint $table) {
                $table->dropForeign(['ejercicio_id']);
                $table->dropIndex(['ejercicio_id']);
                $table->dropColumn('ejercicio_id');
            });
        }
    }
};
