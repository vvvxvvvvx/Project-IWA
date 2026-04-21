@if ($paginator->hasPages())
<nav class="pagination-nav">
    @if ($paginator->onFirstPage())
        <span class="secondary-button pagination-disabled">Vorige</span>
    @else
        <a class="secondary-button" href="{{ $paginator->previousPageUrl() }}" rel="prev">Vorige</a>
    @endif

    <span class="muted">Pagina {{ $paginator->currentPage() }}</span>

    @if ($paginator->hasMorePages())
        <a class="secondary-button" href="{{ $paginator->nextPageUrl() }}" rel="next">Volgende</a>
    @else
        <span class="secondary-button pagination-disabled">Volgende</span>
    @endif
</nav>
@endif
