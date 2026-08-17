<!--
  PageTransition — wrapper que aplica un fade-in al contenido.
  Se usa en layouts para que cada navegación tenga una transición suave.

  Uso:
    <main>
      <PageTransition>
        <slot />
      </PageTransition>
    </main>
-->
<template>
    <Transition name="page" mode="out-in" appear>
        <div :key="$route?.path" class="contents">
            <slot />
        </div>
    </Transition>
</template>

<script setup>
// Vue 3: `$route` no está auto-disponible en componentes que no están
// dentro de un router-view. Como el proyecto no usa Vue Router, usamos
// la URL actual como key. El browser re-ejecuta el Transition al cambiar
// la URL (full page reload), así que esto funciona bien.
import { ref, onMounted } from 'vue';

const routePath = ref(typeof window !== 'undefined' ? window.location.pathname : '/');
onMounted(() => {
    routePath.value = window.location.pathname;
});
</script>

<style scoped>
/* Transición de página: fade + slight slide */
.page-enter-active {
    transition: opacity 0.25s ease-out, transform 0.25s ease-out;
}
.page-leave-active {
    transition: opacity 0.15s ease-in;
}
.page-enter-from {
    opacity: 0;
    transform: translateY(8px);
}
.page-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
