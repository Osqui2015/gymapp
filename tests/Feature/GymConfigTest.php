<?php

namespace Tests\Feature;

use App\Models\GymConfig;
use App\Models\SchedulerRun;
use App\Services\GymConfigService;
use App\Services\SchedulerRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Test de la configuración del gym (Fase 0.4) y del heartbeat del
 * scheduler (Fase 0.3).
 */
class GymConfigTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // GymConfigService
    // -----------------------------------------------------------------

    public function test_los_defaults_estan_disponibles_sin_configurar_nada(): void
    {
        $config = app(GymConfigService::class);

        $this->assertSame(14, $config->trialDias());
        $this->assertSame(3, $config->graceDays());
        $this->assertSame(7, $config->diasAvisoVencimiento());
        $this->assertSame('ARS', $config->moneda());
        $this->assertSame('America/Argentina/Tucuman', $config->timezone());
    }

    public function test_escribir_un_valor_lo_persiste_e_invalida_el_cache(): void
    {
        $config = app(GymConfigService::class);

        $this->assertSame(14, $config->trialDias());

        $config->set('trial_dias', 30);

        // El service se resuelve de nuevo: si el cache no se hubiera
        // invalidado, seguiría devolviendo 14.
        $this->assertSame(30, app(GymConfigService::class)->trialDias());
        $this->assertDatabaseHas('gym_config', ['key' => 'trial_dias']);
    }

    public function test_un_valor_inexistente_devuelve_el_default(): void
    {
        $config = app(GymConfigService::class);

        $this->assertSame(99, $config->getInt('no_existe', 99));
        $this->assertNull($config->get('no_existe'));
    }

    public function test_remove_restaura_el_default(): void
    {
        $config = app(GymConfigService::class);

        $config->set('trial_dias', 30);
        $this->assertSame(30, app(GymConfigService::class)->trialDias());

        $config->remove('trial_dias');
        $this->assertSame(14, app(GymConfigService::class)->trialDias());
    }

    public function test_cambiar_la_configuracion_queda_auditado(): void
    {
        // El plan (§12.8): la config gobierna el acceso (días de trial,
        // gracia) y el precio. Cambiarla sin dejar rastro es exactamente
        // el tipo de operación que después no se puede reconstruir.
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMINISTRADOR]);

        $this->actingAs($admin);
        app(GymConfigService::class)->set('grace_days', 7);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'gym_config_created',
            'model_type' => GymConfig::class,
        ]);
    }

    public function test_set_many_escribe_varios_valores(): void
    {
        $config = app(GymConfigService::class);

        $config->setMany([
            'trial_dias' => 7,
            'grace_days' => 5,
            'moneda' => 'USD',
        ]);

        $nuevo = app(GymConfigService::class);
        $this->assertSame(7, $nuevo->trialDias());
        $this->assertSame(5, $nuevo->graceDays());
        $this->assertSame('USD', $nuevo->moneda());
    }

    // -----------------------------------------------------------------
    // Heartbeat del scheduler
    // -----------------------------------------------------------------

    public function test_una_tarea_exitosa_registra_el_heartbeat(): void
    {
        $service = app(SchedulerRunService::class);

        $resultado = $service->run('tarea:test', fn () => 'ok');

        $this->assertSame('ok', $resultado);
        $this->assertDatabaseHas('scheduler_runs', [
            'task' => 'tarea:test',
            'status' => SchedulerRun::STATUS_OK,
        ]);
    }

    public function test_una_tarea_fallida_registra_el_error_y_lo_relanza(): void
    {
        $service = app(SchedulerRunService::class);

        try {
            $service->run('tarea:falla', function () {
                throw new \RuntimeException('boom');
            });

            $this->fail('La excepcion deberia haberse relanzada.');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        // El fallo queda registrado...
        $this->assertDatabaseHas('scheduler_runs', [
            'task' => 'tarea:falla',
            'status' => SchedulerRun::STATUS_FAIL,
        ]);

        // ...y la excepción NO se traga: una tarea que falla tiene que
        // verse en el log del server.
    }

    public function test_el_estado_reporta_una_tarea_que_nunca_corrio(): void
    {
        $estado = app(SchedulerRunService::class)->estado(['tarea:nunca']);

        $this->assertCount(1, $estado);
        $this->assertSame('nunca', $estado[0]['status']);
        $this->assertFalse($estado[0]['ok']);
        $this->assertStringContainsString('cron', $estado[0]['detalle']);
    }

    public function test_el_estado_reporta_una_tarea_reciente_como_ok(): void
    {
        app(SchedulerRunService::class)->run('tarea:ok', fn () => null);

        $estado = app(SchedulerRunService::class)->estado(['tarea:ok']);

        $this->assertTrue($estado[0]['ok']);
        $this->assertSame('ok', $estado[0]['status']);
    }

    public function test_el_estado_reporta_una_tarea_que_no_corre_hace_mucho_como_falla(): void
    {
        // Un heartbeat de hace 48 h en una tarea diaria = el cron no
        // está corriendo. Esto es lo que avisa la tira de estado del Hub.
        SchedulerRun::create([
            'task' => 'tarea:vieja',
            'status' => SchedulerRun::STATUS_OK,
            'finished_at' => now()->subHours(48),
        ]);

        $estado = app(SchedulerRunService::class)->estado(['tarea:vieja']);

        $this->assertFalse($estado[0]['ok']);
        $this->assertStringContainsString('Sin correr hace', $estado[0]['detalle']);
    }

    public function test_la_purga_borra_las_ejecuciones_viejas(): void
    {
        SchedulerRun::create([
            'task' => 'tarea:vieja',
            'status' => SchedulerRun::STATUS_OK,
            'finished_at' => now()->subDays(60),
            'created_at' => now()->subDays(60),
        ]);

        SchedulerRun::create([
            'task' => 'tarea:nueva',
            'status' => SchedulerRun::STATUS_OK,
            'finished_at' => now(),
        ]);

        $borradas = app(SchedulerRunService::class)->purgar(30);

        $this->assertSame(1, $borradas);
        $this->assertDatabaseMissing('scheduler_runs', ['task' => 'tarea:vieja']);
        $this->assertDatabaseHas('scheduler_runs', ['task' => 'tarea:nueva']);
    }
}
