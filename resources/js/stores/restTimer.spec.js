import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useRestTimerStore } from './restTimer';

describe('useRestTimerStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.useFakeTimers();
        sessionStorage.clear();
        localStorage.clear();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('starts with default inactive state', () => {
        const store = useRestTimerStore();
        expect(store.active).toBe(false);
        expect(store.paused).toBe(false);
        expect(store.remainingSeconds).toBe(0);
        expect(store.formattedTime).toBe('00:00');
    });

    it('starts countdown with target time and exercise name', () => {
        const store = useRestTimerStore();
        store.start({ exerciseName: 'Press Banca', durationSeconds: 60 });

        expect(store.active).toBe(true);
        expect(store.paused).toBe(false);
        expect(store.totalSeconds).toBe(60);
        expect(store.remainingSeconds).toBe(60);
        expect(store.exerciseName).toBe('Press Banca');
        expect(store.formattedTime).toBe('01:00');
    });

    it('counts down each second', () => {
        const store = useRestTimerStore();
        store.start({ exerciseName: 'Sentadilla', durationSeconds: 10 });

        vi.advanceTimersByTime(3000);
        expect(store.remainingSeconds).toBe(7);
        expect(store.formattedTime).toBe('00:07');
    });

    it('pauses and resumes accurately without losing time', () => {
        const store = useRestTimerStore();
        store.start({ exerciseName: 'Dominadas', durationSeconds: 30 });

        vi.advanceTimersByTime(5000);
        expect(store.remainingSeconds).toBe(25);

        store.pause();
        expect(store.paused).toBe(true);

        vi.advanceTimersByTime(10000);
        // While paused, remainingSeconds stays unchanged
        expect(store.remainingSeconds).toBe(25);

        store.resume();
        expect(store.paused).toBe(false);

        vi.advanceTimersByTime(2000);
        expect(store.remainingSeconds).toBe(23);
    });

    it('allows adding 30s and subtracting 15s', () => {
        const store = useRestTimerStore();
        store.start({ exerciseName: 'Remo', durationSeconds: 30 });

        store.addSeconds(30);
        expect(store.remainingSeconds).toBe(60);
        expect(store.totalSeconds).toBe(60);

        store.subtractSeconds(15);
        expect(store.remainingSeconds).toBe(45);
    });

    it('skips and finishes correctly', () => {
        const store = useRestTimerStore();
        store.start({ exerciseName: 'Fondos', durationSeconds: 20 });

        store.skip();
        expect(store.active).toBe(false);
        expect(store.remainingSeconds).toBe(0);
    });

    it('toggles sound and vibrate settings', () => {
        const store = useRestTimerStore();
        expect(store.soundEnabled).toBe(true);
        store.toggleSound();
        expect(store.soundEnabled).toBe(false);
        expect(localStorage.getItem('gymapp_timer_sound')).toBe('false');

        expect(store.vibrateEnabled).toBe(true);
        store.toggleVibrate();
        expect(store.vibrateEnabled).toBe(false);
    });
});
