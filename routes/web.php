<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Página de inicio pública.
Route::get('/', [HomeController::class, 'index'])->name('home');

// Gestión del catálogo: solo administradores con sesión iniciada.
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

    Route::resource('games', GameController::class)->except(['index', 'show']);
});

// Catálogo público: listado y detalle.
Route::resource('games', GameController::class)->only(['index', 'show']);

require __DIR__.'/auth.php';
