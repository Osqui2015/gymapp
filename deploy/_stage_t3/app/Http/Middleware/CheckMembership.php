<?php

namespace App\Http\Middleware;

use App\Models\Membresia;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMembership
{
    /**
     * Días de gracia después del vencimiento antes de bloquear al usuario.
     * Configurable vía .env con MEMBERSHIP_GRACE_DAYS.
     */
    public const GRACE_PERIOD_DAYS = 3;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Los administradores y trainers siempre tienen acceso
        if ($user->hasRole(['administrador', 'trainer'])) {
            return $next($request);
        }

        $graceDays = (int) config('app.membership_grace_days', self::GRACE_PERIOD_DAYS);

        // Verificar membresía activa
        $membresia = Membresia::where('user_id', $user->id)
            ->whereIn('estado', ['activo', 'por_vencer'])
            ->where('fecha_vencimiento', '>=', now()->toDateString())
            ->first();

        if ($membresia) {
            return $next($request);
        }

        // Membresía no activa. Buscar la más reciente para evaluar el período de gracia.
        $ultima = Membresia::where('user_id', $user->id)
            ->orderByDesc('fecha_vencimiento')
            ->first();

        if (! $ultima) {
            // Sin membresía: el alumno nunca tuvo plan.
            // Política: permitir pasar (registro gratuito) — la app está diseñada
            // para que el admin active la membresía manualmente si corresponde.
            return $next($request);
        }

        $vencimiento = $ultima->fecha_vencimiento?->copy();
        if (! $vencimiento) {
            return $next($request);
        }

        $finGracia = $vencimiento->copy()->addDays($graceDays);
        $ahora = now();

        if ($ahora->lessThanOrEqualTo($finGracia)) {
            // Dentro del período de gracia. Permitimos el paso e inyectamos
            // una cabecera para que el frontend muestre el aviso visual.
            $response = $next($request);
            $response->headers->set('X-Membership-In-Grace-Period', '1');
            $response->headers->set('X-Membership-Grace-Days-Remaining', (string) max(0, $ahora->diffInDays($finGracia, false)));

            return $response;
        }

        // Excedió el período de gracia.
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'membership_expired',
                'message' => 'Tu membresía está vencida y el período de gracia terminó.',
                'grace_days' => $graceDays,
            ], 403);
        }

        if (! $request->routeIs('membresia.vencida')) {
            return redirect()->route('membresia.vencida');
        }

        return $next($request);
    }
}
