@props(['io'])
@php
    $markov = $io['markov'];
    $sem = $markov['semaforo'];
    $est = $markov['estacionaria'];
    $mfpt = $markov['tiempo_primer_paso'];
    $sourceLabel = $markov['transiciones_observadas'] > 0
        ? 'Calibrada con '.$markov['transiciones_observadas'].' transiciones históricas observadas + matriz base del informe'
        : 'Matriz base del informe (sin cierres históricos registrados)';
@endphp
<section class="card io-section" id="io-markov" data-io-module="markov" data-io-api="{{ route('io.dashboard.api') }}">
    <div class="card-head">
        <div>
            <p class="eyebrow">Sprint 4 · Cadenas de Markov Ergódicas</p>
            <h2>Semáforo Predictivo de Contingencias y Proyección a 6 Meses</h2>
        </div>
        <span class="badge neutral io-source-pill" data-io="markov-source">{{ $sourceLabel }}</span>
    </div>

    <div class="semaforo-banner {{ $sem['css_class'] }}" data-io="markov-semaforo" role="status">
        <div class="semaforo-left">
            <div class="semaforo-dot" aria-hidden="true"></div>
            <div class="semaforo-body">
                <strong data-io="markov-semaforo-titulo">{{ $sem['titulo'] }}</strong>
                <p><span data-io="markov-semaforo-accion">{{ $sem['accion'] }}</span> Estado actual observado: <strong data-io="markov-estado-actual">{{ $markov['estado_actual'] }} ({{ $markov['estado_actual_etiqueta'] }})</strong>.</p>
            </div>
        </div>
        <div class="semaforo-metric">
            <small>P(E3) mes siguiente</small>
            <strong data-io="markov-prob-e3">{{ number_format($sem['prob_e3_pct'], 1) }}%</strong>
        </div>
    </div>

    <div class="stats io-kpi-grid">
        <div class="stat">
            <small>Estado estacionario Normal (π₁)</small>
            <strong data-io="markov-pi-e1">{{ number_format($est['E1_pct'], 1) }}%</strong>
        </div>
        <div class="stat">
            <small>Estado estacionario Sobredemanda (π₂)</small>
            <strong data-io="markov-pi-e2">{{ number_format($est['E2_pct'], 1) }}%</strong>
        </div>
        <div class="stat">
            <small>Estado estacionario Contingencia (π₃)</small>
            <strong data-io="markov-pi-e3">{{ number_format($est['E3_pct'], 1) }}%</strong>
        </div>
        <div class="stat">
            <small>Tiempo medio de primer paso (m₁₃)</small>
            <strong data-io="markov-m13">{{ number_format($mfpt['m13'], 2) }} meses</strong>
            <span class="trend">Meses promedio desde Normal a Contingencia</span>
        </div>
    </div>

    <div class="grid-two io-markov-grid">
        <div>
            <h3>Tabla de Predicción a 6 Meses</h3>
            <div class="table-wrap">
                <table class="io-table">
                    <thead>
                        <tr>
                            <th>Mes (n)</th>
                            <th>P(E1) Normal</th>
                            <th>P(E2) Sobredemanda</th>
                            <th>P(E3) Contingencia</th>
                            <th>VE(A3)</th>
                        </tr>
                    </thead>
                    <tbody data-io="markov-forecast-tbody">
                        @foreach($markov['prediccion_6_meses'] as $step)
                            <tr>
                                <td><strong>Mes +{{ $step['mes'] }}</strong></td>
                                <td>{{ number_format($step['p_e1_pct'], 1) }}%</td>
                                <td>{{ number_format($step['p_e2_pct'], 1) }}%</td>
                                <td><strong>{{ number_format($step['p_e3_pct'], 1) }}%</strong></td>
                                <td>{{ number_format($step['ve_a3'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h3>Evolución Gráfica de Probabilidades (n = 1...6)</h3>
            <div class="markov-chart" data-io="markov-chart">
                @foreach($markov['prediccion_6_meses'] as $step)
                    <div class="markov-bar-row">
                        <span class="markov-bar-label">Mes +{{ $step['mes'] }}</span>
                        <div class="markov-bar-track" title="E1: {{ $step['p_e1_pct'] }}% · E2: {{ $step['p_e2_pct'] }}% · E3: {{ $step['p_e3_pct'] }}%">
                            <span class="seg-e1" style="width:{{ $step['p_e1_pct'] }}%"></span>
                            <span class="seg-e2" style="width:{{ $step['p_e2_pct'] }}%"></span>
                            <span class="seg-e3" style="width:{{ $step['p_e3_pct'] }}%"></span>
                        </div>
                        <span class="markov-bar-val">VE {{ number_format($step['ve_a3'], 1) }}</span>
                    </div>
                @endforeach
            </div>
            <p class="help markov-legend">
                <span><span class="legend-dot seg-e1"></span>E1 Normal</span>
                <span><span class="legend-dot seg-e2"></span>E2 Sobredemanda</span>
                <span><span class="legend-dot seg-e3"></span>E3 Contingencia</span>
            </p>
        </div>
    </div>

    <details open>
        <summary>Registrar cierre mensual de estado observado (alimenta PostgreSQL y recalibra P)</summary>
        <form method="post" action="{{ route('io.monthly-state.store') }}">
            @csrf
            <div class="form-grid">
                <label>Mes de cierre (AAAA-MM)
                    <input type="month" name="year_month" value="{{ old('year_month', now()->format('Y-m')) }}" required>
                </label>
                <label>Estado observado de la red hospitalaria
                    <select name="state" required>
                        <option value="E1">E1 · Normal</option>
                        <option value="E2">E2 · Sobredemanda</option>
                        <option value="E3">E3 · Contingencia</option>
                    </select>
                </label>
                <label class="full">Observaciones del cierre mensual (opcional)
                    <input type="text" name="notes" maxlength="1000" value="{{ old('notes') }}" placeholder="Ej. Incremento de demanda en Pediatría y activación preventiva de brigadas">
                </label>
            </div>
            <div class="form-actions">
                <button type="submit">Guardar cierre mensual y recalibrar Markov</button>
            </div>
        </form>
    </details>

    <details>
        <summary>Matriz de transición P y historial mensual acumulado ({{ $markov['transiciones_observadas'] }} transiciones)</summary>
        <div class="grid-two">
            <div class="table-wrap">
                <table class="io-table">
                    <thead><tr><th>Matriz P (de \ a)</th><th>E1</th><th>E2</th><th>E3</th></tr></thead>
                    <tbody>
                        @foreach(['E1', 'E2', 'E3'] as $i => $rowState)
                            <tr>
                                <td><strong>{{ $rowState }}</strong></td>
                                <td>{{ number_format($markov['matriz_p'][$i][0], 3) }}</td>
                                <td>{{ number_format($markov['matriz_p'][$i][1], 3) }}</td>
                                <td>{{ number_format($markov['matriz_p'][$i][2], 3) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-wrap">
                <table class="io-table">
                    <thead><tr><th>Mes</th><th>Estado</th><th>Observaciones</th></tr></thead>
                    <tbody>
                        @forelse($io['historial_mensual'] as $item)
                            <tr>
                                <td>{{ $item['year_month'] }}</td>
                                <td><span class="badge">{{ $item['state'] }} · {{ $item['label'] }}</span></td>
                                <td class="wrap">{{ $item['notes'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty">Sin cierres mensuales registrados todavía.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </details>
</section>
