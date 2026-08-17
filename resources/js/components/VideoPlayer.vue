<!--
  VideoPlayer — reproductor embebido para los videos de ejercicios.

  Soporta 3 fuentes:
   - YouTube (yt.com / youtube.com): convierte a /embed/{id}
   - Vimeo: convierte a player.vimeo.com/video/{id}
   - Directo (.mp4, .webm, .mov): usa <video> nativo con controles

  Si no hay URL, muestra un placeholder con icono de "video no disponible".

  Uso:
    <VideoPlayer :src="ejercicio.url_video" :title="ejercicio.nombre" />
-->
<template>
    <div class="rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <!-- YouTube -->
        <div v-if="kind === 'youtube'" class="relative" style="padding-bottom: 56.25%;">
            <iframe
                :src="embedUrl"
                :title="title"
                class="absolute inset-0 w-full h-full"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            ></iframe>
        </div>

        <!-- Vimeo -->
        <div v-else-if="kind === 'vimeo'" class="relative" style="padding-bottom: 56.25%;">
            <iframe
                :src="embedUrl"
                :title="title"
                class="absolute inset-0 w-full h-full"
                frameborder="0"
                allow="autoplay; fullscreen; picture-in-picture"
                allowfullscreen
            ></iframe>
        </div>

        <!-- Directo (mp4, webm, etc.) -->
        <video
            v-else-if="kind === 'direct'"
            :src="src"
            :poster="poster"
            controls
            preload="metadata"
            class="w-full max-h-[480px] bg-black"
        >
            Tu navegador no soporta el tag <code>video</code>.
        </video>

        <!-- Placeholder cuando no hay video -->
        <div
            v-else
            class="flex flex-col items-center justify-center py-12 px-4 text-center"
        >
            <div class="text-5xl mb-2 opacity-50">🎬</div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ title ? `Sin video para "${title}"` : 'Sin video disponible' }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    src: { type: String, default: null },
    poster: { type: String, default: null },
    title: { type: String, default: '' },
});

/**
 * Detecta el tipo de fuente del video.
 */
const kind = computed(() => {
    if (!props.src) return 'none';
    const url = props.src.toLowerCase();

    if (url.includes('youtube.com') || url.includes('youtu.be')) return 'youtube';
    if (url.includes('vimeo.com')) return 'vimeo';
    if (url.match(/\.(mp4|webm|mov|ogv|m4v)(\?.*)?$/)) return 'direct';
    return 'unknown';
});

/**
 * Convierte URLs de YouTube/Vimeo a su formato embed.
 */
const embedUrl = computed(() => {
    if (kind.value === 'youtube') {
        const id = extractYouTubeId(props.src);
        return id ? `https://www.youtube.com/embed/${id}?rel=0` : props.src;
    }
    if (kind.value === 'vimeo') {
        const id = extractVimeoId(props.src);
        return id ? `https://player.vimeo.com/video/${id}` : props.src;
    }
    return props.src;
});

function extractYouTubeId(url) {
    // youtube.com/watch?v=ID
    let m = url.match(/[?&]v=([\w-]+)/);
    if (m) return m[1];
    // youtu.be/ID
    m = url.match(/youtu\.be\/([\w-]+)/);
    if (m) return m[1];
    // youtube.com/embed/ID (ya embed)
    m = url.match(/youtube\.com\/embed\/([\w-]+)/);
    if (m) return m[1];
    return null;
}

function extractVimeoId(url) {
    // vimeo.com/ID
    const m = url.match(/vimeo\.com\/(\d+)/);
    return m ? m[1] : null;
}
</script>
