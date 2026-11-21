<x-layout title="Bitácora">
    <div class="page-heading">
        <div>
            <h1>Bitácora de acciones</h1>
            <p>Registro de acceso, configuración y decisiones del internado.</p>
        </div>
    </div>
    <section class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Detalle</th></tr></thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td>{{ $entry->created_at }}</td>
                            <td>{{ $entry->name ?? 'Sin sesión' }}</td>
                            <td>{{ \App\Services\Audit::title($entry->action) }}</td>
                            <td class="wrap">{{ \App\Services\Audit::describe($entry->action, $entry->details) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No hay acciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $entries->withQueryString()->links() }}
    </section>
</x-layout>
