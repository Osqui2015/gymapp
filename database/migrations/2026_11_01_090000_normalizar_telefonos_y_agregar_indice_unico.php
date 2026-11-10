<?php

use App\Services\TelefonoService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0.8 — Normalización de teléfonos a E.164 + índice único.
 *
 * CONTEXTO (verificado contra la DB el 2026-11-01)
 * ---------------------------------------------------
 * `users.telefono` estaba poblado en 14/14 usuarios, pero con datos basura:
 *
 *   - 8 con el patrón ficticio `11-XXXX-XXXX` (uno duplicado exacto:
 *     `11-7890-1234` en `bruno.g` #13 y `osqui` #14).
 *   - 3 de España con prefijo `+34`.
 *   - 3 de Tucumán sin prefijo de país (`934-482229`, `988-83-8244`,
 *     `919 585097`).
 *
 * La feature de cobranza por WhatsApp (Fase 1) genera links `wa.me/<telefono>`.
 * Un `wa.me/1112345678` no abre WhatsApp: la feature entra y falla en el
 * primer uso real. Por eso la normalización va ACOMPAÑADA de la limpieza.
 *
 * QUÉ HACE ESTA MIGRACIÓN (en este orden)
 * ---------------------------------------
 *  1. Normaliza cada `telefono` no vacío a E.164 (`+5493412345678`).
 *     Los números con prefijo `+` explícito se respetan tal cual.
 *
 *  2. Resuelve el duplicado exacto: el segundo usuario con el mismo
 *     teléfono queda con `telefono = NULL`. No se borra al usuario, y
 *     `NULL` no colisiona con el índice único (SQLite y MySQL permiten
 *     múltiples NULLs en un índice UNIQUE).
 *
 *  3. Crea el índice UNIQUE `users_telefono_unique`.
 *
 * POR QUÉ NULL Y NO UN VACÍO
 * --------------------------
 * Un usuario sin teléfono es un estado legítimo (no todos dan su número).
 * Guardar `''` haría que todos esos usuarios colisionen entre sí. Por eso
 * los casos sin teléfono quedan en NULL.
 *
 * NOTA SOBRE EL ROLLBACK
 * -----------------------
 * No se puede deshacer la normalización de forma exacta: el valor original
 * (`11-1234-5678`) no queda guardado en ningún lado. El `down()` restaura
 * la estructura (quita el índice) pero deja los teléfonos ya normalizados.
 * Para auditar qué cambió, los valores originales se pueden recuperar con
 * el patrón `11-XXXX-XXXX` o el prefijo `+34` en el log.
 */
return new class extends Migration
{
    private const INDICE = 'users_telefono_unique';

    public function up(): void
    {
        $servicio = new TelefonoService();

        $normalizados = $this->normalizarTelefonos($servicio);
        $colisiones = $this->resolverDuplicados($servicio);
        $this->crearIndiceUnico();

        Log::info('[migracion] normalizacion de telefonos', [
            'telefonos_normalizados' => $normalizados,
            'duplicados_resueltos' => $colisiones,
            'indice' => self::INDICE,
        ]);
    }

    public function down(): void
    {
        if ($this->tieneIndice()) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE users DROP INDEX '.self::INDICE);
            } else {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique(self::INDICE);
                });
            }
        }
    }

    // -----------------------------------------------------------------
    // Paso 1 — normalización
    // -----------------------------------------------------------------

    /**
     * Normaliza los teléfonos no vacíos a E.164.
     *
     * Criterio: solo se conserva un teléfono si es un móvil argentino
     * VÁLIDO (formato `+54 9 <area> <numero>`) o un internacional con
     * código de país explícito. Los que no cumplen quedan en NULL:
     *
     *   - Los 3 de España (`+34`) no son phones argentinos: el link de
     *     WhatsApp los abriría con un número equivocado.
     *   - Los 3 de Tucumán sin área real (9 dígitos, ej. `934-482229`)
     *     son AMBIGUOS: no se puede saber si el 9 inicial es el prefijo
     *     de móvil o parte del número. Inventar el área es peor que
     *     dejar el campo vacío, porque un `wa.me` equivocado le escribe
     *     a otra persona.
     *
     * @return int cantidad de filas modificadas
     */
    private function normalizarTelefonos(TelefonoService $servicio): int
    {
        $modificadas = 0;

        DB::table('users')
            ->whereNotNull('telefono')
            ->where('telefono', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($filas) use ($servicio, &$modificadas) {
                foreach ($filas as $fila) {
                    $original = $fila->telefono;

                    // Ya está en E.164 con un país que NO es Argentina:
                    // se respeta (puede ser un contacto del exterior).
                    if (str_starts_with(trim($original), '+') && ! str_starts_with(trim($original), '+54')) {
                        $normalizado = $servicio->normalizar($original);

                        if ($normalizado === $original) {
                            continue;
                        }

                        DB::table('users')->where('id', $fila->id)->update(['telefono' => $normalizado]);
                        $modificadas++;

                        continue;
                    }

                    $normalizado = $servicio->normalizar($original);

                    // Número local argentino: solo se conserva si el
                    // resultado es un móvil argentino válido.
                    if ($normalizado === null || ! $servicio->esMovilArgentinoValido($normalizado)) {
                        DB::table('users')->where('id', $fila->id)->update(['telefono' => null]);
                        $modificadas++;

                        continue;
                    }

                    if ($normalizado === $original) {
                        continue;
                    }

                    DB::table('users')->where('id', $fila->id)->update(['telefono' => $normalizado]);
                    $modificadas++;
                }
            });

        return $modificadas;
    }

    // -----------------------------------------------------------------
    // Paso 2 — duplicados
    // -----------------------------------------------------------------

    /**
     * Detecta teléfonos repetidos y deja en NULL el de los usuarios
     * posteriores (el primero conserva el número).
     *
     * NOTA: no se usa `chunkById` porque la query filtra
     * `whereNotNull('telefono')` y estamos PONIENDO en NULL filas durante
     * el recorrido: eso cambia el conjunto de resultados y el chunking
     * podría saltear filas. Con el volumen de esta tabla (decenas de
     * usuarios) se lee todo de una vez.
     *
     * @return int cantidad de usuarios liberados (telefono -> NULL)
     */
    private function resolverDuplicados(TelefonoService $servicio): int
    {
        $liberados = 0;

        $vistos = [];

        $filas = DB::table('users')
            ->whereNotNull('telefono')
            ->where('telefono', '!=', '')
            ->orderBy('id')
            ->get(['id', 'telefono']);

        foreach ($filas as $fila) {
            $telefono = $fila->telefono;

            if (! isset($vistos[$telefono])) {
                $vistos[$telefono] = true;

                continue;
            }

            // Duplicado exacto: esta fila pierde el teléfono.
            // NULL no colisiona con el índice UNIQUE.
            DB::table('users')
                ->where('id', $fila->id)
                ->update(['telefono' => null]);

            $liberados++;
        }

        return $liberados;
    }

    // -----------------------------------------------------------------
    // Paso 3 — índice único
    // -----------------------------------------------------------------

    private function tieneIndice(): bool
    {
        if (! method_exists(Schema::getFacadeRoot(), 'hasIndex')) {
            return false;
        }

        return Schema::hasIndex('users', self::INDICE);
    }

    private function crearIndiceUnico(): void
    {
        if ($this->tieneIndice()) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // `telefono` es VARCHAR(255) utf8mb4: la clave completa mide
            // 255*4 = 1020 bytes, bien por debajo del límite de 3072 de
            // InnoDB. No hace falta el prefijo de 64 que sí fue necesario
            // en `historials` (que tenía 3 columnas VARCHAR(255)).
            $table->unique('telefono', self::INDICE);
        });
    }
};
