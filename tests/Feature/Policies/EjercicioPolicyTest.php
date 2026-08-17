<?php

namespace Tests\Feature\Policies;

use App\Models\Ejercicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class EjercicioPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_authenticated_user_can_view_ejercicios(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $this->actingAs($user);

        $this->assertTrue(Gate::allows('viewAny', Ejercicio::class));
        $this->assertTrue(Gate::allows('view', $this->makeEjercicio()));
    }

    public function test_admin_can_create_ejercicios(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('create', Ejercicio::class));
        $this->assertTrue(Gate::allows('update', $this->makeEjercicio()));
        $this->assertTrue(Gate::allows('delete', $this->makeEjercicio()));
    }

    public function test_trainer_can_create_ejercicios(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $this->actingAs($trainer);

        $this->assertTrue(Gate::allows('create', Ejercicio::class));
        $this->assertTrue(Gate::allows('update', $this->makeEjercicio()));
        $this->assertTrue(Gate::allows('delete', $this->makeEjercicio()));
    }

    public function test_alumno_cannot_create_ejercicios(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('create', Ejercicio::class));
        $this->assertFalse(Gate::allows('update', $this->makeEjercicio()));
        $this->assertFalse(Gate::allows('delete', $this->makeEjercicio()));
    }

    private function makeEjercicio(array $overrides = []): Ejercicio
    {
        $e = new Ejercicio();
        $e->setRawAttributes(array_merge([
            'nombre' => 'Test',
            'grupo_muscular' => 'pecho',
            'equipamiento' => 'barra',
        ], $overrides));
        return $e;
    }

    public function test_comun_cannot_create_ejercicios(): void
    {
        $comun = User::factory()->create(['role' => User::ROLE_COMUN]);
        $this->actingAs($comun);

        $this->assertFalse(Gate::allows('create', Ejercicio::class));
    }
}
