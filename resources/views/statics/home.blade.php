<x-layouts.app class="home desktop-view" title="Home">
    <x-slot name="head">
        @vite(['resources/css/desktop.scss'])
    </x-slot>
    <x-slot name="foot">
        @vite(['resources/js/desktop.ts'])
    </x-slot>
    <div id="desktop">

    </div>
</x-layouts.app>
