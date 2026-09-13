<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] rounded-xl flex items-center justify-center shadow-[0_4px_12px_var(--color-violet-glow)]">
                <span class="text-white font-black text-base">G</span>
            </div>
            <h2 class="text-xl font-black text-gray-900 dark:text-white tracking-tight">
                {{ __('Dashboard') }}
            </h2>
        </div>
    </x-slot>

    <dashboard-content></dashboard-content>
</x-app-layout>