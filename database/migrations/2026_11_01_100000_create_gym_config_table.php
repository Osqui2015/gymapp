<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0.4 — Configuración del gimnasio (`gym_config`).
 *
 * CONTEXTO
 * --------
 * El plan (planAdmin.md §12.2) marca esto como BLOQUEANTE de la Fase 1
 * porque hoy hay valores hardcodeados que la app necesita cambiar:
 *
 *   - `CheckMembership::GRACE_PERIOD_DAYS = 3` (constante de clase; el
 *     `config('app.membership_grace_days')` que lo acompaña NUNCA se usó
 *     porque la clave no existía en config/app.php).
 *   - Duración del trial de bienvenida.
 *   - Días de aviso de vencimiento.
 *   - Moneda y zona horaria del gimnasio.
 *   - Métodos de pago habilitados.
 *
 * Y porque el plan exige que las fechas usen una zona horaria configurable
 * (§8.3) en lugar del `now()` implícito del servidor — que hoy es UTC.
 *
 * DISEÑO: tabla única key/value
 * -----------------------------
 * Se eligió key/value en vez de columnas fijas porque:
 *   - Agregar un parámetro no requiere migración (el panel de configuración
 *     crece sin deploy).
 *   - El servicio expone defaults tipados en código, así que el conjunto
 *     de claves sigue siendo explícito y auditable.
 *
 * El valor se guarda como JSON para admitir arrays (métodos de pago,
 * plantillas de mensajes) y escalares (días, moneda, timezone).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gym_config')) {
            return;
        }

        Schema::create('gym_config', function (Blueprint $table) {
            $table->id();

            /** Clave única del parámetro. Ej: 'trial_dias', 'moneda', 'timezone'. */
            $table->string('key', 100)->unique();

            /** Valor del parámetro, serializado como JSON. */
            $table->text('value')->nullable();

            /** Categoría para agrupar en la UI de configuración. */
            $table->string('categoria', 50)->default('general')->index();

            /** Descripción legible para la UI. */
            $table->string('descripcion', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_config');
    }
};
