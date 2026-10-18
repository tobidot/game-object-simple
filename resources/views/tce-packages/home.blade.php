<x-layouts.app title="Home">
    <x-slot name="head">
        <style>
            /* This page only: the desktop fills exactly the space below the header, with no
               page-level scroll. Flex alone isn't enough here — `main` also needs its default
               `min-height: auto` reset to 0 (a flex item otherwise refuses to shrink below its
               content's intrinsic size), and its 1rem padding removed, or the desktop would
               still overflow the viewport by that much. */
            html, body {
                height: 100%;
                overflow: hidden;
            }
            body {
                display: flex;
                flex-direction: column;
            }
            .header {
                flex: 0 0 auto;
            }
            main {
                flex: 1 1 auto;
                min-height: 0;
                padding: 0;
                display: flex;
                overflow: hidden;
            }
            tce-desktop {
                display: block;
                width: 100%;
                height: 100%;
            }
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
</x-layouts.app>
