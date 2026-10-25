<?php

namespace Tests\Feature;

use App\Models\Ejercicio;
use App\Models\Historial;
use App\Models\Musculo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Body map (mapa corporal) — endpoints que alimentan BodyMap.vue.
 *
 * Regresión principal: el select parcial del query NO traía 'completado',
 * pero el map lo leía. Con Model::shouldBeStrict() activo en dev eso es
 * MissingAttributeException → 500 en GET /api/body-map/data.
 *
 * Además cubre el ?user_id= que manda el front (trainer/admin viendo el
 * body map de un alumno) y su autorización.
 */
class BodyMapTest extends TestCase
{
    use RefreshDatabase;

    private function crearMusculo(string $slug = 'chest'): Musculo
    {
        return Musculo::create([
            'slug' => $slug,
            'nombre_es' => 'Pecho',
            'nombre_en' => 'Chest',
            'body_part' => 'upper-body',
            'orden' => 1,
        ]);
    }

    private function crearEjercicioConMusculo(Musculo $musculo): Ejercicio
    {
        $ejercicio = Ejercicio::factory()->create([
            'nombre' => 'Press de banca',
            'grupo_muscular' => 'Pecho',
        ]);

        $ejercicio->musculos()->attach($musculo->id, ['tipo' => 'primario', 'peso' => 1.0]);

        return $ejercicio;
    }

