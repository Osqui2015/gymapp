<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Fase 6 — Dashboard today (HomeHero data).
 */
class DashboardTodayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function makeUserWithRutina($dia = 'Día 1')
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        Rutina::create([
            'nivel' => 'Principiante', 'modalidad' => 'Hipertrofia', 'dia' => $dia,
            'ejercicio_nombre' => 'Press banca', 'series' => 3,
            'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 1.5, 'orden' => 1,
        ]);
        $rutina = Rutina::where('nivel', 'Principiante')->where('modalidad', 'Hipertrofia')->where('dia', $dia)->first();
        UserRutina::create([
            'user_id' => $user->id,
            'rutina_id' => $rutina->id,
            'dia_actual' => $dia,
        ]);
        return [$user, $rutina];
    }

    public function test_dashboard_today_sin_rutina_devuelve_null(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/today');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNull($data['rutina']);
        $this->assertEquals('nueva_rutina', $data['quick']);
        $this->assertNotNull($data['hoy']['saludo']);
    }

    public function test_dashboard_today_con_rutina_y_sin_historial(): void
    {
        [$user] = $this->makeUserWithRutina('Día 1');

        $response = $this->actingAs($user)->getJson('/api/dashboard/today');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotNull($data['rutina']);
        $this->assertEquals('Día 1', $data['rutina']['dia_actual']);
        $this->assertEquals('Principiante Hipertrofia', $data['rutina']['nombre']);
        $this->assertEquals('empezar', $data['quick']);
        $this->assertEquals(0, $data['stats']['total_sets_30d']);
    }

    public function test_dashboard_today_con_historial_hoy_sugiere_continuar(): void
    {
        [$user] = $this->makeUserWithRutina();
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Principiante Hipertrofia',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => 60, 'completado' => true,
            'fecha' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/today');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('continuar', $data['quick'], 'quick was: ' . json_encode($data['quick']) . ' days: ' . $data['stats']['days_since_last_workout']);
        $this->assertEquals(0, $data['stats']['days_since_last_workout']);
        $this->assertEquals(1, $data['stats']['total_sets_30d']);
    }

    public function test_dashboard_today_con_racha(): void
    {
        [$user] = $this->makeUserWithRutina();
        // 3 días seguidos
        for ($i = 0; $i < 3; $i++) {
            Historial::create([
                'user_id' => $user->id,
                'rutina_nombre' => 'Principiante Hipertrofia',
                'dia' => 'Día 1',
                'ejercicio_nombre' => 'Press banca',
                'series_numero' => 1,
                'reps_min' => '8', 'reps_max' => '12',
                'descanso_min' => 2, 'peso' => 60, 'completado' => true,
                'fecha' => now()->subDays($i)->toDateString(),
            ]);
        }

        $response = $this->actingAs($user)->getJson('/api/dashboard/today');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertGreaterThanOrEqual(3, $data['stats']['streak']);
    }

    public function test_dashboard_today_requiere_auth(): void
    {
        $response = $this->getJson('/api/dashboard/today');
        $response->assertStatus(401);
    }
}
