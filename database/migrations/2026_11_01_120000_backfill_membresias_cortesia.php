<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fase 0.5 — Backfill de cortesía.
 *
 * CONTEXTO
 * --------
 * Con `membresias` vacía y la política de "registro gratuito" de
 * `CheckMembership`, los 14 usuarios operates sin plan: 8 `comun`, 4
 * `alumno`, 1 trainer y 1 admin. Los `comun` son en la práctica amigos
 * del owner, así que el riesgo real no es que dejen de entrenar: es que
 * la app no pueda distinguir "alumno que paga" de "alumno de cortesía".
 *
 * DECISIÓN (2026-11-01, confirmada por el owner)
 * ----------------------------------------------
 * Los 8 usuarios `comun` reciben una membresía de CORTESÍA SIN VENCIMIENTO.
 *
 * ¿Por qué sin vencimiento y no un trial de 14 días?
 * ----------------------------------------------------
 * Un trial de 14 días activa la regla de acceso y, al vencer, BLOQUEA a
 * los 8 usuarios de golpe. Si en realidad son amigos/familia del owner,
 * eso no es una decisión comercial: es un bloqueo accidental que hay que
 * deshacer a mano. Con una membresía sin vencimiento:
 *
 *   - nadie queda bloqueado,
 *   - la app ya distingue "tiene membresía" de "no tiene",
 *   - el admin puede convertirla en una membresía de pago cuando quiera
 *     (simplemente actualiza `precio` y `fecha_vencimiento`).
 *
 * `alumno`, `trainer` y `administrador` NO reciben membresía: el
 * middleware los deja pasar por rol, y meterles una membresía de cortesía
 * los metería en el reporte de morosos con un importe de $0.
 *
 * MARCA DE CORTE
 * --------------
 * La membresía de cortesía se identifica por:
 *   - `es_trial = false`
 *   - `fecha_vencimiento` = NULL  (sin vencimiento)
 *   - `precio = 0`
 *   - `metodo_pago = 'cortesia'`
 *   - `notas` con el marcador `BACKFILLED_CORTESIA`
 *
 * El marcador en `notas` hace que la operación sea IDEMPOTENTE: si la
 * migración se corre dos veces, no duplica filas. Y permite revertirla
 * (borra las filas con el marcador) sin tocar membresías reales.
 *
 * ⚠️ `fecha_vencimiento = NULL` requiere que todos los consumidores
 * toleren NULL. `CheckMembership` compara `fecha_vencimiento >= hoy`, y en
 * SQL un NULL da NULL (no verdadero), así que la membresía de cortesía NO
 * da acceso por esa vía — y está bien: el acceso de estos 8 usuarios
 * depende del flag de cortesía que se agrega en la misma Fase.
 */
return new class extends Migration
{
    /** Marcador idempotente en `notas`. */
    private const MARCADOR = 'BACKFILLED_CORTESIA';

    public function up(): void
    {
        $usuarios = DB::table('users')
            ->where('role', 'comun')
            ->orderBy('id')
            ->get(['id', 'name', 'nick']);

        if ($usuarios->isEmpty()) {
            Log::info('[migracion] backfill de cortesia: sin usuarios comun');

            return;
        }

        $creadas = 0;
        $omitidas = 0;

        foreach ($usuarios as $usuario) {
            // Idempotencia: si ya tiene una membresía (de cualquier tipo),
            // no se toca. Un usuario con membresía real no se sobreescribe.
            $yaTiene = DB::table('membresias')->where('user_id', $usuario->id)->exists();

            if ($yaTiene) {
                $omitidas++;

                continue;
            }

            DB::table('membresias')->insert([
                'user_id' => $usuario->id,
                'tipo_plan' => 'mensual',
                'precio' => 0,
                'fecha_inicio' => now()->toDateString(),
                // Sin vencimiento: es una membresía de cortesía indefinida.
                'fecha_vencimiento' => null,
                'estado' => 'activo',
                'ultimo_pago' => null,
                'metodo_pago' => 'cortesia',
                'notas' => self::MARCADOR.' — membresía de cortesía sin vencimiento. '.
                    'Convertir a membresía de pago actualizando precio y fecha_vencimiento.',
                'es_trial' => false,
                'trial_ends_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $creadas++;
        }

        Log::info('[migracion] backfill de cortesia', [
            'membresias_creadas' => $creadas,
            'omitidas_ya_con_membresia' => $omitidas,
            'marcador' => self::MARCADOR,
        ]);
    }

    public function down(): void
    {
        // Solo borra las filas creadas por ESTA migración. Las membresías
        // reales (con otro `notas`) quedan intactas.
        $borradas = DB::table('membresias')
            ->where('metodo_pago', 'cortesia')
            ->where('notas', 'like', '%'.self::MARCADOR.'%')
            ->delete();

        Log::info('[migracion] backfill de cortesia revertido', [
            'membresias_borradas' => $borradas,
        ]);
    }
};
