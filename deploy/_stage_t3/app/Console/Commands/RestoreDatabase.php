<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

/**
 * Restaura un backup de la base de datos.
 *
 * Uso:
 *   php artisan db:restore                              # interactivo: lista backups
 *   php artisan db:restore --file=backup-2026-09-06.sql.gz
 *   php artisan db:restore --latest                     # usa el más reciente
 *
 * Pide confirmación explícita antes de pisar la BD. NO usar en producción
 * sin entender que esto SOBREESCRIBE todos los datos actuales.
 */
class RestoreDatabase extends Command
{
    protected $signature = 'db:restore
        {--file= : Nombre del archivo .sql.gz en storage/app/backups/}
        {--latest : Usar el backup más reciente disponible}
        {--connection=mysql : Conexión sobre la que restaurar}';

    protected $description = 'Restaura un backup .sql.gz sobre la base de datos actual (operación destructiva).';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (! File::isDirectory($backupDir)) {
            $this->error("No existe el directorio de backups: $backupDir");

            return self::FAILURE;
        }

        $file = $this->resolveBackupFile($backupDir);
        if (! $file) {
            return self::FAILURE;
        }

        $this->warn('⚠️  ESTA OPERACIÓN SOBREESCRIBE LA BASE DE DATOS ACTUAL.');
        $this->line("  Archivo: {$file->getFilename()}");
        $this->line('  Tamaño:  '.round($file->getSize() / 1024 / 1024, 2).' MB');
        $this->line('  Fecha:   '.date('Y-m-d H:i:s', $file->getMTime()));

        if (! $this->confirm('¿Continuar con la restauración?', false)) {
            $this->info('Cancelado.');

            return self::SUCCESS;
        }

        $connection = (string) $this->option('connection');
        $config = config("database.connections.$connection");
        if (! $config || ($config['driver'] ?? null) !== 'mysql') {
            $this->error("Conexión '$connection' no encontrada o no es MySQL.");

            return self::FAILURE;
        }

        $mysqlBinary = $this->resolveMysql();
        if (! $mysqlBinary) {
            $this->error('No se encontró el binario mysql (cliente).');

            return self::FAILURE;
        }

        // Descomprimir en un archivo temporal.
        $sqlTemp = $backupDir.DIRECTORY_SEPARATOR.'_restore_'.uniqid('', true).'.sql';
        $decoded = @file_get_contents($file->getPathname());
        if ($decoded === false) {
            $this->error('No se pudo leer el archivo de backup.');

            return self::FAILURE;
        }

        $sql = @gzdecode($decoded);
        if ($sql === false) {
            $this->error('El archivo no es gzip válido.');

            return self::FAILURE;
        }

        File::put($sqlTemp, $sql);

        $cmd = [
            $mysqlBinary,
            '--user='.$config['username'],
            '--password='.$config['password'],
            '--host='.$config['host'],
            '--port='.(string) ($config['port'] ?? 3306),
            '--default-character-set=utf8mb4',
            $config['database'],
        ];

        $this->info('Restaurando...');
        $process = new Process($cmd);
        $process->setInput($sql);
        $process->setTimeout(900); // 15 min

        try {
            $process->run();
        } finally {
            // Limpiar el SQL temporal SIEMPRE, incluso si falla.
            if (File::exists($sqlTemp)) {
                File::delete($sqlTemp);
            }
        }

        if (! $process->isSuccessful()) {
            $this->error('mysql salió con código '.$process->getExitCode());
            $this->error($process->getErrorOutput());

            return self::FAILURE;
        }

        $this->info('✓ Restauración completa.');

        AuditLog::log(
            'db_restore',
            "Restauración de BD desde {$file->getFilename()}",
            null,
            null,
            null,
            null,
            ['filename' => $file->getFilename(), 'size_mb' => round($file->getSize() / 1024 / 1024, 2)]
        );

        return self::SUCCESS;
    }

    private function resolveBackupFile(string $dir): ?SplFileInfo
    {
        $files = collect(File::files($dir))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.sql.gz'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        if ($files->isEmpty()) {
            $this->error('No hay backups disponibles en storage/app/backups/.');

            return null;
        }

        if ($this->option('latest')) {
            return $files->first();
        }

        $explicit = $this->option('file');
        if ($explicit) {
            $match = $files->first(fn ($f) => $f->getFilename() === $explicit);
            if (! $match) {
                $this->error("No se encontró el backup '$explicit'.");

                return null;
            }

            return $match;
        }

        // Selección interactiva.
        $choice = $this->choice(
            '¿Qué backup querés restaurar?',
            $files->map(fn ($f) => $f->getFilename().'  ('.round($f->getSize() / 1024 / 1024, 2).' MB, '.date('Y-m-d H:i:s', $f->getMTime()).')')->all()
        );

        // Extraer el nombre real antes del padding.
        preg_match('/^(\S+)/', $choice, $m);

        return $files->first(fn ($f) => $f->getFilename() === $m[1]);
    }

    private function resolveMysql(): ?string
    {
        $explicit = env('MYSQL_PATH');
        if ($explicit && file_exists($explicit)) {
            return $explicit;
        }

        $laragon = 'I:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysql.exe';
        if (file_exists($laragon)) {
            return $laragon;
        }

        $candidates = PHP_OS_FAMILY === 'Windows'
            ? ['mysql.exe', 'mysql']
            : ['mysql'];

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
                // continuar
            }
        }

        return null;
    }
}
