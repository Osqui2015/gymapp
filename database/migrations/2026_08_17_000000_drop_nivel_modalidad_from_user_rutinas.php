<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resuelve D1 (decisión de producto): denormalización de `nivel` y `modalidad`
 * en `user_rutinas`.
 *
 * Estas columnas eran un snapshot de `rutinas.nivel` y `rutinas.modalidad` al
 * momento de asignar la rutina al usuario. Generaban drift: si la Rutina
 * original cambiaba su `nivel` o `modalidad`, los `UserRutina` viejos
 * quedaban desactualizados.
 *
 * Decisión tomada: source of truth es siempre `rutinas` (vía FK `rutina_id`).
 * Los accessors del modelo `UserRutina` ya leen de la relación y caen al
 * campo denormalizado. Como las columnas denormalizadas ya no son necesarias,
 * las dropeamos.
 *
 * Backwards-incompatible:
 *   - Código que escribía a `user_rutinas.nivel` o `user_rutinas.modalidad`
 *     debe usar `rutina_id` y leer de la relación.
 *   - Endpoints que recibían `nivel`/`modalidad` deben recibir `rutina_id`.
 *   - El accessor `$userRutina->nivel` y `$userRutina->modalidad` sigue
 *     funcionando, pero ahora siempre lee de la relación (si no está
 *     cargada, devolverá null — hacer `->load('rutina')` antes).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente: si las columnas no existen (porque una corrida anterior
        // las dropeó), no hacer nada.
        if (Schema::hasColumn('user_rutinas', 'nivel')) {
            Schema::table('user_rutinas', function (Blueprint $table) {
                $table->dropColumn('nivel');
            });
        }
        if (Schema::hasColumn('user_rutinas', 'modalidad')) {
            Schema::table('user_rutinas', function (Blueprint $table) {
                $table->dropColumn('modalidad');
            });
        }
    }

    public function down(): void
    {
        // Re-creamos las columnas. Si la tabla tiene `rutina_id` y el user
        // quiere volver al approach snapshot, debe correr un backfill
        // adicional (no incluido acá).
        if (!Schema::hasColumn('user_rutinas', 'nivel')) {
            Schema::table('user_rutinas', function (Blueprint $table) {
                $table->string('nivel')->nullable()->after('rutina_id');
            });
        }
        if (!Schema::hasColumn('user_rutinas', 'modalidad')) {
            Schema::table('user_rutinas', function (Blueprint $table) {
                $table->string('modalidad')->nullable()->after('nivel');
            });
        }
    }
};
