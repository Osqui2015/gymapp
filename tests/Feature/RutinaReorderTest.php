<?php

namespace Tests\Feature;

use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutinaReorderTest extends TestCase
{
    use RefreshDatabase;

    private function setupRutina(User $creador, string $nivel = 'Personalizada', string $modalidad = 'Mi Rutina', string $dia = 'Día 1'): array
    {
        $items = [];
        for ($i = 0; $i < 3; $i++) {
            $items[] = Rutina::create([
                'nivel' => $nivel,
                'modalidad' => $modalidad,
                'dia' => $dia,
                'ejercicio_nombre' => 'Ejercicio '.($i + 1),
                'series' => 3,
                'reps_min' => '8',
                'reps_max' => '10',
                'descanso_min' => 1.5,
                'orden' => $i,
                'created_by' => $creador->id,
            ]);
        }

        return $items;
    }

    public function test_owner_can_reorder_own_ejercicios(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $items = $this->setupRutina($user);

        // Revertir el orden: [items[2], items[1], items[0]]
        $response = $this->actingAs($user)->postJson('/api/rutinas/reorder', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 1',
            'items' => [
                ['id' => $items[2]->id, 'orden' => 0],
                ['id' => $items[1]->id, 'orden' => 1],
                ['id' => $items[0]->id, 'orden' => 2],
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('rutinas', ['id' => $items[2]->id, 'orden' => 0]);
        $this->assertDatabaseHas('rutinas', ['id' => $items[1]->id, 'orden' => 1]);
        $this->assertDatabaseHas('rutinas', ['id' => $items[0]->id, 'orden' => 2]);
    }

    public function test_other_user_cannot_reorder(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $items = $this->setupRutina($owner);

        $response = $this->actingAs($other)->postJson('/api/rutinas/reorder', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 1',
            'items' => [['id' => $items[0]->id, 'orden' => 99]],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('rutinas', ['id' => $items[0]->id, 'orden' => 0]);
    }

    public function test_admin_can_reorder_any_rutina(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $items = $this->setupRutina($user);

        $response = $this->actingAs($admin)->postJson('/api/rutinas/reorder', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 1',
            'items' => [
                ['id' => $items[2]->id, 'orden' => 0],
                ['id' => $items[0]->id, 'orden' => 1],
                ['id' => $items[1]->id, 'orden' => 2],
            ],
        ]);

        $response->assertOk();
    }

    public function test_rejects_items_from_different_dia(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $items = $this->setupRutina($user, 'Personalizada', 'Mi Rutina', 'Día 1');
        $otherDia = Rutina::create([
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 2',
            'ejercicio_nombre' => 'Otro',
            'series' => 3,
            'reps_min' => '8',
            'reps_max' => '10',
            'descanso_min' => 1.5,
            'orden' => 0,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/rutinas/reorder', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 1',
            'items' => [
                ['id' => $items[0]->id, 'orden' => 1],
                ['id' => $otherDia->id, 'orden' => 2], // no pertenece a Día 1
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_validates_items_array(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        $this->actingAs($user)->postJson('/api/rutinas/reorder', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 1',
            'items' => [], // vacío
        ])->assertStatus(422);

        $this->actingAs($user)->postJson('/api/rutinas/reorder', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 1',
            'items' => [['id' => 99999, 'orden' => 0]], // id inexistente
        ])->assertStatus(422);
    }
}
