<?php

use App\Models\Membresia;
use Illuminate\Support\Facades\Route;

Route::get('/membresia/vencida', function () {
    return view('membresia-vencida');
})->middleware('auth')->name('membresia.vencida');

// Actualizar membresías vencidas.
// Antes: cualquier usuario autenticado podía gatillar la mutación global.
// Ahora: solo administradores. Idealmente se llama desde el scheduler
// (ver bootstrap/app.php), pero dejamos la ruta manual para emergencias.
Route::get('/api/membresias/actualizar-estados', function () {
    Membresia::actualizarEstados();

    return response()->json(['success' => true, 'message' => 'Estados actualizados']);
})->middleware(['auth', 'role:administrador']);
