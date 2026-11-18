import { test, expect } from '@playwright/test';
import fs from 'node:fs';
const accounts = JSON.parse(fs.readFileSync(process.env.SICLIN_DEMO_ACCOUNTS_FILE || new URL('../../.local/presentation-accounts.json', import.meta.url)));
const screenshotDir = new URL('../../.local/demo-screenshots/', import.meta.url);
fs.mkdirSync(screenshotDir, { recursive: true, mode: 0o700 });
async function login(page, name) {
  const account = accounts.find(a => a.email === `${name}@siclin.test`);
  await page.goto('/login');
  await page.locator('[name=email]').fill(account.email);
  await page.locator('[name=password]').fill(account.password);
  await page.getByRole('button', { name: 'Ingresar al portal' }).click();
  await expect(page).toHaveURL(/\/panel$/);
}
async function logout(page) {
  await page.locator('form[action$="/salir"]').evaluate(form => form.submit());
  await expect(page).toHaveURL(/\/login$/);
}
async function pdf(page) {
  const href = await page.getByRole('link', { name: 'Descargar PDF' }).first().getAttribute('href');
  const response = await page.request.get(href);
  expect(response.status()).toBe(200);
  expect((await response.body()).subarray(0, 5).toString()).toBe('%PDF-');
}
const today = () => new Intl.DateTimeFormat('en-CA', { timeZone: 'America/La_Paz', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
test.describe.configure({ mode: 'serial' });

test('demo ampliada: datos visibles, cálculos de escasez, documentos y alcance del interno', async ({ page }) => {
  await login(page, 'coordinador');
  for (const [resource, text] of [
    ['cohortes', 'DEMO ampliada · A · trayectoria clínica'],
    ['hospitales', 'Centro E · convenio vencido'],
    ['internos', 'Alex Pendiente Demo'],
  ]) {
    await page.goto(`/gestion/${resource}`);
    await expect(page.locator('main')).toContainText(text);
  }
  await page.goto('/rotes');
  await expect(page.locator('[data-cycle-id]').filter({ hasText: 'DEMO ampliada' })).toHaveCount(7);
  const shortage = page.locator('[data-run-cycle-id]').filter({ hasText: 'Cupos insuficientes' });
  await expect(shortage.locator('.stat').filter({ hasText: 'Asignados' }).locator('strong')).toHaveText('2');
  await expect(shortage.locator('.stat').filter({ hasText: 'Pendientes' }).locator('strong')).toHaveText('2');
  await page.goto('/escenarios');
  const study = page.locator('section.card').filter({ has: page.getByRole('heading', { name: 'DEMO ampliada · Escenarios: Cupos insuficientes · Salud Pública', exact: true }) });
  await study.getByText('Resultados calculados y sensibilidad de cupos', { exact: true }).click();
  const metrics = study.locator('details').filter({ hasText: 'Resultados calculados y sensibilidad de cupos' }).locator('tbody tr');
  await expect(metrics).toHaveCount(9);
  await expect(metrics.first().locator('td').nth(2)).toHaveText('2');
  await expect(metrics.first().locator('td').nth(3)).toHaveText('2');
  await page.goto('/reportes');
  const act = page.locator('#documentos tr').filter({ hasText: 'DEMO ampliada · Seguimiento activo' });
  await expect(act).toHaveCount(1);
  const actUrl = await act.getByRole('link', { name: 'Descargar PDF' }).getAttribute('href');
  await pdf(page);
  await logout(page);

  await login(page, 'interno.demo01');
  await page.goto('/seguimiento');
  await expect(page.locator('main')).toContainText('Sofía Ampliación Demo');
  await expect(page.locator('main')).not.toContainText('Gabriel Ampliación Demo');
  const history = page.locator('section.card').filter({ has: page.getByRole('heading', { name: 'Sofía Ampliación Demo', exact: true }) }).filter({ hasText: 'Cirugía' });
  await history.getByText('Historial de seguimiento (3)', { exact: true }).click();
  await expect(history.getByRole('cell', { name: 'Presente', exact: true }).first()).toBeVisible();
  await page.goto('/reportes');
  await pdf(page);
  expect((await page.request.get(actUrl)).status()).toBe(403);
  await page.goto('/ayuda');
  await expect(page.getByRole('heading', { name: 'Casos de la demo ampliada' })).toBeVisible();
});

test('cuatro roles: inicio, guía, destinos del menú y vista móvil sin desbordamiento', async ({ browser }) => {
  for (const name of ['admin', 'coordinador', 'tutor', 'interno']) {
    const context = await browser.newContext({ viewport: { width: 1360, height: 900 } });
    const page = await context.newPage();
    await login(page, name);
    await page.screenshot({ path: new URL(`${name}.png`, screenshotDir).pathname, fullPage: true });
    const links = await page.locator('#navigation a').evaluateAll(els => [...new Set(els.map(a => a.href))]);
    for (const url of links) {
      const response = await page.goto(url);
      expect(response.status(), url).toBe(200);
      expect(await page.locator('#navigation details[open]').count()).toBeLessThanOrEqual(1);
      await page.setViewportSize({ width: 320, height: 900 });
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), url).toBe(true);
      await page.setViewportSize({ width: 1360, height: 900 });
    }
    await page.goto('/ayuda');
    await expect(page.getByRole('heading', { name: 'Tu recorrido de demostración' })).toBeVisible();
    await context.close();
  }
});

