@php
    use \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp
@php
    /**
     * @var LengthAwarePaginator $paginator
     */
@endphp
<x-layouts.app class="pages">
    <x-slot name="title">
        Page - Archive
    </x-slot>

    <x-pagination.section :paginator="$paginator"/>

    <div class="archive archive--with-excerpts">
        @foreach($paginator->items() as $page)
            <div class="archive__item">
                <x-excerpt :model="$page"></x-excerpt>
            </div>
        @endforeach
    </div>
</x-layouts.app>

