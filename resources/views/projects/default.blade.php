@php use App\Models\Project; @endphp
@props([
    'project'
])
@php
    /**
     * @var Project $project
     */
@endphp

<x-layouts.app class="project">
    <x-slot name="title">
        {{ $project->title }}
    </x-slot>
    <div class="project__content">
        {!! $project->description !!}
    </div>
    @if($project->code_releases)
        <div class="project__releases">
            <h2>
                Releases
            </h2>
            <ul>
                @foreach($project->code_releases as $release)
                    {{$release->version}} :
                    <x-link :href="route('code_release', ['code_release' => $release])" target="_blank">
                        Play
                    </x-link>
                @endforeach
            </ul>
        </div>
    @endif

</x-layouts.app>

