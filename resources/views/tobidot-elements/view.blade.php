<x-layouts.raw>
    <x-slot name="title">
        {{ $element->name }}
    </x-slot>
    <x-slot name="head">
    </x-slot>
    <x-slot name="foot">
        @vite(['resources/js/tobidot-element-viewer.ts', 'resources/css/tobidot-element-viewer.scss'])
    </x-slot>
    <main
        id="viewer"
        data-element-name="{{ $element->name }}"
        data-element-major="{{ $element->major }}"
        data-element-minor="{{ $element->minor }}"
        data-element-patch="{{ $element->patch }}"
    >

    </main>
</x-layouts.raw>
