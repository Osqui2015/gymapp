<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-sm">G</span>
            </div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                {{ __('Dashboard') }}
            </h2>
        </div>
    </x-slot>

    <dashboard-content></dashboard-content>
</x-app-layout>