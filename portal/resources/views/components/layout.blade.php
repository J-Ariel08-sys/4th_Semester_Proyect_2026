<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $title ?? 'Panel' }} · SICLIN</title>@if(file_exists(public_path('build/manifest.json')))@vite(['resources/css/app.css','resources/js/app.js'])@else<link rel="stylesheet" href="{{ asset('siclin.css') }}"><script src="{{ asset('siclin.js') }}" defer></script>@endif</head><body>
<a href="#contenido" class="skip-link">Saltar al contenido</a>
@auth
<div class="shell"><aside class="sidebar" id="navigation"><a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark">S</span><span>SICLIN<small>Unifranz — Sede La Paz</small></span></a><nav aria-label="Navegación principal"><a href="{{ route('dashboard') }}">Inicio</a>
@foreach(config('navigation.'.auth()->user()->role,[]) as $group=>$links)<details class="nav-group" @if(collect($links)->contains(fn($link)=>request()->routeIs($link[1]))) open @endif><summary>{{ $group }}</summary>@foreach($links as $link)<a href="{{ route($link[1],$link[2] ?? []) }}{{ $link[3] ?? '' }}">{{ $link[0] }}</a>@endforeach</details>@endforeach
<a href="{{ route('help') }}">Ayuda</a><form method="post" action="{{ route('logout') }}">@csrf<button class="button secondary">Cerrar sesión</button></form></nav></aside>
<div class="workspace"><header class="topbar"><button type="button" class="nav-toggle" aria-controls="navigation" aria-expanded="false">Menú de navegación</button><span>SICLIN <small>Unifranz — Sede La Paz</small></span><div class="account"><a href="{{ route('dashboard') }}#notificaciones">Notificaciones</a><a href="{{ route('account') }}">Mi cuenta</a><span><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->role) }}</small></span></div></header><main id="contenido">
@else<div class="guest"><main id="contenido">@endauth
@if(session('status'))<div class="notice success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert"><strong>Revisa los datos</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
{{ $slot }}
</main>@auth<footer>SICLIN · Unifranz — Sede La Paz <span>Gestión académica del internado</span></footer></div></div>@else</div>@endauth
</body></html>
