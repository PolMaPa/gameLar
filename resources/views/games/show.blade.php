@extends('layouts.app')

@section('title', $game->name)

@section('content')
    <nav aria-label="Miga de pan" class="mb-6 text-sm text-slate-400">
        <a href="{{ route('games.index') }}" class="transition hover:text-white">Catálogo</a>
        <span class="mx-2 text-slate-600" aria-hidden="true">/</span>
        <span class="text-slate-300">{{ $game->name }}</span>
    </nav>

    <article class="grid gap-6 lg:grid-cols-[minmax(0,340px)_1fr]">
        <div class="card fade-up overflow-hidden">
            <x-game-poster :game="$game" ratio="aspect-[3/4]" :show-rating="true" />
        </div>

        <div class="card fade-up flex flex-col p-6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $game->name }}</h1>
                    <p class="mt-2 text-xs text-slate-500">
                        Añadido el {{ $game->created_at?->format('d/m/Y') }}
                    </p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <span class="badge">{{ $game->genre }}</span>
                <span class="badge">{{ $game->platform }}</span>
                <span class="badge">
                    {{ $game->release_date?->format('d/m/Y') ?? 'Fecha por confirmar' }}
                </span>
            </div>

            <p class="mt-6 whitespace-pre-line text-sm leading-relaxed text-slate-300 sm:text-base">
                {{ $game->description }}
            </p>

            <dl class="mt-8 grid grid-cols-2 gap-4 rounded-2xl border border-white/5 bg-white/[0.02] p-4 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Valoración</dt>
                    <dd class="mt-1 font-mono text-accent-300">{{ $game->rating ?? 'Sin valorar' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">ID</dt>
                    <dd class="mt-1 font-mono text-slate-300">#{{ $game->id }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Actualizado</dt>
                    <dd class="mt-1 text-slate-300">{{ $game->updated_at?->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="col-span-2 sm:col-span-3">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Imagen</dt>
                    <dd class="mt-1 truncate text-slate-300" title="{{ $game->image_url }}">
                        {{ $game->image_url ?? 'Sin imagen' }}
                    </dd>
                </div>
            </dl>

            <div class="mt-auto flex flex-wrap items-center gap-3 border-t border-white/5 pt-6">
                @auth
                    <a href="{{ route('games.edit', $game) }}" class="btn btn-primary">Editar</a>

                    <form action="{{ route('games.destroy', $game) }}" method="POST"
                          onsubmit="return confirm('¿Eliminar este videojuego?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                @endauth

                <a href="{{ route('games.index') }}"
                   class="btn btn-ghost {{ auth()->check() ? 'sm:ml-auto' : 'ml-auto' }}">
                    <span aria-hidden="true">←</span> Volver al catálogo
                </a>
            </div>
        </div>
    </article>
@endsection
