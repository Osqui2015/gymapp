<?php

namespace App\Http\Controllers;

use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;

/**
 * HealthController — health check enriquecido.
 *
 * Reemplaza al `/up` nativo de Laravel (que solo verifica que el framework
 * arranca y devuelve HTML con un CDN de Tailwind) por un endpoint JSON que
 * dice exactamente qué está roto: base de datos, permisos de storage, espacio
 * en disco, cache o configuración.
 *
 * Diseño:
 *   - Público (sin auth): un monitor externo tiene que poder consultarlo.
 *   - Sin datos sensibles: el endpoint HTTP no expone el nombre de la base
 *     de datos, ni rutas del sistema de archivos, ni versiones exactas.
 *     El detalle completo queda para el comando `php artisan health:check`
 *     (por SSH), que sí lo muestra.
 *   - Códigos HTTP: 200 si todo OK, 503 si la app no puede servir tráfico.
 *
 * Para diagnóstico local por SSH: `php artisan health:check`.
 */
class HealthController extends Controller
{
    public function __construct(private readonly HealthCheckService $health) {}

    /**
     * GET /up — estado de la aplicación.
     */
    public function __invoke(): JsonResponse
    {
        $result = $this->health->run();

        $httpCode = match ($result['status']) {
            'ok' => 200,
            'degraded' => 200,
            default => 503,
        };

        return response()->json($this->sanitize($result), $httpCode);
    }

    /**
     * GET /up/live — liveness probe.
     *
     * Solo verifica que PHP está ejecutando código. No toca la base de datos:
     * es para que un orquestador NO reinicie el contenedor/pod si lo que se
     * cayó es MySQL (reiniciar no arregla una base caída y genera un loop).
     *
     * Query param `?verbose=1` para incluir el detalle de los checks.
     */
    public function live(): JsonResponse
    {
        $verbose = request()->boolean('verbose');

        $payload = [
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ];

        if ($verbose) {
            $payload['checks'] = $this->sanitize($this->health->run())['checks'];
        }

        return response()->json($payload, 200);
    }

    /**
     * Removes the internal data that shouldn't be exposed publicly.
     *
     * The endpoint is public: it must not reveal the database name, the
     * server's absolute paths, or the exact PHP/Laravel version. Those stay
     * available via `php artisan health:check` over SSH, which is where the
     * real diagnosis happens.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function sanitize(array $result): array
    {
        $checks = $result['checks'];

        // database: connection driver yes, database name no.
        if (isset($checks['database']['data']['database'])) {
            unset($checks['database']['data']['database']);
        }

        // storage: relative paths are fine, absolute ones are not (already
        // relative in the service, but we double-check here).
        if (isset($checks['storage']['data']['paths'])) {
            foreach ($checks['storage']['data']['paths'] as $key => $path) {
                if (str_contains($key, '/') || str_contains($key, '\\')) {
                    $checks['storage']['data']['paths'][$key] = [
                        'exists' => $path['exists'] ?? false,
                        'writable' => $path['writable'] ?? false,
                    ];
                }
            }
        }

        // app: versions exactas solo por CLI.
        unset(
            $checks['app']['data']['php_version'],
            $checks['app']['data']['laravel'],
        );

        $result['checks'] = $checks;

        return $result;
    }
}
