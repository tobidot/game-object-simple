@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Pagination Navigation">
        <ul class="pagination__list">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="pagination__item pagination__item--prev pagination__item--disabled disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="pagination__link pagination__link--disabled" aria-hidden="true">&laquo;</span>
                </li>
            @else
                <li class="pagination__item pagination__item--prev">
                    <a class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&laquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="pagination__item pagination__item--ellipsis pagination__item--disabled disabled" aria-disabled="true">
                        <span class="pagination__link pagination__link--ellipsis">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="pagination__item pagination__item--active active" aria-current="page">
                                <span class="pagination__link pagination__link--active">{{ $page }}</span>
                            </li>
                        @else
                            <li class="pagination__item">
                                <a class="pagination__link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="pagination__item pagination__item--next">
                    <a class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&raquo;</a>
                </li>
            @else
                <li class="pagination__item pagination__item--next pagination__item--disabled disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="pagination__link pagination__link--disabled" aria-hidden="true">&raquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
