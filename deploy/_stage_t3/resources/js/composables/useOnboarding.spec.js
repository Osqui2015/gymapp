import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useOnboarding } from './useOnboarding';

describe('useOnboarding', () => {
    const STORAGE_PREFIX = 'onboarding_done_';
    const steps = [
        { selector: '[data-tour="a"]', title: 'A', body: 'Body A', position: 'bottom' },
        { selector: '[data-tour="b"]', title: 'B', body: 'Body B', position: 'top' },
        { selector: '[data-tour="c"]', title: 'C', body: 'Body C', position: 'left' },
    ];

    beforeEach(() => {
        localStorage.clear();
    });

    it('shouldShow es true si el tour no se completó', () => {
        const tour = useOnboarding('test-1', steps);
        expect(tour.shouldShow()).toBe(true);
    });

    it('shouldShow es false si el tour ya se completó', () => {
        localStorage.setItem(STORAGE_PREFIX + 'test-1', '1');
        const tour = useOnboarding('test-1', steps);
        expect(tour.shouldShow()).toBe(false);
    });

    it('start() activa el tour y arranca en step 0', () => {
        const tour = useOnboarding('test-1', steps);
        tour.start();
        expect(tour.isActive.value).toBe(true);
        expect(tour.currentStep.value).toBe(0);
        expect(tour.step.value).toEqual(steps[0]);
    });

    it('next() avanza al siguiente step', () => {
        const tour = useOnboarding('test-1', steps);
        tour.start();
        tour.next();
        expect(tour.currentStep.value).toBe(1);
        expect(tour.step.value).toEqual(steps[1]);
    });

    it('next() en el último step marca el tour como visto', () => {
        const tour = useOnboarding('test-1', steps);
        tour.start();
        tour.next(); // 1
        tour.next(); // 2 (último)
        tour.next(); // finish
        expect(tour.isActive.value).toBe(false);
        expect(localStorage.getItem(STORAGE_PREFIX + 'test-1')).toBe('1');
    });

    it('prev() vuelve al step anterior', () => {
        const tour = useOnboarding('test-1', steps);
        tour.start();
        tour.next();
        tour.next();
        expect(tour.currentStep.value).toBe(2);
        tour.prev();
        expect(tour.currentStep.value).toBe(1);
    });

    it('prev() no hace nada en el primer step', () => {
        const tour = useOnboarding('test-1', steps);
        tour.start();
        tour.prev();
        expect(tour.currentStep.value).toBe(0);
    });

    it('skip() cierra el tour y marca como visto', () => {
        const tour = useOnboarding('test-1', steps);
        tour.start();
        tour.skip();
        expect(tour.isActive.value).toBe(false);
        expect(localStorage.getItem(STORAGE_PREFIX + 'test-1')).toBe('1');
    });

    it('progress refleja el step actual', () => {
        const tour = useOnboarding('test-1', steps);
        tour.start();
        expect(tour.progress.value).toBe(33); // 1/3

        tour.next();
        expect(tour.progress.value).toBe(67); // 2/3

        tour.next();
        expect(tour.progress.value).toBe(100); // 3/3
    });

    it('isFirst y isLast funcionan correctamente', () => {
        const tour = useOnboarding('test-1', steps);
        tour.start();
        expect(tour.isFirst.value).toBe(true);
        expect(tour.isLast.value).toBe(false);

        tour.next();
        tour.next();
        expect(tour.isFirst.value).toBe(false);
        expect(tour.isLast.value).toBe(true);
    });

    it('reset() permite que el tour vuelva a mostrarse', () => {
        localStorage.setItem(STORAGE_PREFIX + 'test-1', '1');
        const tour = useOnboarding('test-1', steps);
        expect(tour.shouldShow()).toBe(false);

        tour.reset();
        expect(tour.shouldShow()).toBe(true);
    });

    it('start() no hace nada si no hay steps', () => {
        const tour = useOnboarding('test-empty', []);
        tour.start();
        expect(tour.isActive.value).toBe(false);
    });
});
