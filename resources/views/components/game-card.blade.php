@props(['game'])

<article
    class="card fade-up group flex flex-col overflow-hidden transition duration-300 hover:-translate-y-1 hover:border-white/20">
    <div class="relative">
        <a href="{{ route('games.show', $game) }}" tabindex="-1" aria-hidden="true" class="block">
            <x-game-poster :game="$game" ratio="aspect-[16/10]"
                           class="transition duration-500 group-hover:scale-[1.02]" />
        </a>

        <span class="badge badge-rating absolute right-3 top-3">
            ★ {{ $game->rating ?? 'Sin valorar' }}
        </span>
    </div>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <h2 class="text-base font-bold leading-snug text-white">
            <a href="{{ route('games.show', $game) }}" class="transition hover:text-brand-300">
                {{ $game->name }}
            </a>
        </h2>

        <p class="flex flex-wrap items-center gap-1.5">
            <span class="badge">{{ $game->genre }}</span>
            <span class="badge">{{ $game->platform }}</span>
        </p>

        <p class="line-clamp-2 text-sm leading-relaxed text-slate-400">{{ $game->description }}</p>

        <p class="mt-auto text-xs text-slate-500">
            {{ $game->release_date?->format('Y') ?? 'Fecha por confirmar' }}
        </p>

        <div class="flex items-center gap-2 border-t border-white/5 pt-3">
            <a href="{{ route('games.show', $game) }}" class="btn btn-ghost btn-sm flex-1">Ver</a>

            @auth
                <a href="{{ route('games.edit', $game) }}" class="btn btn-ghost btn-sm flex-1">Editar</a>
                <form action="{{ route('games.destroy', $game) }}" method="POST" class="flex-1"
                      onsubmit="return confirm('¿Eliminar este videojuego?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm w-full">Eliminar</button>
                </form>
            @endauth
        </div>
    </div>
</article>
