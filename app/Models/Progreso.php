<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Progreso extends Model
{
    protected $fillable = [
        'user_id',
        'fecha',
        'peso',
        'altura',
        'edad',
        'sexo',
        'cuello',
        'hombros',
        'pecho',
        'brazos',
        'cintura',
        'cadera',
        'muslos',
        'pantorrillas',
    ];

    protected $casts = [
        'fecha' => 'date',
        'peso' => 'decimal:2',
        'altura' => 'decimal:2',
        'cuello' => 'decimal:2',
        'hombros' => 'decimal:2',
        'pecho' => 'decimal:2',
        'brazos' => 'decimal:2',
        'cintura' => 'decimal:2',
        'cadera' => 'decimal:2',
        'muslos' => 'decimal:2',
        'pantorrillas' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDiferencia(Progreso $anterior, string $campo): string
    {
        $actual = $this->$campo;
        $anteriorValor = $anterior->$campo;

        if ($actual === null || $anteriorValor === null) {
            return '-';
        }

        $diferencia = $actual - $anteriorValor;
        
        if ($diferencia > 0) {
            return '+' . number_format($diferencia, 1, '.', '');
        } elseif ($diferencia < 0) {
            return number_format($diferencia, 1, '.', '');
        }
        
        return '0';
    }
}