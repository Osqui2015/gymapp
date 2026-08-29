<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: pega contra todos los endpoints que el front consume, con un
 * user real (regular + admin) y verifica que ninguno devuelva 500.
 *
 * Los 401/403/404/422 son respuestas correctas (auth faltante, no autorizado,
 * no encontrado, validación). Solo el 500 indica un bug.
 */
class ApiSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private array $errors = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

        // Seed un minimo de data para que los endpoints tengan que ver
        $rutina = Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 1 (Torso)',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Press banca', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);
        $ejercicio = Ejercicio::firstOrCreate(
            ['nombre' => 'Press banca test'],
            ['grupo_muscular' => 'pecho', 'equipamiento' => 'barra']
        );
        UserRutina::create([
            'user_id' => $this->user->id,
            'rutina_id' => $rutina->id,
            'dia_actual' => 'Día 1 (Torso)',
        ]);
    }

    public function test_smoke_endpoints_comunes_no_devuelven_500(): void
    {
        $this->actingAs($this->user);

        // Endpoints GET que el front consume en flujos normales
        $endpoints = [
            ['GET', '/api/user-info'],
            ['GET', '/api/user-rutina'],
            ['GET', '/api/user-rutina/available-days'],
            ['GET', '/api/rutinas'],
            ['GET', '/api/rutinas/sugeridas'],
            ['GET', '/api/ejercicios'],
            ['GET', '/api/ejercicios/equipamientos'],
            ['GET', '/api/ejercicios/grupos-musculares'],
            ['GET', '/api/musculos'],
            ['GET', '/api/body-map/data'],
            ['GET', '/api/body-map/muscle-recency'],
            ['GET', '/api/historial'],
            ['GET', '/api/historial/calendar'],
            ['GET', '/api/historial/week-summary'],
            ['GET', '/api/stats/resumen'],
            ['GET', '/api/stats/heatmap'],
            ['GET', '/api/stats/esfuerzo'],
            ['GET', '/api/stats/estimated-1rm'],
            ['GET', '/api/progreso'],
            ['GET', '/api/progreso/detalle'],
            ['GET', '/api/progreso/weight-chart'],
            ['GET', '/api/metas'],
            ['GET', '/api/logros'],
            ['GET', '/api/dashboard/today'],
            ['GET', '/api/comunidad/stats'],
            ['GET', '/api/notifications'],
            ['GET', '/api/ejercicios-clave'],
            ['GET', '/api/nutricion'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            if ($response->status() === 500) {
                $this->errors[] = "GET $url => 500 (body: " . substr(json_encode($response->json()), 0, 200) . ")";
            }
        }

        $this->assertEmpty($this->errors, "Endpoints que devolvieron 500:\n  " . implode("\n  ", $this->errors));
    }

    public function test_smoke_endpoints_post_comunes_no_devuelven_500(): void
    {
        $this->actingAs($this->user);

        // POST con body minimo valido. Si requiere mas campos del factory,
        // el 422 es OK (es la respuesta esperada), pero NO debe haber 500.
        $endpoints = [
            ['POST', '/api/user-rutina/dia', ['dia_actual' => 'Día 1 (Torso)']],
            ['POST', '/api/progreso', ['fecha' => '2026-08-28', 'peso' => 70.0]],
            ['POST', '/api/nutricion/agua', ['cantidad_ml' => 250]],
            ['POST', '/api/rutinas/importar', ['nivel' => 'Intermedio', 'modalidad' => '3 Días', 'created_by' => null]],
            ['POST', '/api/notifications/read-all', []],
            ['POST', '/api/metas', ['titulo' => 'Test meta', 'tipo' => 'general']],
        ];

        foreach ($endpoints as [$method, $url, $body]) {
            $response = $this->json($method, $url, $body);
            if ($response->status() === 500) {
                $this->errors[] = "$method $url => 500 (body: " . substr(json_encode($response->json()), 0, 200) . ")";
            }
        }

        $this->assertEmpty($this->errors, "Endpoints POST que devolvieron 500:\n  " . implode("\n  ", $this->errors));
    }

    public function test_smoke_endpoints_admin_no_devuelven_500(): void
    {
        $this->actingAs($this->admin);

        $endpoints = [
            ['GET', '/api/admin/users'],
            ['GET', '/api/admin/membresias'],
            ['GET', '/api/admin/audit-logs'],
            ['GET', '/api/admin/reportes'],
            ['GET', '/api/admin/estadisticas'],
            ['GET', '/api/admin/miembros-activos'],
            ['GET', '/api/admin/usuarios-sin-membresia'],
            ['GET', '/api/admin/trainers-alumnos'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            if ($response->status() === 500) {
                $this->errors[] = "GET $url => 500";
            }
        }

        $this->assertEmpty($this->errors, "Endpoints admin que devolvieron 500:\n  " . implode("\n  ", $this->errors));
    }

    public function test_smoke_user_rutina_dia_no_500_con_relacion_no_cargada(): void
    {
        // El bug especifico que arreglamos: updateDia serializaba UserRutina
        // sin cargar la relacion rutina, y los accessors nivel/modalidad
        // tiraban 500.
        $this->actingAs($this->user);
        $response = $this->postJson('/api/user-rutina/dia', [
            'dia_actual' => 'Día 1 (Torso)',
        ]);
        $this->assertNotEquals(500, $response->status(), "Body: " . $response->getContent());
        $this->assertSame(200, $response->status());
        $response->assertJsonPath('nivel', 'Intermedio');
        $response->assertJsonPath('modalidad', '3 Días');
    }

    public function test_smoke_user_rutina_show_no_500(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson('/api/user-rutina');
        $this->assertNotEquals(500, $response->status(), "Body: " . $response->getContent());
        $this->assertSame(200, $response->status());
        $response->assertJsonPath('nivel', 'Intermedio');
        $response->assertJsonPath('modalidad', '3 Días');
    }

    public function test_smoke_rutinas_con_nivel_filtro(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson('/api/rutinas', ['nivel' => 'Intermedio']);
        $this->assertNotEquals(500, $response->status());
        $this->assertSame(200, $response->status());
    }

    public function test_smoke_body_map_muscle_ejercicios(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson('/api/body-map/muscle/pectoral_major/exercises');
        $this->assertNotEquals(500, $response->status(), "Body: " . $response->getContent());
    }
}
