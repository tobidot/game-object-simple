@php use App\Models\Project; @endphp
@php
/**
 * @var Project[] $projects
 */
@endphp
<x-layouts.app>
    <x-slot name="title">
        Project - Archive
    </x-slot>


    <div class="archive">
        @foreach($projects as $project)
            <div class="archive__item">
                <x-excerpt :model="$project"></x-excerpt>
            </div>
        @endforeach
    </div>

  <?php //<livewire:pagination class="{{Project::class}}" />?>
</x-layouts.app>

