<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WeekSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function makeSet($user, $fecha, $ejercicio = 'Press banca', $peso = 60, $reps = 8)
    {
        return Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Rutina A',
            'dia' => 'Día 1',
            'ejercicio_nombre' => $ejercicio,
            'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12',
            'descanso_min' => 2, 'peso' => $peso, 'reps_realizadas' => $reps,
            'completado' => true,
            'fecha' => $fecha,
        ]);
    }

    public function test_week_summary_devuelve_7_dias(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/historial/week-summary');
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(7, $data['days']);
        $this->assertArrayHasKey('week_start', $data);
        $this->assertArrayHasKey('week_end', $data);
        $this->assertArrayHasKey('totals', $data);
    }

    public function test_week_summary_calcula_totales(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $lunes = now()->startOfWeek(Carbon::MONDAY);
        $miercoles = $lunes->copy()->addDays(2);

        $this->makeSet($user, $lunes->toDateString(), 'Press banca', 60, 8);
        $this->makeSet($user, $lunes->toDateString(), 'Press banca', 60, 8);
        $this->makeSet($user, $miercoles->toDateString(), 'Sentadilla', 80, 5);

        // Run controller-like query directly
        $weekStart = now()->startOfWeek(\Carbon\Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
        $direct = \App\Models\Historial::where('user_id', $user->id)
            ->where('completado', true)
            ->whereDate('fecha', '>=', $weekStart->toDateString())
            ->whereDate('fecha', '<=', $weekEnd->toDateString())
            ->selectRaw('fecha as fecha_raw, DATE(fecha) as fecha, COUNT(*) as sets, SUM(peso * COALESCE(reps_realizadas, 0)) as volumen')
            ->groupBy('fecha_raw', 'fecha')
            ->orderBy('fecha_raw')
            ->get();
        fwrite(STDERR, "\n[DEBUG] direct_sql_count=" . $direct->count() . " rows=" . $direct->toJson() . "\n");

        $response = $this->actingAs($user)->getJson('/api/historial/week-summary');
        $data = $response->json();
        $this->assertEquals(3, $data['totals']['sets']);
        $this->assertEquals(2, $data['totals']['dias_entrenados']);
        $this->assertEquals(5, $data['totals']['dias_descanso']);
    }

    public function test_week_summary_ignora_historial_fuera_de_semana(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $lunes = now()->startOfWeek(Carbon::MONDAY);
        // Set del lunes pasado (fuera de esta semana)
        $this->makeSet($user, $lunes->copy()->subDays(7)->toDateString());

        $response = $this->actingAs($user)->getJson('/api/historial/week-summary');
        $data = $response->json();
        $this->assertEquals(0, $data['totals']['sets']);
    }

    public function test_week_summary_acepta_week_start_custom(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $fechaCustom = '2026-01-05'; // lunes
        $this->makeSet($user, '2026-01-05', 'A');
        $this->makeSet($user, '2026-01-11', 'B'); // domingo

        $response = $this->actingAs($user)->getJson("/api/historial/week-summary?week_start={$fechaCustom}");
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('2026-01-05', $data['week_start']);
        $this->assertEquals('2026-01-11', $data['week_end']);
        $this->assertEquals(2, $data['totals']['sets']);
    }

    public function test_week_summary_marca_es_hoy(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $this->makeSet($user, now()->toDateString());

        $response = $this->actingAs($user)->getJson('/api/historial/week-summary');
        $data = $response->json();
        $hoy = collect($data['days'])->firstWhere('es_hoy', true);
        $this->assertNotNull($hoy);
        $this->assertEquals(now()->toDateString(), $hoy['date']);
    }

    public function test_week_summary_requiere_auth(): void
    {
        $response = $this->getJson('/api/historial/week-summary');
        $response->assertStatus(401);
    }
}
