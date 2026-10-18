<x-layouts.raw>
    <x-slot name="title">
        {{ $package->name }}
    </x-slot>
    <x-slot name="head">
        <style>
            html, body { margin: 0; height: 100%; }
            #viewer, #viewer > * { display: block; width: 100%; height: 100%; border: none; }
        </style>
    </x-slot>
    <x-slot name="foot">
        @foreach ($embed_chain as $dependency)
            <script src="{{ route('tce-elements.embed', ['name' => $dependency->name, 'namespace' => $dependency->namespace]) }}"></script>
        @endforeach
    </x-slot>
    <main id="viewer">
        <{{ $package->name }}></{{ $package->name }}>
    </main>
</x-layouts.raw>
