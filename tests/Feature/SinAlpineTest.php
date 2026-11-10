<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tanda 3 / F5 — Alpine.js eliminado del bundle inicial.
 *
 * La navbar, la bottom nav mobile y el modal de borrado de cuenta pasaron de
 * Blade+Alpine a componentes Vue. Estos tests son la red de seguridad:
 * si alguien reintroduce directivas `x-*` o `window.Alpine`, el CI falla.
 *
 * Contexto de por qué:
 *   Alpine ocupaba 44,8 KB del bundle INICIAL (16 KB gzip) solo para manejar
 *   4 booleanos de apertura de menu. Vue ya estaba en el proyecto, asi que
 *   Alpine era una dependencia completa y redundante.
 */
class SinAlpineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Devuelve todos los archivos Blade del proyecto.
     *
     * @return array<int, string>
     */
    private function bladeFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Limpia el contenido de un .blade.php para poder escanearlo.
     *
     * - Quita los comentarios Blade `{{-- ... --}}` (los comentarios mencionan
     *   `x-data` al explicar la historia de la migracion, pero no son codigo).
     * - Quita los comentarios HTML `<!-- ... -->`.
     * - Quita los docblocks PHP `/** ... *\/` (mismo caso: cuentan la historia).
     */
    private function bladeCode(string $contents): string
    {
        $code = preg_replace('/\{\{--.*?--\}\}/s', '', $contents);
        $code = preg_replace('/<!--.*?-->/s', '', (string) $code);
        $code = preg_replace('#/\*\*.*?\*/#s', '', (string) $code);

        return (string) $code;
    }

    /**
     * Directivas REALES de Alpine.
     *
     * Se listan una por una (y no como `x-xxx`) porque `x-text-input`,
     * `x-primary-button`, etc. son componentes Blade PROPIOS del proyecto y no
     * tienen nada que ver con Alpine.
     */
    private const DIRECTIVAS_ALPINE = [
        'x-data',
        'x-show',
        'x-on',
        'x-transition',
        'x-model',
        'x-bind',
        'x-cloak',
        'x-ref',
        'x-if',
        'x-init',
        'x-teleport',
        'x-html',
        'x-text',
        'x-effect',
        'x-for',
        'x-ignore',
    ];

    public function test_ninguna_vista_blade_usa_directivas_de_alpine(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            $code = $this->bladeCode(file_get_contents($file));

            foreach (self::DIRECTIVAS_ALPINE as $directiva) {
                // `x-text` debe matchear `x-text=` o `x-text:` o ` x-text `,
                // pero NO `x-text-input`. Por eso miramos el caracter que
                // sigue a la directiva.
                if (preg_match('/(?<![\w-])'.preg_quote($directiva, '/').'(?![\w-])/', $code)) {
                    $offenders[] = basename($file).' -> '.$directiva;
                    break;
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Hay directivas de Alpine en vistas Blade. Alpine fue eliminado del proyecto: '
                .implode(', ', $offenders)
        );
    }

    public function test_ninguna_vista_blade_usa_las_modificadores_away_de_alpine(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            $contents = file_get_contents($file);

            if (preg_match('/@(click|keydown)\.away\b/', $contents, $m)) {
                $offenders[] = basename($file).' -> '.$m[0];
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Hay modificadores @click.away / @keydown.away (Alpine). '
                .implode(', ', $offenders)
        );
    }

    public function test_no_se_importa_alpine_en_el_bundle_de_js(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        $this->assertStringNotContainsString(
            'alpinejs',
            $appJs,
            'resources/js/app.js todavia importa Alpine. Alpine fue eliminado del proyecto.'
        );

        $this->assertStringNotContainsString(
            'Alpine.start()',
            $appJs,
            'resources/js/app.js todavia arranca Alpine.'
        );
    }

    public function test_alpine_no_esta_en_las_dependencias_de_package_json(): void
    {
        $package = json_decode(file_get_contents(base_path('package.json')), true);

        $deps = array_merge(
            $package['dependencies'] ?? [],
            $package['devDependencies'] ?? []
        );

        $this->assertArrayNotHasKey(
            'alpinejs',
            $deps,
            'alpinejs sigue en package.json. Alpine fue eliminado del proyecto.'
        );
    }

    public function test_la_navegacion_se_renderiza_con_el_componente_vue(): void
    {
        $user = User::factory()->conMembresia()->create(['role' => 'alumno']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('<app-navigation', false);
    }

    /**
     * REGRESION: la navbar y la bottom nav viven FUERA de #app.
     *
     * La app Vue de pagina se monta sobre el primer custom element dentro de
     * #app, asi que los componentes globales que estan fuera (navbar, bottom
     * nav) NO se montan solos: necesitan su propio punto de montaje.
     *
     * Sin esto, la navbar desaparecia en TODAS las paginas y no habia ningun
     * error en consola (simplemente nunca se montaba).
     */
    public function test_la_navegacion_y_la_bottom_nav_tienen_su_propio_mount_point(): void
    {
        $user = User::factory()->conMembresia()->create(['role' => 'alumno']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('id="navigation-root"', false);
        $response->assertSee('id="mobile-bottom-nav-root"', false);
    }

    /**
     * REGRESION: los props de la navbar llegan como JSON parseable.
     *
     * Vue NO lee los atributos del DOM como props cuando el componente se
     * registra globalmente y se monta sobre markup de Blade:
     *   - `:links="{{ json_encode(...) }}"`  -> llega como STRING (props vacias)
     *   - `v-bind:links='[...]'`            -> Vue lo descarta (no es expresion JS)
     * La unica forma fiable es un unico atributo `data-props` con JSON, que
     * app.js pasa como 2do argumento de createApp(Component, props).
     */
    public function test_el_data_props_de_la_navegacion_es_json_parseable(): void
    {
        $user = User::factory()->create(['role' => 'administrador']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        $html = $response->getContent();

        $this->assertSame(
            1,
            preg_match("/id=\"navigation-root\"[^>]*data-props='([^']*)'/s", $html, $m),
            'No se encontro el atributo data-props en el mount point de la navegacion'
        );

        $props = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);

        $this->assertIsArray($props, 'data-props no es JSON valido');
        $this->assertArrayHasKey('links', $props);
        $this->assertArrayHasKey('trainerLinks', $props);
        $this->assertArrayHasKey('adminLinks', $props);
        $this->assertArrayHasKey('userName', $props);
        $this->assertIsArray($props['links']);
        $this->assertNotEmpty($props['links']);

        // Cada link debe traer label + href para que el menu se renderice.
        foreach ($props['links'] as $link) {
            $this->assertArrayHasKey('label', $link);
            $this->assertArrayHasKey('href', $link);
        }
    }

    public function test_el_mount_point_de_la_navegacion_esta_fuera_del_mount_point_de_la_pagina(): void
    {
        $user = User::factory()->conMembresia()->create(['role' => 'alumno']);

        $response = $this->actingAs($user)->get('/dashboard');

        $html = $response->getContent();

        // #app es el mount point de la app Vue de pagina.
        $posApp = strpos($html, 'id="app"');
        $posNav = strpos($html, 'id="navigation-root"');
        $posBottom = strpos($html, 'id="mobile-bottom-nav-root"');

        $this->assertNotFalse($posApp);
        $this->assertNotFalse($posNav);
        $this->assertNotFalse($posBottom);

        // La navbar tiene que estar FUERA de #app: si estuviera dentro, la
        // app de pagina la pisaria al montarse.
        $cierreApp = strpos($html, '</main>', $posApp);
        $this->assertLessThan(
            $cierreApp,
            $posNav,
            'La navbar quedo DENTRO de #app: la app de pagina la sobreescribiria'
        );
        $this->assertGreaterThan(
            $cierreApp,
            $posBottom,
            'La bottom nav quedo DENTRO de #app: la app de pagina la sobreescribiria'
        );
    }

    public function test_el_menu_de_usuario_tiene_los_datos_del_usuario(): void
    {
        $user = User::factory()->conMembresia()->create([
            'name' => 'Oscar Guerrero',
            'email' => 'oscar@test.com',
            'role' => 'alumno',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Oscar Guerrero', false);
        $response->assertSee('oscar@test.com', false);
        // El token CSRF se pasa como prop al componente Vue (dentro de data-props).
        $response->assertSee('data-props=', false);
        $response->assertSee('csrfToken', false);
    }

    public function test_la_bottom_nav_se_renderiza_con_el_componente_vue(): void
    {
        $user = User::factory()->conMembresia()->create(['role' => 'alumno']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('<mobile-bottom-nav', false);
    }

    public function test_la_navegacion_pasa_los_links_por_prop_como_json(): void
    {
        $user = User::factory()->conMembresia()->create(['role' => 'alumno']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        // Los links llegan al componente Vue como JSON en un unico atributo data-props.
        // Vue NO lee los atributos del DOM como props cuando el componente se registra
        // globalmente y se monta sobre markup de Blade: hay que pasarlos como
        // 2do argumento de createApp(Component, props).
        $response->assertSee('data-props=', false);
        $response->assertSee('&quot;label&quot;', false);
        $response->assertDontSee(':links="', false);
        $response->assertDontSee('v-bind:links=', false);
    }

    public function test_el_admin_ve_los_dropdowns_de_trainer_y_admin(): void
    {
        $user = User::factory()->create(['role' => 'administrador']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        // Los links de trainer y admin llegan en el mismo data-props de la navegacion.
        $response->assertSee('data-props=', false);
        $response->assertSee('&quot;trainerLinks&quot;', false);
        $response->assertSee('&quot;adminLinks&quot;', false);
    }

    public function test_el_modal_de_borrado_de_cuenta_usa_el_componente_vue(): void
    {
        $user = User::factory()->conMembresia()->create(['role' => 'alumno']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('<app-modal', false);
        $response->assertSee('confirm-user-deletion', false);
        // El boton de borrar dispara el evento DOM nativo, no $dispatch de Alpine.
        $response->assertSee("new CustomEvent('open-modal'", false);
    }
}
