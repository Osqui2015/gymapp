<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Parámetro de configuración del gimnasio (tabla `gym_config`).
 *
 * El valor se guarda como JSON para poder representar tanto escalares
 * (días, moneda) como arrays (métodos de pago, plantillas).
 */
class GymConfig extends Model
{
    protected $table = 'gym_config';

    protected $fillable = [
        'key',
        'value',
        'categoria',
        'descripcion',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
