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
    <div class="excerpt__content">
        <h3>
            {{$project->title}}
        </h3>
        <p>
            {{ substr( strip_tags( html_entity_decode( str_replace("<br>"," ", $project->description) ) ) , 0, 255) }}
        </p>
        <div class="excerpt__actions">
            @if($latestRelease !== null)
                <x-link :href="route('code-release', ['codeRelease' => $latestRelease])" target="_blank">
                    {{__("Try it out")}} ({{$latestRelease->version}})
                </x-link>
            @endif
            <x-link :href="route('projects.show', ['project'=>$project])">
                {{__("Read More")}}
            </x-link>
        </div>
    </div>
    <div class="excerpt__background">
        @isset($project->thumbnail)
            {!! ViewHelper::mediaImageHtml($model->thumbnail, 'cover') !!}
        @endif
    </div>
</div>
