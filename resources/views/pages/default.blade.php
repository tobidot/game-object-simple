@props([
    'page',
])
@php
/**
 * @var \App\Models\Page $page
 */
@endphp

<x-layouts.app class="page">
    <x-slot name="title">
        {{$page->title}}
    </x-slot>
    <div class="page__content">
        {!! $page->content !!}
    </div>
</x-layouts.app>
