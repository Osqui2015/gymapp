<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Historial extends Model
{
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
        'trainer_id',
        'superserie_grupo',
        // Fase 3: tracking de esfuerzo por set (opcional)
        'esfuerzo_tipo',     // 'rir' o 'rpe'
        'esfuerzo_valor',    // 0..5 (RIR) o 6..10 (RPE)
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
