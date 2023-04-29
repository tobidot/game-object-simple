<x-layouts.app>
    <x-slot name="title">
        Archive
    </x-slot>

    <div class="archive">
        @foreach($pages as $page)
            <div class="archive__item">
                <x-excerpt :model="$page"></x-excerpt>
            </div>
        @endforeach
    </div>
</x-layouts.app>

