@php use App\Helpers\ViewHelper; @endphp
@php use App\Models\Page; @endphp
@props([
    'page',
])
@php
/**
 * @var Page $page
 */
@endphp

<x-layouts.app class="page">
    <x-slot name="title">
        {{$page->title}}
    </x-slot>
    <div class="page__teaser">
        <img src="{{ViewHelper::mediaUrl($page->thumbnail)}}" alt="{{$page->title}} - Teaser">
    </div>
    <div class="page__content">
        {!! $page->content !!}
    </div>
</x-layouts.app>
