import confetti from 'canvas-confetti';

/**
 * Helpers de confetti listos para usar.
 */
export function useConfetti() {
    /**
     * Celebración estándar (PR, medalla, etc.)
     */
    const celebrate = () => {
        const duration = 2000;
        const end = Date.now() + duration;
        (function frame() {
            confetti({ particleCount: 5, angle: 60, spread: 55, origin: { x: 0, y: 0.7 } });
            confetti({ particleCount: 5, angle: 120, spread: 55, origin: { x: 1, y: 0.7 } });
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
            confetti({
                particleCount: 4,
                angle: 60,
                spread: 70,
                origin: { x: 0, y: 0.6 },
                colors,
            });
            confetti({
                particleCount: 4,
                angle: 120,
                spread: 70,
                origin: { x: 1, y: 0.6 },
                colors,
            });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();

        // Ráfaga central al final
        setTimeout(() => {
            confetti({
                particleCount: 150,
                spread: 100,
                startVelocity: 45,
                origin: { y: 0.5 },
                colors,
            });
        }, duration - 500);
    };

    /**
     * Micro-celebración (acción pequeña completada)
     */
    const mini = () => {
        confetti({
            particleCount: 30,
            spread: 50,
            startVelocity: 25,
            origin: { y: 0.7 },
        });
    };

    return { celebrate, bigCelebration, mini };
}
