@if ($paginator->hasPages())
    <nav class="pagination-wrap" aria-label="Navigasi halaman">
        <div class="ticket-pagination">
            @if ($paginator->onFirstPage())
                <span class="pagination-btn disabled">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="pagination-btn">Sebelumnya</a>
            @endif

            @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                @if ($page === $paginator->currentPage())
                    <span class="pagination-link active">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="pagination-link">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="pagination-btn">Berikutnya</a>
            @else
                <span class="pagination-btn disabled">Berikutnya</span>
            @endif
        </div>
    </nav>
@endif