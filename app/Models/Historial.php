<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

class Historial extends Model
{
    /**
     * RANGOS DE `series_numero` (bug P0 corregido)
     * --------------------------------------------
     * El índice UNIQUE `uq_historials_idempotencia` es:
     *   (user_id, rutina_nombre, dia, ejercicio_nombre, series_numero, fecha)
     * y NO incluye `tipo_serie`. Como el calentamiento y la serie efectiva
     * N partían ambos de 1, la colisión era inevitable: el warmup 1 y la
     * efectiva 1 caían en la MISMA clave, y `guardarIdempotente()` pisaba el
     * warmup con la efectiva (se perdía el registro y el volumen subcuenta).
     *
     * AGENTS.md declara ese índice como invariante, así que la solución NO
     * es agregar `tipo_serie` al índice (requiere migración), sino separar
     * los rangos DENTRO de la misma columna:
     *
     *   - Series efectivas:  1 .. 50
     *   - Calentamientos:   51 .. 99  (offset de 50)
     *
     * Espejo del módulo `resources/js/utils/seriesNumero.js`.
     */
    public const OFFSET_CALENTAMIENTO = 50;

    /** Máximo de series efectivas por ejercicio. */
    public const MAX_SERIES = 50;

    /** Máximo absoluto de `series_numero` (inclusive, calentamiento final). */
    public const MAX_SERIES_NUMERO = 99;

    /**
     * ¿Este `series_numero` corresponde a un calentamiento?
     */
    public static function esCalentamiento(?int $seriesNumero): bool
    {
        return $seriesNumero !== null && $seriesNumero > self::OFFSET_CALENTAMIENTO;
    }

    /**
     * **D2/D3 (resuelta 2026-08-17):** `ejercicio_id` FK agregada vía migración
     * `2026_08_17_000001`. Se mantiene `ejercicio_nombre` como columna legacy
     * para retrocompat con código viejo y para los casos donde el backfill
     * no encontró match.
     *
     * Estrategia:
     *   - Código NUEVO: preferir `$historial->ejercicioRef` (vía FK).
     *   - Código VIEJO: `$historial->ejercicio_nombre` sigue funcionando.
     *
     * Mismo patrón aplicado a `Rutina` con la migración
     * `2026_08_16_000000_add_ejercicio_id_to_rutinas_table.php`.
     */
    protected $fillable = [
        'user_id',
        'rutina_nombre',
        'dia',
        'ejercicio_nombre',   // LEGACY: se mantiene por compat
        'ejercicio_id',       // NUEVO: FK a ejercicios.id
        'series_numero',
        'series_completadas',
        'reps_min',
        'reps_max',
        'reps_realizadas',
        'descanso_min',
        'peso',
        'completado',
        'fecha',
        'comentario_trainer',
        'nota_user',         // Nota libre del usuario por set (cómo se sintió, RPE subjetivo, etc.)
        'trainer_id',
        'superserie_grupo',
        // Fase 3: tracking de esfuerzo por set (opcional)
        //   esfuerzo_tipo  = 'rir' (0..5) | 'rpe' (6..10) | 'fallo' (al fallo absoluto)
        //   esfuerzo_valor = número o NULL para 'fallo'
        'esfuerzo_tipo',
        'esfuerzo_valor',
        // Nivel 4: tipo de serie y vínculo a sesión de entrenamiento
        'tipo_serie',        // 'efectiva', 'calentamiento', 'dropset', 'al_fallo'
        'sesion_uuid',
    ];

    protected $casts = [
        'series_completadas' => 'integer',
        'reps_realizadas' => 'integer',
        'peso' => 'decimal:2',
        'completado' => 'boolean',
        'fecha' => 'date',
        'trainer_id' => 'integer',
        'superserie_grupo' => 'integer',
        'ejercicio_id' => 'integer',
        'esfuerzo_valor' => 'integer',
    ];

    /**
     * Guarda una serie de forma idempotente.
     *
     * La clave única de una serie es:
     *   (user_id, rutina_nombre, dia, ejercicio_nombre, series_numero, fecha)
     *
     * La `fecha` es parte de la clave A PROPÓSITO: registrar el mismo set en
     * otra fecha tiene que crear una fila nueva, no pisar la sesión anterior.
     * El índice UNIQUE `uq_historials_idempotencia` lo garantiza en la base.
     *
     * Se usa whereDate() y no where('fecha', ...): la columna es DATE, pero el
     * cast 'date' del modelo la escribe como 'Y-m-d H:i:s'. En MySQL la columna
     * DATE normaliza ese valor, pero en SQLite (los tests) queda la cadena
     * completa y la comparación exacta no matchearía.
     *
     * @param  array<string, mixed>  $data  Datos de la serie (incluye la clave).
     */
    public static function guardarIdempotente(array $data): self
    {
        $query = static::where('user_id', $data['user_id'])
            ->where('rutina_nombre', $data['rutina_nombre'])
            ->where('dia', $data['dia'])
            ->where('ejercicio_nombre', $data['ejercicio_nombre'])
            ->where('series_numero', $data['series_numero'])
            ->whereDate('fecha', $data['fecha']);

        $existente = $query->first();

        if ($existente) {
            $existente->fill($data)->save();

            return $existente;
        }

        try {
            return static::create($data);
        } catch (QueryException $e) {
            if (! self::esViolacionDeClaveUnica($e)) {
                throw $e;
            }

            // Carrera: otro request insertó la misma fila entre el SELECT y el
            // INSERT. Ahora la fila existe -> la actualizamos.
            $existente = $query->first();

            if (! $existente) {
                throw $e;
            }

            $existente->fill($data)->save();

            return $existente;
        }
    }

    /**
     * ¿La excepción es una violación de clave única (índice UNIQUE)?
     * 23000 = integrity constraint violation en MySQL y SQLite.
     */
    protected static function esViolacionDeClaveUnica(QueryException $e): bool
    {
        if ($e->getCode() === '23000') {
            return true;
        }

        return (int) ($e->errorInfo[0] ?? 0) === 23000;
    }

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionEntrenamiento::class, 'sesion_uuid', 'uuid');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Relación PREFERIDA al ejercicio (vía FK real).
     * Eager-load con `$historial->load('ejercicioRef')` o `->with('ejercicioRef')`.
     */
    public function ejercicioRef(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class, 'ejercicio_id');
    }

    /**
     * Relación LEGACY (vía match por nombre).
     * Mantenerla temporalmente para retrocompat con código viejo que
     * no haya migrado a `ejercicioRef`. Se puede eliminar cuando todos
     * los call sites estén migrados.
     *
     * IMPORTANTE: la firma de belongsTo es (Related, foreignKey, ownerKey):
     *   - foreignKey = columna en la TABLA LOCAL (Historial) que matchea
     *   - ownerKey = columna en la TABLA DEL RELATED (Ejercicio) que matchea
     * Historial tiene `ejercicio_nombre` (string) y Ejercicio matchea por `nombre`.
     */
    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class, 'ejercicio_nombre', 'nombre');
    }
}
