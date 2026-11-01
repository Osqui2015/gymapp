<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Fase 4 — Estimación de 1RM a lo largo del tiempo.
 */
class StatsOneRmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function makeSet($user, $ej, $num, $peso, $reps, $fecha = null, $completado = true)
    {
        return Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Rutina A',
            'dia' => 'Día 1',
            'ejercicio_nombre' => $ej,
            'series_numero' => $num,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => $peso, 'reps_realizadas' => $reps,
            'completado' => $completado,
            'fecha' => $fecha ?? now()->toDateString(),
        ]);
    }

    public function test_estimated_1rm_requiere_ejercicio_nombre(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/stats/estimated-1rm');
        $response->assertStatus(422);
    }

    public function test_estimated_1rm_calcula_epley(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        // 100kg x 5 reps → Epley: 100 * (1 + 5/30) = 116.67
        $this->makeSet($user, 'Press banca', 1, 100, 5);

        $response = $this->actingAs($user)->getJson('/api/stats/estimated-1rm?ejercicio_nombre=Press%20banca');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('Press banca', $data['ejercicio']);
        $this->assertEquals('epley', $data['formula']);
        $this->assertEquals(1, $data['total_sets']);
        $this->assertEqualsWithDelta(116.7, $data['best_1rm']['value'], 0.1);
        $this->assertEqualsWithDelta(116.7, $data['estimated_1rm']['estimated_1rm'], 0.1);
        $this->assertCount(1, $data['timeline']);
    }

    public function test_estimated_1rm_calcula_lander(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        // 100kg x 5 reps → Lander: 10000/(101.3 - 2.6712*5) = 113.7
        $this->makeSet($user, 'Press banca', 1, 100, 5);

        $response = $this->actingAs($user)->getJson('/api/stats/estimated-1rm?ejercicio_nombre=Press%20banca&formula=lander');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('lander', $data['formula']);
        $this->assertEqualsWithDelta(113.7, $data['best_1rm']['value'], 0.2);
    }

    public function test_estimated_1rm_detecta_prs_y_mejor_peso(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        // 3 sets, el último es el PR
        $this->makeSet($user, 'Sentadilla', 1, 80, 8, now()->subDays(10)->toDateString());   // 101.3
        $this->makeSet($user, 'Sentadilla', 1, 90, 5, now()->subDays(5)->toDateString());    // 105.0
        $this->makeSet($user, 'Sentadilla', 1, 100, 3, now()->subDays(1)->toDateString());   // 110.0

        $response = $this->actingAs($user)->getJson('/api/stats/estimated-1rm?ejercicio_nombre=Sentadilla');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(3, $data['total_sets']);
        $this->assertEquals(100, $data['best_1rm']['weight']);
        $this->assertEquals(3, $data['best_1rm']['reps']);
        $this->assertEquals(2, $data['pr_count']); // 2 PRs batidos
    }

    public function test_estimated_1rm_ignora_sets_sin_peso_o_reps(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->makeSet($user, 'Press', 1, 100, 5);
        $this->makeSet($user, 'Press', 2, null, 5);
        $this->makeSet($user, 'Press', 3, 80, null);

        $response = $this->actingAs($user)->getJson('/api/stats/estimated-1rm?ejercicio_nombre=Press');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['total_sets']);
    }

    public function test_estimated_1rm_ignora_no_completados(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->makeSet($user, 'Press', 1, 100, 5, completado: false);
        $this->makeSet($user, 'Press', 2, 100, 5, completado: true);

        $response = $this->actingAs($user)->getJson('/api/stats/estimated-1rm?ejercicio_nombre=Press');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['total_sets']);
    }

    public function test_estimated_1rm_respeta_ventana_months(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $this->makeSet($user, 'Curl', 1, 30, 10, now()->subMonths(8)->toDateString());
        $this->makeSet($user, 'Curl', 1, 40, 8, now()->subMonths(2)->toDateString());

        $response = $this->actingAs($user)->getJson('/api/stats/estimated-1rm?ejercicio_nombre=Curl&months=3');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['total_sets']);
    }

    public function test_estimated_1rm_sin_data_devuelve_null_best(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/stats/estimated-1rm?ejercicio_nombre=NoExiste');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['total_sets']);
        $this->assertNull($data['best_1rm']);
    }

    public function test_estimated_1rm_requiere_auth(): void
    {
        $response = $this->getJson('/api/stats/estimated-1rm?ejercicio_nombre=test');
        $response->assertStatus(401);
    }
}
