import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import WorkoutSummaryModal from './WorkoutSummaryModal.vue';
import { useTrainingSessionStore } from '@/stores/trainingSession';
import axios from 'axios';

vi.mock('canvas-confetti', () => ({
    default: vi.fn(),
}));

vi.mock('axios');

describe('WorkoutSummaryModal', () => {
    let store;

    beforeEach(() => {
        localStorage.clear();
        setActivePinia(createPinia());
        store = useTrainingSessionStore();
        store.start({
            rutina_nombre: 'Fuerza A',
            dia: 'Lunes',
            ejercicios: [
                {
                    nombre: 'Sentadilla',
                    series_objetivo: 2,
                },
            ],
        });
        store.recordSet({ peso: 100, reps: 5 }); // 500 kg
        store.recordSet({ peso: 100, reps: 5 }); // 500 kg -> 1000 kg total
    });

    it('renderiza resumen de métricas cuando open es true', () => {
        const wrapper = mount(WorkoutSummaryModal, {
            props: { open: true },
        });

        expect(wrapper.text()).toContain('¡Entrenamiento Completado!');
        expect(wrapper.text()).toContain('Fuerza A');
        // Acepta formato local (1.000 o 1,000)
        expect(wrapper.text()).toMatch(/1[.,]000/);
        expect(wrapper.text()).toContain('2'); // Series completadas
    });

    it('emite cancel al hacer clic en Volver al entrenamiento', async () => {
        const wrapper = mount(WorkoutSummaryModal, {
            props: { open: true },
        });

        const btnVolver = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Volver al entrenamiento');
        expect(btnVolver).toBeDefined();
        await btnVolver.trigger('click');

        expect(wrapper.emitted('cancel')).toBeTruthy();
    });

    it('guarda en backend y emite saved al hacer clic en Guardar y Finalizar Sesión', async () => {
        axios.post.mockResolvedValueOnce({
            data: {
                message: 'Sesión finalizada',
                resumen: {
                    volumen_total: 1000,
                    series_completadas: 2,
                    prs_superados: 1,
                    new_medals: [],
                },
            },
        });

        const expectedUuid = store.session.id;

        const wrapper = mount(WorkoutSummaryModal, {
            props: { open: true },
        });

        const btnGuardar = wrapper.findAll('button').find((b) =>
            b.text().includes('Guardar y Finalizar')
        );
        await btnGuardar.trigger('click');

        expect(axios.post).toHaveBeenCalledWith(
            '/api/sesiones/finalizar',
            expect.objectContaining({
                uuid: expectedUuid,
            })
        );

        // La sesión debió cerrarse
        expect(store.isActive).toBe(false);
        expect(wrapper.emitted('saved')).toBeTruthy();
    });
});
