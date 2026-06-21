<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Breadcrumbs --}}
            <nav class="flex items-center text-sm" aria-label="Breadcrumb">
                <ol class="flex items-center gap-1 flex-wrap">
                    <li class="flex items-center gap-1">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                            Inicio
                        </a>
                    </li>
                    <li class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 mx-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="inline-flex items-center gap-1.5 px-2 py-1 font-semibold text-gray-900 dark:text-white" aria-current="page">
                            Perfil
                        </span>
                    </li>
                </ol>
            </nav>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
