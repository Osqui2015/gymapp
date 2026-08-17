/**
 * useUrlFilters — sincroniza filtros de la UI con la URL (?key=value).
 *
 * Permite que el user:
 *   - Comparta la URL con sus filtros aplicados
 *   - Vuelva a la vista y encuentre sus filtros (refresh, etc.)
 *   - Use el botón "Atrás" del browser para volver a un filtro anterior
 *
 * Uso:
 *   const { get, set, remove, clear } = useUrlFilters();
 *   set('alumno_id', 42);
 *   const alumnoId = get('alumno_id');
 *
 * O con un composable específico para historial:
 *   const filters = useHistorialFilters();
 *   filters.alumno_id = 42; // reactive set
 *   filters.reset();
 */
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';

export function useUrlFilters(namespace = '') {
    const prefix = namespace ? `${namespace}_` : '';
    const get = (key) => {
        if (typeof window === 'undefined') return null;
        const params = new URLSearchParams(window.location.search);
        const value = params.get(prefix + key);
        if (value === null) return null;
        // intento parsear como number si parece numérico
        if (/^\d+$/.test(value)) return Number(value);
        if (value === 'true') return true;
        if (value === 'false') return false;
        return value;
    };

    const set = (key, value) => {
        if (typeof window === 'undefined') return;
        const url = new URL(window.location.href);
        const params = url.searchParams;
        if (value === null || value === undefined || value === '') {
            params.delete(prefix + key);
        } else {
            params.set(prefix + key, String(value));
        }
        // Reemplaza la URL sin recargar
        const newUrl = url.pathname + (params.toString() ? `?${params.toString()}` : '') + url.hash;
        window.history.replaceState({}, '', newUrl);
    };

    const remove = (key) => set(key, null);

    const clear = () => {
        if (typeof window === 'undefined') return;
        const url = new URL(window.location.href);
        window.history.replaceState({}, '', url.pathname + url.hash);
    };

    const getAll = () => {
        if (typeof window === 'undefined') return {};
        const params = new URLSearchParams(window.location.search);
        const out = {};
        for (const [k, v] of params.entries()) {
            if (k.startsWith(prefix)) {
                out[k.slice(prefix.length)] = v;
            }
        }
        return out;
    };

    return { get, set, remove, clear, getAll };
}

/**
 * useHistorialFilters — filtros predefinidos para Historial.
 * Si la URL tiene `?alumno_id=42&from=2026-01-01&to=2026-12-31`, los carga.
 * Si cambia un filtro, actualiza la URL automáticamente.
 */
export function useHistorialFilters() {
    const filters = useUrlFilters('h');

    const state = ref({
        alumno_id: filters.get('alumno_id'),
        from: filters.get('from'),
        to: filters.get('to'),
        rutina: filters.get('rutina'),
        ejercicio: filters.get('ejercicio'),
    });

    // Watch para sincronizar state → URL
    const stop = watch(state, (newVal) => {
        for (const [k, v] of Object.entries(newVal)) {
            filters.set(k, v);
        }
    }, { deep: true });

    const reset = () => {
        state.value = { alumno_id: null, from: null, to: null, rutina: null, ejercicio: null };
        filters.clear();
    };

    const buildQueryParams = () => {
        const out = {};
        for (const [k, v] of Object.entries(state.value)) {
            if (v !== null && v !== undefined && v !== '') {
                out[k] = v;
            }
        }
        return out;
    };

    onBeforeUnmount(stop);

    return {
        state,
        reset,
        buildQueryParams,
    };
}
