<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutinaTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogo_comunitario_incluye_rutinas_del_sistema(): void
    {
        // Rutina del sistema (created_by=null) con publica=true debe aparecer
        // en el catalogo aunque el user logueado sea admin.
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $this->actingAs($admin);

        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 3',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Press banca', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);

        $response = $this->getJson('/api/rutinas?comunitarias=1');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertSame('Press banca', $response->json('0.ejercicio_nombre'));
    }

    public function test_catalogo_comunitario_excluye_rutinas_propias_del_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Rutina del user (no del sistema): NO debe aparecer en el catalogo
        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 1',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Press banca', 'orden' => 1,
            'publica' => true, 'created_by' => $user->id,
        ]);
        // Rutina del sistema: SI debe aparecer
        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 2',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Sentadilla', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);

        $response = $this->getJson('/api/rutinas?comunitarias=1');
        $this->assertCount(1, $response->json());
        $this->assertSame('Sentadilla', $response->json('0.ejercicio_nombre'));
    }

    public function test_catalogo_comunitario_excluye_rutinas_no_publicas(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Privada: NO debe aparecer
        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 1',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Press banca', 'orden' => 1,
            'publica' => false, 'created_by' => null,
        ]);
        // Publica del sistema: SI debe aparecer
        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 2',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Sentadilla', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);

        $response = $this->getJson('/api/rutinas?comunitarias=1');
        $this->assertCount(1, $response->json());
        $this->assertSame('Sentadilla', $response->json('0.ejercicio_nombre'));
    }
}
