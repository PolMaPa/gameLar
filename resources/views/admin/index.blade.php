@extends('layouts.app')

@section('title', 'Panel de administración')

@section('content')
    <x-page-header title="Panel de administración"
                   description="Gestiona el catálogo: crea, edita y elimina videojuegos. Solo los administradores pueden acceder a esta sección." />

    <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
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
            <dt class="text-[11px] uppercase tracking-wide text-slate-400">Sin valorar</dt>
            <dd class="mt-1 text-xl font-bold text-white">{{ $stats['unrated'] }}</dd>
        </div>
        <div class="stat">
            <dt class="text-[11px] uppercase tracking-wide text-slate-400">Administradores</dt>
            <dd class="mt-1 text-xl font-bold text-white">{{ $stats['admins'] }}</dd>
        </div>
    </dl>

    <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <form method="GET" action="{{ route('admin.index') }}" role="search"
              class="flex w-full max-w-md items-end gap-2">
            <div class="flex-1">
                <label for="search" class="field-label">Buscar por nombre</label>
                <input id="search" name="search" type="search" maxlength="150"
                       value="{{ $filters['search'] ?? '' }}"
                       placeholder="Buscar en el catálogo…"
                       class="field-input">
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
            @if (filled($filters['search'] ?? null))
                <a href="{{ route('admin.index') }}" class="btn btn-ghost">Limpiar</a>
            @endif
        </form>

        <a href="{{ route('games.create') }}" class="btn btn-primary">
            <span aria-hidden="true">+</span> Nuevo juego
        </a>
    </div>

    <section class="mt-6" aria-labelledby="tabla-titulo">
        <h2 id="tabla-titulo" class="sr-only">Videojuegos del catálogo</h2>

        @if ($games->isEmpty())
            @if (filled($filters['search'] ?? null))
                <x-empty-state title="Sin resultados"
                               description="Ningún videojuego coincide con la búsqueda.">
                    <a href="{{ route('admin.index') }}" class="btn btn-ghost">Limpiar búsqueda</a>
                </x-empty-state>
            @else
                <x-empty-state>
                    <a href="{{ route('games.create') }}" class="btn btn-primary">Añadir el primero</a>
                </x-empty-state>
            @endif
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[46rem] text-left text-sm">
                        <thead class="border-b border-white/10 bg-white/[0.03] text-[11px] uppercase tracking-wide text-slate-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">Juego</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Género</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Plataforma</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Lanzamiento</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Nota</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($games as $game)
                                <tr class="transition hover:bg-white/[0.02]">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('games.show', $game) }}"
                                           class="font-semibold text-white transition hover:text-brand-300">
                                            {{ $game->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-slate-300">{{ $game->genre }}</td>
                                    <td class="px-4 py-3 text-slate-300">{{ $game->platform }}</td>
                                    <td class="px-4 py-3 font-mono text-slate-400">
                                        {{ $game->release_date?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-accent-300">{{ $game->rating ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('games.show', $game) }}" class="btn btn-ghost btn-sm">Ver</a>
                                            <a href="{{ route('games.edit', $game) }}" class="btn btn-ghost btn-sm">Editar</a>
                                            <form action="{{ route('games.destroy', $game) }}" method="POST"
                                                  onsubmit="return confirm('¿Eliminar «{{ $game->name }}»? Esta acción no se puede deshacer.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8">
                {{ $games->onEachSide(1)->links('vendor.pagination.gamehub') }}
            </div>
        @endif
    </section>
@endsection
