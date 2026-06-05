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
        'comentario_trainer',
        'trainer_id',
        'superserie_grupo',
    ];

    protected $casts = [
        'series_completadas' => 'integer',
        'reps_realizadas' => 'integer',
        'peso' => 'decimal:2',
        'completado' => 'boolean',
        'fecha' => 'date',
        'trainer_id' => 'integer',
        'superserie_grupo' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
