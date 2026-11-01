<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `rutinas.share_token` NO puede tener índice UNIQUE.
     *
     * El token es POR PLAN, no por fila: al compartir, todas las filas del
     * plan (una por ejercicio) reciben el MISMO `share_token`, y la vista
     * pública (`GET /api/rutinas/publica/{token}`) las reagrupa por token
     * para reconstruir los días.
     *
     * Con el índice UNIQUE, compartir cualquier plan de más de un ejercicio
     * reventaba con "Duplicate entry ... for key 'rutinas_share_token_unique'"
     * → HTTP 500. El UPDATE es de una sola sentencia multi-fila, así que
     * además fallaba entero: la rutina no quedaba compartida a medias.
     */
    public function up(): void
    {
        // 1) Sacar el índice UNIQUE (si existe).
        $uniqueIndex = null;
        foreach (Schema::getIndexes('rutinas') as $index) {
            if (! empty($index['unique']) && ($index['columns'] ?? []) === ['share_token']) {
                $uniqueIndex = $index['name'];
                break;
            }
        }

        if ($uniqueIndex) {
            Schema::table('rutinas', function (Blueprint $table) use ($uniqueIndex) {
                $table->dropUnique($uniqueIndex);
            });
        }

        // 2) Índice normal: la vista pública busca por token.
        $yaExiste = false;
        foreach (Schema::getIndexes('rutinas') as $index) {
            if (($index['columns'] ?? []) === ['share_token']) {
                $yaExiste = true;
                break;
            }
        }

        if (! $yaExiste) {
            Schema::table('rutinas', function (Blueprint $table) {
                $table->index('share_token', 'rutinas_share_token_index');
            });
        }
    }

    public function down(): void
    {
        // Volver a poner el UNIQUE es seguro sólo si no hay planes compartidos
        // (compartir un plan de N ejercicios deja N filas con el mismo token).
        $duplicados = DB::table('rutinas')
            ->whereNotNull('share_token')
            ->groupBy('share_token')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicados > 0) {
            // No se puede reintroducir el UNIQUE: se deja el índice normal.
            return;
        }

        Schema::table('rutinas', function (Blueprint $table) {
            $table->dropIndex('rutinas_share_token_index');
            $table->unique('share_token');
        });
    }
};
