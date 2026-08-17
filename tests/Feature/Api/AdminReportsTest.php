<?php

namespace Tests\Feature\Api;

use App\Models\Historial;
use App\Models\Membresia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $this->alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
    }

    public function test_admin_can_access_reportes(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/reportes');
        $response->assertOk();
        $response->assertJsonStructure([
            'retencion' => [
                'mes_actual', 'mes_anterior', 'activos_mes_pasado',
                'activos_mes_actual', 'retenidos', 'churned', 'nuevos',
                'tasa_retencion', 'tasa_churn',
            ],
            'churn' => ['en_riesgo', 'total_activos', 'tasa'],
            'frecuencia' => ['promedio_dias_por_mes', 'distribucion'],
            'top_alumnos',
        ]);
    }

    public function test_non_admin_cannot_access_reportes(): void
    {
        $this->actingAs($this->alumno)
            ->getJson('/api/admin/reportes')
            ->assertStatus(403);
    }

    public function test_retencion_calculates_correctly(): void
    {
        // Alumno que entrenó el mes pasado Y este mes → retenido
        Membresia::create([
            'user_id' => $this->alumno->id,
            'tipo_plan' => 'mensual',
            'precio' => 1000,
            'fecha_inicio' => Carbon::now()->subMonths(2),
            'fecha_vencimiento' => Carbon::now()->addMonths(1),
            'estado' => 'activo',
        ]);
        Historial::create([
            'user_id' => $this->alumno->id,
            'fecha' => Carbon::now()->subDays(45)->toDateString(),
            'ejercicio_nombre' => 'A', 'rutina_nombre' => 'R', 'dia' => 'D1',
            'series_numero' => 1, 'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 1,
        ]);
        Historial::create([
            'user_id' => $this->alumno->id,
            'fecha' => Carbon::now()->subDays(5)->toDateString(),
            'ejercicio_nombre' => 'B', 'rutina_nombre' => 'R', 'dia' => 'D1',
            'series_numero' => 1, 'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 1,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/reportes');
        $response->assertOk();
        $this->assertEquals(1, $response->json('retencion.retenidos'));
    }

    public function test_churn_detects_users_at_risk(): void
    {
        Membresia::create([
            'user_id' => $this->alumno->id,
            'tipo_plan' => 'mensual',
            'precio' => 1000,
            'fecha_inicio' => Carbon::now()->subMonths(1),
            'fecha_vencimiento' => Carbon::now()->addMonths(1),
            'estado' => 'activo',
        ]);
        // Entrenó hace 20 días (entre 14 y 30), no en los últimos 14 días
        Historial::create([
            'user_id' => $this->alumno->id,
            'fecha' => Carbon::now()->subDays(20)->toDateString(),
            'ejercicio_nombre' => 'A', 'rutina_nombre' => 'R', 'dia' => 'D1',
            'series_numero' => 1, 'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 1,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/reportes');
        $this->assertEquals(1, $response->json('churn.en_riesgo'));
    }

    public function test_top_alumnos_ordered_by_sessions(): void
    {
        $u2 = User::factory()->create();
        $u3 = User::factory()->create();

        // alumno entrenó 5 días
        for ($i = 0; $i < 5; $i++) {
            Historial::create([
                'user_id' => $this->alumno->id,
                'fecha' => Carbon::now()->subDays($i)->toDateString(),
                'ejercicio_nombre' => 'A', 'rutina_nombre' => 'R', 'dia' => 'D1',
                'series_numero' => 1, 'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 1,
            ]);
        }
        // u2 entrenó 3 días
        for ($i = 0; $i < 3; $i++) {
            Historial::create([
                'user_id' => $u2->id,
                'fecha' => Carbon::now()->subDays($i)->toDateString(),
                'ejercicio_nombre' => 'A', 'rutina_nombre' => 'R', 'dia' => 'D1',
                'series_numero' => 1, 'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 1,
            ]);
        }
        // u3 entrenó 1 día
        Historial::create([
            'user_id' => $u3->id,
            'fecha' => Carbon::now()->subDays(1)->toDateString(),
            'ejercicio_nombre' => 'A', 'rutina_nombre' => 'R', 'dia' => 'D1',
            'series_numero' => 1, 'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 1,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/reportes');
        $top = $response->json('top_alumnos');
        $this->assertEquals($this->alumno->id, $top[0]['id']);
        $this->assertEquals(5, $top[0]['dias_entrenados']);
    }
}
