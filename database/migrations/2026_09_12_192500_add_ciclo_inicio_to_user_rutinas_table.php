<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca el inicio del ciclo actual de la rutina.
 *
 * - Antes: cuando el usuario finalizaba el último día, los registros viejos
 *   seguían apareciendo marcados en el dashboard del nuevo ciclo (Día 1),
 *   porque `historial` no distinguía ciclos.
 * - Ahora: al finalizar el último día, el sistema setea `ciclo_inicio = hoy`.
 *   El dashboard filtra `historial.fecha >= user_rutinas.ciclo_inicio` para
 *   que el ciclo nuevo arranque limpio. La página Historial sigue mostrando
 *   todo el histórico (no se borra nada).
 * - `null` = comportamiento legacy (sin filtro), aplica a usuarios existentes
 *   hasta que terminen su próximo ciclo completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_rutinas', function (Blueprint $table) {
            $table->date('ciclo_inicio')->nullable()->after('dia_actual');
        });
    }

    public function down(): void
    {
        Schema::table('user_rutinas', function (Blueprint $table) {
            $table->dropColumn('ciclo_inicio');
        });
    }
};
