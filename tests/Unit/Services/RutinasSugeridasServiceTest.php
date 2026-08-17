<?php

namespace Tests\Unit\Services;

use App\Models\Ejercicio;
use App\Models\Historial;
use App\Models\Rutina;
use App\Models\User;
use App\Services\RutinasSugeridasService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RutinasSugeridasServiceTest extends TestCase
{
    use RefreshDatabase;

    private RutinasSugeridasService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RutinasSugeridasService();
    }

    public function test_user_sin_historial_recibe_sugerencias_principiantes(): void
    {
        // Crear 2 rutinas: una principiante y una avanzada
        Rutina::factory()->create(['nivel' => 'Principiante', 'modalidad' => 'Full body']);
        Rutina::factory()->create(['nivel' => 'Avanzado', 'modalidad' => 'Push']);

        $user = User::factory()->create();
        $sugeridas = $this->service->sugerirPara($user);

        $this->assertGreaterThan(0, $sugeridas->count());
        // La principiante debería rankear más alto
        $primera = $sugeridas->first();
        $this->assertEquals('Principiante', $primera['rutina']->nivel);
    }

    public function test_analizar_perfil_sin_historial(): void
    {
        $user = User::factory()->create();
        $perfil = $this->service->analizarPerfil($user);

        $this->assertFalse($perfil['tiene_historial']);
        $this->assertEquals(0, $perfil['dias_por_mes']);
        $this->assertEquals('principiante', $perfil['nivel_estimado']);
        $this->assertEmpty($perfil['top_grupos']);
    }

    public function test_analizar_perfil_con_historial(): void
    {
        $user = User::factory()->create();

        // Crear ejercicios
        $pecho = Ejercicio::factory()->create(['nombre' => 'Press banca', 'grupo_muscular' => 'Pecho']);
        $espalda = Ejercicio::factory()->create(['nombre' => 'Remo', 'grupo_muscular' => 'Espalda']);
        $pierna = Ejercicio::factory()->create(['nombre' => 'Sentadilla', 'grupo_muscular' => 'Pierna']);

        // Historial: 5 días de press banca, 3 de remo, 2 de sentadilla
        for ($i = 0; $i < 5; $i++) {
            Historial::create([
                'user_id' => $user->id,
                'fecha' => Carbon::now()->subDays($i)->toDateString(),
                'ejercicio_nombre' => $pecho->nombre,
                'rutina_nombre' => 'Push',
                'dia' => 'Día 1',
                'series_numero' => 1, 'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 2, 'peso' => 40,
            ]);
        }
        for ($i = 0; $i < 3; $i++) {
            Historial::create([
                'user_id' => $user->id,
                'fecha' => Carbon::now()->subDays($i + 10)->toDateString(),
                'ejercicio_nombre' => $espalda->nombre,
                'rutina_nombre' => 'Pull',
                'dia' => 'Día 1',
                'series_numero' => 1, 'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 2, 'peso' => 35,
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            Historial::create([
                'user_id' => $user->id,
                'fecha' => Carbon::now()->subDays($i + 20)->toDateString(),
                'ejercicio_nombre' => $pierna->nombre,
                'rutina_nombre' => 'Legs',
                'dia' => 'Día 1',
                'series_numero' => 1, 'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 2, 'peso' => 50,
            ]);
        }

        $perfil = $this->service->analizarPerfil($user);

        $this->assertTrue($perfil['tiene_historial']);
        $this->assertEquals(5, $perfil['dias_por_mes']); // 10 días únicos / 2 = 5 por mes
        // Pecho debería ser el top grupo (50% del trabajo)
        $this->assertEquals('Pecho', array_key_first($perfil['top_grupos']));
        $this->assertEquals(0.5, $perfil['top_grupos']['Pecho']);
    }

    public function test_sugerencias_rankean_por_afinidad(): void
    {
        $user = User::factory()->create();

        $pecho = Ejercicio::factory()->create(['nombre' => 'Press banca', 'grupo_muscular' => 'Pecho']);
        $espalda = Ejercicio::factory()->create(['nombre' => 'Remo', 'grupo_muscular' => 'Espalda']);

        // User hace mucho press banca (pecho)
        for ($i = 0; $i < 10; $i++) {
            Historial::create([
                'user_id' => $user->id,
                'fecha' => Carbon::now()->subDays($i)->toDateString(),
                'ejercicio_nombre' => $pecho->nombre,
                'rutina_nombre' => 'Push', 'dia' => 'Día 1',
                'series_numero' => 1, 'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 2, 'peso' => 40,
            ]);
        }

        // Rutinas disponibles
        $rutinaPecho = Rutina::factory()->create(['nivel' => 'Intermedio', 'modalidad' => 'Push']);
        $pechoEx = Ejercicio::firstWhere('nombre', 'Press banca');
        $rutinaPecho->ejercicio_nombre = $pechoEx->nombre;
        $rutinaPecho->ejercicio_id = $pechoEx->id;
        $rutinaPecho->save();

        $rutinaPierna = Rutina::factory()->create(['nivel' => 'Intermedio', 'modalidad' => 'Legs']);
        $pierna = Ejercicio::factory()->create(['nombre' => 'Sentadilla', 'grupo_muscular' => 'Pierna']);
        $rutinaPierna->ejercicio_id = $pierna->id;
        $rutinaPierna->ejercicio_nombre = $pierna->nombre;
        $rutinaPierna->save();

        $sugeridas = $this->service->sugerirPara($user);

        // La de Pecho debería rankear más alto (afinidad mayor)
        $primera = $sugeridas->first();
        $this->assertEquals('Push', $primera['rutina']->modalidad);
    }

    public function test_usuario_muy_activo_prefiere_splits(): void
    {
        $user = User::factory()->create();
        $pecho = Ejercicio::factory()->create(['nombre' => 'Press', 'grupo_muscular' => 'Pecho']);

        // 15 días de entrenamiento (>12 → muy activo)
        for ($i = 0; $i < 15; $i++) {
            Historial::create([
                'user_id' => $user->id,
                'fecha' => Carbon::now()->subDays($i)->toDateString(),
                'ejercicio_nombre' => $pecho->nombre,
                'rutina_nombre' => 'Push', 'dia' => 'Día 1',
                'series_numero' => 1, 'reps_min' => '8', 'reps_max' => '12', 'descanso_min' => 2, 'peso' => 40,
            ]);
        }

        Rutina::factory()->create(['nivel' => 'Intermedio', 'modalidad' => 'Push']);
        Rutina::factory()->create(['nivel' => 'Intermedio', 'modalidad' => 'Full body']);

        $sugeridas = $this->service->sugerirPara($user);
        // Push (split) debería rankear más alto que Full body para un user muy activo
        $this->assertEquals('Push', $sugeridas->first()['rutina']->modalidad);
    }

    public function test_devuelve_maximo_n_sugerencias(): void
    {
        $user = User::factory()->create();

        // Crear 10 rutinas
        for ($i = 0; $i < 10; $i++) {
            Rutina::factory()->create([
                'nivel' => 'Principiante',
                'modalidad' => "Tipo $i",
            ]);
        }

        $sugeridas = $this->service->sugerirPara($user, 3);
        $this->assertLessThanOrEqual(3, $sugeridas->count());
    }

    public function test_razones_se_generan(): void
    {
        $user = User::factory()->create();
        Rutina::factory()->create(['nivel' => 'Principiante', 'modalidad' => 'Full body']);

        $sugeridas = $this->service->sugerirPara($user);
        $primera = $sugeridas->first();
        $this->assertNotEmpty($primera['razones']);
    }
}
