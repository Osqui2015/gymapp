<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0.3 — `scheduler_runs`: heartbeat de las tareas del scheduler.
 *
 * CONTEXTO (plan §12.6)
 * ---------------------
 * `bootstrap/app.php` define 5 tareas diarias:
 *
 *   00:05  membresias:actualizar-estados
 *   02:00  db:backup --retention-days=7
 *   03:30  audit-logs:purge
 *   09:00  reminders:send
 *   10:00  plateaus:detect
 *
 * Pero NO HAY NINGÚN REGISTRO de sus ejecuciones. Si el cron del server
 * (Hostinger) no está configurado, NINGUNA corre — y tampoco el backup
 * diario de la base — sin que nadie se entere. El admin vería un panel
 * con datos viejos y no sabría por qué.
 *
 * Esta tabla es el heartbeat: cada tarea escribe una fila al terminar.
 * Con eso el panel puede mostrar "🟢 cron ok · último backup 03:02" o
 * "🔴 el cron no corre desde hace 26 h".
 *
 * DISEÑO
 * ------
 * Una fila por (tarea, ejecución). Se conservan los últimos N días para
 * poder ver la HISTORIA de cuando una tarea empezó a fallar — no solo el
 * estado actual. El purge de esta tabla lo hace el propio scheduler.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scheduler_runs')) {
            return;
        }

        Schema::create('scheduler_runs', function (Blueprint $table) {
            $table->id();

            /** Nombre de la tarea/comando. Ej: 'db:backup-diario', 'reminders:send'. */
            $table->string('task', 100)->index();

            /** 'ok' | 'fail' */
            $table->string('status', 20)->default('ok')->index();

            /** Duración en segundos. */
            $table->decimal('duration_ms', 12, 2)->nullable();

            /** Mensaje de error si status = 'fail'. */
            $table->text('error')->nullable();

            /** Parámetros con los que corrió (ej: --retention-days=7). */
            $table->string('params', 255)->nullable();

            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
        });

        // Índice compuesto para el caso de uso principal:
        // "última ejecución de cada tarea".
        Schema::table('scheduler_runs', function (Blueprint $table) {
            $table->index(['task', 'finished_at'], 'scheduler_runs_task_finished_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduler_runs');
    }
};
