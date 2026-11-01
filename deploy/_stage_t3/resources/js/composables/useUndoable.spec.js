import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useUndoable } from './useUndoable';

describe('useUndoable', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('applies the change immediately (optimistic UI)', () => {
        const apply = vi.fn();
        const undo = vi.fn();
        const commit = vi.fn().mockResolvedValue();

        useUndoable({
            message: 'Test',
            apply,
            undo,
            commit,
            duration: 5000,
        });

        expect(apply).toHaveBeenCalledTimes(1);
        expect(undo).not.toHaveBeenCalled();
        expect(commit).not.toHaveBeenCalled();
    });

    it('commits after duration if not cancelled', async () => {
        const apply = vi.fn();
        const undo = vi.fn();
        const commit = vi.fn().mockResolvedValue();

        const result = useUndoable({
            message: 'Test',
            apply,
            undo,
            commit,
            duration: 5000,
        });

        // Avanzar el tiempo
        await vi.advanceTimersByTimeAsync(5000);

        const resolved = await result;
        expect(commit).toHaveBeenCalledTimes(1);
        expect(undo).not.toHaveBeenCalled();
        expect(resolved).toEqual({ cancelled: false, executed: true });
    });

    it('reverts (undo) and skips commit when user clicks Undo', async () => {
        const apply = vi.fn();
        const undo = vi.fn();
        const commit = vi.fn().mockResolvedValue();

        const result = useUndoable({
            message: 'Test',
            apply,
            undo,
            commit,
            duration: 5000,
        });

        // Simular click en "Deshacer" via el toast action
        // (obtenemos el último toast del store)
        // Lo más simple: importar el store y buscar el action
        const { useToastStore } = await import('../stores/toast');
        const toastStore = useToastStore();
        const lastToast = toastStore.toasts[toastStore.toasts.length - 1];
        lastToast.action.onClick();

        const resolved = await result;
        expect(undo).toHaveBeenCalledTimes(1);
        expect(commit).not.toHaveBeenCalled();
        expect(resolved).toEqual({ cancelled: true, executed: false });
    });

    it('reverts (undo) and calls onError when commit fails', async () => {
        const apply = vi.fn();
        const undo = vi.fn();
        const onError = vi.fn();
        const error = new Error('Server error');
        const commit = vi.fn().mockRejectedValue(error);

        const result = useUndoable({
            message: 'Test',
            apply,
            undo,
            commit,
            onError,
            duration: 5000,
        });

        await vi.advanceTimersByTimeAsync(5000);

        const resolved = await result;
        expect(commit).toHaveBeenCalled();
        expect(undo).toHaveBeenCalledTimes(1); // revert visual
        expect(onError).toHaveBeenCalledWith(error);
        expect(resolved.executed).toBe(false);
        expect(resolved.cancelled).toBe(true);
    });

    it('throws if apply, undo, or commit is not a function', () => {
        expect(() => {
            useUndoable({
                message: 'Test',
                apply: 'not a function',
                undo: () => {},
                commit: () => {},
            });
        }).toThrow(/apply, undo y commit son obligatorios/);
    });

    it('uses default duration of 5000ms if not specified', async () => {
        const apply = vi.fn();
        const undo = vi.fn();
        const commit = vi.fn().mockResolvedValue();

        const result = useUndoable({
            message: 'Test',
            apply,
            undo,
            commit,
        });

        // Después de 4999ms, no debe haber commiteado
        await vi.advanceTimersByTimeAsync(4999);
        expect(commit).not.toHaveBeenCalled();

        // Después de 5000ms (total), debe commitear
        await vi.advanceTimersByTimeAsync(1);
        const resolved = await result;
        expect(commit).toHaveBeenCalledTimes(1);
    });
});
