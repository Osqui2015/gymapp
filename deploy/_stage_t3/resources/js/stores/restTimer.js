import { defineStore } from 'pinia';

const STORAGE_KEY = 'gymapp_active_timer';
const SOUND_KEY = 'gymapp_timer_sound';
const VIBRATE_KEY = 'gymapp_timer_vibrate';

export const useRestTimerStore = defineStore('restTimer', {
    state: () => {
        let soundPref = true;
        let vibratePref = true;
        try {
            soundPref = localStorage.getItem(SOUND_KEY) !== 'false';
            vibratePref = localStorage.getItem(VIBRATE_KEY) !== 'false';
        } catch {
            // Ignorar errores de localStorage
        }

        return {
            active: false,
            paused: false,
            totalSeconds: 0,
            remainingSeconds: 0,
            exerciseName: '',
            endTime: null,
            pausedRemaining: null,
            soundEnabled: soundPref,
            vibrateEnabled: vibratePref,
            intervalId: null,
        };
    },

    getters: {
        formattedTime: (state) => {
            const mins = Math.floor(Math.max(0, state.remainingSeconds) / 60);
            const secs = Math.max(0, state.remainingSeconds) % 60;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        },
        progressRatio: (state) => {
            if (!state.totalSeconds) return 0;
            return Math.max(0, Math.min(1, state.remainingSeconds / state.totalSeconds));
        },
    },

    actions: {
        init() {
            this.restoreSession();
            if (typeof document !== 'undefined') {
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible' && this.active && !this.paused) {
                        this.recalculate();
                    }
                });
            }
        },

        start({ exerciseName = 'Descanso', durationSeconds = 90 }) {
            this.clearTimer();
            const total = Math.max(1, Math.round(durationSeconds));
            this.active = true;
            this.paused = false;
            this.totalSeconds = total;
            this.remainingSeconds = total;
            this.exerciseName = exerciseName;
            this.endTime = Date.now() + total * 1000;
            this.pausedRemaining = null;

            this.persistSession();
            this.runLoop();
        },

        pause() {
            if (!this.active || this.paused) return;
            this.paused = true;
            this.pausedRemaining = this.remainingSeconds;
            this.clearLoop();
            this.persistSession();
        },

        resume() {
            if (!this.active || !this.paused) return;
            this.paused = false;
            const remaining = this.pausedRemaining ?? this.remainingSeconds;
            this.endTime = Date.now() + remaining * 1000;
            this.pausedRemaining = null;
            this.persistSession();
            this.runLoop();
        },

        togglePause() {
            if (this.paused) {
                this.resume();
            } else {
                this.pause();
            }
        },

        addSeconds(seconds = 30) {
            if (!this.active) return;
            this.totalSeconds += seconds;
            if (this.paused) {
                this.pausedRemaining = (this.pausedRemaining ?? this.remainingSeconds) + seconds;
                this.remainingSeconds = this.pausedRemaining;
            } else {
                this.endTime = (this.endTime ?? Date.now()) + seconds * 1000;
                this.recalculate();
            }
            this.persistSession();
        },

        subtractSeconds(seconds = 15) {
            if (!this.active) return;
            if (this.paused) {
                const updated = Math.max(
                    1,
                    (this.pausedRemaining ?? this.remainingSeconds) - seconds
                );
                this.pausedRemaining = updated;
                this.remainingSeconds = updated;
            } else {
                const newRemaining = Math.max(1, this.remainingSeconds - seconds);
                this.endTime = Date.now() + newRemaining * 1000;
                this.recalculate();
            }
            this.persistSession();
        },

        skip() {
            this.clearTimer();
        },

        finish() {
            this.clearTimer();
            this.playAlert();
        },

        clearTimer() {
            this.clearLoop();
            this.active = false;
            this.paused = false;
            this.totalSeconds = 0;
            this.remainingSeconds = 0;
            this.exerciseName = '';
            this.endTime = null;
            this.pausedRemaining = null;
            this.clearPersistedSession();
        },

        clearLoop() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
        },

        runLoop() {
            this.clearLoop();
            this.intervalId = setInterval(() => {
                if (!this.paused) {
                    this.recalculate();
                }
            }, 1000);
        },

        recalculate() {
            if (!this.endTime) return;
            const diffMs = this.endTime - Date.now();
            const secs = Math.ceil(diffMs / 1000);
            if (secs <= 0) {
                this.finish();
            } else {
                this.remainingSeconds = secs;
                this.persistSession();
            }
        },

        toggleSound() {
            this.soundEnabled = !this.soundEnabled;
            try {
                localStorage.setItem(SOUND_KEY, String(this.soundEnabled));
            } catch {
                // ignore
            }
        },

        toggleVibrate() {
            this.vibrateEnabled = !this.vibrateEnabled;
            try {
                localStorage.setItem(VIBRATE_KEY, String(this.vibrateEnabled));
            } catch {
                // ignore
            }
        },

        playAlert() {
            // Sonido con Web Audio API sintetizado
            if (this.soundEnabled && typeof window !== 'undefined') {
                try {
                    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                    if (AudioContextClass) {
                        const ctx = new AudioContextClass();
                        const playTone = (freq, start, duration) => {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(freq, ctx.currentTime + start);
                            gain.gain.setValueAtTime(0.3, ctx.currentTime + start);
                            gain.gain.exponentialRampToValueAtTime(
                                0.001,
                                ctx.currentTime + start + duration
                            );
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(ctx.currentTime + start);
                            osc.stop(ctx.currentTime + start + duration);
                        };
                        playTone(880, 0, 0.25);
                        playTone(1174.66, 0.3, 0.4);
                    }
                } catch (e) {
                    console.warn('[RestTimer] Error reproduciendo audio:', e);
                }
            }

            // Vibración en dispositivos móviles soportados
            if (this.vibrateEnabled && typeof navigator !== 'undefined' && navigator.vibrate) {
                try {
                    navigator.vibrate([200, 100, 200, 100, 300]);
                } catch {
                    // ignore
                }
            }
        },

        persistSession() {
            if (typeof sessionStorage === 'undefined') return;
            try {
                if (this.active) {
                    sessionStorage.setItem(
                        STORAGE_KEY,
                        JSON.stringify({
                            active: this.active,
                            paused: this.paused,
                            totalSeconds: this.totalSeconds,
                            remainingSeconds: this.remainingSeconds,
                            exerciseName: this.exerciseName,
                            endTime: this.endTime,
                            pausedRemaining: this.pausedRemaining,
                        })
                    );
                } else {
                    sessionStorage.removeItem(STORAGE_KEY);
                }
            } catch {
                // ignore
            }
        },

        clearPersistedSession() {
            if (typeof sessionStorage === 'undefined') return;
            try {
                sessionStorage.removeItem(STORAGE_KEY);
            } catch {
                // ignore
            }
        },

        restoreSession() {
            if (typeof sessionStorage === 'undefined') return;
            try {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (!raw) return;
                const data = JSON.parse(raw);
                if (!data.active) return;

                this.active = true;
                this.paused = !!data.paused;
                this.totalSeconds = data.totalSeconds || 90;
                this.exerciseName = data.exerciseName || 'Descanso';
                this.pausedRemaining = data.pausedRemaining;

                if (this.paused) {
                    this.remainingSeconds = data.pausedRemaining || data.remainingSeconds || 0;
                } else if (data.endTime) {
                    this.endTime = data.endTime;
                    const diff = Math.ceil((data.endTime - Date.now()) / 1000);
                    if (diff <= 0) {
                        this.clearTimer();
                    } else {
                        this.remainingSeconds = diff;
                        this.runLoop();
                    }
                }
            } catch {
                this.clearTimer();
            }
        },
    },
});
