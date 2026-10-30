<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/games');

// La gestión del catálogo requiere autenticación. El catálogo en sí
// (listado y detalle) sigue siendo público.
Route::middleware('auth')->group(function () {
    Route::resource('games', GameController::class)->except(['index', 'show']);
});

Route::resource('games', GameController::class)->only(['index', 'show']);

require __DIR__.'/auth.php';
