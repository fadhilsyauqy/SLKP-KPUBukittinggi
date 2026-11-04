@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman Ringkas">
        <ul class="pagination pagination-sm mb-0 align-items-center">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link"><i class="bi bi-chevron-left me-1"></i> Sebelumnya</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="bi bi-chevron-left me-1"></i> Sebelumnya</a>
                </li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Selanjutnya <i class="bi bi-chevron-right ms-1"></i></a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">Selanjutnya <i class="bi bi-chevron-right ms-1"></i></span>
                </li>
            @endif
        </ul>
    </nav>
@endif
