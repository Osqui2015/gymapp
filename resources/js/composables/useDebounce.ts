/**
 * Composable de debounce.
 *
 * Ejemplo:
 *   const debouncedFn = useDebounce(() => cargarDatos(search), 300);
 *   watch(search, debouncedFn);
 *
 * TypeScript: tipos inferidos del parámetro `fn`.
 */
export function useDebounce<TArgs extends unknown[]>(
    fn: (...args: TArgs) => void,
    delay = 300,
): (...args: TArgs) => void {
    let timer: ReturnType<typeof setTimeout> | null = null;
    return function debounced(...args: TArgs) {
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

import { ref, watch, type Ref } from 'vue';

interface DebouncedRef<T> {
    value: Ref<T>;
    debounced: Ref<T>;
}

/**
 * Composable que devuelve { value, debounced } para usar con v-model.
 *
 * Ejemplo:
 *   const search = useDebouncedRef('');
 *   watch(search.debounced, (val) => cargarDatos(val));
 *   <input v-model="search.value" />
 */
export function useDebouncedRef<T>(initial: T, delay = 300): DebouncedRef<T> {
    const value = ref<T>(initial) as Ref<T>;
    const debounced = ref<T>(initial) as Ref<T>;

    let timer: ReturnType<typeof setTimeout> | null = null;
    watch(value, (val) => {
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => { debounced.value = val; }, delay);
    });

    return { value, debounced };
}
