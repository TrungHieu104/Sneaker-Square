@if ($paginator->hasPages())
    <div class="paginate d-flex justify-content-center align-items-center gap-3">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="disabled list-unstyled" aria-disabled="true">
                <span class="item-paginate btn" aria-hidden="true"><i class='bx bx-chevron-left btn-left'></i></span>
            </li>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="item-paginate btn">
                <i class='bx bx-chevron-left btn-left'></i>
            </a>
        @endif

        {{-- Pagination Elements --}}
        @php
            $maxLinks = 4; 
            $halfMaxLinks = floor($maxLinks / 2);
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            $startPage = max($currentPage - $halfMaxLinks, 1);
            $endPage = min($startPage + $maxLinks - 1, $lastPage);
        @endphp

        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $currentPage)
                        <a href="#" class="item-paginate active btn" aria-current="page">
                            {{ $page }}
                        </a>
                    @elseif ($page >= $startPage && $page <= $endPage)
                        <a href="{{ $url }}" class="item-paginate btn">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="item-paginate btn">
                <i class='bx bx-chevron-right btn-right'></i>
            </a>
        @else
            <li class="disabled list-unstyled" aria-disabled="true" aria-label="@lang('pagination.next')">
                <span class="item-paginate btn" aria-hidden="true"><i class='bx bx-chevron-right btn-right'></i></span>
            </li>
        @endif
    </div>
@endif