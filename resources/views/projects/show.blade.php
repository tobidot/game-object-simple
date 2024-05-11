@php
    use App\Models\Page;use App\Models\Project;
    use App\Helpers\ViewHelper;use Illuminate\Database\Eloquent\Collection;
@endphp
@props([
    'project',
    'relatedItems' => collect([]),
    'relatingItems' => collect([]),
])
@php
/**
 * @var Project $project
 * @var Collection<Project|Page> $relatedItems
 * @var Collection<Project> $relatingItems
 */
@endphp

<x-layouts.app class="project">
    <x-slot name="title">
        {{ $project->title }}
    </x-slot>
    <x-slot name="meta">
        <div class="meta__created-at">
             <h4 class="meta__label">posted</h4> {{ $project->created_at->setTimezone(new DateTimeZone("Europe/Berlin"))->format('Y-m-d') }}
        </div>
        <div class="meta__updated-at">
            <h4 class="meta__label">updated</h4> {{ $project->updated_at->setTimezone(new DateTimeZone("Europe/Berlin"))->format('Y-m-d') }}
        </div>
    </x-slot>
    <div class="project__teaser">
        @isset($project->thumbnail)
            {!! ViewHelper::mediaImageHtml($project->thumbnail, $project->title . ' - Teaser') !!}
        @endisset
    </div>

    @if($relatingItems !== null && $relatingItems->count() > 0)
        <div class="project__relating">
            <div class="archive archive--button">
                @foreach($relatingItems as $item)
                    <div class="archive__item">
                        <x-excerpt :model="$item" type="button"></x-excerpt>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($project->codeReleases->first() !== null)
        <div class="project__main-release">
            <div class="archive__item">
                <a href="{{route('projects.proxy', ['project'=>$project, 'path' => 'index.html'])}}"
                   target="_blank"
                   style="display: flex; justify-content: center; align-items: center; font-size: 1.35rem;">
                    Latest Version
                </a>
            </div>
        </div>
    @endif

    <div class="project__content">
        {!! $project->description !!}
    </div>

    @if($project->codeReleases !== null && $project->codeReleases->count() > 0)
        <div class="project__releases">
            <h2>
                Releases
            </h2>
            <div class="archive archive--button">
                @foreach($project->codeReleases as $related)
                    <div class="archive__item">
                        <x-excerpt :model="$related" type="button"></x-excerpt>
                    </div>
                @endforeach
            </div>
        </div>
    @endif


    @if($relatedItems !== null && $relatedItems->count() > 0)
        <div class="project__related">
            <h2>
                Related
            </h2>

            <div class="archive">
                @foreach($relatedItems as $item)
                    <div class="archive__item">
                        <x-excerpt :model="$item"></x-excerpt>
                    </div>
                @endforeach
            </div>
        </div>
    @endif


    <div class="project__comments">
        <x-comments.section :commentable="$project" :comments="$project->comments">

        </x-comments.section>
    </div>
</x-layouts.app>

