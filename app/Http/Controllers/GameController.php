<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameRequest;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    /**
     * Listado paginado del catálogo con búsqueda y filtros.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'genre' => ['nullable', 'string', 'max:50'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);

        $games = Game::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['genre'] ?? null, fn ($query, $genre) => $query->where('genre', $genre))
            ->when($filters['platform'] ?? null, fn ($query, $platform) => $query->where('platform', $platform))
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        $hasFilters = collect($filters)->contains(fn ($value) => filled($value));

        $stats = [
            'total' => Game::count(),
            'average' => round((float) Game::avg('rating'), 1),
            'platforms' => Game::query()->distinct()->count('platform'),
        ];

        $genres = Game::query()->select('genre')->distinct()->orderBy('genre')->pluck('genre');
        $platforms = Game::query()->select('platform')->distinct()->orderBy('platform')->pluck('platform');

        return view('games.index', compact('games', 'stats', 'genres', 'platforms', 'filters', 'hasFilters'));
    }

    /**
     * Formulario de creación.
     */
    public function create(): View
    {
        return view('games.create', ['game' => new Game]);
    }

    /**
     * Guarda un videojuego nuevo.
     */
    public function store(GameRequest $request): RedirectResponse
    {
        $game = Game::create($request->validated());

        return redirect()
            ->route('games.show', $game)
            ->with('success', "Juego «{$game->name}» creado correctamente.");
    }

    /**
     * Detalle de un videojuego.
     *
     * Si el id no existe, el route model binding responde 404 automáticamente.
     */
    public function show(Game $game): View
    {
        return view('games.show', compact('game'));
    }

    /**
     * Formulario de edición.
     */
    public function edit(Game $game): View
    {
        return view('games.edit', compact('game'));
    }

    /**
     * Actualiza un videojuego existente.
     */
    public function update(GameRequest $request, Game $game): RedirectResponse
    {
        $game->update($request->validated());

        return redirect()
            ->route('games.show', $game)
            ->with('success', "Juego «{$game->name}» actualizado correctamente.");
    }

    /**
     * Elimina un videojuego.
     */
    public function destroy(Game $game): RedirectResponse
    {
        $name = $game->name;

        $game->delete();

        return redirect()
            ->route('games.index')
            ->with('success', "Juego «{$name}» eliminado correctamente.");
    }
}
