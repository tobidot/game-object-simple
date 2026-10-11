@php use Illuminate\Contracts\Pagination\LengthAwarePaginator; @endphp
@props([
    'paginator',
])

@php
    /**
     * @var LengthAwarePaginator $paginator
     */
@endphp


<div class="pagination-section">
    <div class="pagination-section__links">
        {{ $paginator->links('components.pagination.links') }}
    </div>
    <div class="pagination-section__total">
        {{ __('Total Hits: ') . $paginator->total() }}
    </div>
    <div class="pagination-section__search">
        <form id="search" class="pagination-section__search-form" method="GET" action="{{ url()->current() }}">
            <label class="pagination-section__search-label">
                <input name="text" type="text" class="pagination-section__search-input" placeholder="Search..." value="{{ request()->get('search') }}"/>
            </label>
        </form>
    </div>
</div>
