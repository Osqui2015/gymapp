<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    /**
     * Músculos que trabaja este ejercicio, con tipo (primario/secundario) y peso.
     * Pivot: ejercicio_musculos (tipo, peso, fuente).
     */
    public function musculos(): BelongsToMany
    {
        return $this->belongsToMany(Musculo::class, 'ejercicio_musculos')
            ->withPivot(['tipo', 'peso', 'fuente']);
    }

    /**
     * Usuarios que marcaron este ejercicio como favorito.
     */
    public function favoritos(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ejercicio_favoritos')
            ->withTimestamps();
    }
}
