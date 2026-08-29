<?php

namespace Tests\Feature;

use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRutinaSeleccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_seleccionar_rutina_requiere_rutina_id(): void
    {
        // Backwards compat: el front antes mandaba nivel/modalidad y el
        // backend ya no los acepta. Tiene que fallar con 422 si no se
        // manda `rutina_id`.
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/user-rutina', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
            'dia_actual' => 'Día 1',
        ]);
        $response->assertStatus(422);
        $this->assertArrayHasKey('rutina_id', $response->json('errors'));
    }

    public function test_seleccionar_rutina_con_rutina_id_exitoso(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $rutina = Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 1',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Press banca', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);

        $response = $this->postJson('/api/user-rutina', [
            'rutina_id' => $rutina->id,
            'dia_actual' => 'Día 1',
        ]);
        $response->assertStatus(200);

        $userRutina = UserRutina::where('user_id', $user->id)->first();
        $this->assertNotNull($userRutina);
        $this->assertSame($rutina->id, $userRutina->rutina_id);
        $this->assertSame('Día 1', $userRutina->dia_actual);
        // Los accessors virtuales leen de la relacion, asi que 'nivel' y
        // 'modalidad' del JSON vienen de la rutina relacionada.
        $response->assertJsonPath('nivel', 'Intermedio');
        $response->assertJsonPath('modalidad', '3 Días');
    }

    public function test_seleccionar_otra_rutina_sobreescribe_con_updateOrCreate(): void
    {
        // Un user solo puede tener una user_rutina a la vez. updateOrCreate
        // asegura que la segunda llamada reemplaza la primera.
        $user = User::factory()->create();
        $this->actingAs($user);

        $r1 = Rutina::create([
            'nivel' => 'Principiante', 'modalidad' => 'Full Body', 'dia' => 'Día 1',
            'series' => 3, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Press banca', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);
        $r2 = Rutina::create([
            'nivel' => 'Intermedio', 'modalidad' => '3 Días', 'dia' => 'Día 1',
            'series' => 4, 'reps_min' => '10', 'reps_max' => '12', 'descanso_min' => 1.5,
            'ejercicio_nombre' => 'Sentadilla', 'orden' => 1,
            'publica' => true, 'created_by' => null,
        ]);

        $this->postJson('/api/user-rutina', ['rutina_id' => $r1->id])->assertStatus(200);
        $this->postJson('/api/user-rutina', ['rutina_id' => $r2->id])->assertStatus(200);

        $this->assertCount(1, UserRutina::where('user_id', $user->id)->get());
        $this->assertSame($r2->id, UserRutina::where('user_id', $user->id)->first()->rutina_id);
    }
}
