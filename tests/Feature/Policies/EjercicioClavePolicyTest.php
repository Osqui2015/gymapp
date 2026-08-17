<?php

namespace Tests\Feature\Policies;

use App\Models\EjercicioClave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class EjercicioClavePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_any(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('viewAny', EjercicioClave::class));
    }

    public function test_alumno_can_view_own(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $ec = $this->makeEjercicioClave(['user_id' => $alumno->id]);
        $this->actingAs($alumno);

        $this->assertTrue(Gate::allows('view', $ec));
    }

    public function test_alumno_cannot_view_others(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $ec = $this->makeEjercicioClave(['user_id' => $other->id]);
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('view', $ec));
    }

    public function test_trainer_can_view_their_alumnos(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $ec = $this->makeEjercicioClave([
            'user_id' => $alumno->id,
            'trainer_id' => $trainer->id,
        ]);
        $this->actingAs($trainer);

        $this->assertTrue(Gate::allows('view', $ec));
    }

    public function test_trainer_cannot_view_unassigned_alumnos(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $otherTrainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $otherTrainer->id,
        ]);
        $ec = $this->makeEjercicioClave([
            'user_id' => $alumno->id,
            'trainer_id' => $otherTrainer->id,
        ]);
        $this->actingAs($trainer);

        $this->assertFalse(Gate::allows('view', $ec));
    }

    public function test_trainer_can_create_for_own_alumno(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $this->actingAs($trainer);

        $this->assertTrue(Gate::allows('create', [EjercicioClave::class, $alumno->id]));
    }

    public function test_trainer_cannot_create_for_others_alumno(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $otherAlumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $this->actingAs($trainer);

        $this->assertFalse(Gate::allows('create', [EjercicioClave::class, $otherAlumno->id]));
    }

    public function test_trainer_can_update_own(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $ec = $this->makeEjercicioClave(['trainer_id' => $trainer->id]);
        $this->actingAs($trainer);

        $this->assertTrue(Gate::allows('update', $ec));
        $this->assertTrue(Gate::allows('delete', $ec));
    }

    public function test_trainer_cannot_update_others(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $otherTrainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $ec = $this->makeEjercicioClave(['trainer_id' => $otherTrainer->id]);
        $this->actingAs($trainer);

        $this->assertFalse(Gate::allows('update', $ec));
        $this->assertFalse(Gate::allows('delete', $ec));
    }

    private function makeEjercicioClave(array $overrides = []): EjercicioClave
    {
        $e = new EjercicioClave();
        $e->setRawAttributes(array_merge([
            'user_id' => 1,
            'trainer_id' => 1,
            'ejercicio_nombre' => 'Test Ejercicio',
            'notas_trainer' => 'Notas',
        ], $overrides));
        return $e;
    }
}
