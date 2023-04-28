@php
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
