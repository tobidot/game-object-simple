@php use App\Helpers\ViewHelper; @endphp
@php use App\Models\Page;use App\Models\Project;use Illuminate\Database\Eloquent\Collection; @endphp
@props([
    'page',
    'relatedItems' => collect([]),
    'relatingItems' => collect([]),
])
@php
    /**
     * @var Page $page
     * @var Collection<Project|Page> $relatedItems
     * @var Collection<Page> $relatingItems
     */
@endphp

<x-layouts.app class="page">
    <x-slot name="title">
        {{ $page->title }}
    </x-slot>
    <x-slot name="meta">
        {{ $page->created_at->setTimezone(new DateTimeZone("Europe"))->format('Y-m-d') }}
    </x-slot>
    <div class="page__teaser">
        @isset($page->thumbnail)
            {!! ViewHelper::mediaImageHtml($page->thumbnail, $page->title . ' - Teaser') !!}
        @endisset
    </div>

    @if($relatingItems !== null && $relatingItems->count() > 0)
        <div class="project__relating">
            <div class="archive archive--button">
                @foreach($relatingItems as $item)
                    <div class="archive__item">
                        <x-excerpt :model="$item" type="button"></x-excerpt>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="page__content">
        {!! $page->content !!}
    </div>

    @if($relatedItems !== null && $relatedItems->count() > 0)
        <div class="project__related">
            <h2>
                Related
            </h2>

            <div class="archive">
                @foreach($relatedItems as $item)
                    <div class="archive__item">
                        <x-excerpt :model="$item"></x-excerpt>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</x-layouts.app>
