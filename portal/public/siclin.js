document.querySelectorAll('form[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  });
});
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
const rows = document.querySelector('#shift-rows');
function renumberShifts() {
  rows.querySelectorAll('.shift-row').forEach((row, index) => {
    row.querySelectorAll('[name]').forEach(input => { input.name = input.name.replace(/slots\[\d+\]/, `slots[${index}]`); });
  });
}
document.querySelector('#add-shift')?.addEventListener('click', () => {
  if (rows.children.length >= 100) return;
  const row = rows.firstElementChild.cloneNode(true);
  row.querySelectorAll('input,select').forEach(input => { input.value = ''; });
  rows.append(row);
  renumberShifts();
});
rows?.addEventListener('click', event => {
  if (event.target.matches('.remove-shift') && rows.children.length > 1) {
    event.target.closest('.shift-row').remove();
    renumberShifts();
  }
});

document.querySelectorAll('[data-rubric-toggle]').forEach(toggle => {
  toggle.addEventListener('change', () => {
    toggle.closest('.full').querySelectorAll('.rubric-fields input').forEach(input => {
      input.disabled = !toggle.checked;
      input.required = toggle.checked;
    });
  });
});
const navToggle = document.querySelector('.nav-toggle');
navToggle?.addEventListener('click', () => {
  const expanded = navToggle.getAttribute('aria-expanded') !== 'true';
  navToggle.setAttribute('aria-expanded', String(expanded));
  document.querySelector('#navigation').classList.toggle('open', expanded);
});
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && navToggle?.getAttribute('aria-expanded') === 'true') { navToggle.click(); navToggle.focus(); }
});
document.querySelectorAll('[data-run-status]').forEach(async element => {
  const poll = async () => {
    try {
      const response = await fetch(element.dataset.runStatus, {headers:{Accept:'application/json'}});
      if (!response.ok) throw new Error();
      const run = await response.json();
      element.textContent = `Cálculo: ${run.execution_status} · Plan: ${run.plan_status}`;
      if (['en_cola','ejecutando'].includes(run.execution_status)) setTimeout(poll, 2500);
      else if (element.dataset.initial !== run.execution_status) location.reload();
    } catch { element.textContent = 'No se pudo consultar el progreso. Recarga la página para reintentar.'; }
  };
  if (['en_cola','ejecutando'].includes(element.dataset.initial)) poll();
});

const themeButtons = document.querySelectorAll('[data-theme-toggle]');
function renderThemeButtons() {
  const dark = document.documentElement.dataset.theme === 'dark';
  themeButtons.forEach(button => {
    button.textContent = dark ? 'Modo claro' : 'Modo oscuro';
    button.setAttribute('aria-pressed', String(dark));
    button.setAttribute('aria-label', dark ? 'Activar modo claro' : 'Activar modo oscuro');
  });
}
themeButtons.forEach(button => button.addEventListener('click', () => {
  const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
  document.documentElement.dataset.theme = theme;
  try { localStorage.setItem('siclin-theme', theme); } catch {}
  renderThemeButtons();
}));
renderThemeButtons();
document.querySelectorAll('[data-password-target]').forEach(button => {
  const input = document.getElementById(button.dataset.passwordTarget);
  const label = document.querySelector(`label[for="${input.id}"]`).textContent.toLowerCase();
  button.addEventListener('click', () => {
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.textContent = visible ? 'Ocultar' : 'Mostrar';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', `${visible ? 'Ocultar' : 'Mostrar'} ${label}`);
  });
});
const topbar = document.querySelector('.topbar');
if (topbar && 'ResizeObserver' in window) {
  new ResizeObserver(() => document.documentElement.style.setProperty('--topbar-height', `${topbar.offsetHeight}px`)).observe(topbar);
}

