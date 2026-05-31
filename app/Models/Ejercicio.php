<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ejercicio extends Model
{
    protected $fillable = [
        'nombre',
        'equipamiento',
        'url_img',
        'url_video',
        'visibilidad',
        'grupo_muscular',
        'descripcion',
    ];

    protected $casts = [
        'visibilidad' => 'boolean',
    ];
}
