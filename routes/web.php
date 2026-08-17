<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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
})->middleware(['auth', 'membership', 'role:administrador'])->name('admin.users');

Route::get('/configuracion', function () {
    return view('configuracion');
})->middleware(['auth', 'membership', 'role:administrador'])->name('configuracion');

// Rutas de Admin
Route::get('/admin/estadisticas', function () {
    return view('admin.estadisticas');
})->middleware(['auth', 'membership', 'role:administrador'])->name('admin.estadisticas');

Route::get('/admin/membresias', function () {
    return view('admin.membresias');
})->middleware(['auth', 'membership', 'role:administrador'])->name('admin.membresias');

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

    Route::post('/admin/users', [AdminUserController::class, 'store'])
        ->middleware('role:administrador')
        ->name('admin.users.store');

    Route::get('/api/user-info', function () {
        $user = auth()->user();
        return response()->json([
            'role' => $user->normalizedRole(),
            'trainer_id' => $user->trainer_id,
            'has_trainer' => !empty($user->trainer_id),
        ]);
    })->name('user.info');
});

require __DIR__.'/auth.php';
require __DIR__.'/membresia.php';
