<!--
  EJEMPLO de uso de vue-i18n.
  
  Este componente NO está registrado en app.js, es solo un ejemplo de patrón
  para que veas cómo usar t() y locale en tus componentes.
  
  Para activarlo:
    1) npm install vue-i18n@9
    2) En resources/js/app.js, importá y usá i18n:
         import { i18n } from './i18n';
         app.use(i18n);
    3) Registrá este componente en el map de app.js (lazy import)
    4) Usalo en una blade view con <i18n-example></i18n-example>
-->
<template>
    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
        <h2 class="text-lg font-bold mb-3">
            {{ t('dashboard.welcome', { name: 'Oscar' }) }}
        </h2>

        <div class="flex gap-2 mb-4">
            <button
                v-for="l in SUPPORTED_LOCALES"
                :key="l.code"
                @click="setLocale(l.code)"
                :class="[
                    'px-3 py-1 text-sm rounded transition-colors',
                    locale === l.code
                        ? 'bg-indigo-600 text-white'
                        : 'bg-gray-200 dark:bg-gray-700 hover:bg-gray-300'
                ]"
            >
                {{ l.label }}
            </button>
        </div>

        <div class="space-y-2 text-sm">
            <p><strong>{{ t('common.save') }}:</strong> {{ t('common.save') }}</p>
            <p><strong>{{ t('common.cancel') }}:</strong> {{ t('common.cancel') }}</p>
            <p><strong>{{ t('auth.login') }}:</strong> {{ t('auth.login') }}</p>
            <p><strong>{{ t('errors.network') }}:</strong> {{ t('errors.network') }}</p>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { setLocale, SUPPORTED_LOCALES } from '../i18n';

const { t, locale } = useI18n();
</script>
