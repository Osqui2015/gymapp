import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppModal from './AppModal.vue';

/**
 * AppModal — modal reutilizable migrado de Alpine a Vue.
 *
 * Antes: `x-data="{ show: ... }"` + focus trap en Alpine +
 * eventos `open-modal` / `close-modal` / `close` en `window`.
 */

describe('AppModal', () => {
    it('no renderiza nada cuando show es false', () => {
        const wrapper = mount(AppModal, {
            props: { name: 'test-modal', show: false },
            slots: { default: '<p>contenido</p>' },
        });
        expect(wrapper.find('.fixed').exists()).toBe(false);
        wrapper.unmount();
    });

    it('renderiza el contenido cuando show es true', () => {
        const wrapper = mount(AppModal, {
            props: { name: 'test-modal', show: true },
            slots: { default: '<p>contenido</p>' },
        });
        expect(wrapper.text()).toContain('contenido');
        wrapper.unmount();
    });

    it('se abre con el evento open-modal del window', async () => {
        const wrapper = mount(AppModal, {
            props: { name: 'confirm-user-deletion', show: false },
            slots: { default: '<p>contenido</p>' },
        });

        expect(wrapper.find('.fixed').exists()).toBe(false);

        window.dispatchEvent(
            new CustomEvent('open-modal', { detail: 'confirm-user-deletion' })
        );
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.fixed').exists()).toBe(true);
        wrapper.unmount();
    });

    it('ignora open-modal con otro nombre', async () => {
        const wrapper = mount(AppModal, {
            props: { name: 'otro-modal', show: false },
            slots: { default: '<p>contenido</p>' },
        });

        window.dispatchEvent(
            new CustomEvent('open-modal', { detail: 'confirm-user-deletion' })
        );
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.fixed').exists()).toBe(false);
        wrapper.unmount();
    });

    it('se cierra con close-modal (detail = nombre)', async () => {
        const wrapper = mount(AppModal, {
            props: { name: 'test-modal', show: true },
            slots: { default: '<p>contenido</p>' },
        });

        expect(wrapper.find('.fixed').exists()).toBe(true);

        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'test-modal' }));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.fixed').exists()).toBe(false);
        wrapper.unmount();
    });

    it('se cierra con el evento close generico (boton Cancelar)', async () => {
        const wrapper = mount(AppModal, {
            props: { name: 'test-modal', show: true },
            slots: { default: '<p>contenido</p>' },
        });

        window.dispatchEvent(new CustomEvent('close'));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.fixed').exists()).toBe(false);
        wrapper.unmount();
    });

    it('bloquea el scroll del body mientras esta abierto', async () => {
        const wrapper = mount(AppModal, {
            props: { name: 'test-modal', show: true },
            slots: { default: '<p>contenido</p>' },
            attachTo: document.body,
        });

        expect(document.body.classList.contains('overflow-y-hidden')).toBe(true);

        window.dispatchEvent(new CustomEvent('close'));
        await wrapper.vm.$nextTick();

        expect(document.body.classList.contains('overflow-y-hidden')).toBe(false);
        wrapper.unmount();
    });

    it('emite el evento close al hacer click en el backdrop', async () => {
        const wrapper = mount(AppModal, {
            props: { name: 'test-modal', show: true },
            slots: { default: '<p>contenido</p>' },
        });

        // El backdrop es el primer hijo: div.fixed.inset-0 con el overlay.
        const backdrop = wrapper.find('.fixed > .fixed');
        expect(backdrop.exists()).toBe(true);
        await backdrop.trigger('click');

        expect(wrapper.emitted('close')).toBeTruthy();
        wrapper.unmount();
    });

    it('aplica el max-width segun la prop', () => {
        const wrapper = mount(AppModal, {
            props: { name: 'test-modal', show: true, maxWidth: 'lg' },
            slots: { default: '<p>contenido</p>' },
        });

        expect(wrapper.html()).toContain('sm:max-w-lg');
        wrapper.unmount();
    });

    it('el focus trap mueve el foco al primer elemento focusable', async () => {
        const wrapper = mount(AppModal, {
            props: { name: 'test-modal', show: true },
            slots: {
                default: '<button id="btn1">Uno</button><button id="btn2">Dos</button>',
            },
            attachTo: document.body,
        });

        await new Promise((r) => setTimeout(r, 20));

        expect(document.activeElement.id).toBe('btn1');
        wrapper.unmount();
    });

    it('no contiene directivas de Alpine en el markup renderizado', () => {
        const wrapper = mount(AppModal, {
            props: { name: 'test-modal', show: true },
            slots: { default: '<p>contenido</p>' },
        });
        const html = wrapper.html();
        expect(html).not.toContain('x-data');
        expect(html).not.toContain('x-show');
        expect(html).not.toContain('x-on:');
        expect(html).not.toContain('x-transition');
        wrapper.unmount();
    });
});
