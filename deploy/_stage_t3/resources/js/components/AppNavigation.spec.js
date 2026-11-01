import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import AppNavigation from './AppNavigation.vue';

/**
 * AppNavigation — navbar migrada de Alpine a Vue.
 *
 * Antes: `x-data="{ trainerOpen: false }"` + `@click.away` de Alpine.
 * Ahora: refs de Vue + composable `useClickOutside`.
 */

const stubs = {
    'global-search': { template: '<div data-test="global-search" />' },
};

function mountNav(overrides = {}) {
    return mount(AppNavigation, {
        global: { stubs },
        props: {
            links: [
                { label: 'Dashboard', href: '/dashboard', active: true },
                { label: 'Ejercicios', href: '/ejercicios', active: false },
            ],
            trainerLinks: [
                { label: 'Alumnos', href: '/trainer/alumnos', active: false, icon: '👥' },
            ],
            adminLinks: [
                { label: 'Membresías', href: '/admin/membresias', active: false, icon: '💳' },
            ],
            userName: 'Oscar Guerrero',
            userEmail: 'oscar@example.com',
            settingsHref: '/configuracion',
            profileHref: '/profile',
            logoutUrl: '/logout',
            csrfToken: 'test-token',
            dashboardHref: '/dashboard',
            historialHref: '/historial',
            activeRoute: '',
            ...overrides,
        },
        attachTo: document.body,
    });
}

describe('AppNavigation', () => {
    beforeEach(() => {
        // El componente lee window.darkMode al montar.
        window.darkMode = {
            isDark: () => true,
            toggle: vi.fn(),
        };
    });

    it('renderiza los links principales', () => {
        const wrapper = mountNav();
        expect(wrapper.text()).toContain('Dashboard');
        expect(wrapper.text()).toContain('Ejercicios');
        wrapper.unmount();
    });

    it('renderiza el nombre y email del usuario', () => {
        const wrapper = mountNav();
        expect(wrapper.text()).toContain('Oscar Guerrero');
        expect(wrapper.text()).toContain('oscar@example.com');
        wrapper.unmount();
    });

    it('muestra el dropdown Trainer con sus links', () => {
        const wrapper = mountNav();
        expect(wrapper.text()).toContain('Trainer');
        // El link del dropdown esta en el DOM (v-show, no v-if).
        expect(wrapper.text()).toContain('Alumnos');
        wrapper.unmount();
    });

    it('el dropdown Trainer arranca cerrado', () => {
        const wrapper = mountNav();
        const dropdown = wrapper.findAll('.absolute').find((d) => d.text().includes('Alumnos'));
        expect(dropdown.isVisible()).toBe(false);
        wrapper.unmount();
    });

    it('abre el dropdown Trainer al hacer click en el boton', async () => {
        const wrapper = mountNav();
        const btn = wrapper.findAll('button').find((b) => b.text().includes('Trainer'));
        await btn.trigger('click');

        const dropdown = wrapper.findAll('.absolute').find((d) => d.text().includes('Alumnos'));
        expect(dropdown.isVisible()).toBe(true);
        wrapper.unmount();
    });

    it('cierra el dropdown Trainer al hacer click fuera', async () => {
        const wrapper = mountNav();
        const btn = wrapper.findAll('button').find((b) => b.text().includes('Trainer'));
        await btn.trigger('click');

        let dropdown = wrapper.findAll('.absolute').find((d) => d.text().includes('Alumnos'));
        expect(dropdown.isVisible()).toBe(true);

        // Click fuera del dropdown (en el body).
        document.body.dispatchEvent(new Event('pointerdown', { bubbles: true }));
        await wrapper.vm.$nextTick();

        dropdown = wrapper.findAll('.absolute').find((d) => d.text().includes('Alumnos'));
        expect(dropdown.isVisible()).toBe(false);
        wrapper.unmount();
    });

    it('abre y cierra el menu de usuario', async () => {
        const wrapper = mountNav();
        // El boton de usuario es el que tiene el avatar.
        const userBtn = wrapper
            .findAll('button')
            .find((b) => b.find('.material-symbols-outlined').exists() && b.text().includes('O'));

        expect(userBtn).toBeTruthy();
        await userBtn.trigger('click');

        const menu = wrapper.findAll('.absolute').find((d) => d.text().includes('Cerrar Sesión'));
        expect(menu.isVisible()).toBe(true);

        document.body.dispatchEvent(new Event('pointerdown', { bubbles: true }));
        await wrapper.vm.$nextTick();

        const menuAfter = wrapper.findAll('.absolute').find((d) => d.text().includes('Cerrar Sesión'));
        expect(menuAfter.isVisible()).toBe(false);
        wrapper.unmount();
    });

    it('no renderiza los dropdowns si no hay links de ese rol', () => {
        const wrapper = mountNav({ trainerLinks: [], adminLinks: [] });
        expect(wrapper.text()).not.toContain('Alumnos');
        expect(wrapper.text()).not.toContain('Membresías');
        wrapper.unmount();
    });

    it('el form de logout incluye el token CSRF y la url', () => {
        const wrapper = mountNav();
        const form = wrapper.find('form[method="POST"]');
        expect(form.exists()).toBe(true);
        expect(form.attributes('action')).toBe('/logout');
        expect(wrapper.find('input[name="_token"]').attributes('value')).toBe('test-token');
        wrapper.unmount();
    });

    it('no contiene directivas de Alpine en el markup renderizado', () => {
        const wrapper = mountNav();
        const html = wrapper.html();
        expect(html).not.toContain('x-data');
        expect(html).not.toContain('x-show');
        expect(html).not.toContain('x-on:');
        expect(html).not.toContain('x-transition');
        expect(html).not.toContain('@click.away');
        wrapper.unmount();
    });

    it('el boton de tema llama a window.darkMode.toggle()', async () => {
        const wrapper = mountNav();
        const toggle = vi.fn();
        window.darkMode = { isDark: () => true, toggle };

        const themeBtn = wrapper.find('button[aria-label="Cambiar tema"]');
        await themeBtn.trigger('click');

        expect(toggle).toHaveBeenCalledTimes(1);
        wrapper.unmount();
    });
});
