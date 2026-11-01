<?php

namespace Tests\Feature;

use App\Models\Membresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembresiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_membresias(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        Membresia::create([
            'user_id' => $user->id,
            'tipo_plan' => 'mensual',
            'precio' => 1500,
            'fecha_inicio' => now()->subDays(10),
            'fecha_vencimiento' => now()->addDays(20),
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/membresias');
        $response->assertStatus(200)
            ->assertJsonStructure(['membresias', 'stats']);
    }

    public function test_comun_user_cannot_list_all_membresias(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->getJson('/api/admin/membresias');
        $response->assertStatus(403);
    }

    public function test_admin_can_create_membresia(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($admin)->postJson('/api/admin/membresias', [
            'user_id' => $user->id,
            'tipo_plan' => 'mensual',
            'precio' => 1500,
            'fecha_inicio' => now()->toDateString(),
            'fecha_vencimiento' => now()->addMonth()->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('estado', 'activo');

        $this->assertDatabaseHas('membresias', [
            'user_id' => $user->id,
            'tipo_plan' => 'mensual',
        ]);
    }

    public function test_membresia_validates_dates(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($admin)->postJson('/api/admin/membresias', [
            'user_id' => $user->id,
            'tipo_plan' => 'mensual',
            'precio' => 1500,
            'fecha_inicio' => now()->toDateString(),
            'fecha_vencimiento' => now()->subDays(5)->toDateString(), // anterior a inicio
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_vencimiento']);
    }

    public function test_admin_can_renew_membresia(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $membresia = Membresia::create([
            'user_id' => $user->id,
            'tipo_plan' => 'mensual',
            'precio' => 1500,
            'fecha_inicio' => now()->subMonths(2),
            'fecha_vencimiento' => now()->subDays(5),
            'estado' => 'vencido',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/admin/membresias/{$membresia->id}/renew");
        $response->assertStatus(200)
            ->assertJsonPath('estado', 'activo');

        $membresia->refresh();
        $this->assertEquals('activo', $membresia->estado);
        $this->assertTrue($membresia->fecha_vencimiento->isFuture());
    }

    public function test_membresia_activa_helper(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        // Sin membresía
        $this->assertNull($user->getMembresiaActiva());

        // Membresía activa
        Membresia::create([
            'user_id' => $user->id,
            'tipo_plan' => 'mensual',
            'precio' => 1500,
            'fecha_inicio' => now()->subDays(5),
            'fecha_vencimiento' => now()->addDays(25),
            'estado' => 'activo',
        ]);

        $this->assertNotNull($user->getMembresiaActiva());
        $this->assertTrue($user->getPuedoAcceder());
    }
}
