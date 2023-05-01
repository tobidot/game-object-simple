@php
    use App\Helpers\ViewHelper;
    use App\Models\Project;
@endphp
@props([
    'model',
])
<?php
$project = $model;
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
        <x-link :href="route('project', ['project'=>$project])">
            {{__("Read More")}}
        </x-link>
    </div>
    <div class="excerpt__background">
        @isset($page->thumbnail)
            <img src="{{ViewHelper::mediaUrl($page->thumbnail)}}" alt="cover">
        @endif
    </div>
</div>
