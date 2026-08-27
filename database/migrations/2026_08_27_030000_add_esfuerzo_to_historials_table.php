<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3 — RIR / RPE tracking por set.
 *
 * - `esfuerzo_tipo`: 'rir' o 'rpe' (qué escala usa el usuario en este set)
 * - `esfuerzo_valor`: número entero
 *   - RIR: 0..5  (reps in reserve; 0 = fallo, 5 = muy fácil)
 *   - RPE: 6..10 (rate of perceived exertion; 6 = muchas reps en reserva, 10 = fallo)
 *
 * Ambos campos son nullables: el tracking es opcional y no rompe compat
 * con historials viejos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            $table->string('esfuerzo_tipo', 4)->nullable()->after('peso');
            $table->unsignedTinyInteger('esfuerzo_valor')->nullable()->after('esfuerzo_tipo');

            $table->index(['user_id', 'esfuerzo_tipo'], 'historials_user_esfuerzo_idx');
        });
    }

    public function down(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            $table->dropIndex('historials_user_esfuerzo_idx');
            $table->dropColumn(['esfuerzo_tipo', 'esfuerzo_valor']);
        });
    }
};
