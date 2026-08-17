<?php

namespace Tests\Feature\Policies;

use App\Models\Meta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MetaPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_do_anything(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $meta = $this->makeMeta(['user_id' => 999]);
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('viewAny', Meta::class));
        $this->assertTrue(Gate::allows('view', $meta));
        $this->assertTrue(Gate::allows('create', Meta::class));
        $this->assertTrue(Gate::allows('update', $meta));
        $this->assertTrue(Gate::allows('delete', $meta));
    }

    public function test_alumno_can_view_own_meta(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $meta = $this->makeMeta(['user_id' => $alumno->id]);
        $this->actingAs($alumno);

        $this->assertTrue(Gate::allows('view', $meta));
    }

    public function test_alumno_cannot_view_others_meta(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $meta = $this->makeMeta(['user_id' => $other->id]);
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('view', $meta));
    }

    public function test_trainer_can_view_alumno_meta(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $meta = $this->makeMeta(['user_id' => $alumno->id]);
        $this->actingAs($trainer);

        $this->assertTrue(Gate::allows('view', $meta));
    }

    public function test_alumno_can_create_meta(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $this->actingAs($alumno);

        $this->assertTrue(Gate::allows('create', Meta::class));
    }

    public function test_alumno_can_update_own_meta(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $meta = $this->makeMeta(['user_id' => $alumno->id]);
        $this->actingAs($alumno);

        $this->assertTrue(Gate::allows('update', $meta));
        $this->assertTrue(Gate::allows('delete', $meta));
    }

    public function test_alumno_cannot_update_others_meta(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $meta = $this->makeMeta(['user_id' => $other->id]);
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('update', $meta));
        $this->assertFalse(Gate::allows('delete', $meta));
    }

    public function test_trainer_cannot_update_alumno_meta(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $meta = $this->makeMeta(['user_id' => $alumno->id]);
        $this->actingAs($trainer);

        // Las metas son del alumno, el trainer NO las edita
        $this->assertFalse(Gate::allows('update', $meta));
        $this->assertFalse(Gate::allows('delete', $meta));
    }

    private function makeMeta(array $overrides = []): Meta
    {
        $m = new Meta();
        $m->setRawAttributes(array_merge([
            'user_id' => 1,
            'tipo' => 'otro',
            'descripcion' => 'Test',
            'valor_objetivo' => 1,
            'completada' => false,
        ], $overrides));
        return $m;
    }
}
