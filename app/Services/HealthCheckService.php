<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * HealthCheckService — diagnóstico real de la salud de la aplicación.
 *
 * El endpoint `/up` nativo de Laravel 12 solo verifica que el framework
 * arranca: si MySQL se cae, `/up` sigue respondiendo 200 y el monitor del
 * hosting dice "todo bien" mientras la app está muerta. Este servicio hace
 * los checks que de verdad importan.
 *
 * Checks:
 *   - database : conexión MySQL + query trivial +Ping. Es el que más falla.
 *   - storage  : permisos de escritura en storage/ y storage/app.
 *   - disk     : espacio libre en disco (configurable con umbral).
 *   - cache    : que el cache store responda (database driver usa la DB).
 *   - app      : versión de PHP y si estamos en modo debug en producción.
 *
 * Cada check devuelve [ok, mensaje, datos]. Un check que lanza excepción se
 * marca como fallido con el mensaje del error, nunca revienta el health check
 * completo: un 500 sin detalle es peor que un 503 con diagnóstico.
 */
class HealthCheckService
{
    /**
     * Umbral mínimo de disco libre (en bytes). Por debajo se marca degradado.
     * 500 MB es suficiente para que Laravel escriba logs, sessions y backups
     * temporales sin quedarse sin espacio a mitad de una operación.
     */
    private const MIN_FREE_DISK_BYTES = 500 * 1024 * 1024;

    /**
     * Ejecuta todos los checks.
     *
     * @return array{
     *     status: string,
     *     checks: array<string, array{ok: bool, message: string, data: array}>,
     *     timestamp: string,
     *     duration_ms: float
     * }
     */
    public function run(): array
    {
        $start = microtime(true);

        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'disk' => $this->checkDisk(),
            'cache' => $this->checkCache(),
            'app' => $this->checkApp(),
        ];

        $failed = array_filter($checks, fn (array $c) => ! $c['ok']);

        // "degraded" = algo falla pero la app todavía puede servir tráfico
        // parcial. "down" = la DB no responde (la app no sirve nada).
        $status = 'ok';
        if ($failed !== []) {
            $critical = ! ($checks['database']['ok'] && $checks['cache']['ok']);
            $status = $critical ? 'down' : 'degraded';
        }

