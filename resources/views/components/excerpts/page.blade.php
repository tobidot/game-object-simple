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
    <div class="excerpt__title">
        <h3>
            {{$page->title}}
        </h3>
    </div>
    @isset($page->thumbnail)
        <div class="excerpt__thumbnail">
            {!! ViewHelper::mediaImageHtml($page->thumbnail, 'cover') !!}
        </div>
    @endif
    <div class="excerpt__meta">
        @isset($page->created_at)
            <span>
                {{ $page->created_at->setTimezone(new DateTimeZone("Europe/Berlin"))->format('Y-m-d') }}
            </span>
        @endisset
    </div>
    <div class="excerpt__content">
        <p>
            {{ substr( strip_tags( html_entity_decode( str_replace("<br>"," ", $page->content) ) ) , 0, 255) }}
        </p>
        <div class="excerpt__actions">
            <x-link :href="route('pages.show', ['page'=>$page])">
                {{__("Read More")}}
            </x-link>
        </div>
    </div>
</div>
