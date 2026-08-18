<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRutina extends Model
{
    /**
     * Relación de la rutina seleccionada por un usuario.
     *
     * **D1 (resuelta 2026-08-17) — source of truth:**
     * Las columnas denormalizadas `nivel` y `modalidad` fueron dropeadas
     * (migración 2026_08_17_000000). Esos valores ahora SIEMPRE se leen de
     * la relación `rutina` (vía FK `rutina_id`).
     *
     * Para mantener compat con el frontend, exponemos los accessors `nivel`
     * y `modalidad` que se serializan automáticamente en el JSON
     * (`$appends`). Leen exclusivamente de la relación; si no está
     * cargada, devuelven `null` (hacé `->load('rutina')` antes de
     * serializar si los necesitás sí o sí).
     *
     * **Por qué esta decisión:**
     *   - Antes: snapshot histórico. Ventaja: si renombrás "Intermedio" a
     *     "Avanzado" en Rutina, los UserRutina viejos seguían mostrando
     *     "Intermedio". Desventaja: drift silencioso si no sincronizás.
     *   - Ahora: source of truth. Si renombrás, se refleja en todos lados
     *     en el siguiente request. No hay drift.
     */
    protected $fillable = [
        'user_id',
        'rutina_id',
        'assigned_by',
        'dia_actual',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'rutina_id' => 'integer',
        'assigned_by' => 'integer',
    ];

    /**
     * Atributos virtuales que se incluyen en toArray()/toJson() automáticamente.
     * Esto permite que el frontend siga leyendo `userRutina.nivel` y
     * `userRutina.modalidad` como antes, aunque ya no son columnas.
     */
    protected $appends = ['nivel', 'modalidad'];

    public function rutina(): BelongsTo
    {
        return $this->belongsTo(Rutina::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Accessor: lee de la relación `rutina`. Si la relación no está cargada
     * ni tiene `rutina_id`, devuelve null. Si `rutina_id` está seteada pero
     * la relación no se cargó, hace lazy load (cuidado con N+1: preferí
     * `->load('rutina')` o `with('rutina')` en el query).
     *
     * IMPORTANTE: leer `$this->attributes['rutina_id']` directo (no
     * `$this->rutina_id`) para no disparar Model::shouldBeStrict en dev
     * cuando se accede al accessor sin haber cargado la fila.
     */
    protected function nivel(): Attribute
    {
        return Attribute::make(
            get: function () {
                $fkId = $this->attributes['rutina_id'] ?? null;
                if (! $fkId) {
                    return null;
                }
                // Si la relación está cargada, usar source of truth directo
                if ($this->relationLoaded('rutina')) {
                    return $this->rutina?->nivel;
                }
                // Si no está cargada pero hay FK, lazy load
                return $this->rutina()?->nivel;
            },
        );
    }

    protected function modalidad(): Attribute
    {
        return Attribute::make(
            get: function () {
                $fkId = $this->attributes['rutina_id'] ?? null;
                if (! $fkId) {
                    return null;
                }
                if ($this->relationLoaded('rutina')) {
                    return $this->rutina?->modalidad;
                }
                return $this->rutina()?->modalidad;
            },
        );
    }
}
