@props([
    'title' => 'Todavía no hay videojuegos',
    'description' => 'El catálogo está vacío. Añade tu primer videojuego para empezar la colección.',
])

<div class="card relative overflow-hidden p-10 text-center sm:p-16">
    <div aria-hidden="true"
         class="pointer-events-none absolute inset-x-0 -top-24 mx-auto size-64 rounded-full bg-brand-500/20 blur-3xl"></div>

    <span class="relative mx-auto grid size-16 place-items-center rounded-2xl border border-white/10 bg-white/5 text-brand-300">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
             stroke-linecap="round" stroke-linejoin="round" class="size-8" aria-hidden="true">
            <path d="M7.5 7h9A4.5 4.5 0 0 1 21 11.5v1A4.5 4.5 0 0 1 16.5 17c-1.1 0-2-.6-2.7-1.4l-.4-.5h-2.8l-.4.5C9.5 16.4 8.6 17 7.5 17A4.5 4.5 0 0 1 3 12.5v-1A4.5 4.5 0 0 1 7.5 7Z"/>
            <path d="M7 10.5v3M5.5 12h3M15.5 11.5h.01M17.5 13.5h.01"/>
        </svg>
    </span>

    <h2 class="relative mt-6 text-xl font-bold text-white">{{ $title }}</h2>
    <p class="relative mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-400">{{ $description }}</p>

    @if (! $slot->isEmpty())
        <div class="relative mt-6 flex flex-wrap justify-center gap-3">
            {{ $slot }}
        </div>
    @endif
</div>
