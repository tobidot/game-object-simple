@php
    use App\Helpers\ViewHelper;
    use App\Models\Project;
@endphp
@props([
    'model',
])
<?php
$project = $model;
$latest_release = $project->codeReleases->first();
/**
 * @var Project $project
 */
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
            @if($latest_release !== null)
                <x-link :href="route('code-release', ['codeRelease' => $latest_release])"  target="_blank">
                    {{__("Try it out")}} ({{$latest_release->version}})
                </x-link>
            @endif
            <x-link :href="route('project', ['project'=>$project])">
                {{__("Read More")}}
            </x-link>
        </div>
    </div>
    <div class="excerpt__background">
        @isset($project->thumbnail)
            <img src="{{ViewHelper::mediaUrl($project->thumbnail)}}" alt="cover">
        @endif
    </div>
</div>
