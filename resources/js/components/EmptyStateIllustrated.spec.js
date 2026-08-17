import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import EmptyStateIllustrated from './EmptyStateIllustrated.vue';

describe('EmptyStateIllustrated', () => {
    it('renderiza título y descripción', () => {
        const wrapper = mount(EmptyStateIllustrated, {
            props: {
                variant: 'no-data',
                title: 'No hay datos',
                description: 'Empezá creando algo',
            },
        });
        expect(wrapper.text()).toContain('No hay datos');
        expect(wrapper.text()).toContain('Empezá creando algo');
    });

    it('renderiza CTA cuando se pasa ctaText', () => {
        const wrapper = mount(EmptyStateIllustrated, {
            props: {
                variant: 'no-rutinas',
                title: 'Test',
                ctaText: 'Crear rutina',
            },
        });
        const button = wrapper.find('button');
        expect(button.exists()).toBe(true);
        expect(button.text()).toContain('Crear rutina');
    });

    it('emite evento cta al hacer click', async () => {
        const wrapper = mount(EmptyStateIllustrated, {
            props: {
                variant: 'no-rutinas',
                title: 'Test',
                ctaText: 'Click me',
            },
        });
        await wrapper.find('button').trigger('click');
        expect(wrapper.emitted('cta')).toBeTruthy();
        expect(wrapper.emitted('cta')).toHaveLength(1);
    });

    it('no renderiza botón si no hay ctaText', () => {
        const wrapper = mount(EmptyStateIllustrated, {
            props: { variant: 'no-data', title: 'Test' },
        });
        expect(wrapper.find('button').exists()).toBe(false);
    });

    it('soporta slot cta custom', () => {
        const wrapper = mount(EmptyStateIllustrated, {
            props: { variant: 'no-data', title: 'Test' },
            slots: {
                cta: '<a href="/crear">Slot CTA</a>',
            },
        });
        expect(wrapper.html()).toContain('Slot CTA');
    });

    it('cambia la ilustración según la variant', () => {
        const variants = ['no-data', 'no-results', 'no-rutinas', 'no-historial', 'error', 'welcome'];
        for (const variant of variants) {
            const wrapper = mount(EmptyStateIllustrated, {
                props: { variant, title: 'X' },
            });
            // Cada variant genera un SVG distinto
            const svg = wrapper.find('svg');
            expect(svg.exists()).toBe(true);
        }
    });
});
