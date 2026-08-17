import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

/**
 * Configuración de Vitest para tests de frontend (Vue + Pinia + composables).
 *
 * Setup necesario:
 *   npm install -D vitest @vue/test-utils jsdom
 *
 * Luego corré `npm run test` o `npm run test:run` (sin watch).
 *
 * Solo testeamos unidades JS (stores, composables). Los componentes .vue
 * grandes se pueden testear con @vue/test-utils si querés, pero por ahora
 * arrancamos con lo más valioso: los stores, que tienen lógica de negocio
 * real (auth, rutina, toast).
 */
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        globals: true,
        environment: 'jsdom',
        // Tests viven junto al código o en __tests__/ — ambos funcionan.
        include: ['resources/js/**/*.{test,spec}.{js,ts}'],
        setupFiles: ['resources/js/test-setup.js'],
    },
});
