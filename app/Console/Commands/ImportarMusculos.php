<?php

namespace App\Console\Commands;

use App\Models\Musculo;
use App\Models\MusculoAlias;
use Illuminate\Console\Command;

/**
 * Importa los 18 músculos canónicos + aliases (50+ variaciones de nombres).
 *
 * Modelo de datos: el mismo que usa openGym (frontend/src/lib/muscles.js),
 * que a su vez viene de ExerciseDB v1 (MIT). Los nombres en español son los
 * que ya existen en tu tabla `ejercicios.grupo_muscular`.
 *
 * Idempotente: corre las veces que quieras, solo actualiza si cambió algo.
 *
 * Uso:
 *   php artisan musculos:importar
 *   php artisan musculos:importar --reset   # borra y recrea (útil en dev)
 */
class ImportarMusculos extends Command
{
    protected $signature = 'musculos:importar {--reset : Borra músculos antes de importar}';

    protected $description = 'Importa los 18 músculos canónicos + aliases del modelo openGym';

    /**
     * Los 18 músculos canónicos, en orden head-to-toe (igual que openGym).
     * El svg_id mapea al ID del <path> en el componente BodyMap.vue
     * (lo armamos cuando lleguemos a la Fase 2 del body map).
     */
    private function musculos(): array
    {
        return [
            // upper body
            ['slug' => 'trapezius',      'nombre_es' => 'Trapecio',          'nombre_en' => 'Trapezius',   'body_part' => 'upper-body', 'svg_id' => 'trapezius',      'orden' => 1],
            ['slug' => 'deltoids',       'nombre_es' => 'Hombros',           'nombre_en' => 'Deltoids',    'body_part' => 'upper-body', 'svg_id' => 'deltoids',       'orden' => 2],
            ['slug' => 'chest',          'nombre_es' => 'Pecho',             'nombre_en' => 'Chest',       'body_part' => 'upper-body', 'svg_id' => 'chest',          'orden' => 3],
            ['slug' => 'upper-back',     'nombre_es' => 'Espalda alta',      'nombre_en' => 'Upper back',  'body_part' => 'upper-body', 'svg_id' => 'upper-back',     'orden' => 4],
            ['slug' => 'serratus',       'nombre_es' => 'Serrato',           'nombre_en' => 'Serratus',    'body_part' => 'upper-body', 'svg_id' => 'serratus',       'orden' => 5],
            // arms
            ['slug' => 'biceps',         'nombre_es' => 'Bíceps',            'nombre_en' => 'Biceps',      'body_part' => 'upper-body', 'svg_id' => 'biceps',         'orden' => 6],
            ['slug' => 'triceps',        'nombre_es' => 'Tríceps',           'nombre_en' => 'Triceps',     'body_part' => 'upper-body', 'svg_id' => 'triceps',        'orden' => 7],
            ['slug' => 'forearm',        'nombre_es' => 'Antebrazos',        'nombre_en' => 'Forearms',    'body_part' => 'upper-body', 'svg_id' => 'forearm',        'orden' => 8],
            // core
            ['slug' => 'abs',            'nombre_es' => 'Abdominales',       'nombre_en' => 'Abs',         'body_part' => 'core',       'svg_id' => 'abs',            'orden' => 9],
            ['slug' => 'obliques',       'nombre_es' => 'Oblicuos',          'nombre_en' => 'Obliques',    'body_part' => 'core',       'svg_id' => 'obliques',       'orden' => 10],
            ['slug' => 'lower-back',     'nombre_es' => 'Espalda baja',      'nombre_en' => 'Lower back',  'body_part' => 'core',       'svg_id' => 'lower-back',     'orden' => 11],
            // lower body
            ['slug' => 'gluteal',        'nombre_es' => 'Glúteos',           'nombre_en' => 'Glutes',      'body_part' => 'lower-body', 'svg_id' => 'gluteal',        'orden' => 12],
            ['slug' => 'quadriceps',     'nombre_es' => 'Cuádriceps',        'nombre_en' => 'Quads',       'body_part' => 'lower-body', 'svg_id' => 'quadriceps',     'orden' => 13],
            ['slug' => 'hamstring',      'nombre_es' => 'Isquiotibiales',    'nombre_en' => 'Hamstrings',  'body_part' => 'lower-body', 'svg_id' => 'hamstring',      'orden' => 14],
            ['slug' => 'adductors',      'nombre_es' => 'Aductores',         'nombre_en' => 'Adductors',   'body_part' => 'lower-body', 'svg_id' => 'adductors',      'orden' => 15],
            ['slug' => 'hip-flexors',    'nombre_es' => 'Flexores de cadera','nombre_en' => 'Hip flexors', 'body_part' => 'core',       'svg_id' => 'hip-flexors',    'orden' => 16],
            ['slug' => 'calves',         'nombre_es' => 'Pantorrillas',      'nombre_en' => 'Calves',      'body_part' => 'lower-body', 'svg_id' => 'calves',         'orden' => 17],
            ['slug' => 'tibialis',       'nombre_es' => 'Tibial anterior',   'nombre_en' => 'Shins',       'body_part' => 'lower-body', 'svg_id' => 'tibialis',       'orden' => 18],
        ];
    }

