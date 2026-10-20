{{-- Pagination khusus Serenity GO: tidak memerlukan Tailwind / Bootstrap. --}}
@if ($paginator->hasPages())
    <nav class="serenity-pagination" role="navigation" aria-label="Navigasi halaman">
        @if ($paginator->total() > 0)
            <p class="serenity-pagination-summary">
                Menampilkan <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
                dari <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong> data
            </p>
        @endif

        <div class="serenity-pagination-pages">
            @if ($paginator->onFirstPage())
                <span class="serenity-page serenity-page-direction is-disabled" aria-disabled="true">
                    <span aria-hidden="true">‹</span><span class="serenity-page-direction-label">Sebelumnya</span>
                </span>
            @else
                <a class="serenity-page serenity-page-direction" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">
                    <span aria-hidden="true">‹</span><span class="serenity-page-direction-label">Sebelumnya</span>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="serenity-page-dots" aria-hidden="true">…</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="serenity-page is-active" aria-current="page" aria-label="Halaman {{ $page }}, halaman saat ini">{{ $page }}</span>
                        @else
                            <a class="serenity-page" href="{{ $url }}" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="serenity-page serenity-page-direction" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">
                    <span class="serenity-page-direction-label">Berikutnya</span><span aria-hidden="true">›</span>
                </a>
            @else
                <span class="serenity-page serenity-page-direction is-disabled" aria-disabled="true">
                    <span class="serenity-page-direction-label">Berikutnya</span><span aria-hidden="true">›</span>
                </span>
            @endif
        </div>

        {{-- Mobile: navigasi ringkas, tidak memecah panah Next ke baris tersendiri. --}}
        <div class="serenity-pagination-mobile">
            @if ($paginator->onFirstPage())
                <span class="serenity-page serenity-page-direction is-disabled" aria-disabled="true" aria-label="Sudah halaman pertama">‹</span>
            @else
                <a class="serenity-page serenity-page-direction" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>
            @endif
            <span class="serenity-page-status" aria-live="polite">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a class="serenity-page serenity-page-direction" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>
            @else
                <span class="serenity-page serenity-page-direction is-disabled" aria-disabled="true" aria-label="Sudah halaman terakhir">›</span>
            @endif
        </div>
    </nav>
@endif
