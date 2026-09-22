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
        'dificultad',
        // VisualGym (exercises-dataset)
        'external_id',
        'body_part',
        'target',
        'media_id',
        'image_path',
        'gif_path',
        'instructions',
        'instruction_steps',
        'secondary_muscles',
        'source_created_at',
        'source',
        'fuente_credito',
        'fuente_tipo',
    ];

    protected $casts = [
        'visibilidad' => 'boolean',
        'instructions' => 'array',
        'instruction_steps' => 'array',
        'secondary_muscles' => 'array',
        'source_created_at' => 'datetime',
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

    // === Scopes para el catálogo VisualGym ===

    /** @param  \Illuminate\Database\Eloquent\Builder<self>  $query */
    public function scopeFromVisualGym($query)
    {
        return $query->where('source', 'visualgym');
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<self>  $query */
    public function scopeByBodyPart($query, ?string $bodyPart)
    {
        return $bodyPart ? $query->where('body_part', $bodyPart) : $query;
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<self>  $query */
    public function scopeByTarget($query, ?string $target)
    {
        return $target ? $query->where('target', $target) : $query;
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<self>  $query */
    public function scopeByEquipment($query, ?string $equipment)
    {
        return $equipment ? $query->where('equipamiento', $equipment) : $query;
    }

    /**
     * Búsqueda libre: nombre + equipamiento + target + grupo_muscular
     * + match exacto por external_id.
     * Para datasets grandes considerar FULLTEXT; el LIKE sigue siendo
     * aceptable hasta ~5k registros.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     */
    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.$term.'%';
        // external_id va como match exacto (no LIKE) para que "0001" matchee
        // el id y no cualquier substring.
        $exact = $term;

        return $query->where(function ($q) use ($like, $exact) {
            $q->where('nombre', 'like', $like)
                ->orWhere('equipamiento', 'like', $like)
                ->orWhere('target', 'like', $like)
                ->orWhere('grupo_muscular', 'like', $like)
                ->orWhere('external_id', $exact);
        });
    }

    /**
     * URL pública del thumbnail. Si image_path viene del seeder VisualGym,
     * lo servimos desde /storage/exercises/images/.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image_path) {
            // Normalizar: si viene como "images/foo.jpg" lo dejo igual;
            // si ya viene como "exercises/images/foo.jpg" también.
            $path = ltrim($this->image_path, '/');

            // Si no tiene el prefijo "exercises/" lo agrego.
            if (! str_starts_with($path, 'exercises/')) {
                $path = 'exercises/'.$path;
            }

            return asset('storage/'.$path);
        }

        return $this->url_img
            ? asset('storage/'.$this->url_img)
            : asset('images/placeholder-exercise.png');
    }

    /**
     * URL pública del GIF animado.
     */
    public function getGifUrlAttribute(): string
    {
        if ($this->gif_path) {
            $path = ltrim($this->gif_path, '/');

            if (! str_starts_with($path, 'exercises/')) {
                $path = 'exercises/'.$path;
            }

            return asset('storage/'.$path);
        }

        return $this->url_video
            ? asset('storage/'.$this->url_video)
            : '';
    }

    /**
     * Devuelve las instrucciones en el idioma pedido, fallback a EN, fallback a ES.
     */
    public function getInstruction(?string $lang = 'es'): ?string
    {
        $instr = $this->instructions ?? [];

        return $instr[$lang]
            ?? $instr['en']
            ?? $instr['es']
            ?? null;
    }
}
