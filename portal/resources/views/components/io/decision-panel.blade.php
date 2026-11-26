@props(['io', 'cycles' => collect()])
@php
    $dec = $io['decisiones'];
    $rec = $dec['recomendacion'];
    $ctx = $dec['contexto_demanda'];
    $rhoE3 = $dec['metricas_sprint2']['e3_rho_con_internos_pct'] ?? null;
@endphp
<section class="card io-section" id="io-decisiones" data-io-module="decisiones" data-io-api="{{ route('io.dashboard.api') }}">
    <div class="card-head">
        <div>
            <p class="eyebrow">Sprint 3 · Teoría de Decisiones (Criterio Maximin)</p>
            <h2>Matriz de Pagos y Política Recomendada</h2>
        </div>
        @if($rec)
            <span class="badge io-badge-win">Ganadora Maximin: {{ $rec['codigo'] }} ({{ number_format($rec['peor_escenario'], 0) }}/100)</span>
        @endif
    </div>

    <div class="stats io-kpi-grid">
        <div class="stat">
            <small>Plazas ofertadas</small>
            <strong>{{ $ctx['plazas_ofertadas'] }}</strong>
        </div>
        <div class="stat">
            <small>Reserva de brigadas</small>
            <strong>{{ $ctx['cupos_reserva'] }}</strong>
        </div>
        <div class="stat">
            <small>Postulantes registrados</small>
            <strong>{{ $ctx['postulantes'] }}</strong>
        </div>
        <div class="stat">
            <small>Ocupación E3 (Sprint 2)</small>
            <strong data-io="colas-e3-rho-con">{{ $rhoE3 !== null ? number_format($rhoE3, 1).'%' : '—' }}</strong>
        </div>
    </div>

    <div class="table-wrap">
        <table class="io-table">
            <thead>
                <tr>
                    <th>Política de asignación</th>
                    <th>E1 · Normal</th>
                    <th>E2 · Sobredemanda</th>
                    <th>E3 · Contingencia</th>
                    <th>Peor Escenario (Mínimo por fila)</th>
                    <th>Valor Esperado (VE)</th>
                </tr>
            </thead>
            <tbody data-io="decisiones-tbody">
                @foreach($dec['matriz'] as $code => $row)
                    <tr class="{{ $row['recomendada'] ? 'io-highlight' : '' }}">
                        <td>
                            <strong>{{ $code }}</strong> · {{ $row['nombre'] }}
                            @if($row['recomendada'])<span class="io-rec-tag">Recomendada</span>@endif
                        </td>
                        <td>{{ number_format($row['E1'], 2) }}</td>
                        <td>{{ number_format($row['E2'], 2) }}</td>
                        <td>{{ number_format($row['E3'], 2) }}</td>
                        <td><span class="io-min-pill {{ $row['recomendada'] ? 'win' : '' }}">{{ number_format($row['peor_escenario'], 2) }}</span></td>
                        <td><strong>{{ number_format($row['valor_esperado'], 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($rec)
        <div class="io-recommended-card">
            <div class="io-recommended-header">
                <h3>Política Recomendada: {{ $rec['codigo'] }} · {{ $rec['nombre'] }} (Mínimo garantizado {{ number_format($rec['peor_escenario'], 0) }}/100 · VE = <span data-io="decisiones-ve">{{ number_format($rec['valor_esperado'], 2) }}</span>)</h3>
                <p>Combina balance 2.°/3.er nivel con reserva de internos en Brigadas Rotatorias de Contingencia. Reserva sugerida de brigadas rotatorias: {{ number_format($dec['porcentaje_brigadas'], 1) }}%.</p>
            </div>
            @if($cycles->isNotEmpty())
                <form class="io-apply-inline" method="post" action="{{ route('io.apply-a3.select') }}" data-confirm="¿Aplicar Política A3 (Maximin) y calcular la propuesta de rotes para la convocatoria seleccionada?">
                    @csrf
                    <label>Convocatoria
                        <select name="cycle_id" required>
                            @foreach($cycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->name }} ({{ $cycle->status }})</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit">Aplicar Política A3 y Asignar Rotes</button>
                </form>
            @else
                <p class="help">Cierra las postulaciones de una convocatoria futura en <a href="{{ route('rotations') }}">Rotes</a> para aplicar la Política A3 directamente.</p>
            @endif
        </div>
    @endif
</section>
