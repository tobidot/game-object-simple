<x-layouts.raw>
    <x-slot name="title">
        {{ $package->name }}
    </x-slot>
    <x-slot name="head">
        <style>
            html, body { margin: 0; height: 100%; }
            tce-desktop { display: block; width: 100%; height: 100%; }
        </style>
    </x-slot>
    <x-slot name="foot">
        {{-- Zero-script embed: each dependency is its own explicit <script> tag, loaded in
             dependency order, so they share one page-level loader — see "Integration Into
             Websites: Zero-Script Embed". No bundler/build step runs on this page. --}}
        @foreach ($embed_chain as $dependency)
            <script src="{{ route('tce-elements.embed', ['name' => $dependency->name, 'namespace' => $dependency->namespace]) }}"></script>
        @endforeach
    </x-slot>
    <tce-desktop data-apps="{{ $apps }}" data-app-icons="{{ $app_icons }}"></tce-desktop>
</x-layouts.raw>
