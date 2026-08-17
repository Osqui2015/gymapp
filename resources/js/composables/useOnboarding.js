/**
 * useOnboarding — composable para tours de "primera vez".
 *
 * Define un tour como una lista de steps. Cada step tiene un selector CSS
 * del elemento a destacar, y el contenido del tooltip. El tour se persiste
 * en localStorage para no mostrarlo de nuevo al user.
 *
 * Uso:
 *   const onboarding = useOnboarding('dashboard-tour', [
 *     {
 *       selector: '[data-tour="stats"]',
 *       title: 'Tus stats',
 *       body: 'Acá ves tu progreso.',
 *       position: 'bottom',
 *     },
 *     // ...
 *   ]);
 *
 *   if (onboarding.shouldShow()) {
 *     onboarding.start();
 *   }
 *
 *   <OnboardingTour v-if="onboarding.isActive" v-bind="onboarding" />
 */

import { ref, computed, nextTick } from 'vue';

const STORAGE_PREFIX = 'onboarding_done_';

export function useOnboarding(tourId, steps = []) {
    const currentStep = ref(0);
    const isActive = ref(false);
    const targetRect = ref(null);
    const tooltipStyle = ref({});

    const totalSteps = computed(() => steps.length);
    const step = computed(() => steps[currentStep.value] || null);
    const isFirst = computed(() => currentStep.value === 0);
    const isLast = computed(() => currentStep.value === steps.length - 1);
    const progress = computed(() => {
        if (totalSteps.value === 0) return 0;
        return Math.round(((currentStep.value + 1) / totalSteps.value) * 100);
    });

    const shouldShow = () => {
        try {
            return !localStorage.getItem(STORAGE_PREFIX + tourId);
        } catch {
            return true;
        }
    };

    const markAsSeen = () => {
        try {
            localStorage.setItem(STORAGE_PREFIX + tourId, '1');
        } catch { /* ignore */ }
    };

    const reset = () => {
        try {
            localStorage.removeItem(STORAGE_PREFIX + tourId);
        } catch { /* ignore */ }
    };

    const computePosition = async () => {
        await nextTick();
        if (!step.value) return;
        const el = document.querySelector(step.value.selector);
        if (!el) {
            targetRect.value = null;
            return;
        }
        // Scroll al elemento
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        await new Promise((r) => setTimeout(r, 300));

        const rect = el.getBoundingClientRect();
        targetRect.value = rect;

        const position = step.value.position || 'bottom';
        const margin = 12;
        const tooltipWidth = 320;
        const tooltipHeight = 160;

        let top, left;
        if (position === 'top') {
            top = rect.top - tooltipHeight - margin;
            left = rect.left + rect.width / 2 - tooltipWidth / 2;
        } else if (position === 'left') {
            top = rect.top + rect.height / 2 - tooltipHeight / 2;
            left = rect.left - tooltipWidth - margin;
        } else if (position === 'right') {
            top = rect.top + rect.height / 2 - tooltipHeight / 2;
            left = rect.right + margin;
        } else {
            // bottom
            top = rect.bottom + margin;
            left = rect.left + rect.width / 2 - tooltipWidth / 2;
        }
        // Keep on screen
        left = Math.max(margin, Math.min(window.innerWidth - tooltipWidth - margin, left));
        top = Math.max(margin, top);

        tooltipStyle.value = {
            position: 'fixed',
            top: `${top}px`,
            left: `${left}px`,
            width: `${tooltipWidth}px`,
            zIndex: 9999,
        };
    };

    const start = () => {
        if (steps.length === 0) return;
        isActive.value = true;
        currentStep.value = 0;
        computePosition();
    };

    const next = () => {
        if (isLast.value) {
            finish();
        } else {
            currentStep.value++;
            computePosition();
        }
    };

    const prev = () => {
        if (isFirst.value) return;
        currentStep.value--;
        computePosition();
    };

    const finish = () => {
        isActive.value = false;
        markAsSeen();
    };

    const skip = () => {
        finish();
    };

    return {
        // state
        isActive,
        currentStep,
        targetRect,
        tooltipStyle,
        step,
        totalSteps,
        isFirst,
        isLast,
        progress,
        // actions
        shouldShow,
        start,
        next,
        prev,
        skip,
        finish,
        reset,
    };
}
