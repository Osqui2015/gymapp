<?php

namespace Tests\Feature;

use App\Models\EjercicioClave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EjercicioClaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_key_exercises(): void
    {
        $response = $this->getJson('/api/ejercicios-clave');
        $response->assertStatus(401);
    }

    public function test_alumno_can_view_their_own_key_exercises(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        $ejercicio = EjercicioClave::create([
            'user_id' => $alumno->id,
            'trainer_id' => $trainer->id,
            'ejercicio_nombre' => 'Sentadilla Libre',
            'notas_trainer' => 'Foco en romper el paralelo',
        ]);

        $response = $this->actingAs($alumno)
            ->getJson('/api/ejercicios-clave');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'ejercicio_nombre' => 'Sentadilla Libre',
                'notas_trainer' => 'Foco en romper el paralelo',
            ]);
    }

    public function test_alumno_cannot_view_others_key_exercises(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno1 = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $alumno2 = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        $response = $this->actingAs($alumno1)
            ->getJson("/api/ejercicios-clave?user_id={$alumno2->id}");

        $response->assertStatus(403);
    }

    public function test_alumno_cannot_create_key_exercise(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        $response = $this->actingAs($alumno)
            ->postJson('/api/ejercicios-clave', [
                'user_id' => $alumno->id,
                'ejercicio_nombre' => 'Sentadilla Libre',
                'notas_trainer' => 'Foco en romper el paralelo',
            ]);

        $response->assertStatus(403);
    }

    public function test_trainer_can_view_their_assigned_alumnos_key_exercises(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        $ejercicio = EjercicioClave::create([
            'user_id' => $alumno->id,
            'trainer_id' => $trainer->id,
            'ejercicio_nombre' => 'Sentadilla Libre',
            'notas_trainer' => 'Foco en romper el paralelo',
        ]);

        $response = $this->actingAs($trainer)
            ->getJson("/api/ejercicios-clave?user_id={$alumno->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'ejercicio_nombre' => 'Sentadilla Libre',
            ]);
    }

    public function test_trainer_cannot_view_unassigned_alumnos_key_exercises(): void
    {
        $trainer1 = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $trainer2 = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer1->id,
        ]);

        $response = $this->actingAs($trainer2)
            ->getJson("/api/ejercicios-clave?user_id={$alumno->id}");

        $response->assertStatus(403);
    }

    public function test_trainer_can_create_and_update_key_exercises_for_assigned_alumno(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        // Create
        $response = $this->actingAs($trainer)
            ->postJson('/api/ejercicios-clave', [
                'user_id' => $alumno->id,
                'ejercicio_nombre' => 'Sentadilla Libre',
                'notas_trainer' => 'Foco en romper el paralelo',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'ejercicio_nombre' => 'Sentadilla Libre',
                'notas_trainer' => 'Foco en romper el paralelo',
            ]);

        $this->assertDatabaseHas('ejercicios_clave', [
            'user_id' => $alumno->id,
            'ejercicio_nombre' => 'Sentadilla Libre',
            'notas_trainer' => 'Foco en romper el paralelo',
        ]);

        // Update
        $responseUpdate = $this->actingAs($trainer)
            ->postJson('/api/ejercicios-clave', [
                'user_id' => $alumno->id,
                'ejercicio_nombre' => 'Sentadilla Libre',
                'notas_trainer' => 'Foco en espalda recta',
            ]);

        $responseUpdate->assertStatus(200);
        $this->assertDatabaseHas('ejercicios_clave', [
            'user_id' => $alumno->id,
            'ejercicio_nombre' => 'Sentadilla Libre',
            'notas_trainer' => 'Foco en espalda recta',
        ]);
    }

    public function test_trainer_cannot_create_key_exercises_for_unassigned_alumno(): void
    {
        $trainer1 = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $trainer2 = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer1->id,
        ]);

        $response = $this->actingAs($trainer2)
            ->postJson('/api/ejercicios-clave', [
                'user_id' => $alumno->id,
                'ejercicio_nombre' => 'Sentadilla Libre',
                'notas_trainer' => 'Foco en romper el paralelo',
            ]);

        $response->assertStatus(403);
    }

    public function test_trainer_can_delete_key_exercise_for_assigned_alumno(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        $ejercicio = EjercicioClave::create([
            'user_id' => $alumno->id,
            'trainer_id' => $trainer->id,
            'ejercicio_nombre' => 'Sentadilla Libre',
            'notas_trainer' => 'Foco en romper el paralelo',
        ]);

        $response = $this->actingAs($trainer)
            ->deleteJson("/api/ejercicios-clave/{$ejercicio->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('ejercicios_clave', [
            'id' => $ejercicio->id,
        ]);
    }

    public function test_trainer_cannot_delete_key_exercise_for_unassigned_alumno(): void
    {
        $trainer1 = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $trainer2 = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer1->id,
        ]);

        $ejercicio = EjercicioClave::create([
            'user_id' => $alumno->id,
            'trainer_id' => $trainer1->id,
            'ejercicio_nombre' => 'Sentadilla Libre',
            'notas_trainer' => 'Foco en romper el paralelo',
        ]);

        $response = $this->actingAs($trainer2)
            ->deleteJson("/api/ejercicios-clave/{$ejercicio->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('ejercicios_clave', [
            'id' => $ejercicio->id,
        ]);
    }
}
