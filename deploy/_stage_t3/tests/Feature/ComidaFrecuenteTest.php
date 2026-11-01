<?php

namespace Tests\Feature;

use App\Models\ComidaFrecuente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComidaFrecuenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_own_comidas_frecuentes(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        ComidaFrecuente::create([
            'user_id' => $user1->id,
            'nombre' => 'Avena con scoop',
            'calorias' => 380,
            'proteinas' => 32,
            'carbohidratos' => 50,
            'grasas' => 6,
        ]);

        ComidaFrecuente::create([
            'user_id' => $user2->id,
            'nombre' => 'Pollo con arroz',
            'calorias' => 500,
            'proteinas' => 45,
            'carbohidratos' => 60,
            'grasas' => 8,
        ]);

        $response = $this->actingAs($user1)->getJson('/api/comidas-frecuentes');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['nombre' => 'Avena con scoop'])
            ->assertJsonMissing(['nombre' => 'Pollo con arroz']);
    }

    public function test_user_can_create_comida_frecuente(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/comidas-frecuentes', [
            'nombre' => 'Tortilla de claras',
            'porcion' => '4 claras + 1 yema',
            'calorias' => 180,
            'proteinas' => 22,
            'carbohidratos' => 2,
            'grasas' => 5,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comidas_frecuentes', [
            'user_id' => $user->id,
            'nombre' => 'Tortilla de claras',
            'calorias' => 180,
        ]);
    }

    public function test_user_can_delete_own_comida_frecuente(): void
    {
        $user = User::factory()->create();
        $comida = ComidaFrecuente::create([
            'user_id' => $user->id,
            'nombre' => 'Batido post entreno',
            'calorias' => 250,
            'proteinas' => 30,
            'carbohidratos' => 25,
            'grasas' => 3,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/comidas-frecuentes/{$comida->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comidas_frecuentes', [
            'id' => $comida->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_comida_frecuente(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $comida = ComidaFrecuente::create([
            'user_id' => $user2->id,
            'nombre' => 'Batido de otro user',
            'calorias' => 250,
            'proteinas' => 30,
            'carbohidratos' => 25,
            'grasas' => 3,
        ]);

        $response = $this->actingAs($user1)->deleteJson("/api/comidas-frecuentes/{$comida->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('comidas_frecuentes', [
            'id' => $comida->id,
        ]);
    }
}
