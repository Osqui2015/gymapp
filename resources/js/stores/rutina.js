import { defineStore } from 'pinia';

export const useRutinaStore = defineStore('rutina', {
  state: () => ({
    seleccionada: JSON.parse(localStorage.getItem('rutina_seleccionada') || 'null'),
    diaActual: localStorage.getItem('rutina_dia') || 'Día 1',
    creada: null,
  }),
  actions: {
    seleccionar(nivel, dias) {
      this.seleccionada = { nivel, dias };
      this.diaActual = 'Día 1';
      localStorage.setItem('rutina_seleccionada', JSON.stringify(this.seleccionada));
      localStorage.setItem('rutina_dia', this.diaActual);
    },
    limpiar() {
      this.seleccionada = null;
      this.diaActual = 'Día 1';
      this.creada = null;
      localStorage.removeItem('rutina_seleccionada');
      localStorage.removeItem('rutina_dia');
    },
    crearRutina(nombre) {
      const nueva = {
        id: Date.now(),
        nombre,
        ejercicios: [],
      };
      this.creada = nueva;
    },
    eliminarRutina(id) {
      this.creada = null;
    },
  },
});