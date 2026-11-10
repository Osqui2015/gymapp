<?php

namespace Tests\Feature;

use App\Models\Membresia;
use App\Models\User;
use App\Services\GymConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test de la regla de acceso y del trial (Fase 0.5 / 0.6).
 *
 * CONTEXTO
 * --------
 * Antes de la Fase 0, `CheckMembership` tenía la política de "registro
 * gratuito": si el usuario NUNCA tuvo membresía, lo dejaba pasar. Con la
 * tabla `membresias` vacía (0 filas), los 8 usuarios `comun` operaban sin
 * pagar y los morosos nunca se registraban (no había fila que venza).
 *
 * La Fase 0 activa la regla real: "todo usuario necesita membresía o
 * trial", con excepciones para staff y para las membresías de cortesía.
 */
class MembershipAccessTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------
    // Trial
    // -----------------------------------------------------------------

    public function test_un_trial_en_curso_da_acceso(): void
    {
        $user = User::factory()->conTrial(14)->create(['role' => User::ROLE_ALUMNO]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_un_trial_vencido_no_da_acceso(): void
    {
        $grace = app(GymConfigService::class)->graceDays();

        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        // Vencido FUERA del período de gracia: un trial que venció hace
        // un día todavía está dentro de la gracia (3 días por default).
        $vencio = now()->subDays($grace + 2);

        Membresia::factory()->create([
            'user_id' => $user->id,
            'es_trial' => true,
            'trial_ends_at' => $vencio->toDateString(),
            'fecha_vencimiento' => $vencio->toDateString(),
            'estado' => 'vencido',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('membresia.vencida'));
    }

    public function test_un_trial_vencido_dentro_de_la_gracia_aun_da_acceso(): void
    {
        $grace = app(GymConfigService::class)->graceDays();

        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        $vencio = now()->subDays(max(0, $grace - 1));

        Membresia::factory()->create([
            'user_id' => $user->id,
            'es_trial' => true,
            'trial_ends_at' => $vencio->toDateString(),
            'fecha_vencimiento' => $vencio->toDateString(),
            'estado' => 'vencido',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    // -----------------------------------------------------------------
    // Membresía de cortesía (sin vencimiento)
    // -----------------------------------------------------------------

    public function test_la_membresia_de_cortesia_da_acceso_permanente(): void
    {
        // Es el backfill de la Fase 0.5: los 8 usuarios `comun` (amigos
        // del owner) NO deben quedar bloqueados de golpe.
        $user = User::factory()->conCortesia()->create(['role' => User::ROLE_COMUN]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_la_membresia_de_cortesia_es_una_membresia_real(): void
    {
        // La app tiene que distinguir "tiene membresía" de "no tiene".
        $user = User::factory()->conCortesia()->create(['role' => User::ROLE_COMUN]);

        $this->assertNotNull($user->getMembresiaActiva());
        $this->assertTrue($user->getMembresiaActiva()->esCortesia());
        $this->assertFalse($user->getEstaVencido());
    }

    // -----------------------------------------------------------------
    // Vencimiento y período de gracia
    // -----------------------------------------------------------------

    public function test_una_membresia_vencida_dentro_del_periodo_de_gracia_da_acceso(): void
    {
        $grace = app(GymConfigService::class)->graceDays();

        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        Membresia::factory()->create([
            'user_id' => $user->id,
            'fecha_vencimiento' => now()->subDays(max(0, $grace - 1))->toDateString(),
            'estado' => 'vencido',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_una_membresia_vencida_fuera_del_periodo_de_gracia_bloquea(): void
    {
        $grace = app(GymConfigService::class)->graceDays();

        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        Membresia::factory()->create([
            'user_id' => $user->id,
            'fecha_vencimiento' => now()->subDays($grace + 2)->toDateString(),
            'estado' => 'vencido',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('membresia.vencida'));
    }

    public function test_el_bloqueo_en_la_api_devuelve_403_con_el_error_correcto(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        Membresia::factory()->vencida()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/user-info');

        $response->assertStatus(403);
        $response->assertJsonPath('error', 'membership_expired');
    }

    // -----------------------------------------------------------------
    // Sin membresía: ahora se bloquea (antes pasaba)
    // -----------------------------------------------------------------

    public function test_un_usuario_sin_membresia_ya_no_entra_al_dashboard(): void
    {
        // Este es el CAMBIO DE COMPORTAMIENTO de la Fase 0.6.
        // Antes: la política de "registro gratuito" lo dejaba pasar.
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('membresia.vencida'));
    }

    // -----------------------------------------------------------------
    // Staff: siempre accede
    // -----------------------------------------------------------------

    public function test_admin_y_trainer_acceden_sin_membresia(): void
    {
        foreach ([User::ROLE_ADMINISTRADOR, User::ROLE_TRAINER] as $rol) {
            $user = User::factory()->create(['role' => $rol]);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk("El rol '{$rol}' debería acceder sin membresía.");
        }
    }

    // -----------------------------------------------------------------
    // La pantalla de renovación
    // -----------------------------------------------------------------

    public function test_la_pantalla_de_renovacion_muestra_el_plan_vencido(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ALUMNO]);

        Membresia::factory()->vencida()->create([
            'user_id' => $user->id,
            'tipo_plan' => 'trimestral',
            'precio' => 75000,
        ]);

        $this->actingAs($user)
            ->get(route('membresia.vencida'))
            ->assertOk()
            ->assertSee('Membresía Vencida')
            ->assertSee('Trimestral');
    }

    // -----------------------------------------------------------------
    // Estados de la membresía
    // -----------------------------------------------------------------

    public function test_actualizar_estados_no_toca_las_membresias_de_cortesia(): void
    {
        $cortesia = Membresia::factory()->cortesia()->create();

        Membresia::actualizarEstados();

        $cortesia->refresh();
        $this->assertSame('activo', $cortesia->estado);
        $this->assertNull($cortesia->fecha_vencimiento);
    }

    public function test_actualizar_estados_marca_vencidas_y_por_vencer(): void
    {
        $vencida = Membresia::factory()->create([
            'fecha_vencimiento' => now()->subDay()->toDateString(),
            'estado' => 'activo',
        ]);

        $porVencer = Membresia::factory()->create([
            'fecha_vencimiento' => now()->addDays(3)->toDateString(),
            'estado' => 'activo',
        ]);

        Membresia::actualizarEstados();

        $this->assertSame('vencido', $vencida->refresh()->estado);
        $this->assertSame('por_vencer', $porVencer->refresh()->estado);
    }
}
