{{--
    Navegacion principal.

    La UI (dropdowns, menu de usuario, toggle de tema) vive en el componente
    Vue `app-navigation` (resources/js/components/AppNavigation.vue). Antes
    esto era Blade + Alpine (x-data), y Alpine ocupaba 44,8 KB del bundle
    INICIAL solo para manejar estos menus.

    Blade sigue armando las URLs y decide que links ve cada rol, asi que el
    HTML del servidor no cambia.
--}}
@php
    $user = Auth::user();

    $mainLinks = [
        [
            'label' => __('Dashboard'),
            'href' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
        ],
    ];

    if (! $user->hasRole('alumno')) {
        $mainLinks[] = [
            'label' => __('Rutinas'),
            'href' => route('rutinas'),
            'active' => request()->routeIs('rutinas*'),
        ];
    }

    $mainLinks[] = [
        'label' => __('Ejercicios'),
        'href' => route('ejercicios'),
        'active' => request()->routeIs('ejercicios*'),
    ];
    $mainLinks[] = [
        'label' => __('Historial'),
        'href' => route('historial'),
        'active' => request()->routeIs('historial*'),
    ];
    $mainLinks[] = [
        'label' => __('Progreso'),
        'href' => route('progreso'),
        'active' => request()->routeIs('progreso*'),
    ];

    $trainerLinks = [];
    if ($user->hasRole(['trainer', 'administrador'])) {
        $trainerLinks = [
            [
                'label' => 'Dashboard',
                'href' => route('trainer.dashboard'),
                'active' => request()->routeIs('trainer.dashboard'),
                'icon' => '📊',
            ],
            [
                'label' => 'Alumnos',
                'href' => route('trainer.alumnos'),
                'active' => request()->routeIs('trainer.alumnos'),
                'icon' => '👥',
            ],
            [
                'label' => 'Ejercicios Privados',
                'href' => route('trainer.ejercicios'),
                'active' => request()->routeIs('trainer.ejercicios'),
                'icon' => '🏋️',
            ],
            [
                'label' => 'Duplicar Rutinas',
                'href' => route('trainer.duplicar'),
                'active' => request()->routeIs('trainer.duplicar'),
                'icon' => '📋',
            ],
        ];
    }

    $adminLinks = [];
    if ($user->hasRole('administrador')) {
        $adminLinks = [
            [
                'label' => 'Estadísticas',
                'href' => route('admin.estadisticas'),
                'active' => request()->routeIs('admin.estadisticas'),
                'icon' => '📊',
            ],
            [
                'label' => 'Membresías',
                'href' => route('admin.membresias'),
                'active' => request()->routeIs('admin.membresias'),
                'icon' => '💳',
            ],
            [
                'label' => 'Audit Logs',
                'href' => route('admin.audit-logs'),
                'active' => request()->routeIs('admin.audit-logs'),
                'icon' => '📋',
            ],
            [
                'label' => 'Importar/Exportar',
                'href' => route('admin.import-export'),
                'active' => request()->routeIs('admin.import-export'),
                'icon' => '📂',
            ],
        ];
    }

    // La ruta activa se pasa como string simple: el componente Vue no puede
    // llamar a request() (eso es de Laravel).
    $activeRoute = request()->routeIs('historial*') ? 'historial' : '';
@endphp

<app-navigation
    :links="{{ json_encode($mainLinks) }}"
    :trainer-links="{{ json_encode($trainerLinks) }}"
    :admin-links="{{ json_encode($adminLinks) }}"
    user-name="{{ $user->name }}"
    user-email="{{ $user->email }}"
    settings-href="{{ $user->hasRole('administrador') ? route('configuracion') : route('profile.edit') }}"
    profile-href="{{ route('profile.edit') }}"
    logout-url="{{ route('logout') }}"
    csrf-token="{{ csrf_token() }}"
    dashboard-href="{{ route('dashboard') }}"
    historial-href="{{ route('historial') }}"
    active-route="{{ $activeRoute }}"
></app-navigation>
