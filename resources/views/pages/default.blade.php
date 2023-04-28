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
    <p>
        {!! $page->content !!}
    </p>
</x-layouts.app>
