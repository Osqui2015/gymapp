<!--
  OnboardingTour — UI del tour de "primera vez".

  Recibe un objeto `tour` devuelto por `useOnboarding()` y renderiza:
   - Un overlay oscuro con un "agujero" alrededor del elemento target
   - Un tooltip flotante con título, body y botones next/prev/skip

  Uso:
    <OnboardingTour v-if="onboarding.isActive" v-bind="onboarding" />
-->
<template>
    <Teleport to="body">
        <!-- Overlay oscuro con un hueco rectangular (box-shadow inset) -->
        <div
            v-if="tour.isActive.value && tour.targetRect.value"
            class="fixed inset-0 z-[9998] pointer-events-none transition-all duration-300"
            :style="overlayStyle"
        ></div>

        <!-- Tooltip flotante -->
        <div
            v-if="tour.isActive.value && tour.step.value"
            :style="tour.tooltipStyle.value"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-5 transition-all duration-300"
        >
            <!-- Progress -->
            <div class="flex items-center justify-between mb-3 text-xs">
                <span class="font-semibold text-gray-500 dark:text-gray-400">
                    {{ tour.currentStep.value + 1 }} de {{ tour.totalSteps.value }}
                </span>
                <button
                    @click="tour.skip"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 font-medium"
                >
                    Saltar tour
                </button>
            </div>

            <!-- Progress bar -->
            <div class="h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-4">
                <div
                    class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 transition-all duration-300"
                    :style="{ width: tour.progress.value + '%' }"
                ></div>
            </div>

            <!-- Contenido -->
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">
                {{ tour.step.value.title }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                {{ tour.step.value.body }}
            </p>

            <!-- Botones -->
            <div class="flex items-center justify-between gap-2">
                <button
                    v-if="!tour.isFirst.value"
                    @click="tour.prev"
                    class="px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                    ← Anterior
                </button>
                <span v-else></span>

                <button
                    @click="tour.next"
                    class="px-4 py-1.5 text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-sm transition-colors"
                >
                    {{ tour.isLast.value ? '¡Listo!' : 'Siguiente →' }}
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    tour: { type: Object, required: true },
});

// Crea el efecto de "agujero" alrededor del elemento target
const overlayStyle = computed(() => {
    const rect = props.tour.targetRect.value;
    if (!rect) return {};
    const padding = 8; // espacio alrededor del target
    return {
        boxShadow: `0 0 0 9999px rgba(0, 0, 0, 0.6), 0 0 0 ${padding}px rgba(99, 102, 241, 0.3)`,
        borderRadius: '8px',
        left: `${rect.left - padding}px`,
        top: `${rect.top - padding}px`,
        width: `${rect.width + padding * 2}px`,
        height: `${rect.height + padding * 2}px`,
    };
});
</script>
