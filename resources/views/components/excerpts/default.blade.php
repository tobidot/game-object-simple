@php use Illuminate\Database\Eloquent\Model; @endphp
@props([
    'model',
])
<?php
/**
 * @var Model $model
 */
?>

<div class="excerpt">
    @isset($model->title)
        <h3>
            {{$model->title }}
        </h3>
    @endisset
    @isset($model->content)
        <p>
            {{ substr( strip_tags( html_entity_decode( str_replace("<br>"," ", $page->content ?? '') ) ) , 0, 255) }}
        </p>
    @endisset
{{--    <x-link :href="route('page', ['page'=>$page])">--}}
{{--        {{__("Read More")}}--}}
{{--    </x-link>--}}
</div>
