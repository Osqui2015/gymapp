<?php

namespace Tests\Feature;

use App\Models\BienestarDiario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BienestarDiarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_empty_bienestar_for_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/bienestar?fecha=2026-09-06');

        $response->assertStatus(200)
            ->assertJson([
                'fecha' => '2026-09-06',
                'horas_sueno' => null,
                'calidad_sueno' => null,
                'nivel_estres' => null,
                'dolor_muscular' => null,
            ]);
    }

    public function test_user_can_save_and_update_bienestar_for_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/bienestar', [
            'fecha' => '2026-09-06',
            'horas_sueno' => 7.5,
            'calidad_sueno' => 4,
            'nivel_estres' => 2,
            'dolor_muscular' => 3,
            'notas' => 'Sensación de energía moderada',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('bienestar_diario', [
            'user_id' => $user->id,
            'horas_sueno' => 7.5,
            'calidad_sueno' => 4,
            'nivel_estres' => 2,
            'dolor_muscular' => 3,
            'notas' => 'Sensación de energía moderada',
        ]);
        $record = BienestarDiario::where('user_id', $user->id)->first();
        $this->assertEquals('2026-09-06', $record->fecha->toDateString());

        // Actualizar el mismo día debe modificar el registro existente
        $updateResponse = $this->actingAs($user)->postJson('/api/bienestar', [
            'fecha' => '2026-09-06',
            'horas_sueno' => 8.0,
            'calidad_sueno' => 5,
        ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('bienestar_diario', [
            'user_id' => $user->id,
            'horas_sueno' => 8.0,
            'calidad_sueno' => 5,
        ]);
        $this->assertEquals(1, BienestarDiario::where('user_id', $user->id)->count());
    }

    public function test_bienestar_validates_ranges(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/bienestar', [
            'fecha' => '2026-09-06',
            'horas_sueno' => 30, // max 24
            'calidad_sueno' => 10, // max 5
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['horas_sueno', 'calidad_sueno']);
    }
}
