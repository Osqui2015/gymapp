import { defineStore } from 'pinia';
import { ref } from 'vue';

const STORAGE_KEY_RUTINA = 'rutina_seleccionada';
const STORAGE_KEY_DIA = 'rutina_dia';

export interface RutinaSeleccionada {
    nivel: string;
    dias: string;
}

export interface RutinaCreada {
    id: number;
    nombre: string;
    ejercicios: unknown[];
}

/**
 * Store de la rutina seleccionada por el usuario.
 *
 * El state se inicializa con valores puros (sin tocar `localStorage` en `state()`)
 * para que sea seguro en SSR y serializable. La hidratación desde localStorage
 * se hace explícitamente vía `hidratar()` en el `onMounted` del primer componente
 * que consuma el store (DashboardContent y/o RutinasAccordion).
 *
 * Las acciones que modifican estado (`seleccionar`, `limpiar`, `avanzarDia`)
 * persisten automáticamente en localStorage. Si se modifica el state por fuera
 * (ej. devtools), hay que llamar a `persistir()` manualmente.
 */
export const useRutinaStore = defineStore('rutina', () => {
    const seleccionada = ref<RutinaSeleccionada | null>(null);
    const diaActual = ref<string>('Día 1');
    const creada = ref<RutinaCreada | null>(null);

    /**
     * Lee el localStorage y popula el state.
     * Llamar una sola vez en onMounted del componente que monte el store.
     */
    function hidratar(): void {
        try {
            const rawRutina = localStorage.getItem(STORAGE_KEY_RUTINA);
            const rawDia = localStorage.getItem(STORAGE_KEY_DIA);
            seleccionada.value = rawRutina ? JSON.parse(rawRutina) as RutinaSeleccionada : null;
            diaActual.value = rawDia || 'Día 1';
        } catch (e) {
            // localStorage puede tirar (modo privado, cuota llena, JSON malformado).
            // Si falla, el state queda en sus defaults y seguimos.
            console.warn('[rutinaStore] no se pudo hidratar desde localStorage:', e);
        }
    }

    /**
     * Persiste el state actual a localStorage. Útil si modificás el state
     * por fuera de las acciones (devtools, patch manual, etc.).
     */
    function persistir(): void {
        try {
            if (seleccionada.value) {
                localStorage.setItem(STORAGE_KEY_RUTINA, JSON.stringify(seleccionada.value));
            } else {
                localStorage.removeItem(STORAGE_KEY_RUTINA);
            }
            if (diaActual.value) {
                localStorage.setItem(STORAGE_KEY_DIA, diaActual.value);
            } else {
                localStorage.removeItem(STORAGE_KEY_DIA);
            }
        } catch (e) {
            console.warn('[rutinaStore] no se pudo persistir:', e);
        }
    }

    function seleccionar(nivel: string, dias: string): void {
        seleccionada.value = { nivel, dias };
        diaActual.value = 'Día 1';
        persistir();
    }

    function limpiar(): void {
        seleccionada.value = null;
        diaActual.value = 'Día 1';
        creada.value = null;
        localStorage.removeItem(STORAGE_KEY_DIA);
        localStorage.removeItem(STORAGE_KEY_RUTINA);
    }

    /**
     * Avanza al siguiente día de la rutina y persiste el cambio.
     */
    function setDiaActual(dia: string): void {
        diaActual.value = dia;
        persistir();
    }

    function crearRutina(nombre: string): void {
        creada.value = {
            id: Date.now(),
            nombre,
            ejercicios: [],
        };
    }

    function eliminarRutina(): void {
        creada.value = null;
    }

    return {
        seleccionada,
        diaActual,
        creada,
        hidratar,
        persistir,
        seleccionar,
        limpiar,
        setDiaActual,
        crearRutina,
        eliminarRutina,
    };
});
