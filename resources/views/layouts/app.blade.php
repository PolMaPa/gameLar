<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Catálogo de videojuegos de {{ config('app.name', 'gameLar') }}">
    <title>@yield('title', 'Catálogo') · {{ config('app.name', 'gameLar') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col">
    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-500 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Saltar al contenido
    </a>

    <header class="sticky top-0 z-40 border-b border-white/5 bg-ink-950/80 backdrop-blur">
        <div class="container-page flex h-16 items-center justify-between gap-4">
            <a href="{{ route('games.index') }}" class="flex items-center gap-2.5" aria-label="{{ config('app.name') }} — inicio">
                <span class="grid size-9 place-items-center rounded-xl bg-linear-to-br from-brand-500 to-fuchsia-500 text-white shadow-lg shadow-brand-600/30">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true">
                        <path d="M7.5 7h9A4.5 4.5 0 0 1 21 11.5v1A4.5 4.5 0 0 1 16.5 17c-1.1 0-2-.6-2.7-1.4l-.4-.5h-2.8l-.4.5C9.5 16.4 8.6 17 7.5 17A4.5 4.5 0 0 1 3 12.5v-1A4.5 4.5 0 0 1 7.5 7Z"/>
                        <path d="M7 10.5v3M5.5 12h3M15.5 11.5h.01M17.5 13.5h.01"/>
                    </svg>
                </span>
                <span class="text-lg font-extrabold tracking-tight text-white">{{ config('app.name', 'gameLar') }}</span>
            </a>

            <nav aria-label="Navegación principal" class="flex items-center gap-1 sm:gap-2">
                <a href="{{ route('games.index') }}"
                   class="nav-link {{ request()->routeIs('games.index', 'games.show') ? 'nav-link-active' : '' }}"
                   @if (request()->routeIs('games.index', 'games.show')) aria-current="page" @endif>
                    Catálogo
                </a>
                <a href="{{ route('games.create') }}" class="btn btn-primary btn-sm sm:px-4 sm:py-2.5 {{ request()->routeIs('games.create') ? 'ring-2 ring-white/30' : '' }}">
                    <span aria-hidden="true">+</span>
                    <span class="sm:hidden">Nuevo</span>
                    <span class="hidden sm:inline">Nuevo juego</span>
                </a>
            </nav>
        </div>
    </header>

    <main id="contenido" class="flex-1">
        <div class="container-page py-8 sm:py-10">
            @include('components.flash-messages')

            @yield('content')
        </div>
    </main>

    <footer class="border-t border-white/5 py-6">
        <div class="container-page flex flex-col items-center justify-between gap-2 text-xs text-slate-500 sm:flex-row">
            <p>© {{ date('Y') }} {{ config('app.name', 'gameLar') }} · Catálogo de videojuegos</p>
            <p>Laravel {{ app()->version() }} · SQLite</p>
        </div>
    </footer>
</body>
</html>
