<?php

namespace App\Console\Commands;

use App\Services\HealthCheckService;
use Illuminate\Console\Command;

/**
 * Comando `health:check` — diagnóstico de salud desde la terminal.
 *
 * Es la herramienta para diagnosticar producción por SSH sin tener que
 * abrir el navegador ni esperar que un monitor externo reporte el fallo.
 *
 *   php artisan health:check          # salida legible
 *   php artisan health:check --json   # salida JSON (para scripts)
 *
 * Exit code: 0 = ok, 1 = degraded, 2 = down. Sirve para encadenar en un
 * script de monitoreo sin tener que parsear la salida.
 */
class HealthCheck extends Command
{
    protected $signature = 'health:check
                            {--json : Salida en JSON para consumo por scripts}';

    protected $description = 'Diagnostica el estado de la app: base de datos, storage, disco, cache y configuracion';

    public function handle(HealthCheckService $health): int
    {
        $result = $health->run();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($result['status']);
        }

        $this->newLine();
        $this->line('  GymApp — Health Check');
        $this->line('  '.$result['timestamp'].'  ('.$result['duration_ms'].' ms)');
        $this->newLine();

        foreach ($result['checks'] as $name => $check) {
            $icon = $check['ok'] ? '<fg=green>OK  </>' : '<fg=red>FALLA</>';
            $this->line(sprintf('  %s  %-10s %s', $icon, $name, $check['message']));
        }

        $this->newLine();
        $statusColor = match ($result['status']) {
            'ok' => 'green',
            'degraded' => 'yellow',
            default => 'red',
        };
        $this->line(sprintf('  Estado general: <fg=%s>%s</>', $statusColor, strtoupper($result['status'])));
        $this->newLine();

        return $this->exitCode($result['status']);
    }

    /**
     * @return int 0 = ok, 1 = degraded, 2 = down
     */
    private function exitCode(string $status): int
    {
        return match ($status) {
            'ok' => self::SUCCESS,
            'degraded' => self::FAILURE,
            default => 2,
        };
    }
}
