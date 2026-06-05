<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rutina extends Model
{
    protected $fillable = [
        'nivel',
        'modalidad',
        'dia',
        'created_by',
        'series',
        'reps_min',
        'reps_max',
        'descanso_min',
        'ejercicio_nombre',
        'orden',
        'superserie_grupo',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'series' => 'integer',
        'descanso_min' => 'decimal:2',
        'superserie_grupo' => 'integer',
    ];

    public function ejercicio(): BelongsTo
    {
        return $this->belongsTo(Ejercicio::class, 'nombre', 'ejercicio_nombre');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
