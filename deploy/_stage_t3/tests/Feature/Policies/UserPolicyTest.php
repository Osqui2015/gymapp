<?php

namespace Tests\Feature\Policies;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_any_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('viewAny', User::class));
    }

    public function test_non_admin_cannot_view_any_user(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('viewAny', User::class));
        $this->actingAs($trainer);
        $this->assertFalse(Gate::allows('viewAny', User::class));
    }

    public function test_user_can_view_themselves(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $this->actingAs($user);

        $this->assertTrue(Gate::allows('view', $user));
    }

    public function test_alumno_cannot_view_other_users(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('view', $other));
    }

    public function test_trainer_can_view_their_alumnos(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $otherAlumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $this->actingAs($trainer);

        $this->assertTrue(Gate::allows('view', $alumno));
        $this->assertFalse(Gate::allows('view', $otherAlumno));
    }

    public function test_admin_can_update_any_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $other = User::factory()->create();
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('update', $other));
    }

    public function test_user_can_update_themselves(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertTrue(Gate::allows('update', $user));
    }

    public function test_user_cannot_update_others(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create();
        $this->actingAs($user);

        $this->assertFalse(Gate::allows('update', $other));
    }

    public function test_only_admin_can_delete(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $other = User::factory()->create();
        $this->actingAs($admin);
        $this->assertTrue(Gate::allows('delete', $other));

        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $this->actingAs($alumno);
        $this->assertFalse(Gate::allows('delete', $other));
    }

    public function test_only_admin_can_suspend(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $target = User::factory()->create();
        $this->actingAs($admin);
        $this->assertTrue(Gate::allows('suspend', $target));

        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $this->actingAs($trainer);
        $this->assertFalse(Gate::allows('suspend', $target));
    }
}
