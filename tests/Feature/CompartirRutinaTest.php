<?php

namespace Tests\Feature;

use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test del endpoint POST /api/rutinas/compartir.
 *
 * Regresión: en producción devolvía 500 al compartir la rutina "3 Días V3".
 */
class CompartirRutinaTest extends TestCase
{
    use RefreshDatabase;

    public function test_comparte_una_rutina_personalizada(): void
    {
        $user = User::factory()->create();

        // Un plan real tiene VARIAS filas (una por ejercicio): la V3 tiene 23.
        foreach (['Press de banca', 'Press inclinado', 'Dominadas'] as $i => $nombre) {
            Rutina::create([
                'nivel' => 'Personalizada',
                'modalidad' => '3 Días V3',
                'dia' => 'Día 1 (Torso)',
                'series' => 4,
                'reps_min' => 6,
                'reps_max' => 10,
                'descanso_min' => 2,
                'ejercicio_nombre' => $nombre,
                'orden' => $i + 1,
                'created_by' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)->postJson('/api/rutinas/compartir', [
            'nivel' => 'Personalizada',
            'modalidad' => '3 Días V3',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'new_medals', 'public_url', 'share_token']);

        $rutinas = Rutina::where('created_by', $user->id)->get();
        $this->assertCount(3, $rutinas);
        $this->assertTrue((bool) $rutinas->first()->publica);

        // Todas las filas del plan comparten el mismo token (así se arma la
        // vista pública), y ese token no puede colisionar con otro plan.
        $tokens = $rutinas->pluck('share_token')->unique();
        $this->assertCount(1, $tokens);
        $this->assertNotEmpty($tokens->first());

        // La vista pública reagrupa las filas del plan por token.
        $publica = $this->getJson('/api/rutinas/publica/'.$tokens->first());
        $publica->assertStatus(200);

        $dias = $publica->json('dias');
        $this->assertArrayHasKey('Día 1 (Torso)', $dias);
        $this->assertCount(3, $dias['Día 1 (Torso)']);
        $this->assertSame('Press de banca', $dias['Día 1 (Torso)'][0]['ejercicio_nombre']);
    }

    public function test_cada_plan_compartido_tiene_su_propio_token(): void
    {
        $user = User::factory()->create();

        foreach (['3 Días V3' => 'Press de banca', 'Fuerza Piernas' => 'Sentadilla'] as $modalidad => $nombre) {
            Rutina::create([
                'nivel' => 'Personalizada',
                'modalidad' => $modalidad,
                'dia' => 'Día 1 (Torso)',
                'series' => 4,
                'reps_min' => 6,
                'reps_max' => 10,
                'descanso_min' => 2,
                'ejercicio_nombre' => $nombre,
                'orden' => 1,
                'created_by' => $user->id,
            ]);

            $this->actingAs($user)->postJson('/api/rutinas/compartir', [
                'nivel' => 'Personalizada',
                'modalidad' => $modalidad,
            ])->assertStatus(200);
        }

        $tokens = Rutina::where('created_by', $user->id)->pluck('share_token')->unique();
        $this->assertCount(2, $tokens);
    }
}
