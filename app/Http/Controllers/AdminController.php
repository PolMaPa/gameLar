<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Panel de administración: resumen del catálogo y tabla de gestión.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $games = Game::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Game::count(),
            'average' => round((float) Game::avg('rating'), 1),
            'unrated' => Game::query()->whereNull('rating')->count(),
            'admins' => User::query()->where('is_admin', true)->count(),
        ];

        return view('admin.index', compact('games', 'stats', 'filters'));
    }
}
