<!--
  ExternalVideoLink — Muestra un link externo al video original en Facebook/YouTube/etc.

  Esto es la opción LEGAL: NO embebe el video (eso requiere partnership con la
  plataforma), sino que muestra un preview con el link para que el usuario
  abra el video en su browser/app de Facebook. Le da tráfico a la fuente
  original y evita problemas de copyright.

  Por qué no embeber directamente:
   - Facebook prohibe ToS el uso de videos fuera de su plataforma sin partnership
   - Atribuir (poner "Fuente: Fitness Addict") no es suficiente legalmente
   - Embeds de Facebook no funcionan bien en apps nativas mobile
   - Apps como Strong/Hevy/FitNotes usan este mismo approach

  Props:
    - url: URL completa del video original (ej: "https://www.facebook.com/.../reel/123")
    - title: nombre del ejercicio (para mostrar en el botón)
    - fuente: nombre de la fuente (ej: "Fitness Addict (Facebook)")
    - tipo: 'facebook' | 'youtube' | 'instagram' | 'tiktok' | 'other'
-->
<template>
    <a
        v-if="url"
        :href="url"
        target="_blank"
        rel="noopener noreferrer"
        class="block rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden hover:shadow-lg transition-shadow"
    >
        <!-- Header con icono de la plataforma -->
        <div :class="['flex items-center gap-3 px-4 py-3 text-white', headerBg]">
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                <span class="text-2xl">{{ platformIcon }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm leading-tight">Ver técnica correcta</p>
                <p class="text-xs opacity-90 truncate">{{ fuente || 'Fuente externa' }}</p>
            </div>
            <svg class="w-5 h-5 opacity-80 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </div>

        <!-- Body con CTA -->
        <div class="px-4 py-4 flex items-center gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Abrir en {{ platformName }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                    {{ url }}
                </p>
            </div>
            <button
                type="button"
                @click.prevent="open"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors flex-shrink-0"
            >
                Abrir
            </button>
        </div>
    </a>

    <!-- Placeholder si no hay URL -->
    <div
        v-else
        class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-8 text-center"
    >
        <div class="text-4xl mb-2 opacity-40">🎬</div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Sin video para "{{ title }}"</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    url: { type: String, default: null },
    title: { type: String, default: '' },
    fuente: { type: String, default: '' },
    tipo: { type: String, default: 'other' },
});

const platformName = computed(() => {
    const map = {
        facebook: 'Facebook',
        youtube: 'YouTube',
        instagram: 'Instagram',
        tiktok: 'TikTok',
        vimeo: 'Vimeo',
        other: 'el sitio original',
    };
    return map[props.tipo] || map.other;
});

const platformIcon = computed(() => {
    const map = {
        facebook: '📘',
        youtube: '▶️',
        instagram: '📷',
        tiktok: '🎵',
        vimeo: '🎬',
        other: '🔗',
    };
    return map[props.tipo] || map.other;
});

const headerBg = computed(() => {
    const map = {
        facebook: 'bg-[#1877F2]',
        youtube: 'bg-[#FF0000]',
        instagram: 'bg-gradient-to-r from-[#833ab4] via-[#fd1d1d] to-[#fcb045]',
        tiktok: 'bg-black',
        vimeo: 'bg-[#1ab7ea]',
        other: 'bg-gray-700',
    };
    return map[props.tipo] || map.other;
});

function open() {
    if (!props.url) return;
    window.open(props.url, '_blank', 'noopener,noreferrer');
}
</script>
