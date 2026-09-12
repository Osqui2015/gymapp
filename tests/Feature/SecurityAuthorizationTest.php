<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_no_puede_ver_timeline_de_alumno_ajeno(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $otroTrainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumnoAjeno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $otroTrainer->id,
        ]);

        $response = $this->actingAs($trainer)->getJson("/api/trainer/alumnos/{$alumnoAjeno->id}/timeline");
        $response->assertStatus(403)
            ->assertJson(['error' => 'No tienes acceso a este alumno']);
    }

    public function test_trainer_si_puede_ver_timeline_de_alumno_asignado(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);

        $response = $this->actingAs($trainer)->getJson("/api/trainer/alumnos/{$alumno->id}/timeline");
        $response->assertStatus(200)
            ->assertJsonStructure(['alumno', 'eventos']);
    }

    public function test_admin_puede_ver_timeline_de_cualquier_alumno(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $otroTrainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $otroTrainer->id,
        ]);

        $response = $this->actingAs($admin)->getJson("/api/trainer/alumnos/{$alumno->id}/timeline");
        $response->assertStatus(200);
    }

    public function test_trainer_no_puede_ver_comentarios_de_alumno_ajeno(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $otroTrainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumnoAjeno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $otroTrainer->id,
        ]);

        $response = $this->actingAs($trainer)->getJson("/api/trainer-comments?alumno_id={$alumnoAjeno->id}");
        $response->assertStatus(403);
    }

    public function test_trainer_no_puede_comentar_a_alumno_ajeno(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $otroTrainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumnoAjeno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $otroTrainer->id,
        ]);

        $response = $this->actingAs($trainer)->postJson('/api/trainer-comments', [
            'alumno_id' => $alumnoAjeno->id,
            'body' => 'test',
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_puede_ver_alumno_via_dashboard(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        $response = $this->actingAs($admin)->getJson("/api/trainer/alumno/{$alumno->id}");
        $response->assertStatus(200);
    }

    public function test_alumno_suspendido_no_puede_autenticarse_y_recibe_motivo(): void
    {
        $motivo = 'Falta de pago desde julio';
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'suspended' => true,
            'motivo_suspension' => $motivo,
        ]);

        $response = $this->post('/login', [
            'nick' => $alumno->nick,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('nick');
        $errors = session('errors')->get('nick');
        $this->assertStringContainsString($motivo, $errors[0]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_attempt_suspended',
        ]);
    }

    public function test_login_fallido_registra_audit_log_con_ip_y_user_agent(): void
    {
        $this->post('/login', [
            'nick' => 'usuario_inexistente',
            'password' => 'mal_pass',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_failed',
        ]);

        $log = AuditLog::where('action', 'login_failed')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('usuario_inexistente', $log->new_values['nick']);
    }

    public function test_cabeceras_de_seguridad_presentes_en_respuesta_web(): void
    {
        $response = $this->get('/login');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    public function test_cabeceras_de_seguridad_presentes_en_api(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $response = $this->actingAs($user)->getJson('/api/user');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
