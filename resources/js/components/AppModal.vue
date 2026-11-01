<!--
  AppModal — Modal reutilizable (reemplaza el x-data de Alpine).

  Antes era un partial Blade con Alpine: `show`, focus trap con Tab/Shift+Tab,
  Escape, eventos `open-modal` / `close-modal` en window, y el bloqueo del
  scroll del body.

  Ahora el estado vive en Vue y el focus trap usa el composable
  `useFocusTrap` (que ya existía en el repo y hacia lo mismo).

  Para usarlo desde Blade:
    <x-app-modal name="mi-modal" :show="$var">
        ...contenido...
    </x-app-modal>
-->
<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="name + '-title'"
    >
        <div
            class="fixed inset-0 transform transition-all"
            @click="close"
            aria-hidden="true"
        >
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div
            ref="panelRef"
            tabindex="-1"
            class="mb-6 bg-white dark:bg-[var(--color-obsidian-surface)] rounded-2xl overflow-hidden shadow-xl dark:shadow-[0_24px_60px_rgba(0,0,0,0.6)] transform transition-all sm:w-full sm:mx-auto border border-gray-200 dark:border-[var(--color-obsidian-border-strong)] outline-none"
            :class="maxWidthClass"
        >
            <slot />
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useFocusTrap } from '../composables/useFocusTrap';

const props = defineProps({
    name: { type: String, required: true },
    show: { type: Boolean, default: false },
    maxWidth: { type: String, default: '2xl' },
});

const emit = defineEmits(['close']);

const panelRef = ref(null);
const isOpen = ref(props.show);

const maxWidthClass = computed(
    () =>
        ({
            sm: 'sm:max-w-sm',
            md: 'sm:max-w-md',
            lg: 'sm:max-w-lg',
            xl: 'sm:max-w-xl',
            '2xl': 'sm:max-w-2xl',
        })[props.maxWidth] || 'sm:max-w-2xl',
);

const close = () => {
    isOpen.value = false;
    emit('close');
};

// El focus trap se activa solo mientras el modal esta abierto.
useFocusTrap(panelRef, {
    when: isOpen,
    onEscape: close,
});

// Mantiene el scroll del body bloqueado mientras el modal esta abierto.
watch(
    isOpen,
    (value) => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    },
    { immediate: true },
);

// El show puede venir como prop desde Alpine/Blade o cambiar por eventos.
watch(
    () => props.show,
    (value) => {
        isOpen.value = value;
    },
);

const onOpenModal = (e) => {
    if (e.detail === props.name) isOpen.value = true;
};

const onCloseModal = (e) => {
    // `close-modal` con detail cierra el modal indicado; sin detail (o con
    // detail null) cierra cualquiera. Reemplaza el `$dispatch('close')` de
    // Alpine, que cerraba el modal mas cercano.
    if (e.detail == null || e.detail === props.name) isOpen.value = false;
};

// Evento `close` sin detail: cierra este modal. Es lo que dispara el boton
// "Cancelar" del formulario de borrado de cuenta.
const onClose = () => {
    isOpen.value = false;
};

onMounted(() => {
    window.addEventListener('open-modal', onOpenModal);
    window.addEventListener('close-modal', onCloseModal);
    window.addEventListener('close', onClose);
});

onBeforeUnmount(() => {
    window.removeEventListener('open-modal', onOpenModal);
    window.removeEventListener('close-modal', onCloseModal);
    window.removeEventListener('close', onClose);
    document.body.classList.remove('overflow-y-hidden');
});
</script>
