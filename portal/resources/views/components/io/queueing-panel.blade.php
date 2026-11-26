@props(['io'])
@php
    $colas = $io['colas'];
    $e3 = $colas['escenarios']['E3'];
@endphp
<section class="card io-section" id="io-colas" data-io-module="colas" data-io-api="{{ route('io.dashboard.api') }}">
    <div class="card-head">
        <div>
            <p class="eyebrow">Sprint 2 · Teoría de Colas (M/M/s)</p>
            <h2>Capacidad y Descongestión Hospitalaria</h2>
        </div>
        @if(!empty($io['hospitales_disponibles']))
            <label class="io-inline-select">Hospital / servicio
                <select data-io-hospital-select>
                    @foreach($io['hospitales_disponibles'] as $h)
                        <option value="{{ $h['id'] }}" @selected($h['id'] === $colas['hospital_id'])>{{ $h['name'] }} (s={{ $h['tutors_count'] }})</option>
                    @endforeach
                </select>
            </label>
        @endif
    </div>

    <div class="stats io-kpi-grid">
        <div class="stat">
            <small>Hospital analizado</small>
            <strong class="io-stat-sm" data-io="colas-hospital">{{ $colas['hospital'] }}</strong>
            <span class="trend">s = <span data-io="colas-s">{{ $colas['s_tutores'] }}</span> tutores · <span data-io="colas-internos">{{ $colas['internos_habilitados'] }}</span> internos habilitados</span>
        </div>
        <div class="stat">
            <small>Espera Wq en E3 · Sin Internos</small>
            <strong data-io="colas-e3-wq-sin">{{ $e3['sin_internos']['wq_minutes'] !== null ? number_format($e3['sin_internos']['wq_minutes'], 2).' min' : 'Saturado' }}</strong>
            <span class="trend">Ocupación ρ: <span data-io="colas-e3-rho-sin">{{ number_format($e3['sin_internos']['rho_pct'], 1) }}%</span></span>
        </div>
        <div class="stat">
            <small>Espera Wq en E3 · Con Internos</small>
            <strong data-io="colas-e3-wq-con">{{ $e3['con_internos']['wq_minutes'] !== null ? number_format($e3['con_internos']['wq_minutes'], 2).' min' : 'Saturado' }}</strong>
            <span class="trend">Ocupación ρ: <span data-io="colas-e3-rho-con">{{ number_format($e3['con_internos']['rho_pct'], 1) }}%</span></span>
        </div>
        <div class="stat">
            <small>Descongestión lograda en E3</small>
            <strong data-io="colas-e3-red">{{ $e3['reduccion_wq_pct'] !== null ? '−'.number_format($e3['reduccion_wq_pct'], 1).'%' : 'Estabiliza cola' }}</strong>
            <span class="trend" data-io="colas-e3-red-min">{{ $e3['reduccion_wq_min'] !== null ? '−'.number_format($e3['reduccion_wq_min'], 2).' min de espera' : 'Evita colapso del servicio' }}</span>
        </div>
    </div>

    <div class="table-wrap">
        <table class="io-table">
            <thead>
                <tr>
                    <th>Escenario</th>
                    <th>Llegadas λ (pac/h)</th>
                    <th>Wq Sin Internos</th>
                    <th>L Sin Internos</th>
                    <th>Ocupación ρ Sin Internos</th>
                    <th>Wq Con Internos</th>
                    <th>L Con Internos</th>
                    <th>Ocupación ρ Con Internos</th>
                </tr>
            </thead>
            <tbody data-io="colas-tbody">
                @foreach($colas['escenarios'] as $esc)
                    @php
                        $sin = $esc['sin_internos'];
                        $con = $esc['con_internos'];
                    @endphp
                    <tr>
                        <td><strong>{{ $esc['nombre'] }}</strong></td>
                        <td>{{ number_format($esc['lambda'], 2) }}</td>
                        <td>{{ $sin['wq_minutes'] !== null ? number_format($sin['wq_minutes'], 2).' min' : '∞ (Inestable)' }}</td>
                        <td>{{ $sin['l'] !== null ? number_format($sin['l'], 2).' pac' : '∞' }}</td>
                        <td>
                            <div class="rho-cell">
                                <div class="rho-head"><span>{{ number_format($sin['rho_pct'], 1) }}%</span>@if($sin['sobrecarga'])<span class="badge io-alert">Sobrecarga</span>@endif</div>
                                <div class="progress rho-bar"><span class="{{ $sin['sobrecarga'] ? 'overload' : '' }}" style="width:{{ min(100, $sin['rho_pct']) }}%"></span></div>
                            </div>
                        </td>
                        <td><strong>{{ $con['wq_minutes'] !== null ? number_format($con['wq_minutes'], 2).' min' : '∞ (Inestable)' }}</strong></td>
                        <td>{{ $con['l'] !== null ? number_format($con['l'], 2).' pac' : '∞' }}</td>
                        <td>
                            <div class="rho-cell">
                                <div class="rho-head"><span>{{ number_format($con['rho_pct'], 1) }}%</span>@if($con['sobrecarga'])<span class="badge io-alert">Sobrecarga</span>@endif</div>
                                <div class="progress rho-bar"><span class="{{ $con['sobrecarga'] ? 'overload' : '' }}" style="width:{{ min(100, $con['rho_pct']) }}%"></span></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
