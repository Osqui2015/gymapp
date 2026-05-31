<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Historial extends Model
{
    protected $fillable = [
        'user_id',
        'rutina_nombre',
        'dia',
        'ejercicio_nombre',
        'series_numero',
        'series_completadas',
        'reps_min',
        'reps_max',
        'reps_realizadas',
        'descanso_min',
        'peso',
        'completado',
        'fecha',
    ];

    protected $casts = [
        'series_completadas' => 'integer',
        'reps_realizadas' => 'integer',
        'peso' => 'decimal:2',
        'completado' => 'boolean',
        'fecha' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
