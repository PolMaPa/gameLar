<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/games');

Route::resource('games', GameController::class);
