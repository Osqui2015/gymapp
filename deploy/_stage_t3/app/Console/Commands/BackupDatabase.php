<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Backup diario de la base de datos MySQL.
 *
 * Uso:
 *   php artisan db:backup
 *   php artisan db:backup --retention-days=14
 *   php artisan db:backup --connection=mysql
 *
 * Genera un dump con mysqldump (--single-transaction para InnoDB consistente),
 * lo comprime con gzencode() y lo guarda en storage/app/backups/ con el
 * formato backup-YYYY-MM-DD_HHmmss.sql.gz. Rota los archivos más viejos
 * que --retention-days.
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup
        {--retention-days=7 : Días de backups a conservar (los más viejos se eliminan)}
        {--connection=mysql : Conexión de base de datos a respaldar}';

    protected $description = 'Genera un backup comprimido de la base de datos y rota los archivos según la retención.';

    public function handle(): int
    {
        $connection = (string) $this->option('connection');
        $retentionDays = max(1, (int) $this->option('retention-days'));

        $config = config("database.connections.$connection");
        if (! $config || ($config['driver'] ?? null) !== 'mysql') {
            $this->error("Conexión '$connection' no encontrada o no es MySQL.");

            return self::FAILURE;
        }

        $dumpBinary = $this->resolveMysqldump();
        if (! $dumpBinary) {
            $this->error('No se encontró el binario mysqldump. Instalá MySQL client o agregá la ruta al PATH.');

            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $filename = 'backup-'.now()->format('Y-m-d_His').'.sql.gz';
        $fullPath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        // mysqldump escribe a stdout, lo pipeamos a gzip en PHP.
        // Importante: usar --single-transaction (consistencia InnoDB) y
        // --skip-lock-tables (no bloquea lecturas/escrituras en producción).
        $dumpCmd = [
            $dumpBinary,
            '--user='.$config['username'],
            '--password='.$config['password'],
            '--host='.$config['host'],
            '--port='.(string) ($config['port'] ?? 3306),
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--default-character-set=utf8mb4',
            $config['database'],
        ];

        $this->info("Generando dump de '{$config['database']}' en $filename...");
        $start = microtime(true);

        $process = new Process($dumpCmd);
        $process->setTimeout(600); // 10 min de tope

        try {
            $process->run();
        } catch (\Throwable $e) {
            $this->error('mysqldump falló: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $process->isSuccessful()) {
            $this->error('mysqldump salió con código '.$process->getExitCode());
            $this->error($process->getErrorOutput());

            return self::FAILURE;
        }

        $compressed = gzencode($process->getOutput(), 6);
        if ($compressed === false) {
            $this->error('No se pudo comprimir el dump con gzip.');

            return self::FAILURE;
        }

        File::put($fullPath, $compressed);

        $sizeMb = round(strlen($compressed) / 1024 / 1024, 2);
        $duration = round(microtime(true) - $start, 2);
        $this->info("✓ Backup creado: $filename ({$sizeMb} MB en {$duration}s)");

        // Rotación: borrar archivos más viejos que retention-days.
        $deleted = $this->rotateOldBackups($backupDir, $retentionDays);
        if ($deleted > 0) {
            $this->info("Rotación: $deleted backup(s) eliminado(s) (retención: $retentionDays días).");
        }

        AuditLog::log(
            'db_backup',
            "Backup diario de BD: $filename ({$sizeMb} MB)",
            null,
            null,
            null,
            null,
            [
                'filename' => $filename,
                'size_mb' => $sizeMb,
                'retention_days' => $retentionDays,
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Busca mysqldump. En Laragon Windows usa la ruta conocida; en otros
     * sistemas operativos resuelve via which/where.
     */
    private function resolveMysqldump(): ?string
    {
        // 1. Override por env.
        $explicit = env('MYSQLDUMP_PATH');
        if ($explicit && file_exists($explicit)) {
            return $explicit;
        }

        // 2. Heurística específica de Laragon (Windows dev).
        $laragon = 'I:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe';
        if (file_exists($laragon)) {
            return $laragon;
        }

        // 3. Buscar en PATH.
        $candidates = PHP_OS_FAMILY === 'Windows'
            ? ['mysqldump.exe', 'mysqldump']
            : ['mysqldump'];

        foreach ($candidates as $bin) {
            $finder = new Process([PHP_OS_FAMILY === 'Windows' ? 'where' : 'which', $bin]);
            $finder->setTimeout(5);
            try {
                $finder->run();
                if ($finder->isSuccessful()) {
                    $path = trim(explode("\n", $finder->getOutput())[0]);
                    if ($path !== '' && file_exists($path)) {
                        return $path;
                    }
                }
            } catch (\Throwable) {
                // Continuar con el siguiente candidato
            }
        }

        return null;
    }

    private function rotateOldBackups(string $dir, int $retentionDays): int
    {
        $cutoff = now()->subDays($retentionDays)->getTimestamp();
        $deleted = 0;

        foreach (File::files($dir) as $file) {
            $name = $file->getFilename();
            if (! str_starts_with($name, 'backup-')) {
                continue;
            }
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        return $deleted;
    }
}
