<?php

use Illuminate\Support\Facades\Route;
use App\Models\Membresia;

Route::get('/membresia/vencida', function () {
    return view('membresia-vencida');
})->middleware('auth')->name('membresia.vencida');

// Actualizar membresías vencidas (se puede llamar desde un scheduler)
Route::get('/api/membresias/actualizar-estados', function () {
    Membresia::actualizarEstados();
    return response()->json(['success' => true, 'message' => 'Estados actualizados']);
})->middleware('auth');