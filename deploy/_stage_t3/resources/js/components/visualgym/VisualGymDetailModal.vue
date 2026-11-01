<!--
  VisualGymDetailModal — modal de detalle de un ejercicio del dataset VisualGym.

  Carga vía store.fetchOne(externalId, lang).
  Muestra GIF animado + pasos de instrucciones en español por default.

  Props:
    - externalId: string | null (cuando cambia, recarga el detalle)
    - lang: string (default 'es')
    - open: boolean (v-model)

  Emits:
    - update:open
-->
<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open && externalId"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="close"
            >
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto"
                >
                    <!-- Loading skeleton -->
                    <div
                        v-if="store.currentLoading || !store.current"
                        class="p-8 flex items-center justify-center gap-3 text-gray-500"
                    >
                        <svg class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                            ></path>
                        </svg>
                        <span class="text-sm">Cargando ejercicio…</span>
                    </div>

                    <template v-else>
                        <!-- Header -->
                        <div
                            class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-start justify-between gap-4 z-10"
                        >
                            <div class="flex-1 min-w-0">
                                <h2
                                    class="text-2xl font-bold text-gray-900 dark:text-white truncate"
                                >
                                    {{ store.current.nombre }}
                                </h2>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <span
                                        v-if="store.current.body_part"
                                        class="px-2 py-0.5 bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 rounded text-xs font-medium capitalize"
                                    >
                                        {{ store.current.body_part }}
                                    </span>
                                    <span
                                        v-if="store.current.equipamiento"
                                        class="px-2 py-0.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 rounded text-xs font-medium capitalize"
                                    >
                                        {{ store.current.equipamiento }}
                                    </span>
                                    <span
                                        v-if="store.current.target"
                                        class="px-2 py-0.5 bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 rounded text-xs font-medium capitalize"
                                    >
                                        Target: {{ store.current.target }}
                                    </span>
                                    <span
                                        v-if="store.current.secondary_muscles?.length"
                                        class="px-2 py-0.5 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded text-xs font-medium capitalize"
                                    >
                                        Secundarios: {{ store.current.secondary_muscles.join(', ') }}
                                    </span>
                                </div>
                            </div>
                            <button
                                v-if="store.current"
                                type="button"
                                @click="onToggleFavorite"
                                :aria-label="store.current.is_favorite ? 'Quitar de favoritos' : 'Agregar a favoritos'"
                                :title="store.current.is_favorite ? 'Quitar de favoritos' : 'Agregar a favoritos'"
                                class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center transition-all"
                                :class="
                                    store.current.is_favorite
                                        ? 'bg-rose-500 text-white hover:bg-rose-600 shadow-md'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-rose-500 hover:bg-gray-200 dark:hover:bg-gray-600'
                                "
                            >
                                <svg
                                    class="w-4 h-4"
                                    :fill="store.current.is_favorite ? 'currentColor' : 'none'"
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
                            <button
                                @click="close"
                                class="flex-shrink-0 p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                aria-label="Cerrar"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6 space-y-6">
                            <!-- GIF animado (autoplay + loop) -->
                            <div
                                v-if="store.current.gif_url_full"
                                class="flex justify-center bg-gray-100 dark:bg-gray-900 rounded-xl p-4"
                            >
                                <img
                                    :src="store.current.gif_url_full"
                                    :alt="store.current.nombre + ' (animación)'"
                                    class="max-h-80 w-auto rounded-lg"
                                    loading="lazy"
                                />
                            </div>

                            <!-- Selector de idioma (si hay varios disponibles) -->
                            <div
                                v-if="store.current.available_languages?.length > 1"
                                class="flex flex-wrap items-center gap-2"
                            >
                                <span
                                    class="text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                                >
                                    Idioma:
                                </span>
                                <button
                                    v-for="l in store.current.available_languages"
                                    :key="l"
                                    type="button"
                                    @click="changeLang(l)"
                                    :class="[
                                        'px-2 py-1 text-xs font-semibold rounded uppercase',
                                        lang === l
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600',
                                    ]"
                                >
                                    {{ langLabel(l) }}
                                </button>
                            </div>

                            <!-- Pasos numerados -->
                            <div v-if="store.current.instruction_steps?.length">
                                <h3
                                    class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3"
                                >
                                    Paso a paso ({{ lang }})
                                </h3>
                                <ol class="space-y-3">
                                    <li
                                        v-for="(step, i) in store.current.instruction_steps"
                                        :key="i"
                                        class="flex gap-3"
                                    >
                                        <span
                                            class="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center"
                                        >
                                            {{ i + 1 }}
                                        </span>
                                        <p
                                            class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed pt-0.5"
                                        >
                                            {{ step }}
                                        </p>
                                    </li>
                                </ol>
                            </div>

                            <!-- Fallback: instrucción como texto plano -->
                            <div
                                v-else-if="store.current.instruction"
                                class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed"
                            >
                                {{ store.current.instruction }}
                            </div>

                            <!-- Attribution footer -->
                            <p
                                v-if="store.current.fuente_credito"
                                class="text-[11px] text-gray-400 dark:text-gray-500 italic border-t border-gray-100 dark:border-gray-700 pt-3"
                            >
                                {{ store.current.fuente_credito }}
                            </p>
                        </div>
                    </template>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useVisualGymStore } from '../../stores/visualgym';

const props = defineProps({
    externalId: { type: String, default: null },
    lang: { type: String, default: 'es' },
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['update:open']);

const store = useVisualGymStore();

// `lang` interno (se puede cambiar con los botones del modal)
const lang = ref(props.lang);

watch(
    () => props.lang,
    (v) => {
        lang.value = v;
        if (props.externalId && props.open) {
            store.fetchOne(props.externalId, lang.value);
        }
    },
);

watch(
    () => props.externalId,
    (v) => {
        if (v && props.open) {
            store.fetchOne(v, lang.value);
        }
    },
);

watch(
    () => props.open,
    (v) => {
        if (v && props.externalId) {
            store.fetchOne(props.externalId, lang.value);
        } else if (!v) {
            store.clearCurrent();
        }
    },
);

function close() {
    emit('update:open', false);
}

async function onToggleFavorite() {
    if (! store.current) return;
    try {
        await store.toggleFavorite(store.current);
    } catch (e) {
        // toast global se encarga
    }
}

async function changeLang(newLang) {
    lang.value = newLang;
    await store.fetchOne(props.externalId, newLang);
}

function langLabel(code) {
    const labels = {
        en: 'EN',
        es: 'ES',
        it: 'IT',
        tr: 'TR',
        ru: 'RU',
        zh: 'ZH',
        hi: 'HI',
        pl: 'PL',
        ko: 'KO',
        fr: 'FR',
    };
    return labels[code] || code.toUpperCase();
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
