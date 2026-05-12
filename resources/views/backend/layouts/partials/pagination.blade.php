@if ($paginator->hasPages())
    <nav class="mt-1" aria-label="Page navigation">
        <ul class="pagination justify-content-end flex-wrap">
            @if ($paginator->onFirstPage())
                <li class="page-item pt-1 prev disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true">
                        <i class="tf-icon bx bx-chevron-left"></i>
                    </span>
                </li>
            @else
                <li class="page-item pt-1 prev">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">
                        <i class="tf-icon bx bx-chevron-left"></i>
                    </a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li  class="page-item pt-1 disabled" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item pt-1 active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item pt-1"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="page-item pt-1 next">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')"><i class="tf-icon bx bx-chevron-right"></i></a>
                </li>
            @else
                <li class="page-item pt-1 next disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true"><i class="tf-icon bx bx-chevron-right"></i></span>
                </li>
            @endif
        </ul>
    </nav>
@endif
{{-- @if ($paginator->hasPages())
    <nav class="mt-2" aria-label="Page navigation">
        <ul class="pagination justify-content-end">
            @if ($paginator->onFirstPage())
                <li class="page-item pt-1 prev disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true">
                        <i class="tf-icon bx bx-chevron-left"></i>
                    </span>
                </li>
            @else
                <li class="page-item pt-1 prev">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">
                        <i class="tf-icon bx bx-chevron-left"></i>
                    </a>
                </li>
            @endif

            @php
                $maxPagesToShow = 5; // Số trang tối đa để hiển thị trước và sau trang hiện tại
                $currentPage = $paginator->currentPage();
                $lastPage = $paginator->lastPage();
            @endphp
            
            @for ($i = 1; $i <= $lastPage; $i++)
                @if ($i == 1 || $i == $lastPage || abs($i - $currentPage) < $maxPagesToShow / 2)
                    @if ($i == $currentPage)
                        <li class="page-item pt-1 active d-none d-md-block" aria-current="page"><span class="page-link">{{ $i }}</span></li>
                    @else
                        <li class="page-item pt-1 d-none d-md-block"><a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a></li>
                    @endif
                @elseif (abs($i - $currentPage) == floor($maxPagesToShow / 2) + 1 && $currentPage > 1 && $currentPage < $lastPage)
                    <li class="page-item pt-1 d-none d-md-block"><span class="page-link">..</span></li>
                @endif
            @endfor

            @if ($paginator->hasMorePages())
                <li class="page-item pt-1 next">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')"><i class="tf-icon bx bx-chevron-right"></i></a>
                </li>
            @else
                <li class="page-item pt-1 next disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true"><i class="tf-icon bx bx-chevron-right"></i></span>
                </li>
            @endif
        </ul>
    </nav>
@endif --}}
