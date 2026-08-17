<?php

namespace Tests\Feature\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuditLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_audit_logs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $log = $this->makeAuditLog();
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('viewAny', AuditLog::class));
        $this->assertTrue(Gate::allows('view', $log));
    }

    public function test_coordinador_can_view_audit_logs(): void
    {
        $coordinador = User::factory()->create(['role' => 'coordinador']);
        $log = $this->makeAuditLog();
        $this->actingAs($coordinador);

        $this->assertTrue(Gate::allows('viewAny', AuditLog::class));
        $this->assertTrue(Gate::allows('view', $log));
    }

    public function test_alumno_cannot_view_audit_logs(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $log = $this->makeAuditLog();
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('viewAny', AuditLog::class));
        $this->assertFalse(Gate::allows('view', $log));
    }

    public function test_trainer_cannot_view_audit_logs(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $log = $this->makeAuditLog();
        $this->actingAs($trainer);

        $this->assertFalse(Gate::allows('viewAny', AuditLog::class));
        $this->assertFalse(Gate::allows('view', $log));
    }

    public function test_comun_cannot_view_audit_logs(): void
    {
        $comun = User::factory()->create(['role' => User::ROLE_COMUN]);
        $log = $this->makeAuditLog();
        $this->actingAs($comun);

        $this->assertFalse(Gate::allows('viewAny', AuditLog::class));
    }

    private function makeAuditLog(): AuditLog
    {
        $a = new AuditLog();
        $a->setRawAttributes([
            'user_id' => 1,
            'action' => 'test',
            'description' => 'Test log',
        ]);
        return $a;
    }
}
