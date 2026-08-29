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

    public function test_importar_rutina_del_sistema_con_created_by_null(): void
    {
        // Rutina del sistema (created_by=null). El import debe aceptar
        // created_by null y matchear por IS NULL.
        $user = User::factory()->create();
        $this->actingAs($user);

        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 3 (Full Body)',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Press banca', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);

        $response = $this->postJson('/api/rutinas/importar', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
            'created_by' => null,
        ]);

        $response->assertStatus(200);
        // Se importo la rutina como copia privada del user
        $copia = Rutina::where('created_by', $user->id)
            ->where('nivel', 'Personalizada')
            ->first();
        $this->assertNotNull($copia);
        $this->assertSame('Press banca', $copia->ejercicio_nombre);
    }

    public function test_importar_rutina_con_created_by_de_otro_user(): void
    {
        $author = User::factory()->create();
        $importer = User::factory()->create();
        $this->actingAs($importer);

        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 1',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Sentadilla', 'orden' => 1,
            'publica' => true, 'created_by' => $author->id,
        ]);

        $response = $this->postJson('/api/rutinas/importar', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
            'created_by' => $author->id,
        ]);

        $response->assertStatus(200);
        $copia = Rutina::where('created_by', $importer->id)->first();
        $this->assertNotNull($copia);
        $this->assertSame('Sentadilla', $copia->ejercicio_nombre);
    }

    public function test_importar_rutina_inexistente_devuelve_404(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/rutinas/importar', [
            'nivel' => 'No existe',
            'modalidad' => 'Tampoco',
            'created_by' => null,
        ]);

        $response->assertStatus(404);
    }

    public function test_importar_solo_un_dia_filtra_por_dia(): void
    {
        // Rutina del sistema con 3 dias. El import con dia=Día 1 debe
        // crear solo los ejercicios de ese dia (no los 3).
        $user = User::factory()->create();
        $this->actingAs($user);

        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 1 (Torso)',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Press banca', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);
        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 1 (Torso)',
            'series' => 3, 'reps_min' => '8', 'reps_max' => '10', 'descanso_min' => 1.0,
            'ejercicio_nombre' => 'Remo', 'orden' => 2,
            'publica' => true, 'created_by' => null,
        ]);
        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 2 (Pierna)',
            'series' => 5, 'reps_min' => '5', 'reps_max' => '5', 'descanso_min' => 2.0,
            'ejercicio_nombre' => 'Sentadilla', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);

        $response = $this->postJson('/api/rutinas/importar', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
            'created_by' => null,
            'dia' => 'Día 1 (Torso)',
        ]);
        $response->assertStatus(200);

        // Solo se importaron los 2 ejercicios de Día 1
        $copias = Rutina::where('created_by', $user->id)->get();
        $this->assertCount(2, $copias);
        $this->assertSame('Press banca', $copias[0]->ejercicio_nombre);
        $this->assertSame('Remo', $copias[1]->ejercicio_nombre);

        // La modalidad distingue el dia en el nombre
        $this->assertSame('3 Días - Día 1 (Torso)', $copias[0]->modalidad);
        $this->assertSame('Día 1 (Torso)', $copias[0]->dia);
    }

    public function test_importar_todos_los_dias_sin_filtro(): void
    {
        // Misma rutina con 3 dias. Sin dia en el request: importa todo.
        $user = User::factory()->create();
        $this->actingAs($user);

        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 1',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Press banca', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);
        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 2',
            'series' => 5, 'reps_min' => '5', 'reps_max' => '5', 'descanso_min' => 2.0,
            'ejercicio_nombre' => 'Sentadilla', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);
        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 3',
            'series' => 3, 'reps_min' => '12', 'reps_max' => '15', 'descanso_min' => 1.0,
            'ejercicio_nombre' => 'Jalón al pecho', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);

        $response = $this->postJson('/api/rutinas/importar', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
            'created_by' => null,
        ]);
        $response->assertStatus(200);

        $copias = Rutina::where('created_by', $user->id)->get();
        $this->assertCount(3, $copias);
        $this->assertSame('3 Días', $copias[0]->modalidad);
    }

    public function test_importar_por_dia_genera_nombres_unicos_entre_dias(): void
    {
        // Importar Día 1 y luego Día 2 por separado: cada uno debe terminar
        // con un nombre distinto (y no chocar con la rutina completa).
        $user = User::factory()->create();
        $this->actingAs($user);

        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 1',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Press banca', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);
        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 2',
            'series' => 5, 'reps_min' => '5', 'reps_max' => '5', 'descanso_min' => 2.0,
            'ejercicio_nombre' => 'Sentadilla', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);

        // Import completo
        $this->postJson('/api/rutinas/importar', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
            'created_by' => null,
        ])->assertStatus(200);

        // Import solo Dia 1
        $this->postJson('/api/rutinas/importar', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
            'created_by' => null,
            'dia' => 'Día 1',
        ])->assertStatus(200);

        $nombresUnicos = Rutina::where('created_by', $user->id)
            ->pluck('modalidad')
            ->unique()
            ->values()
            ->all();
        sort($nombresUnicos);
        $this->assertSame(
            ['3 Días', '3 Días - Día 1'],
            $nombresUnicos
        );
    }

    public function test_importar_con_dia_inexistente_devuelve_404(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 1',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Press banca', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);

        $response = $this->postJson('/api/rutinas/importar', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
            'created_by' => null,
            'dia' => 'Día Inexistente',
        ]);
        $response->assertStatus(404);
    }
}
