<?php

namespace Tests\Feature\Policies;

use App\Models\DiarioNutricion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DiarioNutricionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_any(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $diario = $this->makeDiario(['user_id' => 999]);
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('viewAny', DiarioNutricion::class));
        $this->assertTrue(Gate::allows('view', $diario));
    }

    public function test_user_can_view_own(): void
    {
        $user = User::factory()->create();
        $diario = $this->makeDiario(['user_id' => $user->id]);
        $this->actingAs($user);

        $this->assertTrue(Gate::allows('view', $diario));
        $this->assertTrue(Gate::allows('update', $diario));
        $this->assertTrue(Gate::allows('delete', $diario));
    }

    public function test_alumno_cannot_view_others(): void
    {
        $alumno = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $diario = $this->makeDiario(['user_id' => $other->id]);
        $this->actingAs($alumno);

        $this->assertFalse(Gate::allows('view', $diario));
    }

    public function test_trainer_can_view_alumno_diario(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $diario = $this->makeDiario(['user_id' => $alumno->id]);
        $this->actingAs($trainer);

        $this->assertTrue(Gate::allows('view', $diario));
    }

    private function makeDiario(array $overrides = []): DiarioNutricion
    {
        $d = new DiarioNutricion();
        $d->setRawAttributes(array_merge([
            'user_id' => 1,
            'fecha' => now()->toDateString(),
        ], $overrides));
        return $d;
    }
}
