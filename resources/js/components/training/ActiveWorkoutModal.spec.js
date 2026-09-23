import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import ActiveWorkoutModal from './ActiveWorkoutModal.vue';
import { useTrainingSessionStore } from '@/stores/trainingSession';
import { useRestTimerStore } from '@/stores/restTimer';

// Mock useOfflineSeries
vi.mock('@/composables/useOfflineSeries', () => ({
    useOfflineSeries: () => ({
        recordSet: vi.fn().mockResolvedValue({ status: 'sent' }),
    }),
}));

// Mock useWakeLock
vi.mock('@/composables/useWakeLock', () => ({
    useWakeLock: () => ({
        supported: true,
        active: false,
        requestWakeLock: vi.fn(),
        releaseWakeLock: vi.fn(),
    }),
}));

describe('ActiveWorkoutModal', () => {
    let store;

    beforeEach(() => {
        localStorage.clear();
        setActivePinia(createPinia());
        store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'Torso Pierna',
            dia: 'Día 1',
            ejercicios: [
                {
                    nombre: 'Press Banca',
                    series_objetivo: 3,
                    reps_min: '8',
                    reps_max: '10',
                    descanso_min: 2,
                },
                {
                    nombre: 'Remo con Barra',
                    series_objetivo: 3,
                    reps_min: '8',
                    reps_max: '10',
                    descanso_min: 1.5,
                },
            ],
        });
    });

    it('renderiza cuando open es true y muestra el ejercicio actual', () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        expect(wrapper.text()).toContain('Press Banca');
        expect(wrapper.text()).toContain('Torso Pierna');
        expect(wrapper.text()).toContain('Día 1');
        expect(wrapper.text()).toContain('COMPLETAR SERIE #1');
        // Pill con el total de series del ejercicio (Press Banca tiene 3).
        expect(wrapper.text()).toContain('3 series');
    });

    it('no renderiza contenido cuando open es false', () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: false },
        });

        expect(wrapper.find('header').exists()).toBe(false);
    });

    it('emite minimize al tocar el botón de minimizar', async () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        const btnMinimize = wrapper.find('button[aria-label="Minimizar sesión"]');
        await btnMinimize.trigger('click');

        expect(wrapper.emitted('minimize')).toBeTruthy();
    });

    it('emite finish al presionar Finalizar', async () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        const btnFinalizar = wrapper.findAll('button').find((b) => b.text() === 'Finalizar');
        expect(btnFinalizar).toBeDefined();
        await btnFinalizar.trigger('click');

        expect(wrapper.emitted('finish')).toBeTruthy();
    });

    it('permite completar serie y avanzar al siguiente set', async () => {
        const restStore = useRestTimerStore();
        const startSpy = vi.spyOn(restStore, 'start');

        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        // Kinetic Obsidian: el botón COMPLETAR SERIE ahora usa shadow-violet-glow
        const btnCompletar = wrapper.findAll('button').find((b) =>
            b.text().includes('COMPLETAR SERIE')
        );
        expect(btnCompletar).toBeDefined();

        await btnCompletar.trigger('click');

        expect(store.totalSeriesCompletadas).toBe(1);
        expect(store.session.currentSerieNumero).toBe(2);
        expect(startSpy).toHaveBeenCalledWith(120, 'Press Banca'); // 2 min = 120s
    });

    it('las series de calentamiento son separadas y no cuentan para el progreso', async () => {
        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        // 1) Cambiar tipo a calentamiento.
        const btnCalentamiento = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Calentamiento');
        expect(btnCalentamiento).toBeDefined();
        await btnCalentamiento.trigger('click');

        // Header y boton ahora deben mostrar Calentamiento #1.
        expect(wrapper.text()).toContain('Configurar Calentamiento #1');
        expect(wrapper.text()).toContain('COMPLETAR CALENTAMIENTO #1');

        // 2) Completar la primera serie de calentamiento.
        const btnCompletarWarmup = wrapper
            .findAll('button')
            .find((b) => b.text().includes('COMPLETAR CALENTAMIENTO'));
        await btnCompletarWarmup.trigger('click');

        // Progreso global sigue en 0/6 (3 series por ejercicio × 2 ejercicios)
        expect(store.totalSeriesCompletadas).toBe(0);
        expect(store.session.currentSerieNumero).toBe(1);
        expect(store.session.currentCalentamientoNumero).toBe(2);
        // El set queda registrado con su propio numero.
        expect(store.currentEjercicio.sets[0].tipo_serie).toBe('calentamiento');
        expect(store.currentEjercicio.sets[0].series_numero).toBe(1);

        // 3) Volver a tipo efectiva y completar la primera serie de trabajo.
        const btnEfectiva = wrapper
            .findAll('button')
            .find((b) => b.text().trim() === 'Efectiva');
        await btnEfectiva.trigger('click');

        const btnCompletarEfectiva = wrapper
            .findAll('button')
            .find((b) => b.text().includes('COMPLETAR SERIE'));
        await btnCompletarEfectiva.trigger('click');

        // Ahora si avanza el progreso y el contador de calentamiento se reseteo.
        expect(store.totalSeriesCompletadas).toBe(1);
        expect(store.session.currentSerieNumero).toBe(2);
        expect(store.session.currentCalentamientoNumero).toBe(1);
    });

    it('muestra el boton FINALIZAR SESION cuando todos los ejercicios estan completos', async () => {
        // Forzamos una sesion minima para poder terminarla rapido.
        store.discard();
        store.start({
            rutina_nombre: 'Mini',
            dia: 'Día 1',
            ejercicios: [{ nombre: 'Press Banca', series_objetivo: 2 }],
        });

        const wrapper = mount(ActiveWorkoutModal, {
            props: { open: true },
        });

        // Mientras no este todo completo, debe verse COMPLETAR SERIE y NO
        // el boton de finalizar.
        expect(wrapper.text()).toContain('COMPLETAR SERIE #1');
        expect(wrapper.find('[data-testid="btn-finalizar-sesion"]').exists()).toBe(false);

        // Completar las dos series del unico ejercicio.
        const completar = () =>
            wrapper.findAll('button').find((b) => b.text().includes('COMPLETAR SERIE'));
        await completar().trigger('click');
        await completar().trigger('click');

        // Ahora la sesion esta completa y debe aparecer el boton verde.
        expect(store.isSessionComplete).toBe(true);
        expect(wrapper.text()).toContain('FINALIZAR SESIÓN');
        expect(wrapper.text()).not.toContain('COMPLETAR SERIE #');
        const btnFinalizar = wrapper.find('[data-testid="btn-finalizar-sesion"]');
        expect(btnFinalizar.exists()).toBe(true);

        // Al tocar FINALIZAR se emite el evento 'finish' (el padre abre el
        // resumen / cierra la sesion).
        await btnFinalizar.trigger('click');
        expect(wrapper.emitted('finish')).toBeTruthy();
    });
});
