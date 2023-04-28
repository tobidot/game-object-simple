@props([
    'code_release',
])
@php
    /**
     * @var \App\Models\CodeRelease $code_release
     */
@endphp

<x-layouts.raw>
    <x-slot name="head">
        <style>
            {!! $code_release->css !!}
        </style>
    </x-slot>
    <x-slot name="foot">
        <script src="{{$code_release->script_main_url}}">

        </script>
    </x-slot>
    <main id="code-release-container">

    </main>
</x-layouts.raw>
