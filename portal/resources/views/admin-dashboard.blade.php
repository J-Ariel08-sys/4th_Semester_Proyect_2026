<x-layout title="Inicio">
    <div class="page-heading"><div><p class="eyebrow">Administrador</p><h1>Administrar accesos</h1><p>Cuentas, permisos y seguridad del portal.</p></div></div>
    <div class="module-grid">
        <a class="module" href="{{ route('management', 'usuarios') }}"><h3>Cuentas de usuario →</h3><p>Crea cuentas, asigna roles y envía la activación por correo.</p></a>
        <a class="module" href="{{ route('permissions') }}"><h3>Roles y permisos →</h3><p>Consulta qué acciones corresponden a cada rol.</p></a>
        <a class="module" href="{{ route('audit') }}"><h3>Bitácora de acciones →</h3><p>Revisa accesos y cambios registrados en el sistema.</p></a>
    </div>
    <div class="stats">
        <div class="stat"><small>Cuentas</small><strong>{{ $users }}</strong></div>
        <div class="stat"><small>Pendientes de activación</small><strong>{{ $pending }}</strong></div>
        <div class="stat"><small>Intentos fallidos en 24 horas</small><strong>{{ $failed }}</strong></div>
    </div>
    <section class="card" id="notificaciones"><h2>Pendientes de acceso</h2><p class="muted">{{ $pending }} cuentas pendientes de activación. Las nuevas cuentas definen su contraseña mediante un código enviado al correo registrado.</p><a href="{{ route('management', 'usuarios') }}">Revisar cuentas</a></section>
</x-layout>
