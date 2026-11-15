<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $title ?? 'Panel' }} · SICLIN</title><link rel="stylesheet" href="{{ asset('siclin.css') }}"><script src="{{ asset('siclin.js') }}" defer></script></head><body>
@auth
<div class="shell"><aside class="sidebar"><a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark">S</span><span>SICLIN<small>UNIFRANZ · LA PAZ</small></span></a><p class="nav-caption">COORDINACIÓN DE INTERNADO</p><nav aria-label="Navegación principal">
<a class="{{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">⌂ <span>Inicio</span></a>
@if(in_array(auth()->user()->role,['administrador','coordinador']))
@if(auth()->user()->role==='administrador')<a href="{{ route('management','usuarios') }}">01 <span>Usuarios y seguridad</span></a>@endif
<a class="{{ request()->is('gestion*')?'active':'' }}" href="{{ route('management','hospitales') }}">02 <span>Gestión académica</span></a>
@endif
<a class="{{ request()->routeIs('rotations')?'active':'' }}" href="{{ route('rotations') }}">03 <span>Postulación y rotes</span></a>
<a class="{{ request()->routeIs('clinical')?'active':'' }}" href="{{ route('clinical') }}">04 <span>Seguimiento clínico</span></a>
<a class="{{ request()->routeIs('reports')?'active':'' }}" href="{{ route('reports') }}">05 <span>Libreta y reportes</span></a>
@if(in_array(auth()->user()->role,['administrador','coordinador']))<a class="{{ request()->routeIs('scenarios')?'active':'' }}" href="{{ route('scenarios') }}">06 <span>Análisis de escenarios</span></a>@endif
<a class="{{ request()->routeIs('shifts')?'active':'' }}" href="{{ route('shifts') }}">07 <span>Guardias y tutores</span></a>
@if(in_array(auth()->user()->role,['administrador','coordinador']))<a href="{{ route('audit') }}">↗ <span>Bitácora</span></a>@endif
</nav><div class="sidebar-note"><span class="status-dot"></span> Sede La Paz<p>Planificación académica con decisiones trazables.</p></div></aside>
<div class="workspace"><header class="topbar"><span>Internado de Medicina <span class="muted">/ {{ $title ?? 'Panel' }}</span></span><div class="account"><span><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->role) }}</small></span><form method="post" action="{{ route('logout') }}">@csrf<button class="button subtle">Salir</button></form></div></header><main>
@else<div class="guest"><main>@endauth
@if(session('status'))<div class="notice success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert"><strong>Revisa los datos</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
{{ $slot }}
</main>@auth<footer>SICLIN · Universidad Franz Tamayo · Sede La Paz <span>Gestión del internado clínico</span></footer></div></div>@else</div>@endauth
</body></html>
