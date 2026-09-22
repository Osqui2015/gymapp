<!--
  VisualGymCard — card de un ejercicio del dataset VisualGym.

  Comportamiento:
    - Thumbnail estático por default (180×180)
    - El GIF se pre-carga en cache cuando el card entra al viewport
      (ver usePrefetchGif). Al hacer hover se muestra instantáneo.
    - Click emite 'open' con el externalId para que el padre abra el detalle
    - Botón ⭐ de favorito (esquina superior derecha) emite 'toggle-favorite'

  Props:
    - exercise: { id, external_id, nombre, body_part, body_part_es,
                  equipamiento, equipamiento_es, target, target_es,
                  image_url, gif_url_full, is_favorite }
-->
<template>
    <button
        type="button"
        :ref="(el) => (cardEl = el)"
        class="group relative flex flex-col bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-600 transition-all overflow-hidden text-left w-full focus:outline-none focus:ring-2 focus:ring-indigo-500"
        :aria-label="`Ver detalle de ${exercise.nombre}`"
        @click="$emit('open', exercise.external_id)"
        @mouseenter="hovering = true"
        @mouseleave="hovering = false"
        @focusin="hovering = true"
        @focusout="hovering = false"
        @touchstart.passive="hovering = true"
        @touchend="hovering = false"
    >
        <!-- Media: thumbnail estático o GIF animado -->
        <div class="relative w-full aspect-square bg-gray-100 dark:bg-gray-900 overflow-hidden">
            <img
                v-if="!hovering && exercise.image_url"
                :src="exercise.image_url"
                :alt="exercise.nombre"
                loading="lazy"
                class="w-full h-full object-cover"
            />
            <img
                v-else-if="hovering && exercise.gif_url_full"
                :src="exercise.gif_url_full"
                :alt="`${exercise.nombre} (animación)`"
                class="w-full h-full object-cover"
            />
            <div
                v-else
                class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-600"
            >
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
            </div>

            <!-- Hint "hover para ver animación" -->
            <div
                v-if="!hovering && exercise.gif_url_full"
                class="absolute bottom-1.5 right-1.5 px-1.5 py-0.5 bg-black/60 text-white text-[10px] font-semibold rounded backdrop-blur-sm pointer-events-none"
            >
                ▶ GIF
            </div>

            <!-- ID badge -->
            <div
                class="absolute top-1.5 left-1.5 px-1.5 py-0.5 bg-white/90 dark:bg-gray-900/90 text-gray-700 dark:text-gray-300 text-[10px] font-mono rounded"
            >
                #{{ exercise.external_id }}
            </div>

            <!-- Botón favorito (esquina sup. derecha). -->
            <!-- IMPORTANTE: @click.stop para no triggerear el open del card. -->
            <button
                type="button"
                @click.stop="$emit('toggle-favorite', exercise)"
                :aria-label="exercise.is_favorite ? 'Quitar de favoritos' : 'Agregar a favoritos'"
                :title="exercise.is_favorite ? 'Quitar de favoritos' : 'Agregar a favoritos'"
                class="absolute top-1.5 right-1.5 w-7 h-7 rounded-full flex items-center justify-center transition-all backdrop-blur-sm"
                :class="
                    exercise.is_favorite
                        ? 'bg-rose-500 text-white shadow-md hover:bg-rose-600'
                        : 'bg-white/90 dark:bg-gray-900/90 text-gray-500 dark:text-gray-400 hover:text-rose-500 hover:bg-white dark:hover:bg-gray-900'
                "
            >
                <svg
                    class="w-3.5 h-3.5"
                    :fill="exercise.is_favorite ? 'currentColor' : 'none'"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                    />
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-3 flex flex-col gap-2 flex-1">
            <h3
                class="text-sm font-semibold text-gray-900 dark:text-white leading-tight line-clamp-2"
                :title="exercise.nombre"
            >
                {{ exercise.nombre }}
            </h3>

            <div class="flex flex-wrap gap-1 mt-auto">
                <span
                    v-if="exercise.body_part_es || exercise.body_part"
                    class="px-1.5 py-0.5 bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 rounded text-[10px] font-medium"
                    :title="exercise.body_part"
                >
                    {{ exercise.body_part_es || exercise.body_part }}
                </span>
                <span
                    v-if="exercise.equipamiento_es || exercise.equipamiento"
                    class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 rounded text-[10px] font-medium"
                    :title="exercise.equipamiento"
                >
                    {{ exercise.equipamiento_es || exercise.equipamiento }}
                </span>
                <span
                    v-if="exercise.target_es || exercise.target"
                    class="px-1.5 py-0.5 bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 rounded text-[10px] font-medium"
                    :title="exercise.target"
                >
                    {{ exercise.target_es || exercise.target }}
                </span>
            </div>
        </div>
    </button>
</template>

<script setup>
import { ref } from 'vue';
import { usePrefetchGif } from '../../composables/usePrefetchGif';

const props = defineProps({
    exercise: { type: Object, required: true },
});

defineEmits(['open', 'toggle-favorite']);

const hovering = ref(false);
const cardEl = ref(null);

// Pre-fetch del GIF cuando el card entra al viewport.
const gifUrl = ref(props.exercise.gif_url_full);
usePrefetchGif(cardEl, gifUrl);
</script>
