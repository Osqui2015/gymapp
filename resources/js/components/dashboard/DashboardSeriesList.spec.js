import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import DashboardSeriesList from './DashboardSeriesList.vue';

describe('DashboardSeriesList.vue (Acordeón por ejercicio)', () => {
    const sampleFilas = [
        {
            uid: '1-Día 1-1',
            ejercicio_nombre: 'Press de banca',
            series_numero: 1,
            reps_min: '4',
            reps_max: '6',
            reps_realizadas: null,
            peso: null,
            descanso_min: 2,
            completado: false,
            esfuerzo_tipo: null,
            esfuerzo_valor: null,
            notas: 'Bloque progresivo 2x6 RIR 1 + 2x4 RIR 0',
        },
        {
            uid: '1-Día 1-2',
            ejercicio_nombre: 'Press de banca',
            series_numero: 2,
            reps_min: '4',
            reps_max: '6',
            reps_realizadas: null,
            peso: null,
            descanso_min: 2,
            completado: false,
            esfuerzo_tipo: null,
            esfuerzo_valor: null,
            notas: 'Bloque progresivo 2x6 RIR 1 + 2x4 RIR 0',
        },
        {
            uid: '2-Día 1-1',
            ejercicio_nombre: 'Press inclinado',
            series_numero: 1,
            reps_min: '8',
            reps_max: '8',
            reps_realizadas: 8,
            peso: 30,
            descanso_min: 1.5,
            completado: true,
            esfuerzo_tipo: 'rir',
            esfuerzo_valor: 1,
            notas: 'Rest-pause al fallo',
        },
    ];

    const defaultProps = {
        filasSerie: sampleFilas,
        diaIndex: 0,
        textoBotonSiguiente: 'Siguiente Día →',
        botonSiguienteClass: 'bg-green-600',
    };

    it('agrupa las series bajo el mismo nombre de ejercicio en lugar de repetir filas', () => {
        const wrapper = mount(DashboardSeriesList, { props: defaultProps });

        // Debe haber 2 acordeones de ejercicios (Press de banca y Press inclinado)
        const headerButtons = wrapper.findAll('button[aria-expanded]');
        expect(headerButtons.length).toBe(2);

        expect(wrapper.text()).toContain('Press de banca');
        expect(wrapper.text()).toContain('Press inclinado');
        expect(wrapper.text()).toContain('2 ejercicios');
    });

    it('muestra las notas técnicas en la cabecera del ejercicio si existen', () => {
        const wrapper = mount(DashboardSeriesList, { props: defaultProps });
        expect(wrapper.text()).toContain('Bloque progresivo 2x6 RIR 1 + 2x4 RIR 0');
        expect(wrapper.text()).toContain('Rest-pause al fallo');
    });

    it('muestra el número de series y el estado de completado por ejercicio', () => {
        const wrapper = mount(DashboardSeriesList, { props: defaultProps });

        // Press de banca tiene 0/2 completadas
        expect(wrapper.text()).toContain('0/2 series');

        // Press inclinado tiene 1/1 completadas
        expect(wrapper.text()).toContain('✓ 1/1 series');
    });

    it('permite colapsar y expandir un ejercicio al hacer clic en su cabecera', async () => {
        const wrapper = mount(DashboardSeriesList, { props: defaultProps });

        const firstAccordionBtn = wrapper.findAll('button[aria-expanded]')[0];
        expect(firstAccordionBtn.attributes('aria-expanded')).toBe('true');

        // Clic para colapsar
        await firstAccordionBtn.trigger('click');
        expect(firstAccordionBtn.attributes('aria-expanded')).toBe('false');

        // Clic para volver a abrir
        await firstAccordionBtn.trigger('click');
        expect(firstAccordionBtn.attributes('aria-expanded')).toBe('true');
    });

    it('emite guardar al marcar una serie como completada', async () => {
        const wrapper = mount(DashboardSeriesList, { props: defaultProps });

        const checkbox = wrapper.find('input[type="checkbox"]');
        await checkbox.setValue(true);

        expect(wrapper.emitted('guardar')).toBeTruthy();
        expect(wrapper.emitted('guardar')[0][0].ejercicio_nombre).toBe('Press de banca');
    });
});
