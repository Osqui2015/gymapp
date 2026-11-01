<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rutinas', function (Blueprint $table) {
            // Prescripcion estructurada: bloques (series/reps/tipo/valor con
            // tipo = rir | rpe | fallo), rest-pause (mini-sets con su descanso),
            // progresion, consejo y etiqueta.
            //
            // `notas` queda como texto libre (lo sigue usando el front que
            // parsea con regex); esta columna es la fuente de verdad para
            // renderizar bloques y rest-pause sin parsear prosa.
            if (! Schema::hasColumn('rutinas', 'prescripcion')) {
                $table->json('prescripcion')->nullable()->after('notas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rutinas', function (Blueprint $table) {
            if (Schema::hasColumn('rutinas', 'prescripcion')) {
                $table->dropColumn('prescripcion');
            }
        });
    }
};
