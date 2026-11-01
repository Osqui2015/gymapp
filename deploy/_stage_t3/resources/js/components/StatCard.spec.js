import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import StatCard from './StatCard.vue';

describe('StatCard', () => {
    it('renderiza label y value', () => {
        const wrapper = mount(StatCard, {
            props: { label: 'Total usuarios', value: 42 },
        });
        expect(wrapper.text()).toContain('Total usuarios');
        expect(wrapper.text()).toContain('42');
    });

    it('renderiza sub cuando se pasa', () => {
        const wrapper = mount(StatCard, {
            props: { label: 'Activos', value: '80%', sub: '8 de 10' },
        });
        expect(wrapper.text()).toContain('8 de 10');
    });

    it('no renderiza sub cuando no se pasa', () => {
        const wrapper = mount(StatCard, {
            props: { label: 'X', value: 1 },
        });
        // El elemento p con sub no debería existir
        expect(wrapper.findAll('p')).toHaveLength(2); // label + value, sin sub
    });

    it('aplica color al value', () => {
        const wrapper = mount(StatCard, {
            props: { label: 'Test', value: 5, color: 'emerald' },
        });
        // El último <p> (value) debe tener la clase emerald
        const valueP = wrapper.findAll('p').at(1);
        expect(valueP.classes().join(' ')).toContain('text-emerald-600');
    });

    it('color default gray si no se especifica', () => {
        const wrapper = mount(StatCard, {
            props: { label: 'Test', value: 5 },
        });
        const valueP = wrapper.findAll('p').at(1);
        expect(valueP.classes().join(' ')).toContain('text-gray-900');
    });

    it('color red para valor crítico', () => {
        const wrapper = mount(StatCard, {
            props: { label: 'Churn', value: '60%', color: 'red' },
        });
        const valueP = wrapper.findAll('p').at(1);
        expect(valueP.classes().join(' ')).toContain('text-red-600');
    });
});
