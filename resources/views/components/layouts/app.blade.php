@php
@endphp
@props([
    'title' => 'title',
    'footer' => '',
    'head' => '',
    'foot' => '',
])
@php
/**
 * @var string $currentViewName
 */
@endphp

<!doctype html>
<html lang="en" >
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{config('app.name')}} - {{$title}}</title>
    @vite(['resources/css/app.scss', 'resources/js/app.ts'])
    @livewireStyles
    {!! $head !!}
</head>
<body class="view-{{$viewName ?? 'unknown'}}">
<header class="header">
    <div class="header__logo logo">
        <img class="logo__image" width="853" height="426" src="{{asset('logo-dark-stroke.png')}}" alt="logo">
        <span class="logo__text">
            {{config('app.name')}}
        </span>
    </div>
    <h1 class="header__title title">
        {!! $title !!}
    </h1>
    <x-navigation>

    </x-navigation>
</header>
<main {{$attributes->merge([])}}>
    @if(!empty($meta))
        <div class="meta">
            {!! $meta !!}
        </div>
    @endif
    {{ $slot }}
</main>
@if(!empty($footer))
<footer class="footer">
    {{$footer}}
</footer>
@endif
    @livewireScripts
    {!! $foot !!}
</body>
</html>