    /**
     * Mapeo de alias → slug canónico.
     * Cubre los nombres en español de tu DB + los nombres en inglés de
     * openGym/ExerciseDB + abreviaciones comunes.
     */
    private function aliases(): array
    {
        return [
            // trapezius
            'trapecio' => 'trapezius', 'traps' => 'trapezius', 'trapecios' => 'trapezius',

            // deltoids
            'hombros' => 'deltoids', 'deltoid' => 'deltoids', 'deltoides' => 'deltoids',
            'delts' => 'deltoids', 'hombro' => 'deltoids', 'deltoide' => 'deltoids',
            'shoulders' => 'deltoids', 'shoulder' => 'deltoids',
            'rear deltoids' => 'deltoids', 'deltoides traseros' => 'deltoids',
            'rotator cuff' => 'deltoids',

            // chest
            'pecho' => 'chest', 'pectorales' => 'chest', 'pectoral' => 'chest',
            'pectorals' => 'chest', 'chest' => 'chest',

            // upper-back (espalda alta)
            'espalda' => 'upper-back', 'espalda alta' => 'upper-back',
            'dorsales' => 'upper-back', 'lats' => 'upper-back', 'dorsal' => 'upper-back',
            'latissimus dorsi' => 'upper-back', 'upper back' => 'upper-back',
            'rhomboids' => 'upper-back', 'back' => 'upper-back',
            'espalda alta (dorsales)' => 'upper-back',

            // serratus
            'serrato' => 'serratus', 'serratus anterior' => 'serratus',

            // biceps
            'biceps' => 'biceps', 'bíceps' => 'biceps', 'bicep' => 'biceps',

            // triceps
            'triceps' => 'triceps', 'tríceps' => 'triceps', 'tricep' => 'triceps',

            // forearm
            'antebrazos' => 'forearm', 'antebrazo' => 'forearm', 'forearms' => 'forearm',
            'forearm' => 'forearm', 'wrist flexors' => 'forearm', 'wrist extensors' => 'forearm',
            'grip muscles' => 'forearm', 'wrists' => 'forearm',

            // abs
            'abdomen' => 'abs', 'abdominales' => 'abs', 'abdominal' => 'abs',
            'abdominals' => 'abs', 'abs' => 'abs', 'core' => 'abs',
            'lower abs' => 'abs',

            // obliques
            'oblicuos' => 'obliques', 'oblique' => 'obliques', 'obliques' => 'obliques',

            // lower-back (espalda baja)
            'espalda baja' => 'lower-back', 'lower back' => 'lower-back',
            'lumbares' => 'lower-back', 'lumbar' => 'lower-back', 'spine' => 'lower-back',

            // gluteal
            'gluteos' => 'gluteal', 'glúteos' => 'gluteal', 'glute' => 'gluteal',
            'glutes' => 'gluteal', 'gluteal' => 'gluteal',

            // quadriceps
            'cuádriceps' => 'quadriceps', 'cuadriceps' => 'quadriceps',
            'quadriceps' => 'quadriceps', 'quads' => 'quadriceps', 'quad' => 'quadriceps',
            'piernas' => 'quadriceps', 'muslo' => 'quadriceps',

            // hamstring
            'isquiotibiales' => 'hamstring', 'isquiotibial' => 'hamstring',
            'hamstring' => 'hamstring', 'hamstrings' => 'hamstring',

            // adductors
            'aductores' => 'adductors', 'aductor' => 'adductors', 'adductors' => 'adductors',
            'aductores' => 'adductors', 'groin' => 'adductors', 'inner thighs' => 'adductors',

            // hip-flexors
            'flexores de cadera' => 'hip-flexors', 'flexor de cadera' => 'hip-flexors',
            'hip flexors' => 'hip-flexors', 'hip flexor' => 'hip-flexors',
            'flexores' => 'hip-flexors',

            // calves
            'pantorrillas' => 'calves', 'pantorrilla' => 'calves',
            'calves' => 'calves', 'calf' => 'calves', 'gemelos' => 'calves',
            'soleus' => 'calves',

            // tibialis
            'tibial anterior' => 'tibialis', 'tibial' => 'tibialis',
            'tibialis' => 'tibialis', 'shins' => 'tibialis',
        ];
    }

