<x-layout title="Inicio">
    <div class="page-heading"><h1>Inicio</h1></div>
    <div class="module-grid">
        <a class="module" href="{{ route('management', 'usuarios') }}">
            <h2>Cuentas de usuario</h2>
            <p>Crear cuentas y asignar roles.</p>
        </a>
        <a class="module" href="{{ route('management', 'habilitaciones') }}">
            <h2>Habilitar cuentas</h2>
            <p>Autorizar el ingreso al portal.</p>
        </a>
        <a class="module" href="{{ route('management', 'internos') }}">
            <h2>Registro de internos</h2>
            <p>Consultar y completar sus datos.</p>
        </a>
    </div>
    <section class="card" id="notificaciones">
        <div class="card-head"><h2>Estado de las cuentas</h2></div>
        <div class="stats">
            <div class="stat"><small>Cuentas registradas</small><strong>{{ $users }}</strong></div>
            <div class="stat"><small>Sin contraseña</small><strong>{{ $pending }}</strong></div>
            <div class="stat"><small>Sin habilitar</small><strong>{{ $approvalPending }}</strong></div>
            <div class="stat"><small>Intentos fallidos en 24 h</small><strong>{{ $failed }}</strong></div>
        </div>
        <a href="{{ route('audit', ['access' => 1]) }}">Ver accesos y bloqueos</a>
    </section>
</x-layout>
