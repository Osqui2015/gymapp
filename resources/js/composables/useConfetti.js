import confetti from 'canvas-confetti';

/**
 * Helpers de confetti listos para usar.
 *
 * Todas las funciones envuelven `confetti(...)` en try/catch porque canvas-confetti
 * crea un Web Worker desde blob: para animar partículas, y la CSP puede bloquearlo
 * (si CSP no tiene worker-src explícito o está mal configurado). Un throw dentro de
 * un watch de Vue puede romper el render, así que fallamos silencioso.
 */
export function useConfetti() {
    const safeFire = (opts) => {
        try {
            return confetti(opts);
        } catch {
            /* ignore — worker de canvas-confetti bloqueado por CSP */
            return null;
        }
    };

    /**
     * Celebración estándar (PR, medalla, etc.)
     */
    const celebrate = () => {
        const duration = 2000;
        const end = Date.now() + duration;
        (function frame() {
            safeFire({ particleCount: 5, angle: 60, spread: 55, origin: { x: 0, y: 0.7 } });
            safeFire({ particleCount: 5, angle: 120, spread: 55, origin: { x: 1, y: 0.7 } });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();
    };

    /**
     * Celebración grande (medalla especial, objetivo desbloqueado)
     */
    const bigCelebration = () => {
        const duration = 3000;
        const end = Date.now() + duration;
        const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'];

        (function frame() {
            safeFire({ particleCount: 4, angle: 60, spread: 70, origin: { x: 0, y: 0.6 }, colors });
            safeFire({ particleCount: 4, angle: 120, spread: 70, origin: { x: 1, y: 0.6 }, colors });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();

        setTimeout(() => {
            safeFire({ particleCount: 150, spread: 100, startVelocity: 45, origin: { y: 0.5 }, colors });
        }, duration - 500);
    };

    /**
     * Micro-celebración (acción pequeña completada)
     */
    const mini = () => {
        safeFire({ particleCount: 30, spread: 50, startVelocity: 25, origin: { y: 0.7 } });
    };

    return { celebrate, bigCelebration, mini };
}
