<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\Rutina;
use App\Models\SesionEntrenamiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_iniciar_sesion_crea_registro_y_es_idempotente()
    {
        $user = User::factory()->create(['nick' => 'alumno_test']);
        $uuid = 'test-uuid-12345';

        $response = $this->actingAs($user)->postJson('/api/sesiones/iniciar', [
            'uuid' => $uuid,
            'rutina_nombre' => 'Full Body Principiante',
            'dia' => 'Día 1',
            'started_at' => now()->toISOString(),
            'series_totales' => 12,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('sesiones_entrenamiento', [
            'uuid' => $uuid,
            'user_id' => $user->id,
            'rutina_nombre' => 'Full Body Principiante',
            'dia' => 'Día 1',
        ]);

        // Segunda llamada con mismo UUID (idempotente)
        $response2 = $this->actingAs($user)->postJson('/api/sesiones/iniciar', [
            'uuid' => $uuid,
            'rutina_nombre' => 'Full Body Principiante',
            'dia' => 'Día 1',
        ]);

        $response2->assertStatus(201);
        $this->assertEquals(1, SesionEntrenamiento::where('uuid', $uuid)->count());
    }

    public function test_obtener_sesion_activa()
    {
        $user = User::factory()->create();

        // Sin sesión activa
        $resVacia = $this->actingAs($user)->getJson('/api/sesiones/activa');
        $resVacia->assertStatus(200);
        $resVacia->assertJson(['activa' => false]);

        // Crear sesión activa
        SesionEntrenamiento::create([
            'uuid' => 'active-uuid-999',
            'user_id' => $user->id,
            'rutina_nombre' => 'Torso Pierna',
            'dia' => 'Día 1',
            'started_at' => now()->subMinutes(20),
            'series_totales' => 10,
        ]);

        $resActiva = $this->actingAs($user)->getJson('/api/sesiones/activa');
        $resActiva->assertStatus(200);
        $resActiva->assertJson([
            'activa' => true,
            'sesion' => [
                'uuid' => 'active-uuid-999',
                'rutina_nombre' => 'Torso Pierna',
            ],
        ]);
    }

    public function test_guardar_series_con_tipo_serie_y_sesion_uuid()
    {
        $user = User::factory()->create();
        $uuid = 'session-set-test-111';

        $payload = [
            'rutina_nombre' => 'Fuerza',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press Banca',
            'series_numero' => 1,
            'series_completadas' => 1,
            'reps_min' => '8',
            'reps_max' => '10',
            'reps_realizadas' => 10,
            'descanso_min' => 2,
            'peso' => 80.0,
            'completado' => true,
            'tipo_serie' => 'calentamiento',
            'sesion_uuid' => $uuid,
        ];

        $response = $this->actingAs($user)->postJson('/api/historial/guardar', $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('historials', [
            'user_id' => $user->id,
            'ejercicio_nombre' => 'Press Banca',
            'tipo_serie' => 'calentamiento',
            'sesion_uuid' => $uuid,
            'peso' => 80.0,
        ]);
    }

    public function test_finalizar_sesion_calcula_duracion_volumen_y_detecta_prs()
    {
        $user = User::factory()->create();
        $uuid = 'sesion-finalizar-test-222';
        $startedAt = Carbon::now()->subMinutes(45);

        // Marca previa histórica para detectar PR (hace 10 días, peso 90kg)
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Fuerza',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Sentadilla',
            'series_numero' => 1,
            'series_completadas' => 1,
            'reps_min' => '5',
            'reps_max' => '5',
            'reps_realizadas' => 5,
            'descanso_min' => 3,
            'peso' => 90.0,
            'completado' => true,
            'fecha' => Carbon::now()->subDays(10)->toDateString(),
        ]);

        // Sesión activa actual
        $sesion = SesionEntrenamiento::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'rutina_nombre' => 'Fuerza',
            'dia' => 'Día 1',
            'started_at' => $startedAt,
            'series_totales' => 3,
        ]);

        // Series realizadas en esta sesión (supera la marca: 100kg en Sentadilla!)
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Fuerza',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Sentadilla',
            'series_numero' => 1,
            'series_completadas' => 1,
            'reps_min' => '5',
            'reps_max' => '5',
            'reps_realizadas' => 5,
            'descanso_min' => 3,
            'peso' => 100.0, // PR! (100 * 5 = 500 volumen)
            'completado' => true,
            'fecha' => Carbon::now()->toDateString(),
            'sesion_uuid' => $uuid,
        ]);

        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Fuerza',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press Militar',
            'series_numero' => 1,
            'series_completadas' => 1,
            'reps_min' => '8',
            'reps_max' => '8',
            'reps_realizadas' => 8,
            'descanso_min' => 2,
            'peso' => 50.0, // 50 * 8 = 400 volumen
            'completado' => true,
            'fecha' => Carbon::now()->toDateString(),
            'sesion_uuid' => $uuid,
        ]);

        $endedAt = Carbon::now();

        $response = $this->actingAs($user)->postJson('/api/sesiones/finalizar', [
            'uuid' => $uuid,
            'ended_at' => $endedAt->toISOString(),
            'notas' => 'Entrenamiento excelente, rompí récord en sentadilla',
        ]);

        $response->assertStatus(200);
        $resumen = $response->json('resumen');

        $this->assertEquals(900.0, (float) $resumen['volumen_total']); // 500 + 400
        $this->assertEquals(2, $resumen['series_completadas']);
        $this->assertEquals(1, $resumen['prs_superados']); // Sentadilla 100 > 90
        $this->assertGreaterThanOrEqual(44 * 60, $resumen['duracion_segundos']);

        $this->assertDatabaseHas('sesiones_entrenamiento', [
            'uuid' => $uuid,
            'series_completadas' => 2,
            'volumen_total' => 900.0,
            'prs_superados' => 1,
            'notas' => 'Entrenamiento excelente, rompí récord en sentadilla',
        ]);
    }

    public function test_descartar_sesion_incompleta()
    {
        $user = User::factory()->create();
        $uuid = 'discard-uuid-333';

        SesionEntrenamiento::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'rutina_nombre' => 'Full Body',
            'dia' => 'Día 1',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/sesiones/{$uuid}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('sesiones_entrenamiento', [
            'uuid' => $uuid,
        ]);
    }

    public function test_duplicar_dia_de_rutina()
    {
        $user = User::factory()->create();

        // Crear ejercicios en Día 1
        Rutina::create([
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press Plano',
            'series' => 4,
            'reps_min' => '8',
            'reps_max' => '10',
            'descanso_min' => 2.0,
            'orden' => 1,
            'created_by' => $user->id,
        ]);

        Rutina::create([
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Aperturas',
            'series' => 3,
            'reps_min' => '12',
            'reps_max' => '15',
            'descanso_min' => 1.5,
            'orden' => 2,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/rutinas/duplicar-dia', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia_origen' => 'Día 1',
            'dia_destino' => 'Día 2 (Copia)',
        ]);

        $response->assertStatus(201);
        $this->assertEquals(2, $response->json('count'));

        $this->assertDatabaseHas('rutinas', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 2 (Copia)',
            'ejercicio_nombre' => 'Press Plano',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('rutinas', [
            'nivel' => 'Personalizada',
            'modalidad' => 'Mi Rutina',
            'dia' => 'Día 2 (Copia)',
            'ejercicio_nombre' => 'Aperturas',
            'created_by' => $user->id,
        ]);
    }
}
