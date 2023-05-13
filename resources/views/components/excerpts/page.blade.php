@php
    use App\Models\Page;
    use App\Helpers\ViewHelper;
@endphp
@props([
    'model',
])
<?php
$page = $model;
/**
 * @var Page $page
 */
?>

<div class="excerpt">
    <div class="excerpt__content">
        <h3>
            {{$page->title}}
        </h3>
        <p>
            {{ substr( strip_tags( html_entity_decode( str_replace("<br>"," ", $page->content) ) ) , 0, 255) }}
        </p>
        <x-link :href="route('pages.show', ['page'=>$page])">
            {{__("Read More")}}
        </x-link>
    </div>
    <div class="excerpt__background">
        @isset($page->thumbnail)
            {!! ViewHelper::mediaImageHtml($model->thumbnail, 'cover') !!}
        @endif
    </div>
</div>
