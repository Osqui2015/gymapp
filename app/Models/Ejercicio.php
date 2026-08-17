<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ejercicio extends Model
{
    use HasFactory;

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
