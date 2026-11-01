<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorialEditDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function makeSerie(User $user, array $overrides = []): Historial
    {
        return Historial::create(array_merge([
            'user_id' => $user->id,
            'rutina_nombre' => 'Test',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press Banca',
            'series_numero' => 1,
            'series_completadas' => 1,
            'reps_min' => '8',
            'reps_max' => '10',
            'reps_realizadas' => 10,
            'descanso_min' => 2,
            'peso' => 80,
            'completado' => true,
            'fecha' => now()->toDateString(),
        ], $overrides));
    }

    public function test_owner_can_update_own_serie(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $serie = $this->makeSerie($user, ['peso' => 80]);

        $response = $this->actingAs($user)->putJson("/api/historial/{$serie->id}", [
            'peso' => 85,
            'reps_realizadas' => 8,
            'nota_user' => 'Subí 5kg',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('historials', [
            'id' => $serie->id,
            'peso' => 85,
            'reps_realizadas' => 8,
            'nota_user' => 'Subí 5kg',
        ]);
    }

    public function test_owner_can_delete_own_serie(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $serie = $this->makeSerie($user);

        $response = $this->actingAs($user)->deleteJson("/api/historial/{$serie->id}");

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseMissing('historials', ['id' => $serie->id]);
    }

    public function test_other_user_cannot_update_serie(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $serie = $this->makeSerie($owner);

        $response = $this->actingAs($other)->putJson("/api/historial/{$serie->id}", [
            'peso' => 999,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('historials', ['id' => $serie->id, 'peso' => 80]);
    }

    public function test_other_user_cannot_delete_serie(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $serie = $this->makeSerie($owner);

        $response = $this->actingAs($other)->deleteJson("/api/historial/{$serie->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('historials', ['id' => $serie->id]);
    }

    public function test_assigned_trainer_can_update_alumno_serie(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $serie = $this->makeSerie($alumno);

        $response = $this->actingAs($trainer)->putJson("/api/historial/{$serie->id}", [
            'peso' => 90,
            'comentario_trainer' => 'Buena progresión',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('historials', ['id' => $serie->id, 'peso' => 90]);
    }

    public function test_unassigned_trainer_cannot_update_alumno_serie(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $otherTrainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $otherTrainer->id,
        ]);
        $serie = $this->makeSerie($alumno);

        $response = $this->actingAs($trainer)->putJson("/api/historial/{$serie->id}", [
            'peso' => 999,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_any_serie(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $serie = $this->makeSerie($alumno);

        $response = $this->actingAs($admin)->putJson("/api/historial/{$serie->id}", [
            'peso' => 100,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('historials', ['id' => $serie->id, 'peso' => 100]);
    }

    public function test_validates_peso_range(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $serie = $this->makeSerie($user);

        $response = $this->actingAs($user)->putJson("/api/historial/{$serie->id}", [
            'peso' => 5000, // fuera de rango
        ]);

        $response->assertStatus(422);
    }

    public function test_validates_tipo_serie_enum(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $serie = $this->makeSerie($user);

        $response = $this->actingAs($user)->putJson("/api/historial/{$serie->id}", [
            'tipo_serie' => 'invalido',
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_modify_serie(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $serie = $this->makeSerie($user);

        $this->putJson("/api/historial/{$serie->id}", ['peso' => 1])->assertStatus(401);
        $this->deleteJson("/api/historial/{$serie->id}")->assertStatus(401);
    }
}
