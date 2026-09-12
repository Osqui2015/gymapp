<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesionEntrenamiento extends Model
{
    protected $table = 'sesiones_entrenamiento';

    protected $fillable = [
        'uuid',
        'user_id',
        'rutina_nombre',
        'dia',
        'started_at',
        'ended_at',
        'duracion_segundos',
        'volumen_total',
        'series_completadas',
        'series_totales',
        'prs_superados',
        'notas',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duracion_segundos' => 'integer',
        'volumen_total' => 'decimal:2',
        'series_completadas' => 'integer',
        'series_totales' => 'integer',
        'prs_superados' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function historials(): HasMany
    {
        return $this->hasMany(Historial::class, 'sesion_uuid', 'uuid');
    }
}