// Investigación Operativa: actualización dinámica desde /api/dashboard-io
(function initOperationsResearch() {
  const sections = document.querySelectorAll('[data-io-module]');
  if (!sections.length) return;

  const escHtml = (value) => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmt = (n, d = 2) => (n === null || n === undefined) ? '—' : Number(n).toFixed(d);

  function renderQueueing(data) {
    const colas = data.colas;
    if (!colas) return;
    const e3 = colas.escenarios?.E3;
    const setText = (key, val) => document.querySelectorAll(`[data-io="${key}"]`).forEach(el => { el.textContent = val; });
    setText('colas-s', colas.s_tutores);
    setText('colas-hospital', colas.hospital);
    setText('colas-internos', colas.internos_habilitados);
    if (e3) {
      setText('colas-e3-wq-sin', e3.sin_internos.wq_minutes !== null ? `${fmt(e3.sin_internos.wq_minutes)} min` : 'Saturado (ρ≥100%)');
      setText('colas-e3-rho-sin', `${fmt(e3.sin_internos.rho_pct, 1)}%`);
      setText('colas-e3-wq-con', e3.con_internos.wq_minutes !== null ? `${fmt(e3.con_internos.wq_minutes)} min` : 'Saturado (ρ≥100%)');
      setText('colas-e3-rho-con', `${fmt(e3.con_internos.rho_pct, 1)}%`);
      setText('colas-e3-red', e3.reduccion_wq_pct !== null ? `−${fmt(e3.reduccion_wq_pct, 1)}%` : 'Estabiliza cola');
      setText('colas-e3-red-min', e3.reduccion_wq_min !== null ? `−${fmt(e3.reduccion_wq_min)} min de espera` : 'Evita colapso del servicio');
    }
    const tbody = document.querySelector('[data-io="colas-tbody"]');
    if (tbody && colas.escenarios) {
      const rhoCell = (m) => `<div class="rho-cell"><span>${fmt(m.rho_pct, 1)}%</span>${m.sobrecarga ? '<span class="badge io-alert">Sobrecarga</span>' : ''}<div class="progress rho-bar"><span class="${m.sobrecarga ? 'overload' : ''}" style="width:${Math.min(100, m.rho_pct)}%"></span></div></div>`;
      tbody.innerHTML = Object.values(colas.escenarios).map(esc => `<tr>
        <td><strong>${escHtml(esc.nombre)}</strong></td>
        <td>${fmt(esc.lambda)}</td>
        <td>${esc.sin_internos.wq_minutes !== null ? `${fmt(esc.sin_internos.wq_minutes)} min` : '∞ (Inestable)'}</td>
        <td>${esc.sin_internos.l !== null ? `${fmt(esc.sin_internos.l)} pac` : '∞'}</td>
        <td>${rhoCell(esc.sin_internos)}</td>
        <td><strong>${esc.con_internos.wq_minutes !== null ? `${fmt(esc.con_internos.wq_minutes)} min` : '∞ (Inestable)'}</strong></td>
        <td>${esc.con_internos.l !== null ? `${fmt(esc.con_internos.l)} pac` : '∞'}</td>
        <td>${rhoCell(esc.con_internos)}</td>
      </tr>`).join('');
    }
  }

  function renderMarkov(data) {
    const m = data.markov;
    if (!m) return;
    const setText = (key, val) => document.querySelectorAll(`[data-io="${key}"]`).forEach(el => { el.textContent = val; });
    setText('markov-estado-actual', `${m.estado_actual} (${m.estado_actual_etiqueta})`);
    setText('markov-semaforo-titulo', m.semaforo.titulo);
    setText('markov-semaforo-accion', m.semaforo.accion);
    setText('markov-prob-e3', `${fmt(m.semaforo.prob_e3_pct, 1)}%`);
    setText('markov-pi-e1', `${fmt(m.estacionaria.E1_pct, 1)}%`);
    setText('markov-pi-e2', `${fmt(m.estacionaria.E2_pct, 1)}%`);
    setText('markov-pi-e3', `${fmt(m.estacionaria.E3_pct, 1)}%`);
    setText('markov-m13', `${fmt(m.tiempo_primer_paso.m13)} meses`);

    document.querySelectorAll('[data-io="markov-semaforo"]').forEach(el => {
      el.classList.remove('semaforo-verde', 'semaforo-amarillo', 'semaforo-rojo');
      el.classList.add(m.semaforo.css_class);
    });

    const tbody = document.querySelector('[data-io="markov-forecast-tbody"]');
    if (tbody) {
      tbody.innerHTML = m.prediccion_6_meses.map(step => `<tr>
        <td><strong>Mes +${step.mes}</strong></td>
        <td>${fmt(step.p_e1_pct, 1)}%</td>
        <td>${fmt(step.p_e2_pct, 1)}%</td>
        <td><strong>${fmt(step.p_e3_pct, 1)}%</strong></td>
        <td>${fmt(step.ve_a3)}</td>
      </tr>`).join('');
    }
    const chart = document.querySelector('[data-io="markov-chart"]');
    if (chart) {
      chart.innerHTML = m.prediccion_6_meses.map(step => `<div class="markov-bar-row">
        <span class="markov-bar-label">Mes +${step.mes}</span>
        <div class="markov-bar-track" title="E1: ${step.p_e1_pct}% · E2: ${step.p_e2_pct}% · E3: ${step.p_e3_pct}%">
          <span class="seg-e1" style="width:${step.p_e1_pct}%"></span>
          <span class="seg-e2" style="width:${step.p_e2_pct}%"></span>
          <span class="seg-e3" style="width:${step.p_e3_pct}%"></span>
        </div>
        <span class="markov-bar-val">VE ${fmt(step.ve_a3, 1)}</span>
      </div>`).join('');
    }
  }

  async function refreshDashboard(hospitalId = null) {
    const baseUrl = sections[0].dataset.ioApi;
    if (!baseUrl) return;
    const url = hospitalId ? `${baseUrl}?hospital_id=${encodeURIComponent(hospitalId)}` : baseUrl;
    try {
      const response = await fetch(url, { headers: { Accept: 'application/json' } });
      if (!response.ok) return;
      const data = await response.json();
      renderQueueing(data);
      renderMarkov(data);
      if (data.decisiones?.recomendacion) {
        document.querySelectorAll('[data-io="decisiones-ve"]').forEach(el => {
          el.textContent = fmt(data.decisiones.recomendacion.valor_esperado);
        });
      }
    } catch {}
  }

  document.querySelectorAll('[data-io-hospital-select]').forEach(select => {
    select.addEventListener('change', () => refreshDashboard(select.value));
  });
  document.querySelectorAll('[data-io-refresh]').forEach(button => {
    button.addEventListener('click', () => {
      const select = document.querySelector('[data-io-hospital-select]');
      refreshDashboard(select ? select.value : null);
    });
  });
})();
