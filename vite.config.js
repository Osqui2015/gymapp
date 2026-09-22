import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

// SVG primitives que se escriben en camelCase pero NO son componentes Vue.
// Sin esta whitelist, Vue compiler tira warning "Failed to resolve component"
// porque intenta matchearlos como componentes. Esto pasaba con <lineargradient>
// (typo de <linearGradient>) en DashboardContent.vue.
//
// Cubrimos los más usados para no volver a caer en el mismo bug.
const svgCustomElements = [
    'linearGradient',
    'radialGradient',
    'stop',
    'pattern',
    'clipPath',
    'mask',
    'filter',
    'feGaussianBlur',
    'feOffset',
    'feMerge',
    'feMergeNode',
    'feBlend',
    'feColorMatrix',
    'feComponentTransfer',
    'feComposite',
    'feConvolveMatrix',
    'feDiffuseLighting',
    'feDisplacementMap',
    'feDistantLight',
    'feFlood',
    'feFuncA',
    'feFuncB',
    'feFuncG',
    'feFuncR',
    'feImage',
    'feMorphology',
    'fePointLight',
    'feSpecularLighting',
    'feSpotLight',
    'feTile',
    'feTurbulence',
    'textPath',
    'animate',
    'animateTransform',
    'animateMotion',
];

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                compilerOptions: {
                    isCustomElement: (tag) => svgCustomElements.includes(tag),
                },
            },
        }),
    ],
    // Forzar IPv4 (127.0.0.1) para que el navegador no resuelva `localhost`
    // a IPv6 ([::1]). Si el browser pide http://[::1]:5173/... el CSP no
    // lo va a matchear aunque tengamos `localhost:*` y `127.0.0.1:*`
    // porque [::1] es una dirección IPv6 distinta.
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
        },
        cors: {
            origin: ['http://127.0.0.1:8000', 'http://localhost:8000'],
        },
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
            '@': '/resources/js',
        },
    },
    build: {
        modulePreload: {
            polyfill: false,
        },
        rollupOptions: {
            output: {
                // Separa vendors en chunks cacheables por separado.
                // El navegador cachea vue+pinia por ~1 año; al cambiar app code
                // no se re-descargan los vendors. También reduce el tamaño
                // percibido de app.js en el initial load.
                //
                // IMPORTANTE: para libs cargadas dinámicamente (jspdf,
                // laravel-echo, etc.) usamos nombres específicos para que NO
                // terminen en un chunk compartido eager con todos los entries.
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('alpinejs')) return 'vendor-alpine';
                    if (id.includes('axios')) return 'vendor-axios';
                    if (id.includes('pinia') || id.includes('@vue/') || /\/vue\//.test(id)) return 'vendor-vue';
                    if (id.includes('chart.js')) return 'vendor-chart';
                    if (id.includes('canvas-confetti')) return 'vendor-confetti';
                    // jspdf, jspdf-autotable, laravel-echo, pusher-js: los dejamos
                    // en chunks separados por defecto (no los metemos en un
                    // vendor-X compartido, eso los acopla estáticamente al grafo).
                    return undefined;
                },
            },
        },
    },
});
