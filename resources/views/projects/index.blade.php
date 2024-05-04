@php
    use App\Models\Project;
    use \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp
@php
    /**
     * @var Project[] $projects
     * @var LengthAwarePaginator $paginator
     */
@endphp
<x-layouts.app>
    <x-slot name="title">
        Project - Archive
    </x-slot>

    <x-pagination.section :paginator="$paginator"/>

    <div class="archive">
        @foreach($projects as $project)
            <div class="archive__item">
                <x-excerpt :model="$project"></x-excerpt>
            </div>
        @endforeach
    </div>


    <?php //<livewire:pagination class="{{Project::class}}" />?>
</x-layouts.app>

