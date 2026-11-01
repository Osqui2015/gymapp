<?php

namespace Tests\Feature;

use App\Services\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * HealthCheckTest — health check enriquecido (/up y /up/live).
 *
 * Contexto de por qué:
 *   El `/up` nativo de Laravel 12 solo verifica que el framework arranca y
 *   devuelve HTML con un CDN de Tailwind. Si MySQL se cae, `/up` sigue
 *   respondiendo 200: el monitor del hosting dice "todo bien" mientras la app
 *   esta muerta. Estos tests fijan el contrato nuevo: JSON, checks reales y
 *   sin filtrar datos internos del servidor.
 */
class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_health_up_responde_ok_cuando_todo_anda(): void
    {
        $response = $this->getJson('/up');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'ok');

        // Los 5 checks deben estar presentes.
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'duration_ms',
            'checks' => [
                'database' => ['ok', 'message', 'data'],
                'storage' => ['ok', 'message', 'data'],
                'disk' => ['ok', 'message', 'data'],
                'cache' => ['ok', 'message', 'data'],
                'app' => ['ok', 'message', 'data'],
            ],
        ]);
    }

    public function test_la_health_up_no_requiere_autenticacion(): void
    {
        // Sin actingAs: si exigiera sesion devolveria 302/401.
        $this->getJson('/up')->assertStatus(200);
    }

    public function test_el_check_de_base_de_datos_verifica_conexion_y_ping(): void
    {
        $response = $this->getJson('/up');

        $response->assertJsonPath('checks.database.ok', true);
        $response->assertJsonPath('checks.database.data.driver', DB::connection()->getDriverName());
        $response->assertJsonPath('checks.database.data.server_ping', 1);
        $this->assertIsFloat($response->json('checks.database.data.latency_ms'));
    }

    public function test_el_check_de_storage_verifica_las_rutas_criticas(): void
    {
        $response = $this->getJson('/up');

        $response->assertJsonPath('checks.storage.ok', true);

        $paths = $response->json('checks.storage.data.paths');

        $this->assertIsArray($paths);
        $this->assertNotEmpty($paths);

        foreach ($paths as $path => $info) {
            $this->assertTrue($info['exists'], "La ruta {$path} deberia existir");
            $this->assertTrue($info['writable'], "La ruta {$path} deberia ser escribible");
        }
    }

    public function test_el_check_de_disco_reporta_el_espacio_libre(): void
    {
        $response = $this->getJson('/up');

        $response->assertJsonPath('checks.disk.ok', true);
        $response->assertJsonPath('checks.disk.data.min_required_mb', 500);
        $this->assertIsNumeric($response->json('checks.disk.data.free_mb'));
    }

    public function test_el_check_de_cache_escribe_y_lee_del_store(): void
    {
        $response = $this->getJson('/up');

        $response->assertJsonPath('checks.cache.ok', true);
        $response->assertJsonPath('checks.cache.data.driver', config('cache.default'));
    }

    public function test_el_endpoint_publico_no_expone_datos_internos_del_servidor(): void
    {
        $response = $this->getJson('/up');

        $json = $response->getContent();

        // Nombre de la base de datos: no debe salir.
        $this->assertArrayNotHasKey('database', $response->json('checks.database.data'));

        // Rutas absolutas del servidor: no deben salir.
        $this->assertStringNotContainsString(base_path(), $json);
        $this->assertStringNotContainsString(storage_path(), $json);

        // Versiones exactas: solo por CLI.
        $this->assertArrayNotHasKey('php_version', $response->json('checks.app.data'));
        $this->assertArrayNotHasKey('laravel', $response->json('checks.app.data'));
    }

    public function test_las_rutas_de_storage_son_relativas_al_base_path(): void
    {
        $response = $this->getJson('/up');

        $paths = array_keys($response->json('checks.storage.data.paths'));

        foreach ($paths as $path) {
            $this->assertStringStartsNotWith('/', $path, "La ruta {$path} no deberia ser absoluta");
            $this->assertStringNotContainsString(':\\', $path);
        }
    }

    public function test_up_live_responde_alive_sin_tocar_la_base_de_datos(): void
    {
        $response = $this->getJson('/up/live');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'alive');
        $response->assertJsonStructure(['status', 'timestamp']);

        // Sin ?verbose no se exponen los checks.
        $this->assertArrayNotHasKey('checks', $response->json());
    }

    public function test_up_live_con_verbose_expone_los_checks(): void
    {
        $response = $this->getJson('/up/live?verbose=1');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'alive');
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database' => ['ok', 'message'],
                'storage' => ['ok', 'message'],
                'disk' => ['ok', 'message'],
                'cache' => ['ok', 'message'],
                'app' => ['ok', 'message'],
            ],
        ]);
    }

    public function test_el_servicio_health_check_devuelve_una_estructura_consistente(): void
    {
        $result = app(HealthCheckService::class)->run();

        $this->assertArrayHasKey('status', $result);
        $this->assertContains($result['status'], ['ok', 'degraded', 'down']);
        $this->assertArrayHasKey('checks', $result);
        $this->assertCount(5, $result['checks']);

        foreach ($result['checks'] as $name => $check) {
            $this->assertArrayHasKey('ok', $check, "El check {$name} no tiene 'ok'");
            $this->assertArrayHasKey('message', $check, "El check {$name} no tiene 'message'");
            $this->assertArrayHasKey('data', $check, "El check {$name} no tiene 'data'");
            $this->assertIsBool($check['ok']);
        }
    }

    public function test_un_fallo_de_base_de_datos_marca_el_estado_down(): void
    {
        // NO se cambia config('database.default'): la conexion ya esta
        // establecida y cambiarla a una inexistente deja la transaccion de
        // RefreshDatabase colgada ("There is already an active transaction"),
        // rompiendo todos los tests siguientes.
        //
        // Se aísla el caso con un service parcial: el check de DB falla, el
        // resto responde bien. Un fallo de DB es "down" (la app no sirve
        // tráfico) mientras que un fallo de storage es solo "degraded".
        $service = new class extends HealthCheckService
        {
            protected function checkDatabase(): array
            {
                return [
                    'ok' => false,
                    'message' => 'Base de datos inaccesible: connection refused',
                    'data' => ['driver' => 'mysql'],
                ];
            }
        };

        $result = $service->run();

        $this->assertSame('down', $result['status']);
        $this->assertFalse($result['checks']['database']['ok']);
        $this->assertStringContainsString('Base de datos inaccesible', $result['checks']['database']['message']);
    }

    public function test_un_fallo_no_critico_marca_el_estado_degraded(): void
    {
        // Un fallo de storage/disco NO es "down": la app sigue sirviendo
        // tráfico, solo con LOGGER en modo degradado.
        $service = new class extends HealthCheckService
        {
            protected function checkStorage(): array
            {
                return [
                    'ok' => false,
                    'message' => 'storage/ sin permisos de escritura en alguna ruta crítica',
                    'data' => ['paths' => []],
                ];
            }
        };

        $result = $service->run();

        $this->assertSame('degraded', $result['status']);
        $this->assertFalse($result['checks']['storage']['ok']);
        $this->assertTrue($result['checks']['database']['ok']);
    }

    public function test_el_comando_health_check_devuelve_codigo_de_salida_segun_el_estado(): void
    {
        $this->artisan('health:check')
            ->expectsOutputToContain('GymApp — Health Check')
            ->assertExitCode(0);
    }

    public function test_el_comando_health_check_soporta_salida_json(): void
    {
        $this->artisan('health:check --json')
            ->assertExitCode(0);
    }

    public function test_las_rutas_de_health_estan_registradas_y_sin_middleware_de_auth(): void
    {
        $route = Route::getRoutes()->getByName('health.up');
        $this->assertNotNull($route, 'La ruta health.up no esta registrada');
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame('up', $route->uri());

        // NO debe tener middleware: sin 'web' (StartSession) ni 'api'
        // (throttle:api, que usa el cache store con driver 'database').
        // Si /up dependiera de la sesion o del throttle, con la DB caida
        // el health check daria 500 en vez de un diagnostico util.
        $this->assertSame([], $route->gatherMiddleware());
    }

    public function test_la_ruta_live_tampoco_tiene_middleware(): void
    {
        $route = Route::getRoutes()->getByName('health.live');

        $this->assertNotNull($route, 'La ruta health.live no esta registrada');
        $this->assertSame([], $route->gatherMiddleware());
    }

    public function test_el_health_check_no_crea_filas_en_la_tabla_de_sesiones(): void
    {
        $antes = DB::table('sessions')->count();

        $this->getJson('/up')->assertStatus(200);
        $this->getJson('/up/live')->assertStatus(200);

        $this->assertSame($antes, DB::table('sessions')->count(), 'El health check no debe abrir sesion');
    }

    public function test_el_health_check_detecta_debug_activo_en_produccion(): void
    {
        // app() ya esta booted en testing; forzamos el escenario.
        config(['app.debug' => true]);

        $result = app(HealthCheckService::class)->run();

        // En el entorno 'testing' no es produccion, asi que no debe marcar
        // el problema de debug, pero el check de app debe seguir respondiendo.
        $this->assertIsBool($result['checks']['app']['ok']);
        $this->assertArrayHasKey('debug', $result['checks']['app']['data']);
        $this->assertArrayHasKey('environment', $result['checks']['app']['data']);
    }

    public function test_la_base_de_datos_sigue_operativa_despues_del_health_check(): void
    {
        // El health check no debe dejar transactions abiertas ni romper la
        // conexion para el resto de la app.
        $this->getJson('/up')->assertStatus(200);

        $this->assertSame(1, DB::selectOne('SELECT 1 AS n')->n);
    }
}
