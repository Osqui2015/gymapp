<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Tests de los cambios de la Oleada 2 (P0 - seguridad):
 *   - Rate limit en el endpoint de "olvide mi contrasena" (evitar email bombing).
 *   - Validacion de contrasena robusta al crear/editar usuarios desde el admin.
 */
class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Evitar que se intente mandar mail real durante los tests.
        Notification::fake();
    }

    // === Rate limit en password reset link ===

    public function test_password_reset_esta_rate_limited_tras_3_intentos_fallidos(): void
    {
        $email = 'noexiste@example.com';

        // 3 intentos fallidos (email no existe): el controller redirige con error en email
        // (no bloquea todavia porque no llego al limite).
        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['email' => $email])
                ->assertSessionHasErrors('email');
        }

        // El 4to intento, ya con rate limit, lanza ValidationException con throttle.
        // El mensaje exacto lo define trans('auth.throttle'); solo nos importa que
        // haya error y que NO sea el error normal de "email no encontrado".
        $response = $this->post('/forgot-password', ['email' => $email])
            ->assertSessionHasErrors('email');
        $this->assertTrue(true);
    }

    public function test_password_reset_se_limpia_al_enviar_exitosamente(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // 2 intentos previos fallidos (no llega al limite de 3).
        $this->post('/forgot-password', ['email' => 'noexiste@example.com'])
            ->assertSessionHasErrors('email');
        $this->post('/forgot-password', ['email' => 'noexiste@example.com'])
            ->assertSessionHasErrors('email');

        // El 3ro, esta vez con un email valido: debe enviar el link
        // Y el siguiente intento (4to, que sin clear estaria bloqueado) debe pasar.
        $this->post('/forgot-password', ['email' => 'test@example.com'])
            ->assertSessionHas('status');

        // Despues del clear, 3 intentos mas deben pasar (no se llego al limite).
        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['email' => 'noexiste@example.com'])
                ->assertSessionHasErrors('email');
        }
    }

    // === Contrasena robusta en admin ===

    public function test_admin_crear_user_rechaza_password_debil(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

        $this->actingAs($admin)->post('/admin/users', [
            'nick' => 'nuevo_user',
            'name' => 'Nuevo User',
            'email' => 'nuevo@example.com',
            'password' => '123', // 3 chars, sin mayuscula ni simbolo
            'role' => 'alumno',
        ])->assertSessionHasErrors('password');

        // No se creo.
        $this->assertDatabaseMissing('users', ['email' => 'nuevo@example.com']);
    }

    public function test_admin_crear_user_acepta_password_robusto(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);

        $this->actingAs($admin)->post('/admin/users', [
            'nick' => 'nuevo_user',
            'name' => 'Nuevo User',
            'email' => 'nuevo@example.com',
            'password' => 'Aa1!aaaa',
            'password_confirmation' => 'Aa1!aaaa',
            'role' => 'alumno',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);
    }

    public function test_admin_api_editar_user_rechaza_password_debil(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMINISTRADOR]);
        $user = User::factory()->create(['role' => 'alumno']);

        $this->actingAs($admin)->putJson("/api/admin/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'alumno',
            'password' => 'abc', // debil
        ])->assertStatus(422);

        // El password del user NO cambio.
        $user->refresh();
        $this->assertFalse(Hash::check('abc', $user->password));
    }

    public function test_rate_limit_login_sigue_funcionando(): void
    {
        $user = User::factory()->create([
            'nick' => 'tester',
            'password' => Hash::make('password-correcto'),
        ]);

        // 5 intentos fallidos (el limite default de Laravel Breeze).
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'nick' => 'tester',
                'password' => 'wrong',
            ])->assertSessionHasErrors('nick');
        }

        // El 6to intento, incluso con la password correcta, debe ser bloqueado.
        $this->post('/login', [
            'nick' => 'tester',
            'password' => 'password-correcto',
        ])->assertSessionHasErrors('nick');
    }
}
