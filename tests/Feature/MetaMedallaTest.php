<?php

namespace Tests\Feature;

use App\Models\Historial;
use App\Models\MedallaUsuario;
use App\Models\Meta;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaMedallaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_meta(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        $response = $this->actingAs($user)->postJson('/api/metas', [
            'tipo' => 'peso_corporal',
            'descripcion' => 'Llegar a 70kg',
            'valor_objetivo' => 70,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('meta.user_id', $user->id)
            ->assertJsonPath('meta.completada', false);

        $this->assertDatabaseHas('metas', [
            'user_id' => $user->id,
            'tipo' => 'peso_corporal',
        ]);
    }

    public function test_user_can_toggle_meta_completada(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $meta = Meta::create([
            'user_id' => $user->id,
            'tipo' => 'otro',
            'descripcion' => 'Hacer 50 flexiones',
            'valor_objetivo' => 50,
        ]);

        $response = $this->actingAs($user)->postJson("/api/metas/{$meta->id}/completar");
        $response->assertStatus(200)
            ->assertJsonPath('meta.completada', true);
    }

    public function test_completing_first_goal_unlocks_meta_alcanzada_medal(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $meta = Meta::create([
            'user_id' => $user->id,
            'tipo' => 'otro',
            'descripcion' => 'Meta test',
            'valor_objetivo' => 1,
        ]);

        $response = $this->actingAs($user)->postJson("/api/metas/{$meta->id}/completar");

        $this->assertDatabaseHas('medallas_usuario', [
            'user_id' => $user->id,
            'slug' => 'meta_alcanzada',
        ]);
    }

    public function test_user_can_delete_own_meta(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        $meta = Meta::create([
            'user_id' => $user->id,
            'tipo' => 'otro',
            'descripcion' => 'Borrar',
            'valor_objetivo' => 1,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/metas/{$meta->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('metas', ['id' => $meta->id]);
    }

    public function test_user_cannot_view_others_metas(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_COMUN]);
        $other = User::factory()->create(['role' => User::ROLE_COMUN]);
        Meta::create([
            'user_id' => $owner->id,
            'tipo' => 'otro',
            'descripcion' => 'Privada',
            'valor_objetivo' => 1,
        ]);

        $response = $this->actingAs($other)->getJson('/api/metas');
        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    public function test_logros_endpoint_returns_medals_with_progress(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);
        Historial::create([
            'user_id' => $user->id,
            'rutina_nombre' => 'Test',
            'dia' => 'Día 1',
            'ejercicio_nombre' => 'Sentadilla',
            'series_numero' => 1,
            'reps_min' => '5',
            'reps_max' => '10',
            'descanso_min' => 1,
            'fecha' => now()->toDateString(),
            'completado' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/logros');
        $response->assertStatus(200)
            ->assertJsonStructure(['logros', 'stats']);
    }

    public function test_achievement_service_calculates_streak(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COMUN]);

        // 3 días consecutivos
        foreach ([1, 2, 3] as $daysAgo) {
            Historial::create([
                'user_id' => $user->id,
                'rutina_nombre' => 'Test',
                'dia' => 'Día 1',
                'ejercicio_nombre' => 'Sentadilla',
                'series_numero' => 1,
                'reps_min' => '5',
                'reps_max' => '10',
                'descanso_min' => 1,
                'fecha' => now()->subDays($daysAgo)->toDateString(),
                'completado' => true,
            ]);
        }

        $stats = AchievementService::getAchievementsStats($user);
        $this->assertEquals(3, $stats['streak']);
        $this->assertEquals(3, $stats['unique_days']);
    }
}
