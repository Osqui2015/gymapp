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
 *     currentCalentamientoNumero: 1,
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
    currentCalentamientoNumero: 1,
    isPaused: false,
    pausedAt: null,
    accumulatedPauseSeconds: 0,
    completedSets: [],
});

/**
 * Asegura que los campos nuevos del schema estén presentes en sesiones que
 * quedaron persistidas en versiones anteriores (migración silenciosa).
 */
const migrateSessionShape = (parsed) => {
    if (!parsed || typeof parsed !== 'object') return parsed;
    if (parsed.currentCalentamientoNumero == null) {
        parsed.currentCalentamientoNumero = 1;
    }
    if (!Array.isArray(parsed.ejercicios)) parsed.ejercicios = [];
    if (!Array.isArray(parsed.completedSets)) parsed.completedSets = [];
    return parsed;
};

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
        return migrateSessionShape(parsed);
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

    // === Tick reactivo para que `elapsed` se recalcule cada segundo ===
    // El computed `elapsed` lee `Date.now()`. Sin un disparador reactivo, Vue lo
    // cachea y nunca se actualiza solo (bug clásico: el cronómetro quedaba
    // "congelado" en el último valor conocido, mostrando 0 min en el resumen).
    // `tick` se incrementa cada 1s mientras la sesion este activa y fuerza a
    // Vue a invalidar `elapsed` y todos los lugares que dependen del tiempo.
    const tick = ref(0);
    let tickInterval = null;
    const isBrowser = typeof window !== 'undefined';

    const startTick = () => {
        if (!isBrowser) return;
        if (tickInterval) return;
        tick.value = Date.now();
        tickInterval = window.setInterval(() => {
            tick.value = Date.now();
        }, 1000);
    };

    const stopTick = () => {
        if (tickInterval != null && isBrowser) {
            window.clearInterval(tickInterval);
        }
        tickInterval = null;
    };

    // Si arranca con una sesion restaurada desde localStorage, ya hay que
    // empezar a contar el tiempo (no a partir de ahora, sino desde startedAt).
    if (session.value.id && !session.value.endedAt) {
        startTick();
    }

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

    /**
     * True cuando todos los ejercicios de la sesion estan completos
     * (series_completadas >= series_objetivo en cada uno). Cuando esto es
     * cierto la UI deberia mostrar el CTA de finalizar en vez del de
     * completar serie.
     */
    const isSessionComplete = computed(() => {
        const ejs = session.value.ejercicios || [];
        if (ejs.length === 0) return false;
        return ejs.every((ej) => Number(ej.series_completadas || 0) >= Number(ej.series_objetivo || 0));
    });

    const elapsed = computed(() => {
        // Dependemos de `tick` para que Vue invalide este computed cada segundo.
        // (Sin esta lectura, el cache nunca se invalida y el cronómetro queda
        // congelado en el último valor conocido.)
        void tick.value;

        if (!session.value.startedAt) return 0;
        const start = new Date(session.value.startedAt).getTime();
        const pauseAcc = session.value.accumulatedPauseSeconds || 0;
        // `now` parte del tick (que se actualiza cada segundo) y solo cae a
        // `Date.now()` si por algun motivo el tick quedo en cero.
        let now = tick.value || Date.now();
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
            currentCalentamientoNumero: 1,
            isPaused: false,
            pausedAt: null,
            accumulatedPauseSeconds: 0,
            completedSets: [],
        };
        startTick();
    };

    /**
     * Registra datos detallados de la serie actual y avanza.
     *
     * Internamente delega a `recordSetAt` para soportar registros en
     * ejercicios que no son el "current" (caso típico de superseries, donde
     * ambos ejercicios comparten pantalla).
     */
    const recordSet = ({
        peso = 0,
        reps = 0,
        tipo_serie = 'efectiva',
        esfuerzo_tipo = null,
        esfuerzo_valor = null,
        nota_user = '',
    } = {}) => {
        return recordSetAt(session.value.currentEjercicioIndex, {
            peso,
            reps,
            tipo_serie,
            esfuerzo_tipo,
            esfuerzo_valor,
            nota_user,
        });
    };

    /**
     * Variante que permite registrar una serie sobre un ejercicio específico
     * por índice. Usado por ActiveWorkoutModal cuando hay superseries y cada
     * panel debe poder registrar series sin afectar el cursor del store.
     *
     * Si el ejercicio índice coincide con el currentEjercicioIndex, mantiene
     * el comportamiento de avance automático (avanza serie / pasa al
     * siguiente ejercicio cuando se completa). Si es otro índice
     * (superserie partner), SOLO registra el set en ese ejercicio y avanza
     * su contador interno; no toca currentEjercicioIndex / currentSerieNumero.
     */
    const recordSetAt = (
        ejercicioIndex,
        {
            peso = 0,
            reps = 0,
            tipo_serie = 'efectiva',
            esfuerzo_tipo = null,
            esfuerzo_valor = null,
            nota_user = '',
        } = {}
    ) => {
        if (!isActive.value) return null;
        const ej = session.value.ejercicios[ejercicioIndex];
        if (!ej) return null;

        const isCurrent = ejercicioIndex === session.value.currentEjercicioIndex;
        const isWarmup = tipo_serie === 'calentamiento';

        // Número de serie que se persiste en el set:
        //  - Si es el current: usa los contadores globales del store.
        //  - Si es partner de superset: cuenta los sets en ej.sets
        //    directamente, así cada ejercicio lleva su propio contador
        //    independiente.
        let currentSerieNum;
        if (isCurrent) {
            currentSerieNum = isWarmup
                ? (session.value.currentCalentamientoNumero || 1)
                : session.value.currentSerieNumero;
        } else {
            const completedEffective = (ej.sets || []).filter(
                (s) => s.tipo_serie !== 'calentamiento'
            ).length;
            currentSerieNum = completedEffective + 1;
        }

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

        if (!session.value.completedSets) session.value.completedSets = [];
        session.value.completedSets.push(setData);

        // Guardar para Deshacer. Solo si estamos registrando en el current
        // ejercicio (los partners no tienen flujo de "deshacer" desde su
        // propio panel — usar el botón general de deshacer en el padre).
        if (isCurrent) {
            undoStack.value.push({
                ejercicioIndex,
                serieNumero: currentSerieNum,
                calentamientoNumero: session.value.currentCalentamientoNumero || 1,
                serieNumeroAntes: session.value.currentSerieNumero,
                isWarmup,
                setData,
            });

            // Avance automático solo si es el current ejercicio.
            if (isWarmup) {
                session.value.currentCalentamientoNumero =
                    (session.value.currentCalentamientoNumero || 1) + 1;
            } else if (ej.series_completadas + 1 >= ej.series_objetivo) {
                ej.series_completadas += 1;
                ej.completed = true;
                if (
                    session.value.currentEjercicioIndex <
                    session.value.ejercicios.length - 1
                ) {
                    session.value.currentEjercicioIndex += 1;
                    session.value.currentSerieNumero = 1;
                    session.value.currentCalentamientoNumero = 1;
                } else {
                    // Último ejercicio completado
                    session.value.currentSerieNumero = ej.series_completadas + 1;
                    session.value.currentCalentamientoNumero = 1;
                }
            } else {
                ej.series_completadas += 1;
                session.value.currentSerieNumero += 1;
                session.value.currentCalentamientoNumero = 1;
            }
        } else {
            // Partner de superset: no tocamos cursores globales, solo
            // marcamos el set como registrado. Para mantener compatibilidad
            // con el contador global de progreso / series completadas,
            // incrementamos `series_completadas` local del ejercicio.
            if (!isWarmup) {
                ej.series_completadas += 1;
                if (ej.series_completadas >= ej.series_objetivo) {
                    ej.completed = true;
                }
            }
        }

        return setData;
    };

    /**
     * Devuelve el "compañero" de superserie del ejercicio en el índice dado,
     * o `null` si el ejercicio no pertenece a una superserie o no tiene
     * compañero. La búsqueda es por `superserie_grupo` (mismo número de
     * grupo).
     */
    const superseriePartnerFor = (ejercicioIndex) => {
        if (!isActive.value) return null;
        const ej = session.value.ejercicios[ejercicioIndex];
        if (!ej || !ej.superserie_grupo) return null;
        const grupo = ej.superserie_grupo;
        const partnerIndex = session.value.ejercicios.findIndex(
            (other, idx) =>
                idx !== ejercicioIndex &&
                other &&
                other.superserie_grupo === grupo
        );
        if (partnerIndex < 0) return null;
        return {
            index: partnerIndex,
            ejercicio: session.value.ejercicios[partnerIndex],
        };
    };

    /**
     * Progreso de la sesión medido en UNIDADES lógicas en vez de ejercicios
     * crudos. Una superserie cuenta como 1 sola unidad (porque al usuario
     * le importa el "bloque de trabajo", no los ejercicios individuales).
     *
     * Devuelve:
     *   { actual, total, totalEjercicios }
     *
     * - `actual`: número de la unidad actual (1-based, igual al que se muestra
     *   en el header "EJERCICIO X DE Y").
     * - `total`: total de unidades lógicas de la sesión.
     * - `totalEjercicios`: cantidad cruda de ejercicios (útil para debug o
     *   para mostrar entre paréntesis si se quiere).
     *
     * Algoritmo: recorremos los ejercicios; si el actual pertenece a una
     * superserie cuyo compañero todavía no fue visitado, agrupamos ambos en
     * una sola unidad. Si ya visitamos al compañero, el actual es parte de
     * la misma unidad (no incrementa contador).
     */
    const unidadProgreso = computed(() => {
        const ejs = session.value.ejercicios || [];
        if (ejs.length === 0) {
            return { actual: 0, total: 0, totalEjercicios: 0 };
        }

        const visitados = new Set();
        let totalUnidades = 0;
        const unidadPorEjercicio = new Array(ejs.length).fill(0);

        ejs.forEach((ej, idx) => {
            if (visitados.has(idx)) return;
            visitados.add(idx);
            totalUnidades += 1;
            unidadPorEjercicio[idx] = totalUnidades;

            // Si pertenece a una superserie y tiene compañero, lo agrupamos
            // en la misma unidad.
            if (ej && ej.superserie_grupo) {
                const partnerIdx = ejs.findIndex(
                    (other, i) =>
                        i !== idx &&
                        !visitados.has(i) &&
                        other &&
                        other.superserie_grupo === ej.superserie_grupo
                );
                if (partnerIdx >= 0) {
                    visitados.add(partnerIdx);
                    unidadPorEjercicio[partnerIdx] = totalUnidades;
                }
            }
        });

        const actualIndex = session.value.currentEjercicioIndex;
        const actualUnidad = unidadPorEjercicio[actualIndex] || 1;

        return {
            actual: actualUnidad,
            total: totalUnidades,
            totalEjercicios: ejs.length,
        };
    });

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

        // Solo las series NO-calentamiento afectan el contador de progreso.
        if (!lastAction.isWarmup) {
            ej.series_completadas = Math.max(0, ej.series_completadas - 1);
            ej.completed = false;
        }

        // Remover de session.completedSets
        if (session.value.completedSets && session.value.completedSets.length > 0) {
            session.value.completedSets.pop();
        }

        // Restaurar cursores a la serie deshecha
        session.value.currentEjercicioIndex = lastAction.ejercicioIndex;
        if (lastAction.isWarmup) {
            // Calentamiento: volver al numero anterior (o 1 si era el primero).
            session.value.currentCalentamientoNumero = lastAction.calentamientoNumero;
            session.value.currentSerieNumero = lastAction.serieNumeroAntes;
        } else {
            session.value.currentSerieNumero = lastAction.serieNumero;
            // Restauramos el contador de calentamiento al valor previo a
            // registrar la efectiva. Si no habia calentamiento previo, sera 1.
            session.value.currentCalentamientoNumero = lastAction.calentamientoNumero;
        }

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
            session.value.currentCalentamientoNumero = 1;
        }
    };

    const prevEjercicio = () => {
        if (!isActive.value) return;
        if (session.value.currentEjercicioIndex > 0) {
            session.value.currentEjercicioIndex -= 1;
            session.value.currentSerieNumero = 1;
            session.value.currentCalentamientoNumero = 1;
        }
    };

    const setCurrent = (ejercicioIndex, serieNumero = 1) => {
        if (!isActive.value) return;
        session.value.currentEjercicioIndex = ejercicioIndex;
        session.value.currentSerieNumero = serieNumero;
        session.value.currentCalentamientoNumero = 1;
    };

    const end = () => {
        if (!isActive.value) return;
        session.value.endedAt = new Date().toISOString();
        saveToStorage(null);
        session.value = emptySession();
        undoStack.value = [];
        stopTick();
    };

    const discard = () => {
        session.value = emptySession();
        undoStack.value = [];
        saveToStorage(null);
        stopTick();
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
        unidadProgreso,
        isSessionComplete,
        canUndo,
        start,
        recordSet,
        recordSetAt,
        superseriePartnerFor,
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
