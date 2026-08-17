<?php

namespace Tests\Feature;

use App\Models\Rutina;
use App\Models\Historial;
use App\Models\User;
use App\Models\UserRutina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperserieTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_routine_with_superserie_grupo(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/rutinas', [
                'nivel' => 'Personalizada',
                'modalidad' => 'Mi Rutina Combinada',
                'dia' => 'Día 1',
                'ejercicio_nombre' => 'Prensa de Piernas',
                'series' => 3,
                'reps_min' => '10',
                'reps_max' => '12',
                'descanso_min' => 1.5,
                'orden' => 1,
                'superserie_grupo' => 1,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('rutinas', [
            'ejercicio_nombre' => 'Prensa de Piernas',
            'superserie_grupo' => 1,
            'created_by' => $user->id,
        ]);
    }

    public function test_user_can_save_workout_set_with_superserie_grupo(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/historial/guardar', [
                'rutina_nombre' => 'Personalizada Mi Rutina Combinada',
                'dia' => 'Día 1',
                'ejercicio_nombre' => 'Prensa de Piernas',
                'series_numero' => 1,
                'series_completadas' => 1,
                'reps_min' => '10',
                'reps_max' => '12',
                'reps_realizadas' => 10,
                'descanso_min' => 1.5,
                'peso' => 120,
                'completado' => true,
                'superserie_grupo' => 1,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('historials', [
            'user_id' => $user->id,
            'ejercicio_nombre' => 'Prensa de Piernas',
            'series_numero' => 1,
            'superserie_grupo' => 1,
            'completado' => true,
        ]);
    }

    public function test_finalizar_rutina_copies_superserie_grupo(): void
    {
        $user = User::factory()->create();

        // D1: nivel/modalidad viven en la relación `rutina`, no como columnas
        // denormalizadas. Creamos primero la Rutina y después el UserRutina
        // apuntando a ella con `rutina_id`.

        // Create routine exercises in db
        $rutina1 = Rutina::create([
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Curl de Biceps',
            'series' => 2,
            'reps_min' => '8',
            'reps_max' => '12',
            'descanso_min' => 1.0,
            'orden' => 1,
            'superserie_grupo' => 2,
            'created_by' => $user->id,
        ]);

        // Create user routine selection (D1: via rutina_id, no denormalización)
        UserRutina::create([
            'user_id' => $user->id,
            'rutina_id' => $rutina1->id,
            'dia_actual' => 'Día 1',
            'assigned_by' => null,
        ]);

        // Finalize routine via API
        $response = $this->actingAs($user)
            ->postJson('/api/historial/finalizar-rutina', [
                'nivel' => 'Personalizada',
                'modalidad' => 'Mi Rutina',
            ]);

        $response->assertStatus(200);

        // Verify history logs have been created with correct superserie_grupo
        $this->assertDatabaseHas('historials', [
            'user_id' => $user->id,
            'rutina_nombre' => 'Personalizada Mi Rutina',
            'ejercicio_nombre' => 'Curl de Biceps',
            'series_numero' => 1,
            'superserie_grupo' => 2,
            'completado' => true,
        ]);

        $this->assertDatabaseHas('historials', [
            'user_id' => $user->id,
            'rutina_nombre' => 'Personalizada Mi Rutina',
            'ejercicio_nombre' => 'Curl de Biceps',
            'series_numero' => 2,
            'superserie_grupo' => 2,
            'completado' => true,
        ]);
    }
}
