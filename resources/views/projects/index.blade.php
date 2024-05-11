@php
    use App\Models\Project;
    use \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp
@php
    /**
     * @var LengthAwarePaginator $paginator
     */
@endphp
<x-layouts.app class="projects">
    <x-slot name="title">
        Project - Archive
    </x-slot>

    <x-pagination.section :paginator="$paginator"/>

    <div class="archive archive--with-excerpts">
        @foreach($paginator->items() as $project)
            <div class="archive__item">
                <x-excerpt :model="$project"></x-excerpt>
            </div>
        @endforeach
    </div>


    <?php //<livewire:pagination class="{{Project::class}}" />?>
</x-layouts.app>

