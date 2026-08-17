<?php

namespace Tests\Feature\Api;

use App\Models\Ejercicio;
use App\Models\Historial;
use App\Models\Rutina;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutinasSugeridasTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_devuelve_sugeridas_y_perfil(): void
    {
        $user = User::factory()->create();
        Rutina::factory()->create(['nivel' => 'Principiante', 'modalidad' => 'Full body']);

        $response = $this->actingAs($user)
            ->getJson('/api/rutinas/sugeridas');

        $response->assertOk();
        $response->assertJsonStructure([
            'sugeridas',
            'perfil' => [
                'top_grupos', 'dias_por_mes', 'nivel_estimado', 'tiene_historial',
            ],
        ]);
    }

    public function test_endpoint_respeta_el_limit_param(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 10; $i++) {
            Rutina::factory()->create(['nivel' => 'Principiante', 'modalidad' => "Tipo $i"]);
        }

        $response = $this->actingAs($user)->getJson('/api/rutinas/sugeridas?limit=3');
        $this->assertLessThanOrEqual(3, count($response->json('sugeridas')));
    }

    public function test_endpoint_limita_max_10(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/rutinas/sugeridas?limit=999');
        $response->assertOk();
        $this->assertLessThanOrEqual(10, count($response->json('sugeridas')));
    }

    public function test_user_sin_historial_recibe_recomendaciones(): void
    {
        $user = User::factory()->create();
        Rutina::factory()->create(['nivel' => 'Principiante', 'modalidad' => 'Full body']);
        Rutina::factory()->create(['nivel' => 'Avanzado', 'modalidad' => 'Push']);

        $response = $this->actingAs($user)->getJson('/api/rutinas/sugeridas');
        $response->assertOk();
        $this->assertTrue($response->json('perfil.tiene_historial') === false);
        // Debe haber sugerencias
        $this->assertGreaterThan(0, count($response->json('sugeridas')));
    }

    public function test_user_no_autenticado_no_puede_acceder(): void
    {
        $this->getJson('/api/rutinas/sugeridas')->assertStatus(401);
    }

    public function test_cada_sugerencia_tiene_campos_esperados(): void
    {
        $user = User::factory()->create();
        Rutina::factory()->create(['nivel' => 'Principiante', 'modalidad' => 'Full body']);

        $response = $this->actingAs($user)->getJson('/api/rutinas/sugeridas');
        $sugerencia = $response->json('sugeridas.0');

        $this->assertArrayHasKey('id', $sugerencia);
        $this->assertArrayHasKey('nivel', $sugerencia);
        $this->assertArrayHasKey('modalidad', $sugerencia);
        $this->assertArrayHasKey('ejercicios_count', $sugerencia);
        $this->assertArrayHasKey('grupos_cubiertos', $sugerencia);
        $this->assertArrayHasKey('score', $sugerencia);
        $this->assertArrayHasKey('razones', $sugerencia);
    }
}
