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
});
