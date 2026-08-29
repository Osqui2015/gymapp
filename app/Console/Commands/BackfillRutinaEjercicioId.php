<?php

namespace App\Console\Commands;

use App\Models\Ejercicio;
use App\Models\Historial;
use App\Models\Rutina;
use Illuminate\Console\Command;

/**
 * Backfill del FK ejercicio_id en rutinas y historials por match de nombre.
 *
 * Las migraciones originales hacian este backfill en su up(), pero las
 * migraciones corren ANTES que los seeders, asi que la tabla `ejercicios`
 * estaba vacia y nada matcheaba. Por eso el DatabaseSeeder hace el backfill
 * al final, pero si solo corres un seeder puntual (como
 * RutinaDia3FullBodySeeder), el FK queda null y el item aparece vacio en
 * la UI (filtros lo ocultan).
 *
 * Idempotente: solo actualiza filas con ejercicio_id NULL.
 *
 * Uso:
 *   php artisan rutinas:backfill-fk
 *   php artisan rutinas:backfill-fk --dry-run  # ver qué haría sin escribir
 */
class BackfillRutinaEjercicioId extends Command
{
    protected $signature = 'rutinas:backfill-fk {--dry-run : No escribir en la DB}';
    protected $description = 'Setea ejercicio_id en rutinas e historiales matcheando por nombre';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('MODO DRY-RUN: no se escribiran cambios.');
        }

        $ejercicios = Ejercicio::pluck('id', 'nombre');
        if ($ejercicios->isEmpty()) {
            $this->error('Tabla ejercicios vacia. Correr primero php artisan db:seed --class=EjercicioSeeder');
            return self::FAILURE;
        }

        // === Rutinas ===
        $this->info('Backfill de rutinas.ejercicio_id...');
        $totalRutinas = 0;
        foreach ($ejercicios as $nombre => $id) {
            $count = Rutina::where('ejercicio_nombre', $nombre)
                ->whereNull('ejercicio_id')
                ->count();
            if ($count > 0) {
                $this->line("  - {$nombre} (id={$id}): {$count} rutinas");
            }
            if (!$dryRun) {
                $totalRutinas += Rutina::where('ejercicio_nombre', $nombre)
                    ->whereNull('ejercicio_id')
                    ->update(['ejercicio_id' => $id]);
            }
        }

        // === Historiales ===
        $this->info('Backfill de historials.ejercicio_id...');
        $totalHist = 0;
        foreach ($ejercicios as $nombre => $id) {
            $count = Historial::where('ejercicio_nombre', $nombre)
                ->whereNull('ejercicio_id')
                ->count();
            if ($count > 0) {
                $this->line("  - {$nombre} (id={$id}): {$count} historiales");
            }
            if (!$dryRun) {
                $totalHist += Historial::where('ejercicio_nombre', $nombre)
                    ->whereNull('ejercicio_id')
                    ->update(['ejercicio_id' => $id]);
            }
        }

        // === Resumen ===
        $unmatchedRutinas = Rutina::whereNull('ejercicio_id')->count();
        $unmatchedHist = Historial::whereNull('ejercicio_id')->count();

        if ($dryRun) {
            $this->info("DRY-RUN: se hubieran actualizado {$totalRutinas} rutinas y {$totalHist} historiales.");
        } else {
            $this->info("Backfill OK: {$totalRutinas} rutinas y {$totalHist} historiales con ejercicio_id seteado.");
        }

        if ($unmatchedRutinas > 0 || $unmatchedHist > 0) {
            $this->warn("Quedan sin match: {$unmatchedRutinas} rutinas y {$unmatchedHist} historiales.");
            $this->line('Probable causa: el ejercicio no existe en la biblioteca (EjercicioSeeder).');
        }

        return self::SUCCESS;
    }
}
