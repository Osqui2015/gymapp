<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // historials: queries más frecuentes son "historial del usuario",
        // "historial por ejercicio" y "calendario por fecha". Compuestos cubren
        // los 3 sin escanear toda la tabla.
        Schema::table('historials', function (Blueprint $table) {
            $table->index(['user_id', 'fecha'], 'idx_historials_user_fecha');
            $table->index(['user_id', 'ejercicio_nombre'], 'idx_historials_user_ejercicio');
        });

        Schema::table('sesiones_entrenamiento', function (Blueprint $table) {
            $table->index(['user_id', 'started_at'], 'idx_sesiones_user_started');
        });

        Schema::table('progresos', function (Blueprint $table) {
            $table->index(['user_id', 'fecha'], 'idx_progresos_user_fecha');
        });

        Schema::table('diario_nutricion', function (Blueprint $table) {
            $table->index(['user_id', 'fecha'], 'idx_diario_user_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            $table->dropIndex('idx_historials_user_fecha');
            $table->dropIndex('idx_historials_user_ejercicio');
        });

        Schema::table('sesiones_entrenamiento', function (Blueprint $table) {
            $table->dropIndex('idx_sesiones_user_started');
        });

        Schema::table('progresos', function (Blueprint $table) {
            $table->dropIndex('idx_progresos_user_fecha');
        });

        Schema::table('diario_nutricion', function (Blueprint $table) {
            $table->dropIndex('idx_diario_user_fecha');
        });
    }
};
