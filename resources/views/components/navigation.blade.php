@php use App\Types\View\LinkType; @endphp
@props([
    'links' => []
])

<nav {{$attributes->merge(['class' => 'navigation']) }}>
    <ul class="navigation__list">
        @foreach($links as /** @var LinkType $link */ $link )
            <li class="navigation__item">
                <x-link aria-current="{{$link->isActive()}}" href="{{$link->href}}">{{$link->label}}</x-link>
            </li>
        @endforeach
    </ul>
</nav>
