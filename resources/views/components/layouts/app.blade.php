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
    <title>My Home</title>
    @livewireStyles
</head>
<body class="view-{{$viewName ?? 'unknown'}}">
<header>
    <h1 class="title">
        {{$title}}
    </h1>
    <nav>
        <x-navigation>

        </x-navigation>
    </nav>
</header>
<main>
    {{ $slot }}
</main>
<footer>
    {{$footer}}
</footer>
    @livewireScripts
</body>
</html>
