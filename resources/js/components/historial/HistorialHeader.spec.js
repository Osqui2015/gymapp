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

        // Kinetic Obsidian: el header ahora dice "Historial de Sesiones" (mockup)
        expect(wrapper.text()).toContain('Historial de Sesiones');
        expect(wrapper.text()).toContain('8');
        expect(wrapper.text()).toContain('32');
        expect(wrapper.text()).toContain('1,450');
        // Volumen ahora se muestra como "1,450" + "ton" (sin paréntesis "Tonelaje")
        expect(wrapper.text()).toContain('Volumen');
        expect(wrapper.text()).toContain('65.5');
        // Reps prom ahora vive como sub del stat "Series"
        expect(wrapper.text()).toContain('reps prom.');
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

        // Kinetic Obsidian: las tabs ahora son segmented pills (ObsidianSegmentedTabs).
        // Buscamos los buttons de tabs (todos los <button> dentro del segmented control)
        const tabButtons = wrapper.findAll('button.flex-1');
        expect(tabButtons.length).toBeGreaterThan(1);
        // El segundo tab (index 1) es "Matriz Cargas" con id "evolution"
        await tabButtons[1].trigger('click');
        expect(wrapper.emitted('tab-change')).toBeTruthy();
        expect(wrapper.emitted('tab-change')[0][0]).toBe('evolution');
    });
});
