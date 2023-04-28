<x-layouts.app>
    <x-slot name="title">
        Archive
    </x-slot>

    @foreach($pages as $page)
        <x-excerpt :model="$page"></x-excerpt>
    @endforeach
</x-layouts.app>

