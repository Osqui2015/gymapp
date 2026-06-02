<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/rutinas', function () {
    return view('rutinas');
})->middleware(['auth'])->name('rutinas');

Route::get('/rutinas/crear', function () {
    return view('crear-rutina');
})->middleware(['auth', 'role:comun,trainer,administrador'])->name('crear-rutina');

Route::get('/ejercicios', function () {
    return view('ejercicios');
})->middleware(['auth', 'role:trainer,administrador'])->name('ejercicios');

Route::get('/historial', function () {
    return view('historial');
})->middleware(['auth'])->name('historial');

Route::get('/trainer/alumnos', function () {
    return view('trainer.alumnos');
})->middleware(['auth', 'role:trainer,administrador'])->name('trainer.alumnos');

Route::get('/admin/users', function () {
    return view('admin.users');
})->middleware(['auth', 'role:administrador'])->name('admin.users');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/admin/users', [AdminUserController::class, 'store'])
        ->middleware('role:administrador')
        ->name('admin.users.store');

    Route::get('/api/user-info', function () {
        return response()->json([
            'role' => auth()->user()->normalizedRole(),
        ]);
    })->name('user.info');
});

require __DIR__.'/auth.php';
