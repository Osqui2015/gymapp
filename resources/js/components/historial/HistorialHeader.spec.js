import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import HistorialHeader from './HistorialHeader.vue';

describe('HistorialHeader', () => {
    const defaultStats = {
        ejercicios: 8,
        totalSeries: 32,
        tonelajeTotal: '1,450 kg',
        pesoPromedio: '65.5',
        repsPromedio: '10.2',
    };

    it('renderiza correctamente las estadísticas incluyendo tonelaje total', () => {
        const wrapper = mount(HistorialHeader, {
            props: {
                isTrainerOrAdmin: false,
                alumnos: [],
                selectedAlumnoId: null,
                activeTab: 'matrix',
                showKeyExercisesTab: false,
                canExport: true,
                stats: defaultStats,
            },
        });

        expect(wrapper.text()).toContain('Historial de entrenamiento');
        expect(wrapper.text()).toContain('8');
        expect(wrapper.text()).toContain('32');
        expect(wrapper.text()).toContain('1,450 kg');
        expect(wrapper.text()).toContain('Volumen (Tonelaje)');
        expect(wrapper.text()).toContain('65.5 kg');
        expect(wrapper.text()).toContain('10.2');
    });

    it('emite eventos de exportación CSV y PDF al hacer click', async () => {
        const wrapper = mount(HistorialHeader, {
            props: {
                isTrainerOrAdmin: false,
                alumnos: [],
                selectedAlumnoId: null,
                activeTab: 'matrix',
                showKeyExercisesTab: false,
                canExport: true,
                stats: defaultStats,
            },
        });

        const exportCsvBtn = wrapper.find('button[aria-label="Exportar historial a CSV"]');
        expect(exportCsvBtn.exists()).toBe(true);
        await exportCsvBtn.trigger('click');
        expect(wrapper.emitted('export-csv')).toBeTruthy();

        const exportPdfBtn = wrapper.find('button[aria-label="Exportar historial a PDF"]');
        expect(exportPdfBtn.exists()).toBe(true);
        await exportPdfBtn.trigger('click');
        expect(wrapper.emitted('export-pdf')).toBeTruthy();
    });

    it('emite tab-change al hacer click en una pestaña', async () => {
        const wrapper = mount(HistorialHeader, {
            props: {
                isTrainerOrAdmin: false,
                alumnos: [],
                selectedAlumnoId: null,
                activeTab: 'matrix',
                showKeyExercisesTab: false,
                canExport: false,
                stats: defaultStats,
            },
        });

        const tabButtons = wrapper.findAll('button.pb-4');
        expect(tabButtons.length).toBeGreaterThan(1);
        await tabButtons[1].trigger('click');
        expect(wrapper.emitted('tab-change')).toBeTruthy();
        expect(wrapper.emitted('tab-change')[0][0]).toBe('evolution');
    });
});
