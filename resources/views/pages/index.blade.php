<x-layouts.app class="pages" title="Archive">
    <div class="archive">
        @foreach($pages as $page)
            <div class="archive__item">
                <x-excerpt :model="$page"></x-excerpt>
            </div>
        @endforeach
    </div>
</x-layouts.app>

