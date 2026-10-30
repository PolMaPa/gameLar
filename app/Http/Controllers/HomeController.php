<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Página de inicio: presentación de GameHub y juegos mejor valorados.
     */
    public function index(): View
    {
        $featured = Game::query()
            ->whereNotNull('rating')
            ->orderByDesc('rating')
            ->orderBy('name')
            ->take(3)
            ->get();

        $stats = [
            'total' => Game::count(),
            'average' => round((float) Game::avg('rating'), 1),
            'platforms' => Game::query()->distinct()->count('platform'),
        ];

        return view('home', compact('featured', 'stats'));
    }
}
