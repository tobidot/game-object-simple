<div class="livewire-pagination">
    <div class="livewire-pagination__header">
        <span class="livewire-pagination__page-info">Page: {{ $page }}</span>
    </div>
    <ul class="livewire-pagination__list">
        @foreach($items as $item)
            <li class="livewire-pagination__item">
                <span class="livewire-pagination__item-id">{{ $item->id }}:</span>
                <span class="livewire-pagination__item-title">{{ $item->title ?? 'unknown' }}</span>
            </li>
        @endforeach
    </ul>
    <div class="livewire-pagination__controls">
        @if($page > 1)
            <button type="button" class="livewire-pagination__button livewire-pagination__button--prev" wire:click="previous">Previous</button>
        @endif
        @if($has_more_pages)
            <button type="button" class="livewire-pagination__button livewire-pagination__button--next" wire:click="next">Next</button>
        @endif
    </div>
</div>