    public function handle(): int
    {
        if ($this->option('reset')) {
            $count = \DB::table('musculo_aliases')->count() + \DB::table('musculos')->count();
            if ($this->confirm("Borrar {$count} registros y reimportar?", true)) {
                \DB::table('ejercicio_musculos')->delete();
                \DB::table('musculo_aliases')->delete();
                \DB::table('musculos')->delete();
                $this->warn('Tablas limpias.');
            }
        }

        $musculos = $this->musculos();
        $aliases = $this->aliases();
        $aliasCount = 0;
        $musculoCount = 0;

        $this->info('Importando ' . count($musculos) . ' músculos...');

        foreach ($musculos as $m) {
            Musculo::updateOrCreate(
                ['slug' => $m['slug']],
                $m
            );
            $musculoCount++;
            $this->line("  + {$m['slug']} ({$m['nombre_es']})");
        }

        $this->newLine();
        $this->info("✓ {$musculoCount} músculos listos");
        $this->newLine();
        $this->info('Importando ' . count($aliases) . ' aliases...');

        // Mapear alias → musculo_id en una sola query
        $musculosBySlug = Musculo::pluck('id', 'slug');

        foreach ($aliases as $alias => $slug) {
            $musculoId = $musculosBySlug[$slug] ?? null;
            if (!$musculoId) {
                $this->warn("  Alias '{$alias}' apunta a slug '{$slug}' que no existe, saltando.");
                continue;
            }
            MusculoAlias::firstOrCreate(
                ['alias' => $alias, 'musculo_id' => $musculoId]
            );
            $aliasCount++;
        }

        $this->newLine();
        $this->info("✓ {$aliasCount} aliases listos");
        $this->newLine();

        // Mini resumen
        $this->table(
            ['Body part', 'Músculos', 'Aliases'],
            [
                ['upper-body', Musculo::where('body_part', 'upper-body')->count(), MusculoAlias::whereIn('musculo_id', Musculo::where('body_part', 'upper-body')->pluck('id'))->count()],
                ['core',       Musculo::where('body_part', 'core')->count(),       MusculoAlias::whereIn('musculo_id', Musculo::where('body_part', 'core')->pluck('id'))->count()],
                ['lower-body', Musculo::where('body_part', 'lower-body')->count(), MusculoAlias::whereIn('musculo_id', Musculo::where('body_part', 'lower-body')->pluck('id'))->count()],
            ]
        );

        $this->newLine();
        $this->info('Próximo paso: mapear ejercicios existentes con `php artisan musculos:mapear-ejercicios`');

        return self::SUCCESS;
    }
}
