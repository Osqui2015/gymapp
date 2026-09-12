<!--
  UpdateAvailablePrompt — Banner persistente que aparece cuando hay una nueva
  version del Service Worker instalada y lista para activarse.

  Lo dispara el evento `sw:update-available` (emitido desde webPushService.js
  cuando un nuevo SW se instala con uno viejo activo).

  El user hace click en "Actualizar" → forzamos al nuevo SW a tomar el control.
  El controllerchange recarga la pagina automaticamente.
-->
<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div
            v-if="visible"
            class="fixed bottom-20 md:bottom-4 left-1/2 -translate-x-1/2 z-50 max-w-md w-[calc(100%-2rem)]"
            role="status"
            aria-live="polite"
        >
            <div
                class="bg-indigo-600 text-white rounded-xl shadow-2xl px-4 py-3 flex items-center gap-3"
            >
                <div class="text-2xl flex-shrink-0">🔄</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold">Nueva versión disponible</p>
                    <p class="text-xs text-indigo-100 mt-0.5">
                        Toca actualizar para usar la versión más reciente.
                    </p>
                </div>
                <button
                    @click="applyUpdate"
                    class="px-3 py-1.5 bg-white text-indigo-600 font-bold text-sm rounded-lg hover:bg-indigo-50 transition-colors flex-shrink-0"
                >
                    Actualizar
                </button>
                <button
                    @click="dismiss"
                    class="text-indigo-200 hover:text-white p-1 flex-shrink-0"
                    aria-label="Cerrar"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

const visible = ref(false);

const onUpdateAvailable = () => {
    visible.value = true;
};

const applyUpdate = async () => {
    if (!('serviceWorker' in navigator)) {
        window.location.reload();
        return;
    }
    const reg = await navigator.serviceWorker.getRegistration();
    if (!reg || !reg.waiting) {
        // No hay SW esperando, recargar igual.
        window.location.reload();
        return;
    }
    // Forzar al SW en waiting a activarse.
    reg.waiting.postMessage({ type: 'SKIP_WAITING' });
    // El controllerchange listener recarga automaticamente.
};

const dismiss = () => {
    visible.value = false;
};

onMounted(() => {
    window.addEventListener('sw:update-available', onUpdateAvailable);
});

onBeforeUnmount(() => {
    window.removeEventListener('sw:update-available', onUpdateAvailable);
});
</script>
