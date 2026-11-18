<x-layout title="Inicio">
    <div class="page-heading"><div><p class="eyebrow">Administrador</p><h1>Administrar SICLIN</h1><p>Acceso completo a cuentas, gestión académica y seguimiento del internado.</p></div></div>
    <div class="module-grid">
        <a class="module" href="{{ route('management', 'usuarios') }}"><h3>Cuentas de usuario →</h3><p>Crea cuentas, asigna roles y define o cambia sus contraseñas.</p></a>
        <a class="module" href="{{ route('management', 'habilitaciones') }}"><h3>Habilitar cuentas →</h3><p>Completa los datos y autoriza el ingreso de las cuentas nuevas.</p></a>
        <a class="module" href="{{ route('rotations') }}"><h3>Postulación y rotes →</h3><p>Gestiona las propuestas y publica asignaciones.</p></a>
        <a class="module" href="{{ route('scenarios') }}"><h3>Escenarios y decisiones →</h3><p>Compara políticas de asignación y registra decisiones.</p></a>
        <a class="module" href="{{ route('clinical') }}"><h3>Seguimiento clínico →</h3><p>Consulta todos los rotes, registra evaluaciones y resuelve incidencias.</p></a>
        <a class="module" href="{{ route('shifts') }}"><h3>Guardias y tutores →</h3><p>Planifica la supervisión y revisa el cumplimiento.</p></a>
        <a class="module" href="{{ route('reports') }}"><h3>Documentos y reportes →</h3><p>Genera y consulta actas y libretas de todos los ámbitos.</p></a>
        <a class="module" href="{{ route('permissions') }}"><h3>Roles y permisos →</h3><p>Consulta qué acciones corresponden a cada rol.</p></a>
        <a class="module" href="{{ route('audit') }}"><h3>Bitácora de acciones →</h3><p>Revisa accesos y cambios registrados en el sistema.</p></a>
    </div>
    <div class="stats">
        <div class="stat"><small>Cuentas</small><strong>{{ $users }}</strong></div>
        <div class="stat"><small>Pendientes de activación</small><strong>{{ $pending }}</strong></div>
        <div class="stat"><small>Pendientes de habilitación</small><strong>{{ $approvalPending }}</strong></div>
        <div class="stat"><small>Intentos fallidos en 24 horas</small><strong>{{ $failed }}</strong></div>
    </div>
    <section class="card" id="notificaciones"><h2>Pendientes de acceso</h2><p class="muted">{{ $pending }} cuentas sin contraseña definida y {{ $approvalPending }} pendientes de habilitación. Una cuenta nueva necesita ambas condiciones para ingresar, salvo la aprobación de administradores.</p><a href="{{ route('management', 'usuarios') }}">Revisar cuentas</a></section>
</x-layout>
