@php use App\Helpers\ViewHelper;use App\Models\CodeRelease; @endphp
@props([
    'model',
])
<?php
/**
 * @var CodeRelease $model
 */
?>

<x-link :href="ViewHelper::modelUrl($model)" target="_blank">
    <div class="excerpt excerpt--button">
        <div class="excerpt__title">
            <h3>
                {{$model->version}}
            </h3>
        </div>
    </div>
</x-link>

