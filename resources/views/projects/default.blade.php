@php
    use App\Models\Project;
    use App\Helpers\ViewHelper;
@endphp
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
    <div class="project__teaser">
        <img src="{{ViewHelper::mediaUrl($project->thumbnail)}}" alt="{{$project->title}} - Teaser">
    </div>
    <div class="project__content">
        {!! $project->description !!}
    </div>
    @if($project->codeReleases !== null && $project->codeReleases->count() > 0)
        <div class="project__releases">
            <h2>
                Releases
            </h2>
            <ul>
                @foreach($project->codeReleases as $release)
                    {{$release->version}} :
                    <x-link :href="route('code-release', ['codeRelease' => $release])" target="_blank">
                        Play
                    </x-link>
                @endforeach
            </ul>
        </div>
    @endif

</x-layouts.app>