    private function crearSet(User $user, Ejercicio $ejercicio, bool $completado = true, ?string $fecha = null): Historial
    {
        return Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Push',
            'dia' => 'Día 1',
            'ejercicio_nombre' => $ejercicio->nombre,
            'ejercicio_id' => $ejercicio->id,
            'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12',
            'reps_realizadas' => 10,
            'descanso_min' => 2, 'peso' => 60,
            'completado' => $completado,
            'fecha' => $fecha ?? now()->toDateString(),
        ]);
    }

    public function test_body_map_devuelve_200_con_historiales(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $ejercicio = $this->crearEjercicioConMusculo($this->crearMusculo());
        $this->crearSet($user, $ejercicio);

        $response = $this->actingAs($user)->getJson('/api/body-map/data?window=90');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'historiales');
        $this->assertSame(1, $response->json('total'));
    }

    public function test_body_map_marca_todos_los_sets_como_completados(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $ejercicio = $this->crearEjercicioConMusculo($this->crearMusculo());
        $this->crearSet($user, $ejercicio);
        $this->crearSet($user, $ejercicio);

        $response = $this->actingAs($user)->getJson('/api/body-map/data?window=90');

        $response->assertStatus(200);
        foreach ($response->json('historiales') as $item) {
            // El front (useMuscleLoad) descarta los sets con completado=false:
            // si esto viniera en false, el body map se mostraría vacío.
            $this->assertTrue($item['completado']);
        }
    }

    public function test_body_map_incluye_los_musculos_del_ejercicio(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $musculo = $this->crearMusculo();
        $ejercicio = $this->crearEjercicioConMusculo($musculo);
        $this->crearSet($user, $ejercicio);

        $response = $this->actingAs($user)->getJson('/api/body-map/data?window=90');

        $response->assertStatus(200);
        // El mapeo de músculos va en el diccionario de ejercicios (no repetido
        // en cada serie: el payload bajaba de ~37KB a ~15KB).
        $musculos = $response->json("ejercicios.{$ejercicio->id}.musculos");
        $this->assertCount(1, $musculos);
        $this->assertSame('chest', $musculos[0]['musculo_slug']);
        $this->assertSame('primario', $musculos[0]['tipo']);
        $this->assertEquals(1.0, $musculos[0]['peso']);

        // Y la serie referencia al ejercicio por id
        $this->assertSame($ejercicio->id, $response->json('historiales.0.ejercicio_id'));
    }

    public function test_body_map_resuelve_el_ejercicio_por_nombre_cuando_no_hay_fk(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $musculo = $this->crearMusculo();
        $ejercicio = $this->crearEjercicioConMusculo($musculo);

        // Así los guarda la app: la rutina linkea ejercicios por nombre,
        // entonces la FK 'ejercicio_id' viene en null.
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Push',
            'dia' => 'Día 1',
            'ejercicio_nombre' => $ejercicio->nombre,
            'ejercicio_id' => null,
            'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12',
            'reps_realizadas' => 10,
            'descanso_min' => 2, 'peso' => 60,
            'completado' => true,
            'fecha' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/body-map/data?window=90');

        $response->assertStatus(200);
        $musculos = $response->json("ejercicios.{$ejercicio->id}.musculos");
        $this->assertCount(1, $musculos);
        $this->assertSame('chest', $musculos[0]['musculo_slug']);
    }

    public function test_body_map_con_homonimos_usa_el_ejercicio_con_mas_musculos(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $pectoral = $this->crearMusculo('chest');
        $hombro = $this->crearMusculo('deltoids');

        // Dos ejercicios con el mismo nombre (variantes de equipamiento).
        $pocoMapeado = Ejercicio::factory()->create(['nombre' => 'Press de banca']);
        $pocoMapeado->musculos()->attach($pectoral->id, ['tipo' => 'primario', 'peso' => 1.0]);

        $bienMapeado = Ejercicio::factory()->create(['nombre' => 'Press de banca']);
        $bienMapeado->musculos()->attach($pectoral->id, ['tipo' => 'primario', 'peso' => 1.0]);
        $bienMapeado->musculos()->attach($hombro->id, ['tipo' => 'secundario', 'peso' => 0.4]);

        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Push',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Press de banca',
            'ejercicio_id' => null,
            'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12',
            'reps_realizadas' => 10,
            'descanso_min' => 2, 'peso' => 60,
            'completado' => true,
            'fecha' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/body-map/data?window=90');

        $response->assertStatus(200);
        $musculos = $response->json("ejercicios.{$bienMapeado->id}.musculos");
        $this->assertCount(2, $musculos);
        $slugs = array_column($musculos, 'musculo_slug');
        $this->assertContains('deltoids', $slugs);
    }

    public function test_body_map_excluye_sets_no_completados(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $ejercicio = $this->crearEjercicioConMusculo($this->crearMusculo());
        $this->crearSet($user, $ejercicio, completado: false);
        $this->crearSet($user, $ejercicio, completado: true);

        $response = $this->actingAs($user)->getJson('/api/body-map/data?window=90');

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('total'));
    }

    public function test_body_map_respeta_la_ventana_temporal(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $ejercicio = $this->crearEjercicioConMusculo($this->crearMusculo());
        $this->crearSet($user, $ejercicio, fecha: now()->subDays(200)->toDateString());
        $this->crearSet($user, $ejercicio, fecha: now()->toDateString());

        $response = $this->actingAs($user)->getJson('/api/body-map/data?window=90');

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('total'));
        $this->assertSame(90, $response->json('window_days'));
    }

    public function test_usuario_comun_no_puede_ver_body_map_de_otro(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $otro = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson("/api/body-map/data?user_id={$otro->id}");

        $response->assertStatus(403);
    }

    public function test_trainer_puede_ver_body_map_de_alumno_asignado(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN, 'trainer_id' => $trainer->id]);
        $ejercicio = $this->crearEjercicioConMusculo($this->crearMusculo());
        $this->crearSet($alumno, $ejercicio);

        $response = $this->actingAs($trainer)->getJson("/api/body-map/data?user_id={$alumno->id}&window=90");

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('total'));
    }

    public function test_trainer_no_puede_ver_body_map_de_alumno_no_asignado(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($trainer)->getJson("/api/body-map/data?user_id={$alumno->id}");

        $response->assertStatus(403);
    }

    public function test_admin_puede_ver_body_map_de_cualquier_alumno(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN]);
        $ejercicio = $this->crearEjercicioConMusculo($this->crearMusculo());
        $this->crearSet($alumno, $ejercicio);

        $response = $this->actingAs($admin)->getJson("/api/body-map/data?user_id={$alumno->id}&window=90");

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('total'));
    }

    public function test_muscle_recency_respeta_el_user_id(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN, 'trainer_id' => $trainer->id]);
        $ejercicio = $this->crearEjercicioConMusculo($this->crearMusculo());
        $this->crearSet($alumno, $ejercicio);

        $response = $this->actingAs($trainer)->getJson("/api/body-map/muscle-recency?user_id={$alumno->id}");

        $response->assertStatus(200);
        $recency = $response->json('recency');
        $this->assertCount(1, $recency);
        $this->assertSame('chest', $recency[0]['slug']);
        $this->assertNotNull($recency[0]['last_trained_at']);
    }

    public function test_muscle_recency_resuelve_el_ejercicio_por_nombre(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $musculo = $this->crearMusculo();
        $ejercicio = $this->crearEjercicioConMusculo($musculo);

        // Historial sin FK: el recency tiene que resolverlo por nombre.
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Push',
            'dia' => 'Día 1',
            'ejercicio_nombre' => $ejercicio->nombre,
            'ejercicio_id' => null,
            'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12',
            'reps_realizadas' => 10,
            'descanso_min' => 2, 'peso' => 60,
            'completado' => true,
            'fecha' => now()->subDays(3)->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/body-map/muscle-recency');

        $response->assertStatus(200);
        $recency = $response->json('recency');
        $this->assertCount(1, $recency);
        $this->assertSame('chest', $recency[0]['slug']);
        $this->assertSame(3, $recency[0]['days_since']);
    }

    public function test_muscle_recency_rechaza_usuario_no_autorizado(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $otro = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson("/api/body-map/muscle-recency?user_id={$otro->id}");

        $response->assertStatus(403);
    }

    public function test_ejercicios_por_musculo_respeta_el_user_id(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create(['role' => User::ROLE_COMUN, 'trainer_id' => $trainer->id]);
        $ejercicio = $this->crearEjercicioConMusculo($this->crearMusculo());
        $this->crearSet($alumno, $ejercicio);

        $response = $this->actingAs($trainer)->getJson("/api/body-map/muscle/chest/exercises?user_id={$alumno->id}");

        $response->assertStatus(200);
        $this->assertSame('Press de banca', $response->json('ejercicios.0.nombre'));
        $this->assertSame(1, $response->json('ejercicios.0.sets_30d'));
    }

    public function test_ejercicios_por_musculo_resuelve_el_volumen_por_nombre(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $musculo = $this->crearMusculo();
        $ejercicio = $this->crearEjercicioConMusculo($musculo);

        // Sin FK: el volumen tiene que salir por 'ejercicio_nombre'.
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Push',
            'dia' => 'Día 1',
            'ejercicio_nombre' => $ejercicio->nombre,
            'ejercicio_id' => null,
            'series_numero' => 1,
            'reps_min' => '8', 'reps_max' => '12',
            'reps_realizadas' => 10,
            'descanso_min' => 2, 'peso' => 60,
            'completado' => true,
            'fecha' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/body-map/muscle/chest/exercises');

        $response->assertStatus(200);
        $this->assertSame('Press de banca', $response->json('ejercicios.0.nombre'));
        $this->assertSame(1, $response->json('ejercicios.0.sets_30d'));
        $this->assertEquals(60.0, $response->json('ejercicios.0.max_peso_30d'));
    }

    public function test_ejercicios_por_musculo_rechaza_usuario_no_autorizado(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $otro = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson("/api/body-map/muscle/chest/exercises?user_id={$otro->id}");

        $response->assertStatus(403);
    }
}
