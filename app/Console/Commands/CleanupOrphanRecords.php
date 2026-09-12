<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Detecta y (opcionalmente) elimina registros huérfanos.
 *
 * Un huérfano es una fila cuya FK user_id ya no existe en users.
 * Es típico que aparezcan cuando se hace soft/hard delete de un usuario
 * sin limpiar sus datos asociados.
 *
 * Uso:
 *   php artisan db:cleanup-orphans             # solo reporta
 *   php artisan db:cleanup-orphans --prune     # elimina los huérfanos
 *   php artisan db:cleanup-orphans --prune --force  # sin pedir confirmación
 */
class CleanupOrphanRecords extends Command
{
    protected $signature = 'db:cleanup-orphans
        {--prune : Eliminar los registros huérfanos detectados (sin esto, solo reporta)}
        {--force : No pedir confirmación al prunear}';

    protected $description = 'Detecta (y opcionalmente elimina) registros huérfanos con user_id inválido.';

    /**
     * Mapa de tablas y la columna FK que debe apuntar a users.id.
     */
    private const ORPHAN_TABLES = [
        'historials' => 'user_id',
        'sesiones_entrenamiento' => 'user_id',
        'progresos' => 'user_id',
        'diario_nutricion' => 'user_id',
        'user_rutinas' => 'user_id',
        'trainer_comments' => 'alumno_id',     // también apunta a users
        'user_rutina_reschedules' => 'user_id',
        'audit_logs' => 'user_id',
        'notifications' => 'notifiable_id',     // polimórfica, manejada aparte
    ];

    public function handle(): int
    {
        $prune = (bool) $this->option('prune');
        $force = (bool) $this->option('force');

        $this->info('Buscando registros huérfanos...');

        $totalFound = 0;
        $totalDeleted = 0;
        $report = [];

        foreach (self::ORPHAN_TABLES as $table => $column) {
            if (! $this->tableExists($table)) {
                continue;
            }

            // Para la polimórfica de notifications, filtramos por notifiable_type
            $query = DB::table($table)
                ->whereNotNull($column)
                ->whereNotExists(function ($q) use ($table, $column) {
                    $q->select(DB::raw(1))
                        ->from('users')
                        ->whereColumn('users.id', "{$table}.{$column}");
                });

            if ($table === 'notifications') {
                $query->where('notifiable_type', 'App\\Models\\User');
            }

            $count = (clone $query)->count();
            $totalFound += $count;
            $report[] = [$table, $column, $count];

            if ($count === 0) {
                continue;
            }

            if (! $prune) {
                continue;
            }

            if (! $force) {
                if (! $this->confirm("¿Eliminar $count registro(s) huérfano(s) de '$table'?", true)) {
                    $this->line("  Omitido: $table");

                    continue;
                }
            }

            $deleted = $query->delete();
            $totalDeleted += $deleted;
            $this->line("  ✓ $table: $deleted eliminado(s).");
        }

        $this->newLine();
        $this->table(['Tabla', 'Columna FK', 'Huérfanos encontrados'], $report);
        $this->info("Total: $totalFound huérfano(s)".($prune ? ", $totalDeleted eliminado(s)." : ' (modo dry-run).'));

        if (! $prune && $totalFound > 0) {
            $this->newLine();
            $this->comment('Para eliminarlos, ejecutá: php artisan db:cleanup-orphans --prune');
        }

        return self::SUCCESS;
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