        return [
            'status' => $status,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ];
    }

    /**
     * Check de base de datos: conexión + query trivial + ping del server.
     *
     * @return array{ok: bool, message: string, data: array}
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            // Ping del servidor MySQL: detecta conexiones dormidas que el
            // pool del hosting cerró (MySQL wait_timeout en hosting compartido).
            $ping = null;
            try {
                $ping = DB::selectOne('SELECT 1 AS ping')->ping ?? null;
            } catch (Throwable $e) {
                $ping = null;
            }

            return [
                'ok' => true,
                'message' => 'Conexión MySQL OK',
                'data' => [
                    'driver' => DB::connection()->getDriverName(),
                    'database' => DB::connection()->getDatabaseName(),
                    'latency_ms' => $latency,
                    'server_ping' => $ping,
                ],
            ];
        } catch (Throwable $e) {
            Log::error('[health] check de base de datos falló', ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'message' => 'Base de datos inaccesible: '.$e->getMessage(),
                'data' => ['driver' => config('database.default')],
            ];
        }
    }

    /**
     * Check de permisos de escritura en storage/.
     *
     * En hosting compartido el caso real es: la app funciona pero no puede
     * escribir logs ni sessions, y el síntoma es "todo se rompe de a ratos".
     *
     * @return array{ok: bool, message: string, data: array}
     */
    private function checkStorage(): array
    {
        $paths = [
            storage_path(),
            storage_path('app'),
            storage_path('framework/cache/data'),
            storage_path('logs'),
        ];

        $results = [];
        $allWritable = true;

        foreach ($paths as $path) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            // Clave relativa al base path: la ruta ABSOLUTA del servidor no
            // debe salir del service. El endpoint /up es publico y filtrar
            // "I:\laragon\www\GymApp\storage" o "/home/u221495434/..." le
            // dice a un atacante exactamente donde esta el proyecto.
            $results[$this->relativePath($path)] = [
                'exists' => $exists,
                'writable' => $writable,
            ];

            if (! $exists || ! $writable) {
                $allWritable = false;
            }
        }

        return [
            'ok' => $allWritable,
            'message' => $allWritable
                ? 'storage/ con permisos de escritura OK'
                : 'storage/ sin permisos de escritura en alguna ruta crítica',
            'data' => ['paths' => $results],
        ];
    }

    /**
     * Convierte una ruta absoluta en relativa al base path de la app.
     * Si la ruta está fuera del base path, devuelve solo el nombre del
     * directorio para no filtrar nada del sistema de archivos.
     */
    private function relativePath(string $path): string
    {
        $base = base_path();

        if (str_starts_with($path, $base)) {
            return ltrim(substr($path, strlen($base)), '/\\') ?: '.';
        }

        return basename($path);
    }

    /**
     * Check de espacio libre en disco.
     *
     * @return array{ok: bool, message: string, data: array}
     */
    private function checkDisk(): array
    {
        try {
            $free = @disk_free_space(base_path());

            if ($free === false) {
                return [
                    'ok' => true,
                    'message' => 'Espacio en disco no verificable en esta plataforma',
                    'data' => ['free_bytes' => null],
                ];
            }

            $total = @disk_total_space(base_path());
            $ok = $free >= self::MIN_FREE_DISK_BYTES;

            return [
                'ok' => $ok,
                'message' => $ok
                    ? 'Espacio en disco suficiente'
                    : 'Espacio en disco por debajo del mínimo (500 MB)',
                'data' => [
                    'free_bytes' => $free,
                    'free_mb' => round($free / 1024 / 1024, 1),
                    'total_mb' => $total ? round($total / 1024 / 1024, 1) : null,
                    'used_percent' => $total ? round((($total - $free) / $total) * 100, 1) : null,
                    'min_required_mb' => (int) (self::MIN_FREE_DISK_BYTES / 1024 / 1024),
                ],
            ];
        } catch (Throwable $e) {
            return [
                'ok' => true,
                'message' => 'Espacio en disco no verificable',
                'data' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Check del cache store.
     *
     * Con el driver `database` (el que usa GymApp) el cache escribe en la
     * tabla `cache`: si la DB está caída, el cache también, y esto confirma
     * que el fallo es de DB y no de otra cosa.
     *
     * @return array{ok: bool, message: string, data: array}
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_probe';
            cache()->put($key, 'ok', 10);
            $value = cache()->get($key);
            cache()->forget($key);

            $ok = $value === 'ok';

            return [
                'ok' => $ok,
                'message' => $ok ? 'Cache store OK' : 'Cache store no devolvió el valor esperado',
                'data' => ['driver' => config('cache.default')],
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Cache inaccesible: '.$e->getMessage(),
                'data' => ['driver' => config('cache.default')],
            ];
        }
    }

    /**
     * Check de configuración de la app: versión de PHP y modo debug.
     *
     * @return array{ok: bool, message: string, data: array}
     */
    private function checkApp(): array
    {
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.2', '>=');

        $debugEnabled = (bool) config('app.debug');
        $isProduction = app()->isProduction();

        // app.debug=true en producción expone stack traces y variables de
        // entorno en las respuestas de error: es un problema de seguridad,
        // no de disponibilidad, pero tiene que quedar visible.
        $debugProblem = $debugEnabled && $isProduction;

        return [
            'ok' => $phpOk && ! $debugProblem,
            'message' => $debugProblem
                ? 'ATENCIÓN: app.debug está activo en producción'
                : ($phpOk ? 'Configuración de la app OK' : 'Versión de PHP por debajo del mínimo requerido (8.2)'),
            'data' => [
                'php_version' => $phpVersion,
                'php_min' => '8.2',
                'laravel' => app()->version(),
                'environment' => app()->environment(),
                'debug' => $debugEnabled,
                'timezone' => config('app.timezone'),
            ],
        ];
    }
}
