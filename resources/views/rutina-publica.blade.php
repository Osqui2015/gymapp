<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rutina compartida - GymApp</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div id="app" data-component="rutina-publica">
        <div class="min-h-screen flex items-center justify-center">
            <div class="text-center">
                <svg class="animate-spin w-12 h-12 mx-auto mb-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-gray-600 dark:text-gray-400">Cargando rutina...</p>
            </div>
        </div>
    </div>

    <div id="toast-root"></div>

    <script>
        window.__rutinaToken = '{{ $token }}';
    </script>
</body>
</html>