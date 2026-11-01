<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tests del endpoint POST /api/historial/guardar con soporte offline-first
 * (Oleada 1, seccion 5 del plan de mejoras).
 *
 * Cubre:
 *   - Aceptar fecha del pasado (caso offline, registrado hace dias).
 *   - Aceptar fecha de hoy (caso online, retrocompat).
 *   - Rechazar fecha futura.
 *   - Rechazar fecha mas vieja que 7 dias.
 *   - Aceptar client_id sin romper la logica.
 *   - Idempotencia: updateOrCreate con la misma clave compuesta no duplica.
 */
class HistorialOfflineTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'rutina_nombre' => 'Full Body A',
            'dia' => 'Lunes',
            'ejercicio_nombre' => 'Sentadilla',
            'series_numero' => 1,
            'reps_min' => '8',
            'reps_max' => '10',
            'descanso_min' => 2.0,
            'peso' => 80.0,
            'reps_realizadas' => 10,
            'completado' => true,
        ], $overrides);
    }

    public function test_acepta_fecha_hoy_sin_client_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/historial/guardar', $this->payload());

        $response->assertOk();
        $response->assertJsonStructure(['message', 'count', 'new_medals']);

        $record = Historial::where('user_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(now()->toDateString(), $record->fecha->toDateString());
    }

    public function test_acepta_fecha_pasada_de_hasta_7_dias(): void
    {
        $user = User::factory()->create();
        $hace3Dias = Carbon::now()->subDays(3)->toDateString();

        $response = $this->actingAs($user)
            ->postJson('/api/historial/guardar', $this->payload([
                'fecha' => $hace3Dias,
                'client_id' => 'offline-uuid-123',
            ]));

        $response->assertOk();
        $record = Historial::where('user_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals($hace3Dias, $record->fecha->toDateString());
    }

    public function test_rechaza_fecha_futura(): void
    {
        $user = User::factory()->create();
        $manana = Carbon::now()->addDay()->toDateString();

        $response = $this->actingAs($user)
            ->postJson('/api/historial/guardar', $this->payload([
                'fecha' => $manana,
            ]));

        // 422 = validacion falla
        $response->assertStatus(422);
    }

    public function test_rechaza_fecha_mas_vieja_que_7_dias(): void
    {
        $user = User::factory()->create();
        $hace10Dias = Carbon::now()->subDays(10)->toDateString();

        $response = $this->actingAs($user)
            ->postJson('/api/historial/guardar', $this->payload([
                'fecha' => $hace10Dias,
            ]));

        $response->assertStatus(422);
    }

    public function test_es_idempotente_con_update_or_create(): void
    {
        $user = User::factory()->create();

        // Primer guardado: completo.
        $this->actingAs($user)->postJson('/api/historial/guardar', $this->payload([
            'peso' => 80.0,
        ]))->assertOk();

        // Segundo guardado de la misma serie (mismo client_id o sin el, da igual)
        // con un peso distinto: debe ACTUALIZAR, no duplicar.
        $this->actingAs($user)->postJson('/api/historial/guardar', $this->payload([
            'peso' => 85.0,
        ]))->assertOk();

        $this->assertEquals(1, Historial::where('user_id', $user->id)
            ->where('ejercicio_nombre', 'Sentadilla')
            ->where('series_numero', 1)
            ->count());

        $this->assertDatabaseHas('historials', [
            'user_id' => $user->id,
            'peso' => 85.0,
        ]);
    }

    public function test_acepta_batch_de_series_con_fechas_distintas(): void
    {
        $user = User::factory()->create();
        $ayer = Carbon::now()->subDay()->toDateString();

        $response = $this->actingAs($user)
            ->postJson('/api/historial/guardar', [
                'series' => [
                    $this->payload(['series_numero' => 1, 'peso' => 80, 'fecha' => $ayer]),
                    $this->payload(['series_numero' => 2, 'peso' => 80, 'fecha' => $ayer]),
                    $this->payload(['series_numero' => 3, 'peso' => 75, 'fecha' => $ayer]),
                ],
            ]);

        $response->assertOk();
        $response->assertJson(['count' => 3]);

        // Las 3 series con la misma fecha (ayer).
        $count = Historial::where('user_id', $user->id)
            ->get()
            ->filter(fn ($r) => $r->fecha->toDateString() === $ayer)
            ->count();
        $this->assertEquals(3, $count);
    }
}
