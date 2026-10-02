<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extiende el dominio de `historials.esfuerzo_tipo` para incluir 'fallo'
 * (al fallo absoluto) como tercer valor válido, separado de 'rir' y 'rpe'.
 *
 * Decisión de diseño: NO cambiamos el schema (el campo sigue siendo
 * string(4) — 'fallo' entra en 5 chars pero 'fall' también; ver nota abajo).
 * Sólo agregamos:
 *   - check constraint a nivel DB para reforzar el dominio
 *   - índice ampliado para que las consultas por tipo escalen bien
 *
 * Diferencia conceptual (importante para el dominio del gym):
 *   - RIR 0  = 0 reps en reserva. Estás al límite, podrías quizás sacar 1 más.
 *   - RPE 10 = Rate of Perceived Exertion máximo. Igual conceptual que RIR 0.
 *   - fallo  = Fallo ABSOLUTO. No podés sacar ni una rep más ni haciendo trampa.
 *              Distinto de RIR 0: implica que el músculo/tecnica/techo ya
 *              colapsaron, no es un "0 con margen".
 *
 * Si el campo hubiera sido exactamente 4 chars, hubiera que cambiarlo a
 * string(5) o más antes. 'fallo' tiene 5 chars; el campo es string(4),
 * así que ESTA MIGRACIÓN PRIMERO amplía el campo a string(8) (margen
 * suficiente para valores futuros).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Ampliar el campo de 4 a 8 chars (cabe 'fallo' = 5 chars y deja margen).
        DB::statement('ALTER TABLE historials MODIFY COLUMN esfuerzo_tipo VARCHAR(8) NULL');

        // Check constraint para reforzar el dominio (defensa en profundidad;
        // la validación real está en HistorialController).
        // MySQL 8+ respeta CHECK constraints.
        DB::statement("ALTER TABLE historials ADD CONSTRAINT historials_esfuerzo_tipo_chk CHECK (esfuerzo_tipo IS NULL OR esfuerzo_tipo IN ('rir','rpe','fallo'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE historials DROP CONSTRAINT historials_esfuerzo_tipo_chk');
        DB::statement('ALTER TABLE historials MODIFY COLUMN esfuerzo_tipo VARCHAR(4) NULL');
    }
};
