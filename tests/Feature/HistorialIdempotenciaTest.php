<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Regresión del P0 de idempotencia del historial.
 *
 * Antes, `HistorialController::guardar()`-usaba `updateOrCreate` con la clave
 * (user_id, rutina_nombre, dia, ejercicio_nombre, series_numero) SIN `fecha`.
 * Consecuencia: al volver a registrar el mismo set en otra fecha se pisaba la
 * sesión anterior en vez de acumularse, y todos los KPIs (volumen, 1RM,
 * rachas, heatmap) subcontaban.
 *
 * Ahora la clave incluye `fecha` y hay un índice UNIQUE que lo garantiza en la
 * base (migración 2026_10_29_000000_fix_historials_idempotencia_y_fecha).
 */
class HistorialIdempotenciaTest extends TestCase
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

    public function test_mismo_set_en_otra_fecha_crea_una_fila_nueva(): void
    {
        $user = User::factory()->create();
        $hoy = Carbon::now()->toDateString();
        $ayer = Carbon::now()->subDay()->toDateString();

        $this->actingAs($user)->postJson('/api/historial/guardar', $this->payload([
            'fecha' => $ayer,
            'peso' => 70.0,
        ]))->assertOk();

        $this->actingAs($user)->postJson('/api/historial/guardar', $this->payload([
            'fecha' => $hoy,
            'peso' => 80.0,
        ]))->assertOk();

        // Dos sesiones distintas: la anterior NO se sobreescribe.
        $this->assertSame(2, Historial::where('user_id', $user->id)->count());

        $ayerRow = Historial::where('user_id', $user->id)->whereDate('fecha', $ayer)->first();
        $hoyRow = Historial::where('user_id', $user->id)->whereDate('fecha', $hoy)->first();

        $this->assertNotNull($ayerRow);
        $this->assertNotNull($hoyRow);
        $this->assertEquals(70.0, (float) $ayerRow->peso);
        $this->assertEquals(80.0, (float) $hoyRow->peso);
    }

    public function test_mismo_set_en_la_misma_fecha_actualiza_en_vez_de_duplicar(): void
    {
        $user = User::factory()->create();
        $hoy = Carbon::now()->toDateString();

        $this->actingAs($user)->postJson('/api/historial/guardar', $this->payload([
            'fecha' => $hoy,
            'peso' => 80.0,
        ]))->assertOk();

        $this->actingAs($user)->postJson('/api/historial/guardar', $this->payload([
            'fecha' => $hoy,
            'peso' => 85.0,
        ]))->assertOk();

        $this->assertSame(1, Historial::where('user_id', $user->id)->count());
        $this->assertEquals(85.0, (float) Historial::where('user_id', $user->id)->first()->peso);
    }

    public function test_reentrenar_el_mismo_set_varios_dias_acumula_historial(): void
    {
        $user = User::factory()->create();

        foreach ([3, 2, 1, 0] as $diasAtras) {
            $this->actingAs($user)->postJson('/api/historial/guardar', $this->payload([
                'fecha' => Carbon::now()->subDays($diasAtras)->toDateString(),
                'peso' => 70.0 + $diasAtras,
            ]))->assertOk();
        }

        // 4 sesiones distintas, una por día.
        $this->assertSame(4, Historial::where('user_id', $user->id)->count());
        $this->assertSame(
            4,
            Historial::where('user_id', $user->id)->distinct()->count('fecha')
        );
    }

    public function test_series_distintas_del_mismo_ejercicio_coexisten(): void
    {
        $user = User::factory()->create();
        $hoy = Carbon::now()->toDateString();

        foreach ([1, 2, 3] as $serie) {
            $this->actingAs($user)->postJson('/api/historial/guardar', $this->payload([
                'fecha' => $hoy,
                'series_numero' => $serie,
            ]))->assertOk();
        }

        $this->assertSame(3, Historial::where('user_id', $user->id)->count());
    }

    public function test_marcar_completado_afecta_la_serie_de_la_fecha_indicada(): void
    {
        $user = User::factory()->create();
        $hoy = Carbon::now()->toDateString();
        $ayer = Carbon::now()->subDay()->toDateString();

        // Dos sesiones del mismo set, en fechas distintas.
        $this->actingAs($user)->postJson('/api/historial/guardar', $this->payload([
            'fecha' => $ayer,
            'completado' => false,
        ]))->assertOk();

        $this->actingAs($user)->postJson('/api/historial/guardar', $this->payload([
            'fecha' => $hoy,
            'completado' => false,
        ]))->assertOk();

        $this->actingAs($user)->postJson('/api/historial/completar', [
            'rutina_nombre' => 'Full Body A',
            'dia' => 'Lunes',
            'ejercicio_nombre' => 'Sentadilla',
            'series_numero' => 1,
            'fecha' => $hoy,
        ])->assertOk();

        // Solo la de HOY queda completada: la de ayer sigue como estaba.
        $this->assertTrue((bool) Historial::where('user_id', $user->id)->whereDate('fecha', $hoy)->first()->completado);
        $this->assertFalse((bool) Historial::where('user_id', $user->id)->whereDate('fecha', $ayer)->first()->completado);
    }

    public function test_marcar_completado_valida_la_entrada(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/historial/completar', [
            'rutina_nombre' => 'Full Body A',
            'dia' => 'Lunes',
            // falta ejercicio_nombre y series_numero
        ])->assertStatus(422);
    }

    public function test_el_indice_unico_rechaza_filas_duplicadas(): void
    {
        $user = User::factory()->create();
        $hoy = Carbon::now()->toDateString();

        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Full Body A',
            'dia' => 'Lunes',
            'ejercicio_nombre' => 'Sentadilla',
            'series_numero' => 1,
            'reps_min' => '8',
            'reps_max' => '10',
            'descanso_min' => 2,
            'completado' => true,
            'fecha' => $hoy,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Full Body A',
            'dia' => 'Lunes',
            'ejercicio_nombre' => 'Sentadilla',
            'series_numero' => 1,
            'reps_min' => '8',
            'reps_max' => '10',
            'descanso_min' => 2,
            'completado' => true,
            'fecha' => $hoy,
        ]);
    }

    public function test_quick_log_dos_veces_el_mismo_dia_no_duplica_la_fila(): void
    {
        $user = User::factory()->create();
        $ejercicio = \App\Models\Ejercicio::factory()->create(['nombre' => 'Press militar']);

        $this->actingAs($user)->postJson("/api/ejercicios/{$ejercicio->id}/quick-log")->assertOk();
        $this->actingAs($user)->postJson("/api/ejercicios/{$ejercicio->id}/quick-log")->assertOk();

        $this->assertSame(1, Historial::where('user_id', $user->id)->count());
    }

    // -----------------------------------------------------------------
    // DELETE /api/rutinas — no debe borrar el catálogo del sistema
    // -----------------------------------------------------------------

    public function test_admin_no_borra_el_catalogo_sin_pedirlo_explicitamente(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

        // Rutinas del catálogo oficial (created_by NULL).
        $catalogo = Rutina::factory()->create([
            'created_by' => null,
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
        ]);

        $this->actingAs($admin)->deleteJson('/api/rutinas', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
        ])->assertOk();

        $this->assertDatabaseHas('rutinas', ['id' => $catalogo->id]);
    }

    public function test_admin_puede_borrar_el_catalogo_con_scope_explicito(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

        $catalogo = Rutina::factory()->create([
            'created_by' => null,
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
        ]);

        $this->actingAs($admin)->deleteJson('/api/rutinas', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
            'scope' => 'catalogo',
        ])->assertOk();

        $this->assertDatabaseMissing('rutinas', ['id' => $catalogo->id]);
    }

    public function test_usuario_comun_solo_borra_sus_propias_rutinas(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $otro = User::factory()->create(['role' => User::ROLE_COMUN]);

        $mia = Rutina::factory()->create([
            'created_by' => $user->id,
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
        ]);
        $ajena = Rutina::factory()->create([
            'created_by' => $otro->id,
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
        ]);

        $this->actingAs($user)->deleteJson('/api/rutinas', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
        ])->assertOk();

        $this->assertDatabaseMissing('rutinas', ['id' => $mia->id]);
        $this->assertDatabaseHas('rutinas', ['id' => $ajena->id]);
    }

    public function test_usuario_comun_no_puede_borrar_el_catalogo(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $catalogo = Rutina::factory()->create([
            'created_by' => null,
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
        ]);

        $this->actingAs($user)->deleteJson('/api/rutinas', [
            'nivel' => 'Intermedio',
            'modalidad' => '3 Días',
            'scope' => 'catalogo',
        ])->assertStatus(403);

        $this->assertDatabaseHas('rutinas', ['id' => $catalogo->id]);
    }
}
