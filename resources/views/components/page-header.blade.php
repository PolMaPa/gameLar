@props([
    'title',
    'description' => null,
    'backRoute' => null,
    'backLabel' => 'Volver',
])

<header class="mb-6">
    @if ($backRoute)
        <a href="{{ $backRoute }}"
           class="inline-flex items-center gap-1.5 text-sm text-slate-400 transition hover:text-white">
            <span aria-hidden="true">←</span> {{ $backLabel }}
        </a>
    @endif

    <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $title }}</h1>

    @if ($description)
        <p class="mt-1.5 max-w-2xl text-sm text-slate-400">{{ $description }}</p>
    @endif
</header>
