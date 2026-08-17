<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rutina extends Model
{
    use HasFactory;

    protected $fillable = [
        'nivel',
        'modalidad',
        'dia',
        'created_by',
        'series',
        'reps_min',
        'reps_max',
        'descanso_min',
        'ejercicio_nombre',  // LEGACY: se mantiene por compat, dropear en migración futura
        'ejercicio_id',      // NUEVO: FK a ejercicios.id
        'orden',
        'superserie_grupo',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'ejercicio_id' => 'integer',
        'series' => 'integer',
        'descanso_min' => 'decimal:2',
        'superserie_grupo' => 'integer',
    ];

    /**
     * Relación PREFERIDA al ejercicio (vía FK real).
     * Eager-load con `$rutina->load('ejercicioRef')` o `->with('ejercicioRef')`.
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
     */
    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class, 'nombre', 'ejercicio_nombre');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Accessor: prioriza el FK, cae al string legacy si la FK no se cargó.
     * Así `$rutina->ejercicio_nombre` sigue funcionando sin cambios en los
     * call sites, pero internamente lee de la fuente correcta cuando se
     * eager-loada `ejercicioRef`.
     */
    protected function ejercicioNombre(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->ejercicio_id && $this->relationLoaded('ejercicioRef') && $this->ejercicioRef) {
                    return $this->ejercicioRef->nombre;
                }
                if ($this->ejercicio_id) {
                    // FK seteada pero relación no eager-loaded: NO hacemos lazy load
                    // (sería N+1). Devolvemos el nombre legacy como fallback seguro.
                    // Si querés el nombre actualizado, hacé ->load('ejercicioRef') antes.
                    return $this->attributes['ejercicio_nombre'] ?? null;
                }
                return $this->attributes['ejercicio_nombre'] ?? null;
            },
        );
    }
}
