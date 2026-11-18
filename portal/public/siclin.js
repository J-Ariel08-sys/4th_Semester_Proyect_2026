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
