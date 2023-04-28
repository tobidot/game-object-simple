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

    @foreach($projects as $project)
        <x-excerpt :model="$project"></x-excerpt>
    @endforeach

    <livewire:pagination class="{{Project::class}}" />
</x-layouts.app>

