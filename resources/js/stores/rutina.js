import { defineStore } from 'pinia';

const STORAGE_KEY_RUTINA = 'rutina_seleccionada';
const STORAGE_KEY_DIA = 'rutina_dia';

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
export const useRutinaStore = defineStore('rutina', {
  state: () => ({
    seleccionada: null,
    diaActual: 'Día 1',
    creada: null,
  }),

  actions: {
    /**
     * Lee el localStorage y popula el state.
     * Llamar una sola vez en onMounted del componente que monte el store.
     */
    hidratar() {
      try {
        const rawRutina = localStorage.getItem(STORAGE_KEY_RUTINA);
        const rawDia = localStorage.getItem(STORAGE_KEY_DIA);
        this.$patch({
          seleccionada: rawRutina ? JSON.parse(rawRutina) : null,
          diaActual: rawDia || 'Día 1',
        });
      } catch (e) {
        // localStorage puede tirar (modo privado, cuota llena, JSON malformado).
        // Si falla, el state queda en sus defaults y seguimos.
        console.warn('[rutinaStore] no se pudo hidratar desde localStorage:', e);
      }
    },

    /**
     * Persiste el state actual a localStorage. Útil si modificás el state
     * por fuera de las acciones (devtools, patch manual, etc.).
     */
    persistir() {
      try {
        if (this.seleccionada) {
          localStorage.setItem(STORAGE_KEY_RUTINA, JSON.stringify(this.seleccionada));
        } else {
          localStorage.removeItem(STORAGE_KEY_RUTINA);
        }
        if (this.diaActual) {
          localStorage.setItem(STORAGE_KEY_DIA, this.diaActual);
        } else {
          localStorage.removeItem(STORAGE_KEY_DIA);
        }
      } catch (e) {
        console.warn('[rutinaStore] no se pudo persistir:', e);
      }
    },

    seleccionar(nivel, dias) {
      this.seleccionada = { nivel, dias };
      this.diaActual = 'Día 1';
      this.persistir();
    },

    limpiar() {
      this.seleccionada = null;
      this.diaActual = 'Día 1';
      this.creada = null;
      localStorage.removeItem(STORAGE_KEY_DIA);
      localStorage.removeItem(STORAGE_KEY_RUTINA);
    },

    /**
     * Avanza al siguiente día de la rutina y persiste el cambio.
     */
    setDiaActual(dia) {
      this.diaActual = dia;
      this.persistir();
    },

    crearRutina(nombre) {
      const nueva = {
        id: Date.now(),
        nombre,
        ejercicios: [],
      };
      this.creada = nueva;
    },

    eliminarRutina() {
      this.creada = null;
    },
  },
});
