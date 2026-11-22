@if ($paginator->hasPages())
<nav role="navigation" aria-label="Paginasi" style="display:flex; flex-direction:column; align-items:center; gap:0.5rem;">
    <style>
        .gs-pager { display:flex; flex-wrap:wrap; gap:0.25rem; list-style:none; margin:0; padding:0; justify-content:center; }
        .gs-pager a, .gs-pager span.gs-pg {
            display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 0.65rem;
            border-radius:8px; border:1px solid rgba(22, 50, 79, 0.14); background:#fff; color:#16324f;
            font-weight:500; font-size:0.84rem; text-decoration:none;
            transition: background-color 140ms cubic-bezier(0.22, 1, 0.36, 1), border-color 140ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        @media (hover: hover) and (pointer: fine) {
            .gs-pager a:hover { background:#f3f1ec; border-color:rgba(22, 50, 79, 0.28); }
        }
        .gs-pager .gs-active span.gs-pg { background:#16324f; border-color:#16324f; color:#fff; }
        .gs-pager .gs-disabled span.gs-pg { opacity:.45; cursor:not-allowed; }
    </style>

    <ul class="gs-pager">
        @if ($paginator->onFirstPage())
            <li class="gs-disabled"><span class="gs-pg">&laquo; Sebelum</span></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Sebelum</a></li>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="gs-disabled"><span class="gs-pg">{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="gs-active" aria-current="page"><span class="gs-pg">{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Seterusnya &raquo;</a></li>
        @else
            <li class="gs-disabled"><span class="gs-pg">Seterusnya &raquo;</span></li>
        @endif
    </ul>

    <p style="font-size:0.78rem; color:#6b7280; margin:0; font-variant-numeric:tabular-nums;">
        Menunjukkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} daripada {{ $paginator->total() }} rekod
    </p>
</nav>
@endif
