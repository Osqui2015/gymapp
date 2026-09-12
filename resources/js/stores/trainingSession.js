/**
 * trainingSession — Store de Pinia que mantiene la sesion de entrenamiento activa.
 *
 * Si el user cierra la app o mata el navegador a mitad de un entrenamiento, al volver
 * la sesion se restaura desde localStorage y puede seguir donde quedo.
 *
 * Estructura persistida:
 *   {
 *     id: 'uuid',
 *     startedAt: '2026-09-04T19:30:00.000Z',
 *     rutina_nombre: 'Full Body A',
 *     dia: 'Lunes',
 *     ejercicios: [
 *       {
 *         nombre: 'Sentadilla',
 *         series_objetivo: 4,
 *         reps_min: '8',
 *         reps_max: '10',
 *         descanso_min: 2,
 *         superserie_grupo: null,
 *         series_completadas: 0,
 *         completed: false,
 *         sets: [ { peso: 80, reps: 10, tipo_serie: 'efectiva', esfuerzo_tipo: 'rir', esfuerzo_valor: 2, nota: '' } ]
 *       },
 *       ...
 *     ],
 *     currentEjercicioIndex: 0,
 *     currentSerieNumero: 1,
 *     isPaused: false,
 *     pausedAt: null,
 *     accumulatedPauseSeconds: 0,
 *     completedSets: [],
 *     endedAt: null
 *   }
 */
import { defineStore } from 'pinia';
import { computed, ref, watch } from 'vue';

const STORAGE_KEY = 'gymapp:training-session:v1';

const emptySession = () => ({
    id: null,
    startedAt: null,
    endedAt: null,
    rutina_nombre: null,
    dia: null,
    ejercicios: [],
    currentEjercicioIndex: 0,
    currentSerieNumero: 1,
    isPaused: false,
    pausedAt: null,
    accumulatedPauseSeconds: 0,
    completedSets: [],
});

const generateId = () => {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }
    return 'ts-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
};

const loadFromStorage = () => {
    if (typeof localStorage === 'undefined') return null;
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        // Si termino, no restaurar.
        if (parsed.endedAt) return null;
        return parsed;
    } catch {
        return null;
    }
};

const saveToStorage = (value) => {
    if (typeof localStorage === 'undefined') return;
    try {
        if (value && value.id) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(value));
        } else {
            localStorage.removeItem(STORAGE_KEY);
        }
    } catch {
        // localStorage no disponible o lleno
    }
};

