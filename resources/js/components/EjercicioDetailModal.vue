<!--
  EjercicioDetailModal — modal con el detail de un ejercicio.
  Incluye el VideoPlayer si el ejercicio tiene url_video.

  Props:
    - ejercicio: { id, nombre, equipamiento, grupo_muscular, descripcion, url_video, url_img }
    - open: boolean (v-model)
-->
<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open && ejercicio"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                @click.self="$emit('update:open', false)"
            >
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-start justify-between gap-4 z-10">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white truncate">
                                {{ ejercicio.nombre }}
                            </h2>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded text-xs font-medium">
                                    {{ ejercicio.equipamiento }}
                                </span>
                                <span v-if="ejercicio.grupo_muscular" class="px-2 py-0.5 bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 rounded text-xs font-medium">
                                    {{ ejercicio.grupo_muscular }}
                                </span>
                            </div>
                        </div>
                        <button
                            @click="$emit('update:open', false)"
                            class="flex-shrink-0 p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            aria-label="Cerrar"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-6">
                        <!-- Video -->
                        <VideoPlayer
                            v-if="ejercicio.url_video"
                            :src="ejercicio.url_video"
                            :title="ejercicio.nombre"
                        />

                        <!-- Imagen (si no hay video) -->
                        <img
                            v-else-if="ejercicio.url_img"
                            :src="ejercicio.url_img"
                            :alt="ejercicio.nombre"
                            class="w-full max-h-96 object-cover rounded-xl"
                        />

                        <!-- Descripción -->
                        <div v-if="ejercicio.descripcion">
                            <h3 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                Descripción
                            </h3>
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                                {{ ejercicio.descripcion }}
                            </p>
                        </div>

                        <!-- Hint si no hay video ni imagen -->
                        <div
                            v-if="!ejercicio.url_video && !ejercicio.url_img && !ejercicio.descripcion"
                            class="text-center py-8 text-gray-500 dark:text-gray-400"
                        >
                            <p class="text-sm">Este ejercicio todavía no tiene detalles cargados.</p>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import VideoPlayer from './VideoPlayer.vue';

defineProps({
    ejercicio: { type: Object, default: null },
    open: { type: Boolean, default: false },
});

defineEmits(['update:open']);
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
