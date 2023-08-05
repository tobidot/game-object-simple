@php
@endphp
@props([
    'title' => 'title',
    'footer' => '',
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
</head>
<body class="view-{{$viewName ?? 'unknown'}}">
<header>
    <div class="logo">
        <img class="logo__image" width="853" height="426" src="{{asset('logo-dark-stroke.png')}}" alt="logo">
        <span class="logo__text">
            {{config('app.name')}}
        </span>
    </div>
    <h1 class="title">
        {!! $title !!}
    </h1>
    <div class="meta">
        {!! $meta ?? '' !!}
    </div>
    <x-navigation>

    </x-navigation>
</header>
<main {{$attributes->merge([])}}>
    {{ $slot }}
</main>
<footer>
    {{$footer}}
</footer>
    @livewireScripts
</body>
</html>
