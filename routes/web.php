<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// NOTA: las rutas del health check (/up, /up/live) NO estan en este archivo.
// Viven en routes/health.php y se registran FUERA del grupo 'web' desde
// bootstrap/app.php, para que no dependan de la sesion ni de la tabla
// `sessions` (si la DB se cae, /up tiene que seguir respondiendo).

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Página de fallback offline (servida por el service worker cuando no hay red)
Route::view('/offline', 'offline')->name('offline');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'membership'])->name('dashboard');

Route::get('/rutinas', function () {
    return view('rutinas');
})->middleware(['auth', 'membership', 'role:comun,trainer,administrador'])->name('rutinas');

Route::get('/rutinas/crear', function () {
    return view('crear-rutina');
})->middleware(['auth', 'membership', 'role:comun,trainer,administrador'])->name('crear-rutina');

// Vista pública de una rutina compartida (token en URL)
Route::get('/r/{token}', function ($token) {
    return view('rutina-publica', ['token' => $token]);
})->name('rutina.publica');

Route::get('/ejercicios', function () {
    return view('ejercicios');
})->middleware(['auth', 'membership'])->name('ejercicios');

// === Catálogo VisualGym (exercises-dataset, 1.324 ejercicios) ===
// Pantalla de referencia de diseño interno: sólo accesible por administradores.
Route::get('/visualgym', function () {
    return view('visualgym');
})->middleware(['auth', 'membership', 'role:administrador'])->name('visualgym');

Route::get('/historial', function () {
    return view('historial');
})->middleware(['auth', 'membership'])->name('historial');

Route::get('/progreso', function () {
    return view('progreso');
})->middleware(['auth', 'membership'])->name('progreso');

Route::get('/nutricion', function () {
    return view('nutricion');
})->middleware(['auth', 'membership'])->name('nutricion');

Route::get('/trainer/alumnos', function () {
    return view('trainer.alumnos');
})->middleware(['auth', 'membership', 'role:administrador,trainer'])->name('trainer.alumnos');

Route::get('/trainer/dashboard', function () {
    return view('trainer.dashboard');
})->middleware(['auth', 'membership', 'role:administrador,trainer'])->name('trainer.dashboard');

Route::get('/trainer/ejercicios', function () {
    return view('trainer.ejercicios');
})->middleware(['auth', 'membership', 'role:administrador,trainer'])->name('trainer.ejercicios');

Route::get('/trainer/duplicar', function () {
    return view('trainer.duplicar');
})->middleware(['auth', 'membership', 'role:administrador,trainer'])->name('trainer.duplicar');

Route::get('/admin/users', function () {
    return redirect()->route('configuracion');
})->middleware(['auth', 'membership', 'role:administrador,coordinador,recepcionista'])->name('admin.users');

Route::get('/configuracion', function () {
    return view('configuracion');
})->middleware(['auth', 'membership', 'role:administrador'])->name('configuracion');

// Rutas de Admin
Route::get('/admin/estadisticas', function () {
    return view('admin.estadisticas');
})->middleware(['auth', 'membership', 'role:administrador,coordinador'])->name('admin.estadisticas');

Route::get('/admin/membresias', function () {
    return view('admin.membresias');
})->middleware(['auth', 'membership', 'role:administrador,coordinador,recepcionista'])->name('admin.membresias');

// Reportes avanzados (retención, churn, frecuencia de entrenamiento).
// El componente AdminReports.vue, el endpoint GET /api/admin/reportes y sus
// tests ya existían: faltaba únicamente la vista Blade que lo montaba,
// por lo que el módulo era inaccesible desde la UI.
Route::get('/admin/reportes', function () {
    return view('admin.reportes');
})->middleware(['auth', 'membership', 'role:administrador,coordinador'])->name('admin.reportes');

Route::get('/admin/audit-logs', function () {
    return view('admin.audit-logs');
})->middleware(['auth', 'membership', 'role:administrador,coordinador'])->name('admin.audit-logs');

Route::get('/admin/import-export', function () {
    return view('admin.import-export');
})->middleware(['auth', 'membership', 'role:administrador'])->name('admin.import-export');

Route::middleware(['auth', 'membership'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// NOTA: `/api/user-info` estaba DEFINIDO ACÁ Y en routes/api.php.
// La de api.php gana el match (misma URI y método) y esta quedaba como
// código muerto. Se eliminó la de acá para que no haya dos definiciones
// del mismo endpoint con middleware DISTINTO — que es exactamente el
// tipo de duplicación que hace que una regla de seguridad se aplique en
// un lado y no en el otro. Ver routes/api.php:264.

require __DIR__.'/auth.php';
require __DIR__.'/membresia.php';
