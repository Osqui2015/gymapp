<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0.5 — Trial como esquema mínimo en `membresias`.
 *
 * CONTEXTO
 * --------
 * La tabla `membresias` estaba VACÍA (0 filas) y `CheckMembership` tiene
 * una política explícita de "registro gratuito": si el usuario nunca tuvo
 * membresía, lo deja pasar. Consecuencia: los 8 usuarios `comun` operan
 * sin pagar y los morosos NUNCA se registran (no hay fila que venza).
 *
 * Un trial = membresía con `es_trial = true` y `trial_ends_at` en el futuro.
 * El middleware actual ya acepta `estado='activo'` con
 * `fecha_vencimiento >= hoy`, así que el trial FUNCIONA con el middleware
 * sin tocarlo: solo hay que mantener `trial_ends_at` sincronizado con
 * `fecha_vencimiento`.
 *
 * Ventajas del esquema de 2 columnas sobre una tabla `trials` aparte:
 *   - Cero tablas nuevas.
 *   - El trial aparece en los listados existentes de membresías.
 *   - El cálculo de MRR puede excluir trials con un solo filtro.
 *
 * Costo asumido: no permite medir conversión trial → membresía con
 * precisión, ni trials sin membresía asociada. Se acepta por ahora
 * (plan §8.2: "elegir un único modelo").
 *
 * COLUMNAS
 * --------
 *   es_trial      bool, default false. Marca la membresía como periodo de prueba.
 *   trial_ends_at date, nullable. Fecha de fin del trial (normalmente igual a
 *                 fecha_vencimiento). Nullable para no romper filas existentes.
 *
 * Ambas son NULLABLE/additive: `membresias` gobierna el acceso de los
 * usuarios, y un error acá bloquea la app entera (ver AGENTS.md y el
 * criterio de aceptación transversal del plan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresias', function (Blueprint $table) {
            if (! Schema::hasColumn('membresias', 'es_trial')) {
                $table->boolean('es_trial')->default(false)->after('estado');
            }

            if (! Schema::hasColumn('membresias', 'trial_ends_at')) {
                $table->date('trial_ends_at')->nullable()->after('es_trial');
            }
        });

        // `fecha_vencimiento` pasa a NULLABLE.
        //
        // Necesario para las membresías de CORTESÍA sin vencimiento (ver
        // la migración 2026_11_01_120000): un plan de cortesía no tiene
        // fecha de expiración, y antes el NOT NULL impedía crearlo.
        //
        // ⚠️ Requiere que TODOS los consumidores toleren NULL. Los que
        // tocan esta columna en la Fase 0:
        //   - CheckMembership: usa `whereNull(...) OR >= hoy` (ya preparado).
        //   - Membresia::actualizarEstados(): solo toca filas con estado
        //     activo/por_vencer; una membresía de cortesía tiene
        //     fecha_vencimiento NULL, así que getDiasRestantes() debe
        //     devolver un valor seguro (ver Membresia::getDiasRestantes()).
        //   - MembresiaController: las membresías de cortesía se listan
        //     igual que las demás.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE membresias MODIFY fecha_vencimiento DATE NULL');
        } else {
            Schema::table('membresias', function (Blueprint $table) {
                $table->date('fecha_vencimiento')->nullable()->change();
            });
        }

        // Índice parcial para encontrar trials activos rápido.
        // Se crea aparte porque `Schema::hasColumn` dentro del closure
        // no puede garantizar el orden de las columnas en MySQL.
        Schema::table('membresias', function (Blueprint $table) {
            $table->index(['es_trial', 'trial_ends_at'], 'membresias_trial_index');
        });
    }

    public function down(): void
    {
        Schema::table('membresias', function (Blueprint $table) {
            $table->dropIndex('membresias_trial_index');
        });

        // Antes de volver a NOT NULL, hay que deshacer la cortesía: las
        // membresías sin vencimiento no pueden volver a ser NOT NULL.
        DB::table('membresias')
            ->whereNull('fecha_vencimiento')
            ->update(['fecha_vencimiento' => now()->toDateString()]);

        Schema::table('membresias', function (Blueprint $table) {
            if (Schema::hasColumn('membresias', 'trial_ends_at')) {
                $table->dropColumn('trial_ends_at');
            }
            if (Schema::hasColumn('membresias', 'es_trial')) {
                $table->dropColumn('es_trial');
            }
        });
    }
};
