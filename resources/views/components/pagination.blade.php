@props(['paginator'])

<div class="board-pagination">
    <nav aria-label="페이지 네비게이션">
        <ul class="pagination">
            {{-- 첫 페이지로 이동 --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link">
                        <i class="fas fa-angle-double-left"></i>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url($navigation['first_page']) }}" title="첫 페이지로">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                </li>
            @endif

            {{-- 이전 10페이지 이동 --}}
            @if (! $navigation['has_previous_chunk'])
                <li class="page-item disabled">
                    <span class="page-link">
                        <i class="fas fa-chevron-left"></i>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url($navigation['previous_chunk_page']) }}" rel="prev" title="이전 10페이지로">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            @endif

            {{-- 페이지 번호들 --}}
            @foreach ($navigation['page_urls'] as $page => $url)
                @if ($page == $paginator->currentPage())
                    <li class="page-item active">
                        <span class="page-link">{{ $page }}</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    </li>
                @endif
            @endforeach

            {{-- 다음 10페이지 이동 --}}
            @if ($navigation['has_next_chunk'])
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url($navigation['next_chunk_page']) }}" rel="next" title="다음 10페이지로">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link">
                        <i class="fas fa-chevron-right"></i>
                    </span>
                </li>
            @endif

            {{-- 마지막 페이지로 이동 --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url($navigation['last_page']) }}" title="마지막 페이지로">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link">
                        <i class="fas fa-angle-double-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>   
   
</div>
