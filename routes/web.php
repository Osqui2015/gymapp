<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/rutinas', function () {
    return view('rutinas');
})->middleware(['auth'])->name('rutinas');

Route::get('/rutinas/crear', function () {
    return view('crear-rutina');
})->middleware(['auth'])->name('crear-rutina');

Route::get('/ejercicios', function () {
    return view('ejercicios');
})->middleware(['auth'])->name('ejercicios');

Route::get('/historial', function () {
    return view('historial');
})->middleware(['auth'])->name('historial');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
