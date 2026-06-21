<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_sees_only_own_alumnos(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $otherTrainer = User::factory()->create(['role' => User::ROLE_TRAINER]);

        $alumnoPropio = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $alumnoAjeno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $otherTrainer->id,
        ]);

        $response = $this->actingAs($trainer)->getJson('/api/trainer/dashboard');
        $response->assertStatus(200)
            ->assertJsonPath('total_alumnos', 1)
            ->assertJsonPath('alumnos.0.id', $alumnoPropio->id);
    }

    public function test_comun_cannot_access_trainer_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/trainer/dashboard');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_any_trainer_dashboard(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

        $response = $this->actingAs($admin)->getJson('/api/trainer/dashboard');
        $response->assertStatus(200);
    }

    public function test_inactive_alumnos_are_detected(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        // Alumno entrenó hace 10 días (inactivo)
        Historial::create([
            'user_id' => $alumno->id,
            'rutina_nombre' => 'Test',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Sentadilla',
            'series_numero' => 1,
            'reps_min' => '5',
            'reps_max' => '10',
            'descanso_min' => 1,
            'fecha' => now()->subDays(10)->toDateString(),
            'completado' => true,
        ]);

        $response = $this->actingAs($trainer)->getJson('/api/trainer/dashboard');
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('alumnos_inactivos_7dias')));
    }
}
