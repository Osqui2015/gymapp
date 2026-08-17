@extends('layouts.app')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full text-center">
        <div class="text-8xl mb-6 animate-bounce-slow">📡</div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">
            Estás sin conexión
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            No pudimos conectar con el servidor. Pero tranquilo, algunas partes de la app siguen funcionando offline.
        </p>

        <div class="space-y-3 text-left bg-indigo-50 dark:bg-indigo-950/30 rounded-2xl p-5 mb-6">
            <h2 class="text-sm font-bold text-indigo-900 dark:text-indigo-200 uppercase tracking-wider mb-2">
                Lo que podés hacer offline
            </h2>
            <ul class="space-y-2 text-sm text-indigo-800 dark:text-indigo-300">
                <li class="flex items-start gap-2">
                    <span>✅</span>
                    <span>Ver tu rutina ya cargada (si la abriste antes)</span>
                </li>
                <li class="flex items-start gap-2">
                    <span>✅</span>
                    <span>Revisar tu historial en caché</span>
                </li>
                <li class="flex items-start gap-2">
                    <span>✅</span>
                    <span>Activar modo oscuro / claro</span>
                </li>
                <li class="flex items-start gap-2 opacity-60">
                    <span>⏳</span>
                    <span>Registrar series: se guarda y se sincroniza al reconectar</span>
                </li>
            </ul>
        </div>

        <button
            onclick="location.reload()"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-lg shadow-md transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Reintentar
        </button>
    </div>
</div>
@endsection

<style>
.animate-bounce-slow {
    animation: bounce-slow 2s ease-in-out infinite;
}
@keyframes bounce-slow {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
</style>
