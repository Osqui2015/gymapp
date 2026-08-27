<?php

namespace Tests\Feature;

use App\Models\Rutina;
use App\Models\User;
use App\Models\UserRutina;
use App\Models\UserRutinaReschedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 5 — Reschedule de día de rutina.
 */
class RescheduleTest extends TestCase
{
    use RefreshDatabase;

    private function makeRutinaConDias($nivel = 'Principiante', $modalidad = 'Hipertrofia', $dias = ['Día 1', 'Día 2', 'Día 3'])
    {
        foreach ($dias as $i => $dia) {
            Rutina::create([
                'nivel' => $nivel,
                'modalidad' => $modalidad,
                'dia' => $dia,
                'ejercicio_nombre' => "Ej{$i}",
                'series' => 3,
                'reps_min' => '8',
                'reps_max' => '12',
                'descanso_min' => 1.5,
                'orden' => $i + 1,
            ]);
        }
    }

    private function makeUserConRutina($nivel = 'Principiante', $modalidad = 'Hipertrofia', $diaActual = 'Día 1')
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $rutina = Rutina::where('nivel', $nivel)
            ->where('modalidad', $modalidad)
            ->where('dia', $diaActual)
            ->first();
        UserRutina::create([
            'user_id' => $user->id,
            'rutina_id' => $rutina->id,
            'dia_actual' => $diaActual,
        ]);
        return $user;
    }

    public function test_reschedule_cambia_dia_y_logea(): void
    {
        $this->makeRutinaConDias();
        $user = $this->makeUserConRutina(diaActual: 'Día 1');

        $response = $this->actingAs($user)->postJson('/api/user-rutina/reschedule', [
            'to_day' => 'Día 3',
            'reason' => 'missed_day',
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('Día 3', $data['user_rutina']['dia_actual']);
        $this->assertEquals(1, UserRutinaReschedule::count());
        $this->assertDatabaseHas('user_rutina_reschedules', [
            'user_id' => $user->id,
            'from_day' => 'Día 1',
            'to_day' => 'Día 3',
            'reason' => 'missed_day',
        ]);
    }

    public function test_reschedule_mismo_dia_no_logea(): void
    {
        $this->makeRutinaConDias();
        $user = $this->makeUserConRutina(diaActual: 'Día 2');

        $response = $this->actingAs($user)->postJson('/api/user-rutina/reschedule', [
            'to_day' => 'Día 2',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, UserRutinaReschedule::count());
    }

    public function test_reschedule_rechaza_dia_invalido(): void
    {
        $this->makeRutinaConDias();
        $user = $this->makeUserConRutina();

        $response = $this->actingAs($user)->postJson('/api/user-rutina/reschedule', [
            'to_day' => 'Día 99',
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, UserRutinaReschedule::count());
    }

    public function test_reschedule_requiere_to_day(): void
    {
        $this->makeRutinaConDias();
        $user = $this->makeUserConRutina();

        $response = $this->actingAs($user)->postJson('/api/user-rutina/reschedule', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_day']);
    }

    public function test_reschedule_404_si_no_tiene_rutina(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->postJson('/api/user-rutina/reschedule', [
            'to_day' => 'Día 1',
        ]);

        $response->assertStatus(404);
    }

    public function test_reschedule_requiere_auth(): void
    {
        $response = $this->postJson('/api/user-rutina/reschedule', ['to_day' => 'Día 1']);
        $response->assertStatus(401);
    }

    public function test_reschedule_valida_reason(): void
    {
        $this->makeRutinaConDias();
        $user = $this->makeUserConRutina();

        $response = $this->actingAs($user)->postJson('/api/user-rutina/reschedule', [
            'to_day' => 'Día 2',
            'reason' => 'invalid_reason',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_available_days_devuelve_los_dias_de_la_rutina(): void
    {
        $this->makeRutinaConDias(dias: ['Día 1', 'Día 2', 'Día 3', 'Día 4']);
        $user = $this->makeUserConRutina(diaActual: 'Día 2');

        $response = $this->actingAs($user)->getJson('/api/user-rutina/available-days');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(['Día 1', 'Día 2', 'Día 3', 'Día 4'], $data['days']);
        $this->assertEquals('Día 2', $data['current']);
    }

    public function test_available_days_sin_rutina_devuelve_vacio(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/user-rutina/available-days');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals([], $data['days']);
        $this->assertNull($data['current']);
    }

    public function test_available_days_requiere_auth(): void
    {
        $response = $this->getJson('/api/user-rutina/available-days');
        $response->assertStatus(401);
    }
}
