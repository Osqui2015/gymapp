<?php

namespace Tests\Feature\Policies;

use App\Models\MedallaUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MedallaPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_any_medallas(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $medalla = $this->makeMedalla(['user_id' => 999]);
        $this->actingAs($admin);

        $this->assertTrue(Gate::allows('viewAny', MedallaUsuario::class));
        $this->assertTrue(Gate::allows('view', $medalla));
    }

    public function test_user_can_view_own_medallas(): void
    {
        $user = User::factory()->create();
        $medalla = $this->makeMedalla(['user_id' => $user->id]);
        $this->actingAs($user);

        $this->assertTrue(Gate::allows('view', $medalla));
    }

    public function test_user_cannot_view_others_medallas(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $other = User::factory()->create(['role' => User::ROLE_ALUMNO]);
        $medalla = $this->makeMedalla(['user_id' => $other->id]);
        $this->actingAs($user);

        $this->assertFalse(Gate::allows('view', $medalla));
    }

    public function test_trainer_can_view_alumno_medallas(): void
    {
        $trainer = User::factory()->create(['role' => User::ROLE_TRAINER]);
        $alumno = User::factory()->create([
            'role' => User::ROLE_ALUMNO,
            'trainer_id' => $trainer->id,
        ]);
        $medalla = $this->makeMedalla(['user_id' => $alumno->id]);
        $this->actingAs($trainer);

        $this->assertTrue(Gate::allows('view', $medalla));
    }

    private function makeMedalla(array $overrides = []): MedallaUsuario
    {
        $m = new MedallaUsuario();
        $m->setRawAttributes(array_merge([
            'user_id' => 1,
            'medalla' => 'test-medal',
        ], $overrides));
        return $m;
    }
}
