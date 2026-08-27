<?php

namespace App\Console\Commands;

use App\Models\Ejercicio;
use App\Models\Musculo;
use Illuminate\Console\Command;

/**
 * Mapea los ejercicios existentes (tabla `ejercicios`) a los músculos canónicos
 * basándose en el campo `grupo_muscular` que ya tienen.
 *
 * Para músculos secundarios (que openGym modela aparte), usamos un fallback
 * por body_part conocido del modelo openGym. Por ejemplo:
 *   - "Abdomen" como grupo_muscular → abs (primario) + obliques (secundario)
 *   - "Espalda" como grupo_muscular → upper-back (primario) + lower-back (secundario)
 *
 * Idempotente: corre las veces que quieras.
 *
 * Uso:
 *   php artisan musculos:mapear-ejercicios
 *   php artisan musculos:mapear-ejercicios --dry-run   # ver qué haría sin escribir
 */
class MapearEjerciciosMusculos extends Command
{
    protected $signature = 'musculos:mapear-ejercicios {--dry-run : No escribir en la DB}';

    protected $description = 'Mapea los ejercicios existentes a músculos vía grupo_muscular';

    /**
     * Mapeo explícito de grupo_muscular (texto de tu DB) → músculos primarios.
     * Cuando un grupo es ambiguo (ej. "Espalda"), se agrega un secundario
     * en la sección de abajo.
     */
    private function mapeoDirecto(): array
    {
        return [
            'Abdomen'              => ['abs'],
            'Antebrazos'           => ['forearm'],
            'Bíceps'               => ['biceps'],
            'Cuádriceps'           => ['quadriceps'],
            'Espalda'              => ['upper-back'],
            'Espalda baja'         => ['lower-back'],
            'Glúteos'              => ['gluteal'],
            'Hombros'              => ['deltoids'],
            'Isquiotibiales'       => ['hamstring'],
            'Pantorrillas'         => ['calves'],
            'Pecho'                => ['chest'],
            'Trapecio'             => ['trapezius'],
            'Tríceps'              => ['triceps'],
            'Piernas'              => ['quadriceps'],   // ambiguo, default a quads
            'Full Body'            => ['chest', 'upper-back', 'quadriceps', 'gluteal'],
            'Cardio'               => [],                // no es un músculo
            'Cardio / Espalda'     => ['upper-back'],     // secundario
            'Cardio / Pantorrillas'=> ['calves'],         // secundario
        ];
    }

    /**
     * Mapeo de secundarios (basado en el modelo openGym de "secundario natural"
     * de cada grupo principal). Se agregan siempre con peso 0.4.
     */
    private function mapeoSecundarios(): array
    {
        return [
            'Abdomen'        => ['obliques'],
            'Antebrazos'     => ['biceps', 'triceps'],
            'Bíceps'         => ['forearm'],
            'Cuádriceps'     => ['gluteal', 'hamstring', 'adductors', 'calves'],
            'Espalda'        => ['biceps', 'trapezius', 'lower-back'],
            'Espalda baja'   => ['gluteal', 'hamstring', 'upper-back'],
            'Glúteos'        => ['hamstring', 'quadriceps', 'lower-back'],
            'Hombros'        => ['trapezius', 'chest', 'upper-back', 'triceps'],
            'Isquiotibiales' => ['gluteal', 'calves', 'lower-back'],
            'Pantorrillas'   => ['hamstring'],
            'Pecho'          => ['triceps', 'deltoids', 'serratus'],
            'Trapecio'       => ['deltoids', 'upper-back'],
            'Tríceps'        => ['chest', 'deltoids'],
            'Piernas'        => ['hamstring', 'gluteal', 'calves'],
            'Full Body'      => ['biceps', 'triceps', 'deltoids', 'abs', 'hamstring', 'calves'],
        ];
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('MODO DRY-RUN: no se escribirá nada en la DB');
        }

        $directo = $this->mapeoDirecto();
        $secundarios = $this->mapeoSecundarios();
        $musculosBySlug = Musculo::pluck('id', 'slug');

        $ejercicios = Ejercicio::whereNotNull('grupo_muscular')
            ->where('grupo_muscular', '!=', '')
            ->get();

        $this->info("Procesando {$ejercicios->count()} ejercicios...");
        $this->newLine();

        $mapeados = 0;
        $omitidos = 0;
        $sinGrupo = [];

        foreach ($ejercicios as $ej) {
            $grupo = trim($ej->grupo_muscular);
            $primarios = $directo[$grupo] ?? null;
            $segundos = $secundarios[$grupo] ?? [];

            if ($primarios === null) {
                $sinGrupo[$grupo] = ($sinGrupo[$grupo] ?? 0) + 1;
                $omitidos++;
                continue;
            }

            if (empty($primarios) && empty($segundos)) {
                // Cardio puro sin músculos
                $omitidos++;
                continue;
            }

            $mapeados++;
            foreach ($primarios as $slug) {
                $musculoId = $musculosBySlug[$slug] ?? null;
                if (!$musculoId) continue;
                if (!$dryRun) {
                    \DB::table('ejercicio_musculos')->updateOrInsert(
                        ['ejercicio_id' => $ej->id, 'musculo_id' => $musculoId, 'tipo' => 'primario'],
                        ['peso' => 1.00, 'fuente' => 'mapeo_automatico', 'created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
            foreach ($segundos as $slug) {
                $musculoId = $musculosBySlug[$slug] ?? null;
                if (!$musculoId) continue;
                if (!$dryRun) {
                    \DB::table('ejercicio_musculos')->updateOrInsert(
                        ['ejercicio_id' => $ej->id, 'musculo_id' => $musculoId, 'tipo' => 'secundario'],
                        ['peso' => 0.40, 'fuente' => 'mapeo_automatico', 'created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }

        $this->newLine();
        $this->info("✓ {$mapeados} ejercicios mapeados");
        $this->info("⊘ {$omitidos} omitidos (sin mapeo o cardio puro)");

        if (!empty($sinGrupo)) {
            $this->newLine();
            $this->warn('Grupos no reconocidos (sumalos a los arrays de mapeo si querés cubrirlos):');
            foreach ($sinGrupo as $g => $c) {
                $this->line("  '$g' → $c ejercicios");
            }
        }

        if (!$dryRun) {
            $this->newLine();
            $totalMapeos = \DB::table('ejercicio_musculos')->count();
            $this->info("Total de mapeos ejercicio-músculo en la DB: {$totalMapeos}");

            // Top músculos más usados
            $top = \DB::table('ejercicio_musculos as em')
                ->join('musculos as m', 'm.id', '=', 'em.musculo_id')
                ->select('m.slug', 'm.nombre_es', \DB::raw('count(*) as total'))
                ->where('em.tipo', 'primario')
                ->groupBy('m.id', 'm.slug', 'm.nombre_es')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            $this->newLine();
            $this->info('Top 10 músculos primarios:');
            $rows = [];
            foreach ($top as $t) {
                $rows[] = [$t->slug, $t->nombre_es, $t->total];
            }
            $this->table(['Slug', 'Nombre ES', 'Ejercicios como primario'], $rows);
        }

        return self::SUCCESS;
    }
}
