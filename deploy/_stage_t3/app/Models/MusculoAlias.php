<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MusculoAlias extends Model
{
    protected $table = 'musculo_aliases';

    public $timestamps = false;  // la tabla no tiene created_at/updated_at

    protected $fillable = [
        'alias',
        'musculo_id',
    ];

    public function musculo(): BelongsTo
    {
        return $this->belongsTo(Musculo::class);
    }
}
