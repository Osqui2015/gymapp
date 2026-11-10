import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import DarkModeToggle from './DarkModeToggle.vue';
import theme from '../utils/theme';

describe('DarkModeToggle', () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.className = '';
        delete document.documentElement.dataset.theme;
        document.documentElement.style.colorScheme = '';
        theme.set('auto');
    });

    it('renderiza con el modo auto por defecto', () => {
        const wrapper = mount(DarkModeToggle);
        expect(wrapper.find('button[aria-label*="Automático"]').exists()).toBe(true);
        wrapper.unmount();
    });

    it('abre el menú desplegable al hacer click', async () => {
        const wrapper = mount(DarkModeToggle);
        await wrapper.find('button').trigger('click');
        expect(wrapper.text()).toContain('Claro');
        expect(wrapper.text()).toContain('Oscuro');
        expect(wrapper.text()).toContain('Automático (sistema)');
        wrapper.unmount();
    });

    it('muestra el checkmark según la preferencia guardada, no según el estado efectivo', async () => {
        theme.set('auto');
        const wrapper = mount(DarkModeToggle);
        await wrapper.find('button').trigger('click');

        // Los botones de opciones dentro del menú
        const buttons = wrapper.findAll('.dropdown-enter-active button, div.absolute button');
        const autoBtn = buttons.find((b) => b.text().includes('Automático'));
        expect(autoBtn?.text()).toContain('✓');

        // Cambiamos a 'light'
        const lightBtn = buttons.find((b) => b.text().includes('Claro'));
        await lightBtn?.trigger('click');

        expect(theme.get()).toBe('light');
        expect(theme.effective()).toBe('light');

        // Reabrimos el dropdown y verificamos que 'Claro' tiene el checkmark
        await wrapper.find('button').trigger('click');
        const updatedButtons = wrapper.findAll('div.absolute button');
        const updatedLightBtn = updatedButtons.find((b) => b.text().includes('Claro'));
        expect(updatedLightBtn?.text()).toContain('✓');

        wrapper.unmount();
    });

    it('permite volver a auto tras 3 selecciones o ciclos', async () => {
        const wrapper = mount(DarkModeToggle);
        await wrapper.find('button').trigger('click');

        // 1. Elegir oscuro
        let buttons = wrapper.findAll('div.absolute button');
        await buttons.find((b) => b.text().includes('Oscuro'))?.trigger('click');
        expect(theme.get()).toBe('dark');

        // 2. Elegir claro
        await wrapper.find('button').trigger('click');
        buttons = wrapper.findAll('div.absolute button');
        await buttons.find((b) => b.text().includes('Claro'))?.trigger('click');
        expect(theme.get()).toBe('light');

        // 3. Volver a auto
        await wrapper.find('button').trigger('click');
        buttons = wrapper.findAll('div.absolute button');
        await buttons.find((b) => b.text().includes('Automático'))?.trigger('click');
        expect(theme.get()).toBe('auto');

        wrapper.unmount();
    });
});
