@php use Illuminate\Pagination\LengthAwarePaginator; @endphp
@php
    /**
     * @var LengthAwarePaginator $paginator
     */
@endphp

<div class="archive archive--with-excerpts">
    @foreach($paginator->items() as $item)
        <div class="archive__item">
            <x-excerpt :model="$item"></x-excerpt>
        </div>
    @endforeach
</div>
