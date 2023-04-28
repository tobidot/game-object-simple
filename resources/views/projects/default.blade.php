@php use App\Models\Project; @endphp
@props([
    'project'
])
@php
/**
 * @var Project $project
 */
@endphp

<x-layouts.app>
    <x-slot name="title">
        {{ $project->title }}
    </x-slot>
    <p>
        {!! $project->description !!}
    </p>
    @if($project->code_releases)
        <h3>
            Releases
        </h3>
        <ul>
            @foreach($project->code_releases as $release)
                {{$release->version}} :
                <x-link :href="route('code_release', ['code_release' => $release])" target="_blank">
                    Play
                </x-link>
            @endforeach
        </ul>
    @endif

</x-layouts.app>

