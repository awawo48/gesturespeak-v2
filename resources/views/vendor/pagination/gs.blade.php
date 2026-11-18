@if ($paginator->hasPages())
<nav role="navigation" aria-label="Paginasi" style="display:flex; flex-direction:column; align-items:center; gap:0.6rem;">
    <style>
        .gs-pager { display:flex; flex-wrap:wrap; gap:0.35rem; list-style:none; margin:0; padding:0; justify-content:center; }
        .gs-pager a, .gs-pager span.gs-pg {
            display:inline-flex; align-items:center; justify-content:center; min-width:38px; height:38px; padding:0 0.7rem;
            border-radius:10px; border:2px solid var(--border-light, #e2e8f0); background:#fff; color:var(--text-dark, #2d3436);
            font-weight:800; font-size:0.9rem; text-decoration:none; transition:all .15s ease;
        }
        .gs-pager a:hover { border-color:var(--primary, #6c5ce7); color:var(--primary, #6c5ce7); transform:translateY(-1px); }
        .gs-pager .gs-active span.gs-pg { background:var(--primary, #6c5ce7); border-color:var(--primary, #6c5ce7); color:#fff; }
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

    <p style="font-size:0.8rem; color:var(--text-muted, #636e72); margin:0;">
        Menunjukkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} daripada {{ $paginator->total() }} rekod
    </p>
</nav>
@endif

