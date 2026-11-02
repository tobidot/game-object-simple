@php
    use App\Models\Page;
    use App\Helpers\ViewHelper;
@endphp
@props([
    'model',
])
@php
    /** @var Page $page */
    $page = $model;
    $thumbnail = $page->thumbnail;
@endphp

<div class="excerpt">
    <div class="excerpt__title">
        <h3 title="{{ $page->title }}">
            {{ $page->title }}
        </h3>
    </div>
    @if(!empty($thumbnail))
        <div class="excerpt__thumbnail">
            {!! ViewHelper::mediaImageHtml($thumbnail, $page->title) !!}
        </div>
    @else
        <div class="excerpt__thumbnail excerpt__thumbnail--placeholder" aria-label="No thumbnail">
            <div class="excerpt__placeholder"></div>
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
        <p class="excerpt__text">{{ ViewHelper::excerpt($page->content_html) }}</p>
    </div>
    <div class="excerpt__actions">
        <x-link :href="route('pages.show', ['page' => $page])">
            {{ __("Read More") }}
        </x-link>
    </div>
</div>
