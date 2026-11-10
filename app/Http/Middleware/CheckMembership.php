<?php

namespace App\Http\Middleware;

use App\Models\Membresia;
use App\Services\GymConfigService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMembership
{
    /**
     * Middleware de acceso al área de entrenamiento.
     *
     * REGLA DE ACCESO (Fase 0.6)
     * --------------------------
     * "Todo usuario necesita membresía o trial", con tres excepciones:
     *   - `administrador` y `trainer`: acceso siempre (son staff del gym).
     *   - Membresía de CORTESÍA (fecha_vencimiento NULL): acceso siempre.
     *   - Membresía VENCIDA: se evalúa el período de gracia.
     *
     * Antes de la Fase 0 la política era "registro gratuito": si el
     * usuario NUNCA tuvo membresía, lo dejaba pasar. Eso hacía que
     * 8 usuarios `comun` operaran sin pagar y que los morosos nunca se
     * registraran (no había fila que venciera). El backfill de cortesía
     * (migración 2026_11_01_120000) da a esos usuarios una membresía real,
     * y esta regla los mantiene dentro sin bloquearlos de golpe.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Los administradores y trainers siempre tienen acceso.
        if ($user->hasRole([\App\Models\User::ROLE_ADMINISTRADOR, \App\Models\User::ROLE_TRAINER])) {
            return $next($request);
        }

        $graceDays = $this->graceDays();

        // 1) Membresía activa (incluye trials en curso).
        //
        // La membresía de CORTESÍA se identifica por `metodo_pago =
        // 'cortesia'`, NO por `fecha_vencimiento IS NULL`: esa columna
        // nullable también la tienen los trials mal configurados y las
        // filas viejas, y tratarlas como cortesía abriría la puerta sin
        // que nadie haya pagado.
        $membresia = Membresia::where('user_id', $user->id)
            ->whereIn('estado', ['activo', 'por_vencer'])
            ->where(function ($q) {
                // Membresía de cortesía: sin vencimiento, siempre vigente.
                $q->where('metodo_pago', Membresia::METODO_PAGO_CORTESIA)
                    // Membresía con vencimiento, vigente a hoy.
                    ->orWhere('fecha_vencimiento', '>=', now()->toDateString());
            })
            ->first();

        if ($membresia) {
            return $next($request);
        }

        // 2) Membresía no activa: se busca la más reciente para evaluar
        //    el período de gracia.
        $ultima = Membresia::where('user_id', $user->id)
            ->orderByDesc('fecha_vencimiento')
            ->first();

        if (! $ultima) {
            // Sin membresía y sin trial: se bloquea el acceso al área de
            // entrenamiento. El usuario ve la pantalla de renovación.
            return $this->bloquear($request, $graceDays);
        }

        $vencimiento = $ultima->fecha_vencimiento?->copy();
        if (! $vencimiento) {
            // Membresía sin fecha de vencimiento pero no capturada arriba
            // (p. ej. estado 'cancelado'). Se trata como no pagada.
            return $this->bloquear($request, $graceDays);
        }

        $finGracia = $vencimiento->copy()->addDays($graceDays);
        $ahora = now();

        if ($ahora->lessThanOrEqualTo($finGracia)) {
            // Dentro del período de gracia. Permitimos el paso e inyectamos
            // cabeceras para que el frontend muestre el aviso visual.
            $response = $next($request);
            $response->headers->set('X-Membership-In-Grace-Period', '1');
            $response->headers->set('X-Membership-Grace-Days-Remaining', (string) max(0, $ahora->diffInDays($finGracia, false)));

            return $response;
        }

        // Excedió el período de gracia.
        return $this->bloquear($request, $graceDays);
    }

    /**
     * Días de gracia configurables (Fase 0.4).
     *
     * Antes era la constante de clase `GRACE_PERIOD_DAYS = 3`, que
     * duplicaba el default de `config('app.membership_grace_days')` — que
     * además nunca existió en config/app.php, así que el `config()` siempre
     * caía al default de la constante.
     */
    private function graceDays(): int
    {
        $desdeConfig = config('app.membership_grace_days');

        if ($desdeConfig !== null) {
            return (int) $desdeConfig;
        }

        try {
            return app(GymConfigService::class)->graceDays();
        } catch (\Throwable) {
            // Si la tabla `gym_config` todavía no existe (instalación
            // fresca o tests sin migraciones), se usa el default.
            return 3;
        }
    }

    /**
     * Bloquea el acceso: JSON para API, redirect a la pantalla de
     * renovación para web.
     */
    private function bloquear(Request $request, int $graceDays): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'membership_expired',
                'message' => 'Tu membresía está vencida y el período de gracia terminó.',
                'grace_days' => $graceDays,
            ], 403);
        }

        // La pantalla de renovación NO tiene el middleware `membership`
        // (para que el usuario pueda verla), así que acá nunca se llega
        // con esa ruta. El guard queda por si en el futuro se le agrega.
        if ($request->routeIs('membresia.vencida')) {
            return response('', 403);
        }

        return redirect()->route('membresia.vencida');
    }
}
