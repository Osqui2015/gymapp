<?php

namespace Tests\Feature;

use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Verifica el comportamiento de "auto-reset al cerrar el ciclo":
 *
 *   - Cuando se finaliza el ÚLTIMO día de la rutina, el sistema setea
 *     `user_rutinas.ciclo_inicio = hoy` y vuelve a Día 1.
 *   - Cuando se finaliza un día intermedio, NO se toca `ciclo_inicio`
 *     (el ciclo todavía no se cerró).
 *
 * Esto permite que el dashboard del próximo ciclo arranque con todo
 * desmarcado, filtrando historial por `fecha >= ciclo_inicio`.
 */
class CicloResetTest extends TestCase
{
    use RefreshDatabase;

    private function seedRutina(User $user): array
    {
        // Rutina de 3 días con 1 ejercicio por día para mantener el test chico.
        $rutinas = [];
        foreach (['Día 1', 'Día 2', 'Día 3'] as $i => $dia) {
            $rutinas[] = Rutina::create([
                'nivel' => 'Personalizada',
                'modalidad' => 'Ciclo Test',
                'dia' => $dia,
                'ejercicio_nombre' => "Ejercicio {$dia}",
                'series' => 2,
                'reps_min' => '8',
                'reps_max' => '12',
                'descanso_min' => 1.0,
                'orden' => $i + 1,
                'created_by' => $user->id,
            ]);
        }

        UserRutina::create([
            'user_id' => $user->id,
            'rutina_id' => $rutinas[0]->id,
            'dia_actual' => 'Día 1',
        ]);

        return $rutinas;
    }

    public function test_finalizar_dia_intermedio_no_setea_ciclo_inicio(): void
    {
        $user = User::factory()->create();
        $this->seedRutina($user);

        // Finalizar Día 1 (NO es el último día)
        $response = $this->actingAs($user)
            ->postJson('/api/historial/finalizar-rutina', [
                'nivel' => 'Personalizada',
                'modalidad' => 'Ciclo Test',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_rutinas', [
            'user_id' => $user->id,
            'dia_actual' => 'Día 2',
            'ciclo_inicio' => null, // aún no se cerró el ciclo
        ]);
    }

    public function test_finalizar_ultimo_dia_setea_ciclo_inicio_y_vuelve_a_dia_1(): void
    {
        Carbon::setTestNow('2026-09-12 18:00:00');

        $user = User::factory()->create();
        $this->seedRutina($user);

        // Forzar que esté en Día 3 (último)
        UserRutina::where('user_id', $user->id)->update(['dia_actual' => 'Día 3']);

        // Finalizar Día 3
        $response = $this->actingAs($user)
            ->postJson('/api/historial/finalizar-rutina', [
                'nivel' => 'Personalizada',
                'modalidad' => 'Ciclo Test',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['dia_actual' => 'Día 1']);

        $this->assertDatabaseHas('user_rutinas', [
            'user_id' => $user->id,
            'dia_actual' => 'Día 1',
            'ciclo_inicio' => '2026-09-12',
        ]);

        Carbon::setTestNow(); // reset
    }
}
