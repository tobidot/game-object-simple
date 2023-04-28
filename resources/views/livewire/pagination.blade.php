<div>
    Page: {{$page}}
    <ul>
        @foreach($items as $item)
            <li>
                {{$item->id}} :
                {{$item->title ?? 'unknown'}}
            </li>
        @endforeach
    </ul>
    @if($page > 0)
        <button wire:click="previous">Previous</button>
    @endif
    @if($has_more_pages)
        <button wire:click="next">Next</button>
    @endif

</div>
