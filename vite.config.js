import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
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
