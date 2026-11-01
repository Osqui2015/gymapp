@php
    /**
     * Bottom navigation (mobile only).
     *
     * El markup y el estado viven en el componente Vue
     * `resources/js/components/MobileBottomNav.vue`. Este partial solo arma
     * las URLs y decide que items ve cada rol, y se los pasa como props.
     *
     * Antes era Blade + Alpine (`x-data="{ menuOpen: false }"`), y Alpine
     * ocupaba 44,8 KB del bundle INICIAL (16 KB gzip) para manejar un solo
     * booleano.
     *
     * @var \App\Models\User $user
     */
    $user = Auth::user();
    $isStaff = $user->hasRole(['trainer', 'administrador']);
    $isAdmin = $user->hasRole('administrador');

    // Los 4 items principales del bottom nav (siempre visibles junto a "Menú").
    $primary = [
        [
            'label' => 'Inicio',
            'href' => route('dashboard'),
            'icon' => 'dashboard',
            'active' => request()->routeIs('dashboard'),
        ],
        [
            'label' => 'Rutinas',
            'href' => route('rutinas'),
            'icon' => 'assignment',
            'active' => request()->routeIs('rutinas*'),
        ],
        [
            'label' => 'Historial',
            'href' => route('historial'),
            'icon' => 'history',
            'active' => request()->routeIs('historial*'),
        ],
        [
            'label' => 'Progreso',
            'href' => route('progreso'),
            'icon' => 'monitoring',
            'active' => request()->routeIs('progreso*'),
        ],
    ];

    $trainerLinks = [];
    if ($isStaff) {
        $trainerLinks = [
            ['label' => 'Dashboard Trainer', 'href' => route('trainer.dashboard'), 'icon' => 'space_dashboard'],
            ['label' => 'Alumnos', 'href' => route('trainer.alumnos'), 'icon' => 'groups'],
            ['label' => 'Ejercicios Privados', 'href' => route('trainer.ejercicios'), 'icon' => 'exercise'],
            ['label' => 'Duplicar Rutinas', 'href' => route('trainer.duplicar'), 'icon' => 'content_copy'],
        ];
    }

    $adminLinks = [];
    if ($isAdmin) {
        $adminLinks = [
            ['label' => 'Estadísticas', 'href' => route('admin.estadisticas'), 'icon' => 'analytics'],
            ['label' => 'Membresías', 'href' => route('admin.membresias'), 'icon' => 'card_membership'],
            ['label' => 'Audit Logs', 'href' => route('admin.audit-logs'), 'icon' => 'receipt_long'],
            ['label' => 'Importar / Exportar', 'href' => route('admin.import-export'), 'icon' => 'sync_alt'],
        ];
    }
@endphp

{{--
    La bottom nav vive FUERA de #app (el mount point de la app Vue de pagina),
    asi que necesita su propio punto de montaje.

    Los props van TODOS en un unico atributo `data-props` (JSON). Ver la nota
    completa en `layouts/navigation.blade.php`: Vue no lee los atributos del
    elemento como props cuando el componente se monta sobre markup de Blade.
--}}
<mobile-bottom-nav
    id="mobile-bottom-nav-root"
    data-props='{{ json_encode([
        'primary' => $primary,
        'trainerLinks' => $trainerLinks,
        'adminLinks' => $adminLinks,
        'userName' => $user->name,
        'roleLabel' => $user->normalizedRole(),
        'settingsHref' => $isAdmin ? route('configuracion') : route('profile.edit'),
        'profileHref' => route('profile.edit'),
        'logoutUrl' => route('logout'),
        'csrfToken' => csrf_token(),
        'ejerciciosHref' => route('ejercicios'),
        'ejerciciosActive' => request()->routeIs('ejercicios*'),
        'isStaff' => $isStaff,
        'isAdmin' => $isAdmin,
    ]) }}'
></mobile-bottom-nav>
