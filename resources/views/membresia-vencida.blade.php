<?php

use App\Models\Membresia;
use App\Services\GymConfigService;

$gymConfig = app(GymConfigService::class);
$nombreGym = $gymConfig->nombre();
$emailGym = $gymConfig->get('email_contacto', 'soporte@gymapp.com');
$telefonoGym = $gymConfig->get('telefono_contacto', '+1234567890');
$moneda = $gymConfig->simboloMoneda();

// Último plan del usuario (para mostrar qué tenía y qué debe).
$ultimaMembresia = auth()->check()
    ? Membresia::where('user_id', auth()->id())->orderByDesc('fecha_vencimiento')->first()
    : null;

$nombrePlan = match ($ultimaMembresia?->tipo_plan) {
    'mensual' => 'Mensual',
    'trimestral' => 'Trimestral',
    'semestral' => 'Semestral',
    'anual' => 'Anual',
    default => null,
};

$precio = $ultimaMembresia?->precio;
$diasVencida = $ultimaMembresia?->fecha_vencimiento
    ? (int) now()->diffInDays($ultimaMembresia->fecha_vencimiento, false)
    : null;
?>

<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-slate-900 to-indigo-900 px-4">
        <div class="max-w-md w-full">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Membresía Vencida</h1>
                <p class="text-gray-400">
                    Tu membresía en {{ $nombreGym }} ha vencido. Ponete en contacto para renovarla.
                </p>
            </div>

            {{-- Detalle del plan vencido (Fase 0.6): el usuario tiene que ver
                 QUÉ se venció y CUÁNTO debe, no solo un mensaje genérico. --}}
            @if ($ultimaMembresia)
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-4">
                    <dl class="space-y-2 text-sm">
                        @if ($nombrePlan)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Plan</dt>
                                <dd class="text-white font-medium">{{ $nombrePlan }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Venció el</dt>
                            <dd class="text-white font-medium">
                                {{ $ultimaMembresia->fecha_vencimiento?->format('d/m/Y') }}
                            </dd>
                        </div>
                        @if ($diasVencida !== null && $diasVencida > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Días vencida</dt>
                                <dd class="text-red-300 font-medium">{{ $diasVencida }}</dd>
                            </div>
                        @endif
                        @if ($precio !== null && (float) $precio > 0)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Monto del plan</dt>
                                <dd class="text-white font-medium">{{ $moneda }} {{ number_format((float) $precio, 2) }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-8 border border-white/20">
                <div class="text-center">
                    <p class="text-indigo-300 text-sm mb-4">Si tenés alguna duda, comunicate con nosotros:</p>

                    <div class="space-y-3">
                        <a href="mailto:{{ $emailGym }}" class="flex items-center justify-center gap-2 px-4 py-3 bg-white/10 hover:bg-white/20 rounded-xl text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            {{ $emailGym }}
                        </a>

                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $telefonoGym) }}" class="flex items-center justify-center gap-2 px-4 py-3 bg-white/10 hover:bg-white/20 rounded-xl text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a1 1 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            {{ $telefonoGym }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center space-y-3">
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-white transition-colors text-sm">
                        Cerrar sesión
                    </button>
                </form>

                <div>
                    <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-white transition-colors text-sm underline">
                        Volver a intentar
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
