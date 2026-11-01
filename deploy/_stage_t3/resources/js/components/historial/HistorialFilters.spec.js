import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import HistorialFilters from './HistorialFilters.vue';

describe('HistorialFilters', () => {
    const defaultModelValue = {
        search: '',
        rutina: '',
        dia: '',
        periodo: 'all',
        soloCompletados: false,
        orden: 'fecha_desc',
    };

    it('renderiza correctamente los campos con valores por defecto', () => {
        const wrapper = mount(HistorialFilters, {
            props: {
                modelValue: defaultModelValue,
                rutinasDisponibles: ['Hipertrofia', 'Fuerza'],
                diasDisponibles: ['Día 1', 'Día 2'],
                totalFiltrados: 20,
                totalOriginal: 20,
            },
        });

        expect(wrapper.text()).toContain('Filtros y Búsqueda');
        expect(wrapper.find('input[type="text"]').element.value).toBe('');
        // No debe mostrar botón de limpiar filtros cuando no hay filtros activos
        expect(wrapper.text()).not.toContain('Limpiar filtros');
    });

    it('emite update:modelValue al escribir en el campo de búsqueda', async () => {
        const wrapper = mount(HistorialFilters, {
            props: {
                modelValue: defaultModelValue,
                rutinasDisponibles: [],
                diasDisponibles: [],
                totalFiltrados: 10,
                totalOriginal: 10,
            },
        });

        const input = wrapper.find('input[type="text"]');
        await input.setValue('Banca');

        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')[0][0]).toMatchObject({
            search: 'Banca',
        });
    });

    it('muestra badge de filtros activos y botón de limpiar cuando hay un filtro aplicado', async () => {
        const wrapper = mount(HistorialFilters, {
            props: {
                modelValue: {
                    ...defaultModelValue,
                    search: 'Sentadilla',
                },
                rutinasDisponibles: [],
                diasDisponibles: [],
                totalFiltrados: 5,
                totalOriginal: 25,
            },
        });

        expect(wrapper.text()).toContain('Filtros activos (5 de 25)');
        expect(wrapper.text()).toContain('Limpiar filtros');

        const clearBtn = wrapper.find('button.text-rose-600');
        expect(clearBtn.exists()).toBe(true);
        await clearBtn.trigger('click');

        expect(wrapper.emitted('limpiar')).toBeTruthy();
    });

    it('emite cambio cuando se selecciona un día rápido', async () => {
        const wrapper = mount(HistorialFilters, {
            props: {
                modelValue: defaultModelValue,
                rutinasDisponibles: ['Hipertrofia'],
                diasDisponibles: ['Día 1', 'Día 2'],
                totalFiltrados: 10,
                totalOriginal: 10,
            },
        });

        const dayButtons = wrapper.findAll('div.flex.items-center.gap-1\\.5 button');
        // El primer botón es "Todos", el segundo es "Día 1"
        expect(dayButtons.length).toBe(3);
        await dayButtons[1].trigger('click');

        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')[0][0]).toMatchObject({
            dia: 'Día 1',
        });
    });
});
