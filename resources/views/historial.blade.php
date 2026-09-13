<x-app-layout>
    {{-- Header slot: oculto en mobile porque el HistorialHeader.vue ya renderiza
         su propio header mobile con el look del mockup Figma. --}}
    <x-slot name="header">
        <div class="hidden md:block">
            <h2 class="font-black text-xl text-gray-900 dark:text-white tracking-tight">
                {{ __('Historial') }}
            </h2>
        </div>
    </x-slot>

    <historial-content></historial-content>
</x-app-layout>
