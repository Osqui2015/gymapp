<?php

namespace Database\Seeders;

use App\Models\Ejercicio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * VisualGymSeeder — importa los 1.324 ejercicios del dataset
 * hasaneyldrm/exercises-dataset a la tabla `ejercicios`.
 *
 * Idempotente: si ya existe un registro con el mismo external_id,
 * lo actualiza en vez de duplicarlo.
 *
 * Los assets (imágenes y GIFs) deben estar copiados en
 *   storage/app/public/exercises/images/
 *   storage/app/public/exercises/videos/
 * antes de correr este seeder (ver VisualGym.md, Fase 1).
 *
 * Uso:
 *   php artisan db:seed --class=VisualGymSeeder
 */
class VisualGymSeeder extends Seeder
{
    /**
     * Tamaño de chunk para inserts. MySQL acepta ~1000 placeholders
     * por INSERT. 200 es seguro y rápido.
     */
    private const CHUNK_SIZE = 200;

    public function run(): void
    {
        $path = database_path('seeders/data/exercises.json');

        if (! File::exists($path)) {
            $this->command->error("No se encontró $path. Copiá el JSON del dataset VisualGym primero.");

            return;
        }

        $this->command->info('Cargando exercises.json...');
        $json = File::get($path);
        $items = json_decode($json, true);

        if (! is_array($items) || empty($items)) {
            $this->command->error('El JSON está vacío o mal formado.');

            return;
        }

        $this->command->info('Ejercicios a procesar: '.count($items));

        // Mapeo del dataset a la tabla `ejercicios`:
        //   name              → nombre
        //   category          → body_part (categoría gruesa del dataset)
        //   equipment         → equipamiento
        //   muscle_group      → grupo_muscular (legacy / compat con código existente)
        //   target            → target (músculo primario en inglés)
        //   instructions      → instructions (JSON)
        //   instruction_steps → instruction_steps (JSON)
        //   secondary_muscles → secondary_muscles (JSON)
        //   media_id          → media_id
        //   image             → image_path (path relativo en storage/exercises/images/)
        //   gif_url           → gif_path (path relativo en storage/exercises/videos/)
        //   attribution       → fuente_credito
        //   created_at        → source_created_at
        //
        // Decisión: equipment del dataset viene en inglés
        // ("body weight", "dumbbell", "cable", "barbell"...). Lo guardamos tal cual
        // en `equipamiento`. Para mostrar en español usaremos el catálogo de
        // equipamientos existente o un mapping cliente.

        $now = now();
        $rows = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $externalId = $item['id'] ?? null;

            if (! $externalId) {
                $skipped++;
                continue;
            }

            $rows[] = [
                'external_id' => (string) $externalId,
                'nombre' => $item['name'] ?? '',
                'body_part' => $item['body_part'] ?? $item['category'] ?? null,
                'equipamiento' => $item['equipment'] ?? null,
                'grupo_muscular' => $item['muscle_group'] ?? null,
                'target' => $item['target'] ?? null,
                'media_id' => $item['media_id'] ?? null,
                'image_path' => $item['image'] ?? null,
                'gif_path' => $item['gif_url'] ?? null,
                'instructions' => isset($item['instructions'])
                    ? json_encode($item['instructions'], JSON_UNESCAPED_UNICODE)
                    : null,
                'instruction_steps' => isset($item['instruction_steps'])
                    ? json_encode($item['instruction_steps'], JSON_UNESCAPED_UNICODE)
                    : null,
                'secondary_muscles' => isset($item['secondary_muscles'])
                    ? json_encode($item['secondary_muscles'], JSON_UNESCAPED_UNICODE)
                    : null,
                'fuente_credito' => $item['attribution'] ?? null,
                'fuente_tipo' => 'gym_visual',
                'source' => 'visualgym',
                'source_created_at' => isset($item['created_at'])
                    ? date('Y-m-d H:i:s', strtotime($item['created_at']))
                    : null,
                'visibilidad' => true,
                'dificultad' => 'intermedio',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) >= self::CHUNK_SIZE) {
                [$c, $u] = $this->upsertChunk($rows);
                $created += $c;
                $updated += $u;
                $rows = [];
            }
        }

        // Flush final
        if (! empty($rows)) {
            [$c, $u] = $this->upsertChunk($rows);
            $created += $c;
            $updated += $u;
        }

        $this->command->info("VisualGym import completo:");
        $this->command->info("  • Creados:   $created");
        $this->command->info("  • Actualizados: $updated");
        $this->command->info("  • Saltados (sin id): $skipped");

        $total = Ejercicio::where('source', 'visualgym')->count();
        $this->command->info("  • Total source=visualgym en DB: $total");
    }

    /**
     * Upsert por external_id. Devuelve [creados, actualizados].
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{0:int, 1:int}
     */
    private function upsertChunk(array $rows): array
    {
        if (empty($rows)) {
            return [0, 0];
        }

        // Contamos los que ya existen para reportar.
        $externalIds = array_column($rows, 'external_id');
        $existing = DB::table('ejercicios')
            ->whereIn('external_id', $externalIds)
            ->pluck('external_id')
            ->all();
        $existingSet = array_flip($existing);
        $created = 0;
        $updated = 0;
        foreach ($externalIds as $eid) {
            if (isset($existingSet[$eid])) {
                $updated++;
            } else {
                $created++;
            }
        }

        DB::table('ejercicios')->upsert(
            $rows,
            ['external_id'], // clave única
            [
                'nombre', 'body_part', 'equipamiento', 'grupo_muscular', 'target',
                'media_id', 'image_path', 'gif_path',
                'instructions', 'instruction_steps', 'secondary_muscles',
                'fuente_credito', 'fuente_tipo', 'source', 'source_created_at',
                'visibilidad', 'dificultad', 'updated_at',
            ]
        );

        return [$created, $updated];
    }
}
