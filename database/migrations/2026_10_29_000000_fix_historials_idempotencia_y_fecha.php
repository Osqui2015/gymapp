<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * P0 — Idempotencia del historial: la clave única debe incluir `fecha`.
 *
 * CONTEXTO (bug corregido en HistorialController::guardar)
 * -------------------------------------------------------
 * `Historial::updateOrCreate()` usaba la clave
 *     (user_id, rutina_nombre, dia, ejercicio_nombre, series_numero)
 * SIN `fecha`. Consecuencia: al volver a registrar el mismo set en otra
 * fecha, Laravel buscaba la fila existente y la ACTUALIZABA, pisando la
 * sesión anterior. El historial no acumulaba: se sobreescribía.
 *
 * En la base de desarrollo eso dejó 161 de 210 filas (77%) con `fecha`
 * apuntando a la fecha de la última actualización en lugar de la original.
 *
 * Qué hace esta migración (en este orden):
 *
 *  1. Crea la tabla sombra `historials_reparacion_fecha`, donde queda
 *     registrado el `fecha` ORIGINAL de cada fila que se modifica.
 *     Gracias a esto la reparación es 100% reversible y auditable.
 *
 *  2. Backfill: para las filas cuya `fecha` fue movida a la fecha del
 *     `updated_at` (huella inequívoca de un overwrite), se restaura
 *     `fecha = DATE(created_at)`, que es la única traza que quedó de la
 *     sesión original.
 *
 *     LIMITACIÓN CONOCIDA: el `peso`/`reps` de esa sesión original no se
 *     pueden recuperar (fueron sobrescritos en la fila). La reparación
 *     restituye la fecha correcta y conserva la última medición conocida,
 *     que es la mejor aproximación disponible. Ver README de esta migration.
 *
 *  3. Dedupe: una vez applied el backfill puede haber creado colisiones
 *     reales (mismo set, misma fecha). Se fusionan, quedándose con la fila
 *     más completa (completada, con peso, y la de menor id).
 *
 *  4. Índice UNIQUE sobre la clave completa, para que la base de datos
 *     garantice la invariante y no dependa del código de aplicación.
 *
 * Sobre el índice: en MySQL las columnas son VARCHAR(255) utf8mb4, y la
 * clave completa mediría 8 + (255*4)*3 + 4 + 3 = 3075 bytes, apenas por
 * encima del límite de 3072 de InnoDB (ERROR 1071). Por eso en MySQL se
 * crea con prefijos de 64 caracteres —el doble del largo real máximo
 * observado (36)— y en SQLite (sin límite de key length) con las columnas
 * completas.
 */
