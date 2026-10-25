import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Media query reactiva.
 *
 * Sirve para renderizar SOLO el layout que corresponde (mobile o desktop) en
 * vez de tener los dos en el DOM ocultos con clases CSS: con los dos montados
 * se duplican requests, gráficos y renders.
 *
 * Se inicializa de forma síncrona (no hay SSR en esta app) para evitar el
 * flash de layout al cargar.
 *
 * @param {string} query  p.ej. '(max-width: 767px)'
 * @returns {import('vue').Ref<boolean>}
 */
export function useMediaQuery(query) {
    const matches = ref(
        typeof window !== 'undefined' && typeof window.matchMedia === 'function'
            ? window.matchMedia(query).matches
            : false
    );

    let mql = null;
    const onChange = (e) => {
        matches.value = e.matches;
    };

    onMounted(() => {
        if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') return;
        mql = window.matchMedia(query);
        matches.value = mql.matches;
        mql.addEventListener('change', onChange);
    });

    onUnmounted(() => {
        if (mql) mql.removeEventListener('change', onChange);
    });

    return matches;
}
