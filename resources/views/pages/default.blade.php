@props([
    'page',
])
@php
/**
 * @var \App\Models\Page $page
 */
@endphp

<x-layouts.app>
    <x-slot name="title">
        {{$page->title}}
    </x-slot>
    <div class="page-content">
        {!! $page->content !!}
    </div>
</x-layouts.app>
