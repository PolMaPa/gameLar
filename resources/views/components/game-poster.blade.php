@props([
    'game',
    'ratio' => 'aspect-[16/10]',
    'showRating' => false,
])

@php
    $initials = collect(preg_split('/\s+/', trim($game->name)) ?: [])
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');

    $palette = 'poster-'.((($game->id ?: crc32($game->name)) % 6) + 1);
@endphp

<div {{ $attributes->merge(['class' => "poster {$palette} {$ratio}"]) }}>
    <span aria-hidden="true"
          class="absolute inset-0 grid place-items-center text-5xl font-black tracking-tight text-white/15">
        {{ $initials }}
    </span>

    @if ($game->image_url)
        <img src="{{ $game->image_url }}"
             alt="Portada de {{ $game->name }}"
             loading="lazy"
             class="absolute inset-0 h-full w-full object-cover"
             onerror="this.remove()">
    @endif

    <div aria-hidden="true" class="absolute inset-x-0 bottom-0 h-16 bg-linear-to-t from-ink-950/80 to-transparent"></div>

    @if ($showRating)
        <span class="badge badge-rating absolute right-3 top-3">
            ★ {{ $game->rating ?? 'Sin valorar' }}
        </span>
    @endif
</div>
