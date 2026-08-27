<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 5 — Reschedule: log de cambios manuales de `dia_actual`.
 *
 * El user puede haber entrenado un día distinto al "esperado" (por ejemplo,
 * se saltó el lunes y quiere registrar el martes como Día 1). Esta tabla
 * guarda un historial de cambios manuales para análisis y para mostrar
 * "Última reprogramación" en la UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_rutina_reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_rutina_id')->nullable()->constrained('user_rutinas')->nullOnDelete();
            $table->string('from_day', 64);
            $table->string('to_day', 64);
            $table->string('reason', 32)->nullable();  // 'missed_day' | 'manual' | 'trainer'
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_rutina_reschedules');
    }
};
