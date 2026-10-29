<?php

namespace Database\Seeders;

use App\Models\Ejercicio;
use App\Models\Musculo;
use Illuminate\Database\Seeder;

/**
 * Ejercicio "Movilidad articular y activación" (bloque de calentamiento del
 * Día 1 de la V3).
 *
 * Es idempotente: hace updateOrCreate por nombre y sincroniza el pivot de
 * músculos sin tocar el resto de la biblioteca. Se puede correr las veces que
 * haga falta sin duplicar nada.
 *
 * NO usar `db:seed --class=EjercicioSeeder` para esto: ese seeder inserta sin
 * control de duplicados (la tabla `ejercicios` no tiene unique sobre `nombre`
 * y ya hay nombres repetidos del catálogo VisualGym).
 *
 * Uso:
 *   php artisan db:seed --class=EjercicioMovilidadSeeder
 */
class EjercicioMovilidadSeeder extends Seeder
{
    public const NOMBRE = 'Movilidad articular y activación';

    /** Músculos: principal + sinergistas, con los slugs del catálogo de 18. */
    private const MUSCULOS = [
        'deltoids' => ['tipo' => 'primario', 'peso' => 1.00],
        'serratus' => ['tipo' => 'secundario', 'peso' => 0.40],
        'upper-back' => ['tipo' => 'secundario', 'peso' => 0.40],
        'obliques' => ['tipo' => 'secundario', 'peso' => 0.40],
    ];

    public function run(): void
    {
        $descripcion = implode(' ', [
            'Bloque de movilidad articular y activación (agregado inicial del día):',
            '5 a 10 minutos continuos, fluido y sin fatiga muscular.',
            'Enfoque en el complejo articular del hombro, la columna torácica y las escápulas.',
            'Dislocaciones de hombro con banda elástica o bastón,',
            'rotaciones torácicas en cuadrupedia,',
            'retracciones escapulares colgado en barra (scapular pulls)',
            'y rotaciones externas para el manguito rotador antes de tocar la barra.',
            'No es una serie de trabajo: se hace antes de la barra.',
        ]);

        $ejercicio = Ejercicio::updateOrCreate(
            ['nombre' => self::NOMBRE],
            [
                'equipamiento' => 'Banda elástica / Bastón',
                'grupo_muscular' => 'Hombros',
                'body_part' => 'upper-body',
                'target' => 'deltoids',
                'secondary_muscles' => ['serratus', 'upper-back', 'obliques'],
                'descripcion' => $descripcion,
                'instructions' => ['es' => $descripcion],
                'instruction_steps' => ['es' => [
                    'Dislocaciones de hombro con banda elástica o bastón.',
                    'Rotaciones torácicas en cuadrupedia.',
                    'Retracciones escapulares colgado en barra (scapular pulls).',
                    'Rotaciones externas para el manguito rotador.',
                ]],
                'dificultad' => 'principiante',
                'visibilidad' => true,
            ]
        );

        // Pivot de músculos (tipo + peso + fuente).
        $pivot = [];
        foreach (self::MUSCULOS as $slug => $meta) {
            $musculo = Musculo::where('slug', $slug)->first();
            if (! $musculo) {
                $this->command->warn("  Músculo '{$slug}' no encontrado en el catálogo: se omite.");

                continue;
            }
            $pivot[$musculo->id] = [
                'tipo' => $meta['tipo'],
                'peso' => $meta['peso'],
                'fuente' => 'manual',
            ];
        }
        $ejercicio->musculos()->syncWithoutDetaching($pivot);

        $this->command->info("Ejercicio '{$ejercicio->nombre}' (id {$ejercicio->id}) con ".count($pivot).' músculos.');
    }
}
