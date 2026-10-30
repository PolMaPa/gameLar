@extends('layouts.app')

@section('title', 'Catálogo')

@section('content')
    <section class="hero fade-up px-6 py-10 sm:px-10 sm:py-14">
        <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-300">Catálogo de videojuegos</p>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                Tu próxima aventura empieza aquí
            </h1>
            <p class="mt-3 text-sm leading-relaxed text-slate-300 sm:text-base">
                Explora la colección, consulta los detalles y mantén el catálogo al día desde un único lugar.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="#catalogo" class="btn btn-primary">Ver catálogo</a>
                @auth
                    <a href="{{ route('games.create') }}" class="btn btn-ghost">
                        <span aria-hidden="true">+</span> Añadir videojuego
                    </a>
                @endauth
            </div>
        </div>

        <dl class="mt-8 grid grid-cols-3 gap-3 sm:max-w-xl">
            <div class="stat">
                <dt class="text-[11px] uppercase tracking-wide text-slate-400">Juegos</dt>
                <dd class="mt-1 text-xl font-bold text-white">{{ $stats['total'] }}</dd>
            </div>
            <div class="stat">
                <dt class="text-[11px] uppercase tracking-wide text-slate-400">Nota media</dt>
                <dd class="mt-1 text-xl font-bold text-accent-300">
                    {{ $stats['average'] > 0 ? number_format($stats['average'], 1, '.', '') : '—' }}
                </dd>
            </div>
            <div class="stat">
                <dt class="text-[11px] uppercase tracking-wide text-slate-400">Plataformas</dt>
                <dd class="mt-1 text-xl font-bold text-white">{{ $stats['platforms'] }}</dd>
            </div>
        </dl>
    </section>

    <form method="GET" action="{{ route('games.index') }}" role="search"
          aria-label="Buscar y filtrar videojuegos"
          class="card fade-up mt-6 grid gap-4 p-4 sm:grid-cols-[minmax(0,1fr)_11rem_11rem_auto] sm:items-end sm:p-5">
        <div>
            <label for="search" class="field-label">Buscar por nombre</label>
            <div class="relative">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                     class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-500">
                    <path fill-rule="evenodd"
                          d="M9 3.5a5.5 5.5 0 1 0 3.4 9.84l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5.5 9a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0Z"
                          clip-rule="evenodd"/>
                </svg>
                <input id="search" name="search" type="search" maxlength="150"
                       value="{{ $filters['search'] ?? '' }}"
                       placeholder="Buscar por título…"
                       class="field-input pl-9">
            </div>
        </div>

        <div>
            <label for="genre" class="field-label">Género</label>
            <select id="genre" name="genre" class="field-input">
                <option value="">Todos</option>
                @foreach ($genres as $genre)
                    <option value="{{ $genre }}" @selected(($filters['genre'] ?? '') === $genre)>{{ $genre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="platform" class="field-label">Plataforma</label>
            <select id="platform" name="platform" class="field-input">
                <option value="">Todas</option>
                @foreach ($platforms as $platform)
                    <option value="{{ $platform }}" @selected(($filters['platform'] ?? '') === $platform)>{{ $platform }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary flex-1 sm:flex-none">Buscar</button>

            @if ($hasFilters)
                <a href="{{ route('games.index') }}" class="btn btn-ghost">Limpiar</a>
            @endif
        </div>
    </form>

    <section id="catalogo" class="mt-10" aria-labelledby="catalogo-titulo">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="catalogo-titulo" class="text-xl font-bold text-white">Catálogo</h2>
                <p class="text-sm text-slate-400">
                    {{ $games->total() }} {{ $games->total() === 1 ? 'juego' : 'juegos' }}
                    @if ($games->lastPage() > 1)
                        · página {{ $games->currentPage() }} de {{ $games->lastPage() }}
                    @endif
                </p>
            </div>

            @auth
                <a href="{{ route('games.create') }}" class="btn btn-ghost btn-sm">
                    <span aria-hidden="true">+</span> Nuevo juego
                </a>
            @endauth
        </div>

        @if ($games->isEmpty())
            @if ($hasFilters)
                <x-empty-state title="Sin resultados"
                               description="Ningún videojuego coincide con la búsqueda o los filtros aplicados.">
                    <a href="{{ route('games.index') }}" class="btn btn-ghost">Limpiar filtros</a>
                </x-empty-state>
            @else
                <x-empty-state>
                    @auth
                        <a href="{{ route('games.create') }}" class="btn btn-primary">Añadir el primero</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">Iniciar sesión para añadir juegos</a>
                    @endauth
                </x-empty-state>
            @endif
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($games as $game)
                    <x-game-card :game="$game" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $games->onEachSide(1)->links('vendor.pagination.gamehub') }}
            </div>
        @endif
    </section>
@endsection
