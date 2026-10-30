<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Rate limiting.
 *
 * Antes de este test la API no tenia NINGUN limite de peticiones: Laravel 12
 * solo agrega `throttle:api` al grupo de middleware `api` si existe un
 * limiter con ese nombre (ver AppServiceProvider::registerRateLimiters y
 * bootstrap/app.php).
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_rutas_de_api_tienen_el_limiter_de_peticiones(): void
    {
        $route = collect(Route::getRoutes())
            ->first(fn ($r) => $r->uri() === 'api/dashboard/today');

        $this->assertNotNull($route, 'no se encontro la ruta api/dashboard/today');

        // gatherMiddleware() no expande los grupos: hay que pasar por el
        // router, que es quien resuelve `api` -> throttle:api.
        $resolved = app('router')->gatherRouteMiddleware($route);

        $this->assertContains(
            ThrottleRequests::class.':api',
            $resolved,
            'las rutas de api no tienen el middleware throttle:api'
        );
    }

    public function test_la_api_publica_limita_las_peticiones_por_ip(): void
    {
        // El limite para anonimos es de 120/min por IP.
        for ($i = 0; $i < 120; $i++) {
            $this->getJson('/api/ejercicios/grupos-musculares')->assertOk();
        }

        $this->getJson('/api/ejercicios/grupos-musculares')->assertStatus(429);
    }

    public function test_el_login_bloquea_despues_de_diez_intentos(): void
    {
        $user = User::factory()->create();

        // 10 intentos fallidos: pasan (el limite es 10/min por nick+IP).
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', [
                'nick' => $user->nick,
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        // El 11vo tiene que rebotar con 429.
        $response = $this->post('/login', [
            'nick' => $user->nick,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
        $this->assertGuest();
    }

    public function test_el_limite_de_login_no_afecta_a_otros_nicks(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', [
                'nick' => $user->nick,
                'password' => 'wrong-password',
            ]);
        }

        // Otro nick desde la misma IP: tiene su propio bucket.
        $this->post('/login', [
            'nick' => 'otro-nick-distinto',
            'password' => 'wrong-password',
        ])->assertRedirect();
    }

    public function test_el_registro_bloquea_despues_de_cinco_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', [
                'name' => 'Test',
                'nick' => 'nick'.$i,
                'email' => 'test'.$i.'@example.com',
                'password' => 'password123',
            ]);
        }

        $this->post('/register', [
            'name' => 'Test',
            'nick' => 'nick999',
            'email' => 'test999@example.com',
            'password' => 'password123',
        ])->assertStatus(429);
    }
}
