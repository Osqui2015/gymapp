<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Musculo extends Model
{
    use HasFactory;

    protected $table = 'musculos';

    protected $fillable = [
        'slug',
        'nombre_es',
        'nombre_en',
        'body_part',
        'svg_id',
        'orden',
    ];

    /**
     * Aliases que colapsan a este músculo (deltoids, delts, hombros → deltoids).
     */
    public function aliases(): HasMany
    {
        return $this->hasMany(MusculoAlias::class);
    }

    /**
     * Ejercicios que trabajan este músculo (con tipo y peso).
     */
    public function ejercicios(): BelongsToMany
    {
        return $this->belongsToMany(Ejercicio::class, 'ejercicio_musculos')
            ->withPivot(['tipo', 'peso', 'fuente'])
            ->withTimestamps();
    }

    /**
     * Resuelve un texto libre a este músculo usando los aliases.
     * Si no matchea, devuelve null.
     */
    public static function resolverAlias(string $texto): ?self
    {
        $texto = mb_strtolower(trim($texto));
        // Primero busca por slug exacto
        $m = self::where('slug', $texto)->first();
        if ($m) return $m;
        // Después por alias
        $alias = MusculoAlias::where('alias', $texto)->first();
        return $alias?->musculo;
    }
}
