<x-layout title="Inicio">
    <div class="page-heading"><h1>Hola, {{ explode(' ', auth()->user()->name)[0] }}</h1></div>
    <div class="module-grid">
        @foreach($actions as [$label, $route, $description])
            <a class="module" href="{{ route($route) }}"><h2>{{ $label }}</h2><p>{{ $description }}</p></a>
        @endforeach
    </div>
    <div class="stats">
        @foreach($stats as $label => $count)<div class="stat"><small>{{ $label }}</small><strong>{{ $count }}</strong></div>@endforeach
    </div>
    <section class="card">
        <div class="card-head"><h2>{{ $manager ? 'Asignaciones recientes' : 'Mis rotes' }}</h2><a href="{{ route('clinical') }}">Ver seguimiento</a></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Interno</th><th>Especialidad</th><th>Hospital</th><th>Periodo</th></tr></thead>
            <tbody>
            @forelse($assignments->sortByDesc('id')->take(5) as $assignment)
                <tr><td>{{ $assignment->intern }}</td><td>{{ $assignment->specialty }}</td><td>{{ $assignment->hospital }}</td><td>{{ $assignment->starts_on }} — {{ $assignment->ends_on }}</td></tr>
            @empty
                <tr><td colspan="4" class="empty">Todavía no hay rotes asignados.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <section class="card" id="notificaciones">
        <h2>Avisos</h2>
        @forelse($notifications as $notification)
            <div class="timeline-item">
                <strong>{{ $notification->data['title'] }}</strong><p>{{ $notification->data['message'] }}</p>
                <div class="form-actions"><a href="{{ $notification->data['url'] }}">Consultar</a>
                @if(!$notification->read_at)<form method="post" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="button small secondary">Marcar leída</button></form>@endif</div>
            </div>
        @empty
            <p class="muted">No tienes avisos nuevos.</p>
        @endforelse
    </section>
</x-layout>
