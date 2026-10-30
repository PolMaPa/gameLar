@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
    <section class="hero fade-up px-6 py-12 sm:px-10 sm:py-16">
        <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-300">Catálogo de videojuegos</p>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-white sm:text-5xl">
                Bienvenido a {{ config('app.name', 'GameHub') }}
            </h1>
            <p class="mt-4 text-sm leading-relaxed text-slate-300 sm:text-base">
                Consulta tu colección de videojuegos, busca por título y filtra por género o plataforma.
                Los administradores pueden añadir, editar y eliminar fichas desde un panel protegido.
            </p>

            <div class="mt-7 flex flex-wrap gap-3">
                <a href="{{ route('games.index') }}" class="btn btn-primary">Explorar catálogo</a>

                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.index') }}" class="btn btn-ghost">Ir al panel</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost">Iniciar sesión</a>
                @endauth
            </div>

            <dl class="mt-9 grid grid-cols-3 gap-3 sm:max-w-xl">
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
        </div>
    </section>

    <section class="mt-10" aria-labelledby="destacados-titulo">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="destacados-titulo" class="text-xl font-bold text-white">Mejor valorados</h2>
                <p class="text-sm text-slate-400">Una muestra del catálogo, ordenada por nota.</p>
            </div>
            <a href="{{ route('games.index') }}" class="btn btn-ghost btn-sm">Ver todo</a>
        </div>

        @if ($featured->isEmpty())
            <x-empty-state />
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $game)
                    <x-game-card :game="$game" />
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-10 grid gap-4 sm:grid-cols-3" aria-label="Cómo funciona GameHub">
        <article class="card p-5">
            <h3 class="text-sm font-bold text-white">Catálogo público</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-400">
                Cualquier visitante puede consultar el listado, buscar y filtrar sin iniciar sesión.
            </p>
        </article>
        <article class="card p-5">
            <h3 class="text-sm font-bold text-white">Administración protegida</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-400">
                Solo las cuentas con rol de administrador pueden crear, editar o eliminar videojuegos.
            </p>
        </article>
        <article class="card p-5">
            <h3 class="text-sm font-bold text-white">Docker + CI/CD</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-400">
                Nginx, PHP-FPM y MySQL 8.4 en contenedores, con pruebas automáticas y despliegue en un VPS.
            </p>
        </article>
    </section>
@endsection
