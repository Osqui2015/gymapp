<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tanda 3 / T3-B â€” purga de audit_logs.
 *
 * La tabla `audit_logs` es append-only. Este comando le pone retenciÃ³n.
 *
 * Regla clave: los logs de SEGURIDAD (login_failed, role_changed, etc.) NUNCA
 * se purgan, aunque sean los mÃ¡s antiguos. Perder el rastro de un intento de
 * acceso fallido es justo lo que un audit log no deberÃ­a hacer.
 */
class AuditLogsPurgeTest extends TestCase
{
    use RefreshDatabase;

    private function crearLog(string $action, string $createdAt): int
    {
        // Se inserta directo por query builder porque `created_at` no es
        // fillable en el modelo (Eloquent lo gestiona solo).
        return (int) DB::table('audit_logs')->insertGetId([
            'user_id' => null,
            'action' => $action,
            'description' => 'test',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    public function test_por_defecto_es_dry_run_y_no_borra_nada(): void
    {
        $viejo = $this->crearLog('created', now()->subDays(500)->toDateTimeString());
        $nuevo = $this->crearLog('created', now()->toDateTimeString());

        $this->artisan('audit-logs:purge --days=365')
            ->expectsOutputToContain('Candidatos a borrar      : 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', ['id' => $viejo]);
        $this->assertDatabaseHas('audit_logs', ['id' => $nuevo]);
    }

    public function test_borra_los_logs_viejos_con_prune(): void
    {
        $viejo = $this->crearLog('created', now()->subDays(500)->toDateTimeString());
        $reciente = $this->crearLog('created', now()->subDays(10)->toDateTimeString());

        $this->artisan('audit-logs:purge --days=365 --prune --force')
            ->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $viejo]);
        $this->assertDatabaseHas('audit_logs', ['id' => $reciente]);
    }

    public function test_no_borra_los_logs_dentro_de_la_retencion(): void
    {
        $dentro = $this->crearLog('created', now()->subDays(30)->toDateTimeString());

        $this->artisan('audit-logs:purge --days=365 --prune --force')
            ->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', ['id' => $dentro]);
    }

    public function test_conserva_los_logs_de_seguridad_aunque_sean_viejos(): void
    {
        $loginFallido = $this->crearLog('login_failed', now()->subDays(900)->toDateTimeString());
        $rolCambiado = $this->crearLog('role_changed', now()->subDays(900)->toDateTimeString());
        $logout = $this->crearLog('logout', now()->subDays(900)->toDateTimeString());

        $this->artisan('audit-logs:purge --days=365 --prune --force')
            ->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', ['id' => $loginFallido]);
        $this->assertDatabaseHas('audit_logs', ['id' => $rolCambiado]);
        $this->assertDatabaseHas('audit_logs', ['id' => $logout]);
    }

    public function test_con_keep_security_se_pueden_purgar_tambien_los_de_seguridad(): void
    {
        $loginFallido = $this->crearLog('login_failed', now()->subDays(900)->toDateTimeString());

        $this->artisan('audit-logs:purge --days=365 --prune --force --keep-security')
            ->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $loginFallido]);
    }

    public function test_el_corte_usa_la_retencion_configurada(): void
    {
        // Con 90 dÃ­as, un log de 100 dÃ­as se purga y uno de 80 no.
        $viejo = $this->crearLog('created', now()->subDays(100)->toDateTimeString());
        $reciente = $this->crearLog('created', now()->subDays(80)->toDateTimeString());

        $this->artisan('audit-logs:purge --days=90 --prune --force')
            ->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $viejo]);
        $this->assertDatabaseHas('audit_logs', ['id' => $reciente]);
    }

    public function test_rechaza_un_numero_de_dias_negativo(): void
    {
        $this->artisan('audit-logs:purge --days=-1')
            ->assertExitCode(2);
    }

    public function test_informa_cuando_no_hay_nada_que_purgar(): void
    {
        $this->crearLog('created', now()->toDateTimeString());

        $this->artisan('audit-logs:purge --days=365 --prune --force')
            ->expectsOutputToContain('Nada que purgar')
            ->assertSuccessful();
    }
}
