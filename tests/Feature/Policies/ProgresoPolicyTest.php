<?php

namespace Tests\Feature\Policies;

use App\Models\Progreso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProgresoPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_any_progreso(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $progreso = $this->makeProgreso(['user_id' => 999]);
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('viewAny', Progreso::class));
        $this->assertTrue(Gate::allows('view', $progreso));
    }

    public function test_user_can_view_own_progreso(): void
    {
        $user = User::factory()->create();
        $progreso = $this->makeProgreso(['user_id' => $user->id]);
        $this->actingAs($user);

        $this->assertTrue(Gate::allows('view', $progreso));
        $this->assertTrue(Gate::allows('update', $progreso));
        $this->assertTrue(Gate::allows('delete', $progreso));
    }

    public function test_alumno_cannot_view_others_progreso(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $progreso = $this->makeProgreso(['user_id' => $other->id]);
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('view', $progreso));
    }

    public function test_trainer_can_view_alumno_progreso(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $progreso = $this->makeProgreso(['user_id' => $alumno->id]);
        $this->actingAs($trainer);

        $this->assertTrue(Gate::allows('view', $progreso));
    }

    public function test_trainer_cannot_view_unassigned_alumno_progreso(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
        ]);
        $progreso = $this->makeProgreso(['user_id' => $alumno->id]);
        $this->actingAs($trainer);

        $this->assertFalse(Gate::allows('view', $progreso));
    }

    public function test_anyone_authenticated_can_create_progreso(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $comun = User::factory()->create(['role' => User::ROLE_COMUN]);
        $this->actingAs($alumno);
        $this->assertTrue(Gate::allows('create', Progreso::class));
        $this->actingAs($comun);
        $this->assertTrue(Gate::allows('create', Progreso::class));
    }

    private function makeProgreso(array $overrides = []): Progreso
    {
        $p = new Progreso();
        $p->setRawAttributes(array_merge([
            'user_id' => 1,
            'fecha' => now()->toDateString(),
            'peso' => 80.0,
        ], $overrides));
        return $p;
    }
}
