/**
 * usePrefetchGif — observa un element y cuando entra al viewport
 * pre-carga el GIF en el cache del navegador.
 *
 * Por qué:
 *  - Con 1.324 ejercicios en el catálogo, cargar todos los GIFs upfront
 *    son ~130 MB y ~2.700 requests. Mata el initial load.
 *  - Sin pre-carga, al hacer hover hay que esperar a que baje el GIF
 *    (~50-300 KB por GIF, depende de la conexión).
 *
 * Solución:
 *  - Solo thumbnail estático por default.
 *  - Al entrar el card al viewport, pre-cargar el GIF en cache.
 *  - Cuando el user hace hover, el GIF está listo y se muestra al toque.
 *
 * Usa IntersectionObserver (con fallback a always-true si no está).
 */

import { onMounted, onBeforeUnmount } from 'vue';

export function usePrefetchGif(elRef, gifUrl) {
    let observer = null;

    onMounted(() => {
        if (!elRef.value) return;

        // Si no hay IntersectionObserver (SSR / viejos browsers),
        // pre-cargar igual — peor caso volvemos al comportamiento original.
        if (!('IntersectionObserver' in window)) {
            prefetch(gifUrl.value);
            return;
        }

        observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        prefetch(gifUrl.value);
                        observer.unobserve(entry.target);
                    }
                }
            },
            {
                // Empezar a pre-cargar cuando el card está a 200px de entrar
                // al viewport. Buen balance entre anticipar y no gastar
                // bandwidth en cards que el user nunca va a ver.
                rootMargin: '200px',
            },
        );

        observer.observe(elRef.value);
    });

    onBeforeUnmount(() => {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
    });
}

/**
 * Cachea el GIF usando el Image() trick. Si ya se está bajando, no hace nada.
 */
const prefetched = new Set();

function prefetch(url) {
    if (!url || prefetched.has(url)) return;
    prefetched.add(url);
    const img = new Image();
    img.src = url;
    // No necesitamos appendearlo al DOM — el browser lo cachea solo.
}