return new class extends Migration
{
    private const TABLA_SOMBRA = 'historials_reparacion_fecha';

    private const INDICE = 'uq_historials_idempotencia';

    /** Columnas que se copian del duplicado al keeper cuando faltan en este. */
    private const CAMPOS_FUSION = [
        'ejercicio_id',
        'peso',
        'reps_realizadas',
        'reps_min',
        'reps_max',
        'descanso_min',
        'completado',
        'tipo_serie',
        'esfuerzo_tipo',
        'esfuerzo_valor',
        'nota_user',
        'comentario_trainer',
        'trainer_id',
        'superserie_grupo',
        'sesion_uuid',
        'series_completadas',
    ];

    public function up(): void
    {
        $this->crearTablaSombra();

        $reparadas = $this->repararFechas();
        $fusionadas = $this->fusionarDuplicados();
        $this->crearIndiceUnico();

        Log::info('[migracion] idempotencia de historials', [
            'fechas_restauradas' => $reparadas,
            'filas_fusionadas' => $fusionadas,
            'indice' => self::INDICE,
        ]);
    }

    public function down(): void
    {
        // 1) Restaurar las fechas originales desde la tabla sombra.
        if (Schema::hasTable(self::TABLA_SOMBRA)) {
            foreach (DB::table(self::TABLA_SOMBRA)->orderBy('id')->get() as $row) {
                DB::table('historials')
                    ->where('id', $row->id)
                    ->update(['fecha' => $row->fecha_original]);
            }
            Schema::drop(self::TABLA_SOMBRA);
        }

        // 2) Sacar el índice.
        if ($this->tieneIndice()) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE historials DROP INDEX '.self::INDICE);
            } else {
                Schema::table('historials', function (Blueprint $table) {
                    $table->dropUnique(self::INDICE);
                });
            }
        }
    }

    // -----------------------------------------------------------------
    // Paso 1 — tabla sombra
    // -----------------------------------------------------------------

    private function crearTablaSombra(): void
    {
        if (Schema::hasTable(self::TABLA_SOMBRA)) {
            return;
        }

        Schema::create(self::TABLA_SOMBRA, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('historial_id')->index();
            $table->date('fecha_original');
            $table->date('fecha_reparada')->nullable();
            $table->timestamp('reparado_at')->nullable();
        });
    }

    // -----------------------------------------------------------------
    // Paso 2 — backfill de la fecha pisada
    // -----------------------------------------------------------------

    /**
     * Restaura `fecha = DATE(created_at)` en las filas cuya `fecha` fue
     * movida a la fecha del `updated_at` (la huella de un overwrite).
     *
     * @return int cantidad de filas reparadas
     */
    private function repararFechas(): int
    {
        $reparadas = 0;

        DB::table('historials')
            ->select('id', 'fecha', 'created_at', 'updated_at')
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->orderBy('id')
            ->chunkById(1000, function ($filas) use (&$reparadas) {
                foreach ($filas as $fila) {
                    $fecha = $this->parteFecha($fila->fecha);
                    $creado = $this->parteFecha($fila->created_at);
                    $actualizado = $this->parteFecha($fila->updated_at);

                    // Solo si la fecha fue movida exactamente a la fecha de
                    // actualización y difiere de la de creación.
                    if ($fecha === null || $creado === null || $actualizado === null) {
                        continue;
                    }
                    if ($fecha === $creado || $fecha !== $actualizado) {
                        continue;
                    }

                    DB::table(self::TABLA_SOMBRA)->insert([
                        'historial_id' => $fila->id,
                        'fecha_original' => $fecha,
                        'fecha_reparada' => $creado,
                        'reparado_at' => now(),
                    ]);

                    DB::table('historials')
                        ->where('id', $fila->id)
                        // Se escribe con la hora a cero para que el valor sea
                        // idéntico al que deja el cast 'date' del modelo
                        // ('Y-m-d H:i:s'). En MySQL la columna DATE lo
                        // normaliza; en SQLite queda la cadena completa y así el
                        // índice UNIQUE compara valores homogéneos.
                        ->update(['fecha' => $creado.' 00:00:00']);

                    $reparadas++;
                }
            });

        return $reparadas;
    }

    /**
     * Normaliza cualquier valor de fecha a 'YYYY-MM-DD'.
     * MySQL devuelve 'Y-m-d' para columnas DATE y 'Y-m-d H:i:s' para
     * timestamps; SQLite devuelve 'Y-m-d H:i:s'.
     */
    private function parteFecha($valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $texto = substr((string) $valor, 0, 10);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto) === 1 ? $texto : null;
    }

    // -----------------------------------------------------------------
    // Paso 3 — fusión de duplicados reales
    // -----------------------------------------------------------------

    /**
     * Fusiona las filas que comparten la clave completa. Se queda con la
     * más completa: completada > con peso > menor id. Los campos que falten
     * en el keeper se copian del duplicado.
     *
     * @return int cantidad de filas eliminadas
     */
    private function fusionarDuplicados(): int
    {
        $eliminadas = 0;

        $grupos = DB::table('historials')
            ->selectRaw('user_id, rutina_nombre, dia, ejercicio_nombre, series_numero, fecha, COUNT(*) as c')
            ->groupBy('user_id', 'rutina_nombre', 'dia', 'ejercicio_nombre', 'series_numero', 'fecha')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($grupos as $grupo) {
            $filas = DB::table('historials')
                ->where('user_id', $grupo->user_id)
                ->where('rutina_nombre', $grupo->rutina_nombre)
                ->where('dia', $grupo->dia)
                ->where('ejercicio_nombre', $grupo->ejercicio_nombre)
                ->where('series_numero', $grupo->series_numero)
                ->whereDate('fecha', $this->parteFecha($grupo->fecha))
                ->orderByDesc('completado')
                ->orderByRaw('CASE WHEN peso IS NULL THEN 1 ELSE 0 END')
                ->orderBy('id')
                ->get();

            if ($filas->count() < 2) {
                continue;
            }

            $keeper = $filas->first();

            foreach ($filas->skip(1) as $duplicado) {
                $this->completarCampos($keeper, $duplicado);
                DB::table('historials')->where('id', $duplicado->id)->delete();
                $eliminadas++;
            }
        }

        return $eliminadas;
    }

    /**
     * Copia del duplicado al keeper los campos que en el keeper están vacíos.
     */
    private function completarCampos($keeper, $duplicado): void
    {
        $cambios = [];

        foreach (self::CAMPOS_FUSION as $campo) {
            $valorKeeper = $keeper->{$campo} ?? null;
            $valorDuplicado = $duplicado->{$campo} ?? null;

            if ($valorKeeper === null && $valorDuplicado !== null) {
                $cambios[$campo] = $valorDuplicado;
            }
        }

        if ($cambios !== []) {
            DB::table('historials')->where('id', $keeper->id)->update($cambios);
        }
    }

    // -----------------------------------------------------------------
    // Paso 4 — índice único
    // -----------------------------------------------------------------

    private function tieneIndice(): bool
    {
        if (! method_exists(Schema::getFacadeRoot(), 'hasIndex')) {
            return false;
        }

        return Schema::hasIndex('historials', self::INDICE);
    }

    private function crearIndiceUnico(): void
    {
        if ($this->tieneIndice()) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            // Prefijos de 64 caracteres: la clave completa con VARCHAR(255)
            // utf8mb4 excede el límite de 3072 bytes de InnoDB.
            DB::statement(
                'ALTER TABLE historials ADD UNIQUE KEY '.self::INDICE.
                ' (user_id, rutina_nombre(64), dia(64), ejercicio_nombre(64), series_numero, fecha)'
            );

            return;
        }

        Schema::table('historials', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'rutina_nombre', 'dia', 'ejercicio_nombre', 'series_numero', 'fecha'],
                self::INDICE
            );
        });
    }
};
