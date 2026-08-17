<?php

namespace Tests\Feature\Policies;

use App\Models\Membresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MembresiaPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_do_anything(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $membresia = $this->makeMembresia(['user_id' => 999]);
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('viewAny', Membresia::class));
        $this->assertTrue(Gate::allows('view', $membresia));
        $this->assertTrue(Gate::allows('create', Membresia::class));
        $this->assertTrue(Gate::allows('update', $membresia));
        $this->assertTrue(Gate::allows('renew', $membresia));
        $this->assertTrue(Gate::allows('delete', $membresia));
    }

    public function test_non_admin_cannot_view_any(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('viewAny', Membresia::class));
    }

    public function test_alumno_can_view_own_membresia(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $membresia = $this->makeMembresia(['user_id' => $alumno->id]);
        $this->actingAs($alumno);

        $this->assertTrue(Gate::allows('view', $membresia));
    }

    public function test_alumno_cannot_view_others_membresia(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $membresia = $this->makeMembresia(['user_id' => $other->id]);
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('view', $membresia));
    }

    public function test_alumno_cannot_create_or_modify(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $membresia = $this->makeMembresia(['user_id' => $alumno->id]);
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('create', Membresia::class));
        $this->assertFalse(Gate::allows('update', $membresia));
        $this->assertFalse(Gate::allows('renew', $membresia));
        $this->assertFalse(Gate::allows('delete', $membresia));
    }

    private function makeMembresia(array $overrides = []): Membresia
    {
        $m = new Membresia();
        $m->setRawAttributes(array_merge([
            'user_id' => 1,
            'plan' => 'mensual',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonth()->toDateString(),
            'estado' => 'activa',
        ], $overrides));
        return $m;
    }
}
