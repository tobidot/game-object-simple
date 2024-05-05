@php
    use App\Helpers\ViewHelper;
    use App\Models\CodeRelease;use App\Models\Project;
@endphp
@props([
    'model',
    'latestRelease' => null,
])
<?php
/**
 * @var Project $project
 * @var CodeRelease $latestRelease
 */
$project = $model;
$latestRelease = $project->codeReleases()->latest()->first();
?>

<div class="excerpt">
    <div class="excerpt__title">
        <h3>
            {{$project->title}}
        </h3>
    </div>
    @isset($project->thumbnail)
        <div class="excerpt__thumbnail">
            {!! ViewHelper::mediaImageHtml($model->thumbnail, 'cover') !!}
        </div>
    @endif
    <div class="excerpt__meta">
        @isset($project->created_at)
            <span>
                {{ $project->created_at->setTimezone(new DateTimeZone("Europe/Berlin"))->format('Y-m-d') }}
            </span>
        @endisset
    </div>
    <div class="excerpt__content">
        {{ substr( strip_tags( html_entity_decode( str_replace("<br>"," ", $project->description) ) ) , 0, 255) }}
    </div>
    <div class="excerpt__actions">
        <x-link :href="route('projects.show', ['project'=>$project])">
            {{__("Read More")}}
        </x-link>
        @if($latestRelease !== null)
            <x-link :href="route('projects.proxy', ['project' => $project, 'path' => 'index.html'])"
                    target="_blank">
                {{__("Try it out")}} ({{$latestRelease->version}})
            </x-link>
        @endif
    </div>
</div>
