import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

export default [
    {
        ignores: [
            'node_modules/**',
            'vendor/**',
            'public/**',
            'storage/**',
            'bootstrap/**',
            'dist/**',
            'scripts/**',
            'yo/**',
            // Paquetes de deploy locales (build compilado para subir al server).
            'deploy/**',
        ],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/essential'],
    {
        files: ['resources/js/**/*.{js,vue}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                axios: 'readonly',
                route: 'readonly',
                Echo: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
            'vue/no-v-html': 'off',
            'no-undef': 'error',
        },
    },
    {
        // Scripts de Node (CommonJS): necesitan los globals de Node
        // (require, module, process, Buffer, console...).
        // Antes no se declaraban y ESLint reportaba 26 falsos "no-undef".
        files: ['**/*.cjs'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'commonjs',
            globals: {
                ...globals.node,
            },
        },
    },
];
