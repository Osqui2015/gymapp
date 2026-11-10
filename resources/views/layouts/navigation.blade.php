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
                'label' => 'Reportes',
                'href' => route('admin.reportes'),
                'active' => request()->routeIs('admin.reportes'),
                'icon' => '📈',
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

{{--
    La navegacion vive FUERA de #app (el mount point de la app Vue de pagina),
    asi que necesita su propio punto de montaje.

    Los props van TODOS en un unico atributo `data-props` (JSON). No se pueden
    pasar como atributos sueltos: Vue NO lee los atributos de un elemento del
    DOM como props cuando el componente se registra globalmente y se monta
    sobre markup de Blade (eso solo funciona en templates compilados). Con
    `links='[...]'` la prop llegaba como string sin parsear y con
    `:links="..."` Vue descartaba el atributo: en ambos casos el menu de
    links salia VACIO.

    Antes esto era HTML Blade puro + Alpine, y siempre se renderizaba. Ahora es
    un componente Vue, y sin montarlo explicitamente la navbar desaparece.
--}}
<app-navigation
    id="navigation-root"
    data-props='{{ json_encode([
        'links' => $mainLinks,
        'trainerLinks' => $trainerLinks,
        'adminLinks' => $adminLinks,
        'userName' => $user->name,
        'userEmail' => $user->email,
        'settingsHref' => $user->hasRole('administrador') ? route('configuracion') : route('profile.edit'),
        'profileHref' => route('profile.edit'),
        'logoutUrl' => route('logout'),
        'csrfToken' => csrf_token(),
        'dashboardHref' => route('dashboard'),
        'historialHref' => route('historial'),
        'activeRoute' => $activeRoute,
    ]) }}'
></app-navigation>
