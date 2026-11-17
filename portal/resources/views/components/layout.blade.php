<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'Panel' }} · SICLIN</title>
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css','resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('siclin.css') }}">
        <script src="{{ asset('siclin.js') }}" defer></script>
    @endif
</head>
<body>
<a href="#contenido" class="skip-link">Saltar al contenido</a>
@auth
    <div class="shell">
    <aside class="sidebar" id="navigation">
        <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark">S</span><span>SICLIN<small>Unifranz · La Paz</small></span></a>
        <nav aria-label="Navegación principal">
            <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>Inicio</a>
            @foreach(config('navigation.'.auth()->user()->role, []) as $group => $links)
                @php
                    $matches = fn ($link) => request()->routeIs($link[1], $link[1].'.*')
                        && (!isset($link[2]) || request()->route('resource') === $link[2]);
                    $active = collect($links)->contains($matches);
                @endphp
                @if(count($links) === 1)
                    <a href="{{ route($links[0][1], $links[0][2] ?? []) }}{{ $links[0][3] ?? '' }}" @if($active) aria-current="page" @endif>{{ $group }}</a>
                @else
                    <details class="nav-group" name="navigation-section" @if($active) open @endif>
                        <summary>{{ $group }}</summary>
                        @foreach($links as $link)
                            <a href="{{ route($link[1], $link[2] ?? []) }}{{ $link[3] ?? '' }}" @if($matches($link) && !isset($link[3])) aria-current="page" @endif>{{ $link[0] }}</a>
                        @endforeach
                    </details>
                @endif
            @endforeach
            <a href="{{ route('help') }}" @if(request()->routeIs('help')) aria-current="page" @endif>{{ config('siclin.demo') ? 'Guía de demostración' : 'Ayuda' }}</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="button secondary">Cerrar sesión</button></form>
        </nav>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <button type="button" class="nav-toggle button secondary" aria-controls="navigation" aria-expanded="false">Menú de navegación</button>
            <span class="page-context">{{ $title ?? 'Panel' }}</span>
            <div class="account">
                <a href="{{ route('dashboard') }}#notificaciones" aria-label="Notificaciones">Avisos</a>
                <a href="{{ route('account') }}">Mi cuenta</a>
                <span><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->role) }}</small></span>
            </div>
        </header>
        <main id="contenido">
@else
    <div class="guest"><main id="contenido">
@endauth
@if(config('siclin.demo'))
    <div class="demo-banner">Demostración · datos ficticios @auth<span>{{ ucfirst(auth()->user()->role) }} · <a href="{{ route('help') }}">Ver recorrido</a></span>@endauth</div>
@endif
@if(session('status'))<div class="notice success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())
    <div class="notice error" role="alert"><strong>Revisa los datos</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
{{ $slot }}
        </main>
@auth
        <footer>SICLIN · Unifranz — Sede La Paz <span>Gestión académica del internado</span></footer>
    </div></div>
@else
    </div>
@endauth
</body>
</html>
