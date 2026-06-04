<?php

namespace App\Http\Middleware;

use App\Models\Membresia;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Los administradores siempre tienen acceso
        if ($user->hasRole(['administrador'])) {
            return $next($request);
        }

        // Verificar membresía activa
        $membresia = Membresia::where('user_id', $user->id)
            ->whereIn('estado', ['activo', 'por_vencer'])
            ->where('fecha_vencimiento', '>=', now()->toDateString())
            ->first();

        if (!$membresia) {
            // Verificar si tiene membresía vencida
            $membresiaVencida = Membresia::where('user_id', $user->id)
                ->where('estado', 'vencido')
                ->first();

            if ($membresiaVencida) {
                // Redirigir a página de membresía vencida
                if (!$request->routeIs('membresia.vencida')) {
                    return redirect()->route('membresia.vencida');
                }
            }
            // Si no tiene membresía en absoluto pero no es trainer, permitir acceso limitado
            // (los trainers pueden no tener membresía formal)
        }

        return $next($request);
    }
}