import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import MobileBottomNav from './MobileBottomNav.vue';

/**
 * MobileBottomNav — barra inferior mobile + sheet de menu.
 *
 * Antes: Blade + Alpine (`x-data="{ menuOpen: false }"`).
 * Ahora: Vue con un unico ref `menuOpen`.
 */

function mountNav(overrides = {}) {
    return mount(MobileBottomNav, {
        props: {
            primary: [
                { label: 'Inicio', href: '/dashboard', icon: 'dashboard', active: true },
                { label: 'Rutinas', href: '/rutinas', icon: 'assignment', active: false },
                { label: 'Historial', href: '/historial', icon: 'history', active: false },
                { label: 'Progreso', href: '/progreso', icon: 'monitoring', active: false },
            ],
            trainerLinks: [{ label: 'Alumnos', href: '/trainer/alumnos', icon: 'groups' }],
            adminLinks: [{ label: 'Membresías', href: '/admin/membresias', icon: 'card_membership' }],
            userName: 'Oscar Guerrero',
            roleLabel: 'administrador',
            settingsHref: '/configuracion',
            profileHref: '/profile',
            logoutUrl: '/logout',
            csrfToken: 'test-token',
            ejerciciosHref: '/ejercicios',
            ejerciciosActive: false,
            isStaff: true,
            isAdmin: true,
            ...overrides,
        },
        attachTo: document.body,
    });
}

describe('MobileBottomNav', () => {
    it('renderiza los 4 items principales', () => {
        const wrapper = mountNav();
        const nav = wrapper.find('nav[data-purpose="bottom-persistent-navigation"]');
        expect(nav.exists()).toBe(true);
        expect(nav.text()).toContain('Inicio');
        expect(nav.text()).toContain('Rutinas');
        expect(nav.text()).toContain('Historial');
        expect(nav.text()).toContain('Progreso');
        wrapper.unmount();
    });

    it('el sheet arranca cerrado', () => {
        const wrapper = mountNav();
        // El sheet es el div con `rounded-t-3xl` (el contenedor raiz tambien
        // es .fixed, asi que hay que ser especifico).
        const sheet = wrapper.find('.rounded-t-3xl');
        expect(sheet.exists()).toBe(true);
        expect(sheet.isVisible()).toBe(false);
        wrapper.unmount();
    });

    it('abre el sheet al hacer click en el boton Menu', async () => {
        const wrapper = mountNav();
        const menuBtn = wrapper.findAll('button').find((b) => b.text().includes('Menú'));
        await menuBtn.trigger('click');

        const sheet = wrapper.find('.rounded-t-3xl');
        expect(sheet.isVisible()).toBe(true);
        wrapper.unmount();
    });

    it('cierra el sheet al hacer click en el backdrop', async () => {
        const wrapper = mountNav();
        const menuBtn = wrapper.findAll('button').find((b) => b.text().includes('Menú'));
        await menuBtn.trigger('click');

        // El backdrop es el div fijo con fondo negro (sin contenido).
        const backdrop = wrapper
            .findAll('.fixed')
            .find((el) => el.classes().includes('bg-black/70'));
        expect(backdrop).toBeTruthy();
        await backdrop.trigger('click');

        expect(wrapper.find('.rounded-t-3xl').isVisible()).toBe(false);
        wrapper.unmount();
    });

    it('cierra el sheet con la tecla Escape', async () => {
        const wrapper = mountNav();
        const menuBtn = wrapper.findAll('button').find((b) => b.text().includes('Menú'));
        await menuBtn.trigger('click');
        expect(wrapper.find('.rounded-t-3xl').isVisible()).toBe(true);

        // El listener de Escape está en `window` (en Vue 3 no existe
        // `@keydown.escape.window`).
        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.rounded-t-3xl').isVisible()).toBe(false);
        wrapper.unmount();
    });

    it('Escape no hace nada si el sheet esta cerrado', async () => {
        const wrapper = mountNav();

        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.rounded-t-3xl').isVisible()).toBe(false);
        wrapper.unmount();
    });

    it('muestra los paneles Trainer y Admin cuando corresponde', async () => {
        const wrapper = mountNav();
        const menuBtn = wrapper.findAll('button').find((b) => b.text().includes('Menú'));
        await menuBtn.trigger('click');

        expect(wrapper.text()).toContain('Panel Trainer');
        expect(wrapper.text()).toContain('Panel Admin');
        expect(wrapper.text()).toContain('Alumnos');
        expect(wrapper.text()).toContain('Membresías');
        wrapper.unmount();
    });

    it('oculta los paneles Trainer y Admin para un alumno', async () => {
        const wrapper = mountNav({
            trainerLinks: [],
            adminLinks: [],
            isStaff: false,
            isAdmin: false,
        });
        const menuBtn = wrapper.findAll('button').find((b) => b.text().includes('Menú'));
        await menuBtn.trigger('click');

        expect(wrapper.text()).not.toContain('Panel Trainer');
        expect(wrapper.text()).not.toContain('Panel Admin');
        wrapper.unmount();
    });

    it('el form de logout incluye el token CSRF', () => {
        const wrapper = mountNav();
        const form = wrapper.find('form[method="POST"]');
        expect(form.exists()).toBe(true);
        expect(form.attributes('action')).toBe('/logout');
        expect(wrapper.find('input[name="_token"]').attributes('value')).toBe('test-token');
        wrapper.unmount();
    });

    it('muestra el badge en el boton Menu para admin', () => {
        const wrapper = mountNav({ isAdmin: true, isStaff: true });
        const menuBtn = wrapper.findAll('button').find((b) => b.text().includes('Menú'));
        expect(menuBtn.find('.bg-rose-500').exists()).toBe(true);
        wrapper.unmount();
    });

    it('no contiene directivas de Alpine en el markup renderizado', () => {
        const wrapper = mountNav();
        const html = wrapper.html();
        expect(html).not.toContain('x-data');
        expect(html).not.toContain('x-show');
        expect(html).not.toContain('x-on:');
        expect(html).not.toContain('x-transition');
        wrapper.unmount();
    });
});
