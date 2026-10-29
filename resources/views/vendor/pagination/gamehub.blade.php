@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación del catálogo"
         class="flex flex-col items-center justify-between gap-3 border-t border-white/5 pt-5 sm:flex-row">
        <p class="text-xs text-slate-500">
            Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </p>

        <div class="flex flex-wrap items-center justify-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="btn btn-ghost btn-sm cursor-not-allowed opacity-40" aria-disabled="true">
                    <span aria-hidden="true">←</span> Anterior
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-ghost btn-sm">
                    <span aria-hidden="true">←</span> Anterior
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1.5 text-sm text-slate-500" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-primary btn-sm pointer-events-none" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn btn-ghost btn-sm"
                               aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-ghost btn-sm">
                    Siguiente <span aria-hidden="true">→</span>
                </a>
            @else
                <span class="btn btn-ghost btn-sm cursor-not-allowed opacity-40" aria-disabled="true">
                    Siguiente <span aria-hidden="true">→</span>
                </span>
            @endif
        </div>
    </nav>
@endif