export const useTrainingSessionStore = defineStore('trainingSession', () => {
    const session = ref(loadFromStorage() || emptySession());
    const undoStack = ref([]);

    // Persistir automaticamente cada vez que cambia.
    watch(session, (value) => saveToStorage(value), { deep: true });

    const isActive = computed(() => !!session.value.id && !session.value.endedAt);

    const currentEjercicio = computed(() => {
        if (!isActive.value) return null;
        return session.value.ejercicios[session.value.currentEjercicioIndex] || null;
    });

    const isPaused = computed(() => !!session.value.isPaused);

    const totalSeriesObjetivo = computed(() => {
        return (session.value.ejercicios || []).reduce(
            (acc, ej) => acc + (Number(ej.series_objetivo) || 0),
            0
        );
    });

    const totalSeriesCompletadas = computed(() => {
        return (session.value.ejercicios || []).reduce(
            (acc, ej) => acc + (Number(ej.series_completadas) || 0),
            0
        );
    });

    const volumenTotal = computed(() => {
        let total = 0;
        (session.value.ejercicios || []).forEach((ej) => {
            (ej.sets || []).forEach((s) => {
                const p = Number(s.peso) || 0;
                const r = Number(s.reps) || 0;
                if (p > 0 && r > 0) {
                    total += p * r;
                }
            });
        });
        return total;
    });

    const progresoPorcentaje = computed(() => {
        if (totalSeriesObjetivo.value <= 0) return 0;
        return Math.min(
            100,
            Math.round((totalSeriesCompletadas.value / totalSeriesObjetivo.value) * 100)
        );
    });

    const elapsed = computed(() => {
        if (!session.value.startedAt) return 0;
        const start = new Date(session.value.startedAt).getTime();
        const pauseAcc = session.value.accumulatedPauseSeconds || 0;
        let now = Date.now();
        if (session.value.isPaused && session.value.pausedAt) {
            now = new Date(session.value.pausedAt).getTime();
        }
        return Math.max(0, Math.floor((now - start) / 1000) - pauseAcc);
    });

    const canUndo = computed(() => undoStack.value.length > 0);

    /**
     * Inicia una sesion nueva.
     */
    const start = ({ rutina_nombre, dia, ejercicios }) => {
        undoStack.value = [];
        session.value = {
            id: generateId(),
            startedAt: new Date().toISOString(),
            endedAt: null,
            rutina_nombre,
            dia,
            ejercicios: (ejercicios || []).map((e) => ({
                nombre: e.ejercicio_nombre || e.nombre,
                series_objetivo: Number(e.series_objetivo || e.series || 0),
                reps_min: e.reps_min || '8',
                reps_max: e.reps_max || '10',
                descanso_min: Number(e.descanso_min ?? 1.5),
                superserie_grupo: e.superserie_grupo || null,
                series_completadas: 0,
                completed: false,
                sets: [],
            })),
            currentEjercicioIndex: 0,
            currentSerieNumero: 1,
            isPaused: false,
            pausedAt: null,
            accumulatedPauseSeconds: 0,
            completedSets: [],
        };
    };

    /**
     * Registra datos detallados de la serie actual y avanza.
     */
    const recordSet = ({
        peso = 0,
        reps = 0,
        tipo_serie = 'efectiva',
        esfuerzo_tipo = null,
        esfuerzo_valor = null,
        nota_user = '',
    } = {}) => {
        if (!isActive.value) return null;
        const ej = session.value.ejercicios[session.value.currentEjercicioIndex];
        if (!ej) return null;

        const currentSerieNum = session.value.currentSerieNumero;
        const setData = {
            ejercicio_nombre: ej.nombre,
            series_numero: currentSerieNum,
            peso: Number(peso) || 0,
            reps: Number(reps) || 0,
            tipo_serie,
            esfuerzo_tipo,
            esfuerzo_valor,
            nota_user,
            completed_at: new Date().toISOString(),
        };

        if (!ej.sets) ej.sets = [];
        ej.sets.push(setData);
        ej.series_completadas += 1;

        if (!session.value.completedSets) session.value.completedSets = [];
        session.value.completedSets.push(setData);

        // Guardar para Deshacer
        undoStack.value.push({
            ejercicioIndex: session.value.currentEjercicioIndex,
            serieNumero: currentSerieNum,
            setData,
        });

        // Avance automático
        if (ej.series_completadas >= ej.series_objetivo) {
            ej.completed = true;
            if (session.value.currentEjercicioIndex < session.value.ejercicios.length - 1) {
                session.value.currentEjercicioIndex += 1;
                session.value.currentSerieNumero = 1;
            } else {
                // Último ejercicio completado
                session.value.currentSerieNumero = ej.series_completadas + 1;
            }
        } else {
            session.value.currentSerieNumero += 1;
        }

        return setData;
    };

    /**
     * Backward-compatible: marca serie completada genérica sin parámetros.
     */
    const completeCurrentSerie = () => {
        return recordSet({
            peso: 0,
            reps: 0,
            tipo_serie: 'efectiva',
        });
    };

    /**
     * Deshace la última serie registrada.
     */
    const undoLastSet = () => {
        if (!canUndo.value || !isActive.value) return null;
        const lastAction = undoStack.value.pop();
        const ej = session.value.ejercicios[lastAction.ejercicioIndex];
        if (!ej) return null;

        // Remover de ej.sets
        if (ej.sets && ej.sets.length > 0) {
            ej.sets.pop();
        }
        ej.series_completadas = Math.max(0, ej.series_completadas - 1);
        ej.completed = false;

        // Remover de session.completedSets
        if (session.value.completedSets && session.value.completedSets.length > 0) {
            session.value.completedSets.pop();
        }

        // Restaurar cursores a la serie deshecha
        session.value.currentEjercicioIndex = lastAction.ejercicioIndex;
        session.value.currentSerieNumero = lastAction.serieNumero;

        return lastAction.setData;
    };

    /**
     * Actualiza una serie ya completada (ej: corregir peso/reps).
     */
    const updateSet = (ejercicioIndex, setIndex, updatedFields) => {
        if (!isActive.value) return;
        const ej = session.value.ejercicios[ejercicioIndex];
        if (!ej || !ej.sets || !ej.sets[setIndex]) return;

        Object.assign(ej.sets[setIndex], updatedFields);
    };

    const pause = () => {
        if (!isActive.value || session.value.isPaused) return;
        session.value.isPaused = true;
        session.value.pausedAt = new Date().toISOString();
    };

    const resume = () => {
        if (!isActive.value || !session.value.isPaused) return;
        if (session.value.pausedAt) {
            const pausedDuration = Math.floor(
                (Date.now() - new Date(session.value.pausedAt).getTime()) / 1000
            );
            session.value.accumulatedPauseSeconds =
                (session.value.accumulatedPauseSeconds || 0) + Math.max(0, pausedDuration);
        }
        session.value.isPaused = false;
        session.value.pausedAt = null;
    };

    const nextEjercicio = () => {
        if (!isActive.value) return;
        if (session.value.currentEjercicioIndex < session.value.ejercicios.length - 1) {
            session.value.currentEjercicioIndex += 1;
            session.value.currentSerieNumero = 1;
        }
    };

    const prevEjercicio = () => {
        if (!isActive.value) return;
        if (session.value.currentEjercicioIndex > 0) {
            session.value.currentEjercicioIndex -= 1;
            session.value.currentSerieNumero = 1;
        }
    };

    const setCurrent = (ejercicioIndex, serieNumero = 1) => {
        if (!isActive.value) return;
        session.value.currentEjercicioIndex = ejercicioIndex;
        session.value.currentSerieNumero = serieNumero;
    };

    const end = () => {
        if (!isActive.value) return;
        session.value.endedAt = new Date().toISOString();
        saveToStorage(null);
        session.value = emptySession();
        undoStack.value = [];
    };

    const discard = () => {
        session.value = emptySession();
        undoStack.value = [];
        saveToStorage(null);
    };

    return {
        session,
        isActive,
        isPaused,
        currentEjercicio,
        elapsed,
        volumenTotal,
        totalSeriesObjetivo,
        totalSeriesCompletadas,
        progresoPorcentaje,
        canUndo,
        start,
        recordSet,
        completeCurrentSerie,
        undoLastSet,
        updateSet,
        pause,
        resume,
        nextEjercicio,
        prevEjercicio,
        setCurrent,
        end,
        discard,
    };
});