test('demo completa: simulación real, publicación, planificación de guardia y seguimiento entre roles', async ({ page }) => {
  test.setTimeout(120000);
  page.on('dialog', dialog => dialog.accept());
  await login(page, 'interno');
  await page.goto('/rotes');
  const cycle = page.locator('section.card').filter({ has: page.getByRole('heading', { name: 'Convocatoria de demostración · Medicina Interna' }) });
  const cycleId = await cycle.getAttribute('data-cycle-id');
  const startDay = (await cycle.locator('.card-head .help').textContent()).match(/\d{4}-\d{2}-\d{2}/)[0];
  await cycle.getByText('Registrar mis preferencias', { exact: true }).click();
  const application = cycle.locator('form[action$="/postular"]');
  await application.locator('select').nth(0).selectOption({ label: 'Hospital de Enseñanza A (ficticio)' });
  await application.locator('select').nth(1).selectOption({ label: 'Hospital de Enseñanza B (ficticio)' });
  await application.getByRole('button', { name: 'Confirmar postulación' }).click();
  await expect(page.getByRole('status')).toContainText('Postulación registrada');
  await logout(page);

  await login(page, 'coordinador');
  await page.goto('/rotes');
  await cycle.getByRole('button', { name: 'Cerrar postulaciones' }).click();
  await page.goto('/escenarios');
  const simulation = page.locator('form[action$="/escenarios/simular"]');
  await simulation.locator('[name=cycle_id]').selectOption({ label: 'Convocatoria de demostración · Medicina Interna' });
  await simulation.locator('[name=name]').fill('Demo calculada en navegador');
  await simulation.getByRole('button', { name: 'Simular nueve combinaciones' }).click();
  const study = page.locator('section.card').filter({ has: page.getByRole('heading', { name: 'Demo calculada en navegador', exact: true }) });
  await expect(study.getByRole('button', { name: 'Aprobar política' })).toBeVisible({ timeout: 30000 });
  await study.getByText('Resultados calculados y sensibilidad de cupos', { exact: true }).click();
  const metrics = study.locator('details').filter({ hasText: 'Resultados calculados y sensibilidad de cupos' }).locator('tbody tr');
  await expect(metrics).toHaveCount(9);
  await expect(metrics.nth(0)).toContainText('100 %');
  await expect(metrics.nth(2).locator('td').nth(2)).toHaveText('0');
  await expect(metrics.nth(2).locator('td').nth(3)).toHaveText('3');
  await study.locator('[name=chosen]').selectOption('A1');
  await study.locator('[name=justification]').fill('Priorizamos las preferencias y el mérito en esta convocatoria de demostración.');
  await study.getByRole('button', { name: 'Aprobar política' }).click();
  await page.goto('/rotes');
  const generate = cycle.locator('form[action$="/optimizar"]');
  await generate.locator('select').selectOption({ label: 'Demo calculada en navegador · A1' });
  await generate.getByRole('button', { name: 'Calcular propuesta' }).click();
  const run = page.locator(`[data-run-cycle-id="${cycleId}"]`).first();
  await expect(run.getByRole('button', { name: 'Pasar a revisión' })).toBeVisible({ timeout: 30000 });
  await run.getByRole('button', { name: 'Pasar a revisión' }).click();
  await run.getByRole('button', { name: 'Aprobar plan', exact: true }).click();
  await run.getByRole('button', { name: 'Publicar asignaciones' }).click();
  await expect(page.getByRole('status')).toContainText('Asignaciones publicadas');
  await page.goto('/reportes');
  await page.locator('form').filter({ hasText: 'Convocatoria de demostración · Medicina Interna' }).getByRole('button', { name: 'Generar acta PDF' }).click();
  await pdf(page);
  await logout(page);

  await login(page, 'tutor');
  await page.goto('/guardias');
  const availability = page.locator('form[action$="/disponibilidad"]');
  await availability.locator('[name=starts_at]').fill(`${startDay}T08:00`);
  await availability.locator('[name=ends_at]').fill(`${startDay}T20:00`);
  await availability.getByRole('button', { name: 'Guardar disponibilidad' }).click();
  await expect(page.getByRole('alert')).toHaveCount(0);
  await logout(page);
  await login(page, 'coordinador');
  await page.goto('/guardias');
  const shifts = page.locator('form[action$="/guardias/generar"]');
  await shifts.locator('[name=name]').fill('Guardia de Ana · demo navegador');
  const ana = await shifts.locator('option').filter({ hasText: 'Ana Demo' }).getAttribute('value');
  await shifts.locator('select').selectOption(ana);
  await shifts.locator('[name="slots[0][starts_at]"]').fill(`${startDay}T10:00`);
  await shifts.locator('[name="slots[0][ends_at]"]').fill(`${startDay}T16:00`);
  await shifts.getByRole('button', { name: 'Generar propuesta' }).click();
  const batch = page.getByRole('row').filter({ hasText: 'Guardia de Ana · demo navegador' }).filter({ has: page.getByRole('button', { name: 'Aprobar', exact: true }) });
  await expect(batch.getByRole('button', { name: 'Aprobar', exact: true })).toBeVisible({ timeout: 30000 });
  await batch.getByRole('button', { name: 'Aprobar', exact: true }).click();
  await expect(page.getByRole('status')).toContainText('aprobado');
  await logout(page);

  await login(page, 'interno');
  await page.goto('/guardias');
  await expect(page.locator('main')).toContainText('Guardia de Ana · demo navegador');
  await expect(page.locator('main')).toContainText('Dra. Lucía Demo');
  await logout(page);
  await login(page, 'tutor');
  await page.goto('/seguimiento');
  const camila = page.locator('section.card').filter({ has: page.getByRole('heading', { name: 'Camila Clínica Demo', exact: true }) });
  await camila.getByText('Registrar asistencia y evaluación', { exact: true }).click();
  const record = camila.locator('form[action$="/registro"]');
  await record.locator('[name=date]').fill(today());
  await record.locator('[data-rubric-toggle]').check();
  for (const [key, value] of Object.entries({ conocimiento: 80, procedimientos: 60, comunicacion: 100, profesionalismo: 80 })) {
    await record.locator(`[name="competencies[${key}]"]`).fill(String(value));
  }
  await record.getByRole('button', { name: 'Guardar seguimiento' }).click();
  await expect(page.getByRole('status')).toContainText('Seguimiento registrado');
  await page.getByRole('row').filter({ hasText: 'Camila Clínica Demo' }).getByRole('button', { name: 'Confirmar realizada' }).click();
  await expect(page.getByRole('status')).toContainText('Cumplimiento de guardia registrado');
  await logout(page);

  await login(page, 'interno.clinico');
  await page.goto('/seguimiento');
  await expect(page.locator('main')).not.toContainText('Ana Demo');
  await page.getByText('Historial de seguimiento (1)', { exact: true }).click();
  await expect(page.getByRole('cell', { name: '80', exact: true })).toBeVisible();
  await page.getByText('Reportar incidencia o solicitar ajuste', { exact: true }).click();
  const incident = page.locator('form[action$="/incidencias"]');
  await incident.locator('[name=type]').selectOption('correccion');
  await incident.locator('[name=description]').fill('Solicito revisar la observación registrada durante la demostración.');
  await incident.getByRole('button', { name: 'Registrar incidencia' }).click();
  await expect(page.getByRole('status')).toContainText('Incidencia enviada');
  await page.goto('/reportes');
  await page.getByRole('button', { name: 'Generar libreta PDF' }).click();
  await pdf(page);
  await logout(page);
  await login(page, 'coordinador');
  await page.goto('/seguimiento');
  const resolution = camila.locator('form[action$="/resolver"]');
  await resolution.locator('[name=resolution]').fill('Se revisó la observación; se conserva el registro original de demostración.');
  await resolution.getByRole('button', { name: 'Registrar resolución' }).click();
  await expect(page.getByRole('status')).toContainText('Resolución registrada');
  await logout(page);
  await login(page, 'interno.clinico');
  await page.goto('/seguimiento');
  await expect(page.locator('main')).toContainText('Se revisó la observación');
});
