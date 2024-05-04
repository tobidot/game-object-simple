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
        {{ $paginator->links('pagination::default') }}
    </div>
    <div class="pagination-section__search">
        <form id="search">
            <label>
                <input name="text" type="text" placeholder="Search..." value="{{request()->get('search')}}"/>
            </label>
        </form>
    </div>
{{--    <div class="pagination-section__info">--}}
{{--        <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>--}}
{{--    </div>--}}
</div>
