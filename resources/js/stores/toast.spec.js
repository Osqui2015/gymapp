import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { useToastStore } from './toast';

describe('useToastStore', () => {
    let toast;

    beforeEach(() => {
        vi.useFakeTimers();
        toast = useToastStore();
        toast.dismissAll();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('inicia vacío', () => {
        expect(toast.toasts).toEqual([]);
    });

    it('agrega un toast con string y type por default', () => {
        const id = toast.add('Hola');
        expect(toast.toasts).toHaveLength(1);
        expect(toast.toasts[0].message).toBe('Hola');
        expect(toast.toasts[0].type).toBe('info');
        expect(toast.toasts[0].id).toBe(id);
    });

    it('agrega un toast con objeto de opciones', () => {
        const onClick = vi.fn();
        const id = toast.add({
            message: 'Con acción',
            type: 'success',
            title: 'OK',
            action: { label: 'Click', onClick },
        });
        const t = toast.toasts[0];
        expect(t.type).toBe('success');
        expect(t.title).toBe('OK');
        expect(t.action.label).toBe('Click');
        expect(t.id).toBe(id);
    });

    it('success/error/warning/info setean el type correcto', () => {
        toast.success('s');
        toast.error('e');
        toast.warning('w');
        toast.info('i');
        expect(toast.toasts.map((t) => t.type)).toEqual(['success', 'error', 'warning', 'info']);
    });

    it('error usa duration 6000 por default', () => {
        toast.error('Boom');
        expect(toast.toasts[0].duration).toBe(6000);
    });

    it('warning usa duration 5000 por default', () => {
        toast.warning('Cuidado');
        expect(toast.toasts[0].duration).toBe(5000);
    });

    it('info y success usan duration 4000 por default', () => {
        toast.info('info');
        toast.success('ok');
        expect(toast.toasts[0].duration).toBe(4000);
        expect(toast.toasts[1].duration).toBe(4000);
    });

    it('opts.duration override el default del tipo', () => {
        toast.error('rápido', { duration: 1000 });
        expect(toast.toasts[0].duration).toBe(1000);
    });

    it('dismiss elimina el toast por id', () => {
        const id = toast.add('a');
        const id2 = toast.add('b');
        expect(toast.toasts).toHaveLength(2);
        toast.dismiss(id);
        expect(toast.toasts).toHaveLength(1);
        expect(toast.toasts[0].id).toBe(id2);
    });

    it('dismiss con id inexistente no rompe', () => {
        toast.add('a');
        toast.dismiss(999);
        expect(toast.toasts).toHaveLength(1);
    });

    it('dismissAll limpia todos los toasts', () => {
        toast.add('a');
        toast.add('b');
        toast.add('c');
        toast.dismissAll();
        expect(toast.toasts).toEqual([]);
    });

    it('persistent=true deshabilita el auto-dismiss', () => {
        toast.add({ message: 'Sticky', persistent: true });
        vi.advanceTimersByTime(10_000);
        expect(toast.toasts).toHaveLength(1);
    });

    it('duration=0 también deshabilita el auto-dismiss', () => {
        toast.add({ message: 'Sticky2', duration: 0 });
        vi.advanceTimersByTime(10_000);
        expect(toast.toasts).toHaveLength(1);
    });

    it('toasts normales se auto-eliminan después de duration', () => {
        toast.add({ message: 'Efímero', duration: 1000 });
        expect(toast.toasts).toHaveLength(1);
        vi.advanceTimersByTime(1001);
        expect(toast.toasts).toHaveLength(0);
    });

    it('cada toast tiene un id único', () => {
        const ids = new Set();
        for (let i = 0; i < 10; i++) {
            ids.add(toast.add('msg'));
        }
        expect(ids.size).toBe(10);
    });
});
