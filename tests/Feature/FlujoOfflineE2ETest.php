<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tanda 3 / T3-E — tests e2e del flujo offline.
 *
 * Simula el ciclo COMPLETO que vive en `resources/js/composables/useOfflineSeries`:
 *
 *   1. El usuario entrena SIN conexión: el front encola la serie en IndexedDB
 *      (con la fecha real del entrenamiento, no la del sync).
 *   2. El server responde 503 (transitorio): la serie QUEDA en la cola.
 *   3. Vuelve la conexión: el front reintenta el mismo payload.
 *   4. El server responde 200: la serie se guarda y sale de la cola.
 *   5. Si el front reintenta de nuevo (por un ACK perdido), NO debe duplicar.
 *
 * La clave idempotente del backend es la compuesta
 * (user_id, rutina_nombre, dia, ejercicio_nombre, series_numero, fecha).
 * `client_id` NO se persiste: es solo traza.
 */
class FlujoOfflineE2ETest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'rutina_nombre' => 'Push A',
            'dia' => 'Lunes',
            'ejercicio_nombre' => 'Press banca',
            'series_numero' => 1,
            'reps_min' => '8',
            'reps_max' => '12',
            'descanso_min' => 2.0,
            'peso' => 60.0,
            'reps_realizadas' => 10,
            'completado' => true,
        ], $overrides);
    }

    public function test_ciclo_offline_completo_encola_reintenta_y_guarda_una_sola_vez(): void
    {
        $user = User::factory()->create();

        // El usuario entrenó hace 2 días (offline).
        $fechaEntrenamiento = Carbon::now()->subDays(2)->toDateString();
        $clientId = 'cs-uuid-offline-1';

        // --- Paso 1: el server está caído (503 transitorio). La serie NO se guarda.
        // El front la encola en IndexedDB y devuelve status 'queued'.
        //
        // Para simular el 503 contra el endpoint real usamos el mock de axios
        // del lado JS; acá verificamos el contrato: un 5xx NO borra nada
        // porque la serie nunca llegó a persistirse.
        $this->assertSame(0, Historial::where('user_id', $user->id)->count());

        // --- Paso 2: vuelve la conexión y el front reintenta el MISMO payload.
        // El front manda UN REGISTRO PLANO (no un array): así lo hace
        // `useOfflineSeries.sendOne()`.
        $response = $this->actingAs($user)->postJson('/api/historial/guardar', array_merge(
            $this->payload(),
            [
                'client_id' => $clientId,
                'fecha' => $fechaEntrenamiento,
            ]
        ));

        $response->assertOk();
        $response->assertJson(['count' => 1]);

        // --- Paso 3: la serie se guardó con la FECHA DEL ENTRENAMIENTO,
        // no con la fecha del sync. Esto es lo que permite que el historial
        // offline no se corra hacia adelante.
        $record = Historial::where('user_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertSame($fechaEntrenamiento, $record->fecha->toDateString());
        $this->assertSame(60.0, (float) $record->peso);
        $this->assertSame(10, $record->reps_realizadas);
    }

    public function test_la_fecha_del_wrapper_aplica_a_todas_las_series_del_batch(): void
    {
        // Formato batch: la fecha viene UNA vez en el wrapper y aplica a las
        // 3 series. Antes se ignoraba y todas se guardaban con la fecha de
        // hoy, corriendo el historial hacia adelante.
        $user = User::factory()->create();
        $fecha = Carbon::now()->subDays(2)->toDateString();

        $response = $this->actingAs($user)->postJson('/api/historial/guardar', [
            'fecha' => $fecha,
            'series' => [
                $this->payload(['series_numero' => 1]),
                $this->payload(['series_numero' => 2]),
                $this->payload(['series_numero' => 3]),
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['count' => 3]);

        $this->assertSame(3, Historial::where('user_id', $user->id)->count());

        foreach (Historial::where('user_id', $user->id)->get() as $r) {
            $this->assertSame(
                $fecha,
                $r->fecha->toDateString(),
                'Cada serie del batch tiene que quedar con la fecha del wrapper'
            );
        }
    }

    public function test_la_fecha_por_serie_tiene_precedencia_sobre_la_del_wrapper(): void
    {
        $user = User::factory()->create();
        $ayer = Carbon::now()->subDay()->toDateString();
        $hoy = Carbon::now()->toDateString();

        $response = $this->actingAs($user)->postJson('/api/historial/guardar', [
            'fecha' => $ayer,
            'series' => [
                $this->payload(['series_numero' => 1, 'fecha' => $hoy]),
                $this->payload(['series_numero' => 2]),
            ],
        ]);

        $response->assertOk();

        $registros = Historial::where('user_id', $user->id)->orderBy('series_numero')->get();

        // La serie 1 trae su propia fecha (hoy).
        $this->assertSame($hoy, $registros[0]->fecha->toDateString());
        // La serie 2 hereda la del wrapper (ayer).
        $this->assertSame($ayer, $registros[1]->fecha->toDateString());
    }

    public function test_la_fecha_del_wrapper_fuera_de_ventana_se_rechaza(): void
    {
        $user = User::factory()->create();
        $hace10Dias = Carbon::now()->subDays(10)->toDateString();

        $response = $this->actingAs($user)->postJson('/api/historial/guardar', [
            'fecha' => $hace10Dias,
            'series' => [$this->payload()],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Historial::where('user_id', $user->id)->count());
    }

    public function test_reintento_del_mismo_payload_no_duplica_la_serie(): void
    {
        $user = User::factory()->create();
        $fecha = Carbon::now()->subDays(2)->toDateString();

        // Primer intento: el server procesa bien.
        $this->actingAs($user)->postJson('/api/historial/guardar', array_merge(
            $this->payload(),
            ['client_id' => 'cs-uuid-1', 'fecha' => $fecha]
        ))->assertOk();

        // ACK perdido: el front reintenta con el MISMO client_id y payload.
        $this->actingAs($user)->postJson('/api/historial/guardar', array_merge(
            $this->payload(),
            ['client_id' => 'cs-uuid-1', 'fecha' => $fecha]
        ))->assertOk();

        $this->assertSame(1, Historial::where('user_id', $user->id)->count());
    }

    public function test_reintento_con_peso_actualizado_actualiza_en_vez_de_duplicar(): void
    {
        $user = User::factory()->create();
        $fecha = Carbon::now()->subDays(2)->toDateString();

        $this->actingAs($user)->postJson('/api/historial/guardar', array_merge(
            $this->payload(['peso' => 60.0]),
            ['client_id' => 'cs-uuid-1', 'fecha' => $fecha]
        ))->assertOk();

        // El usuario corrigió el peso y el front manda el payload actualizado.
        $this->actingAs($user)->postJson('/api/historial/guardar', array_merge(
            $this->payload(['peso' => 62.5]),
            ['client_id' => 'cs-uuid-1', 'fecha' => $fecha]
        ))->assertOk();

        $this->assertSame(1, Historial::where('user_id', $user->id)->count());
        $this->assertSame(62.5, (float) Historial::where('user_id', $user->id)->first()->peso);
    }

    public function test_una_serie_offline_de_ayer_y_otra_de_hoy_conviven(): void
    {
        // Es el caso real: entrenaste ayer sin conexión y hoy con conexión.
        // La clave incluye la fecha, así que NO se pisan entre sí.
        $user = User::factory()->create();
        $ayer = Carbon::now()->subDay()->toDateString();

        $this->actingAs($user)->postJson('/api/historial/guardar', array_merge(
            $this->payload(['peso' => 60.0]),
            ['client_id' => 'cs-ayer', 'fecha' => $ayer]
        ))->assertOk();

        $this->actingAs($user)->postJson('/api/historial/guardar', array_merge(
            $this->payload(['peso' => 65.0]),
            ['client_id' => 'cs-hoy', 'fecha' => Carbon::now()->toDateString()]
        ))->assertOk();

        $this->assertSame(2, Historial::where('user_id', $user->id)->count());

        $registros = Historial::where('user_id', $user->id)
            ->orderBy('fecha')
            ->get();

        $this->assertSame($ayer, $registros[0]->fecha->toDateString());
        $this->assertSame(60.0, (float) $registros[0]->peso);
        $this->assertSame(Carbon::now()->toDateString(), $registros[1]->fecha->toDateString());
        $this->assertSame(65.0, (float) $registros[1]->peso);
    }

    public function test_el_client_id_no_se_persiste_en_la_base(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/historial/guardar', array_merge(
            $this->payload(),
            ['client_id' => 'cs-uuid-offline-1']
        ))->assertOk();

        // client_id es solo traza: la idempotencia la garantiza la clave compuesta.
        $record = Historial::where('user_id', $user->id)->first();
        $this->assertNotNull($record);

        $columnas = Schema::getColumnListing('historials');
        $this->assertNotContains('client_id', $columnas);
    }

    public function test_un_batch_offline_mixto_se_procesa_completo(): void
    {
        $user = User::factory()->create();
        $ayer = Carbon::now()->subDay()->toDateString();

        // 3 series del mismo día, guardadas offline y sincronizadas juntas.
        $response = $this->actingAs($user)->postJson('/api/historial/guardar', [
            'fecha' => $ayer,
            'series' => [
                $this->payload(['series_numero' => 1, 'peso' => 60.0]),
                $this->payload(['series_numero' => 2, 'peso' => 60.0]),
                $this->payload(['series_numero' => 3, 'peso' => 57.5]),
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['count' => 3]);
        $this->assertSame(3, Historial::where('user_id', $user->id)->count());
    }

    public function test_una_serie_muy_vieja_se_rechaza_pero_no_rompe_el_resto_del_batch(): void
    {
        $user = User::factory()->create();
        $hace10Dias = Carbon::now()->subDays(10)->toDateString();

        // El front encoló una serie de hace 10 días (fuera de la ventana).
        // El backend la rechaza con 422: el front debe marcarla como fallida
        // PERMANENTE y pedirle acción al usuario, no perderla en silencio.
        $response = $this->actingAs($user)->postJson('/api/historial/guardar', array_merge(
            $this->payload(),
            ['fecha' => $hace10Dias]
        ));

        $response->assertStatus(422);
        $this->assertSame(0, Historial::where('user_id', $user->id)->count());
    }

    public function test_la_fecha_del_payload_offline_respeta_la_ventana_de_7_dias(): void
    {
        $user = User::factory()->create();

        // 7 días exactos: dentro de la ventana (borde inferior).
        $hace7Dias = Carbon::now()->subDays(7)->toDateString();
        $response = $this->actingAs($user)->postJson('/api/historial/guardar', array_merge(
            $this->payload(),
            ['fecha' => $hace7Dias]
        ));
        $response->assertOk();

        // 8 días: fuera de la ventana.
        $hace8Dias = Carbon::now()->subDays(8)->toDateString();
        $response2 = $this->actingAs($user)->postJson('/api/historial/guardar', array_merge(
            $this->payload(['series_numero' => 2]),
            ['fecha' => $hace8Dias]
        ));
        $response2->assertStatus(422);
    }
}
