<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rutinas', function (Blueprint $table) {
            // Notas libres para guardar detalle que no entra en los campos
            // estructurados: RIR target por bloque, tecnicas especiales
            // (rest-pause, drop-set, cluster), tempo, etc.
            if (!Schema::hasColumn('rutinas', 'notas')) {
                $table->text('notas')->nullable()->after('superserie_grupo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rutinas', function (Blueprint $table) {
            if (Schema::hasColumn('rutinas', 'notas')) {
                $table->dropColumn('notas');
            }
        });
    }
};
