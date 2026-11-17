import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
const composeFile = new URL('../../docker-compose.e2e.yml',import.meta.url).pathname;
const mailWorker = action => execFileSync('docker',['compose','-p','siclin-e2e','-f',composeFile,action,'queue-mail'],{stdio:'ignore'});

const accounts = () => JSON.parse(fs.readFileSync(new URL('../../.local/e2e-accounts.json', import.meta.url)));
const credentials = email => accounts().find(account => account.email === `${email}@siclin.test`);
const mailpit = process.env.MAILPIT_E2E_URL || 'http://localhost:8026';
async function login(page, account) {
  await page.goto('/login');
  await page.locator('[name=email]').fill(account.email);
  await page.locator('[name=password]').fill(account.password);
  await page.getByRole('button', { name: 'Ingresar al portal' }).click();
  await expect(page).toHaveURL(/\/panel$/);
}
async function logout(page) {
  await page.goto('/panel');
  await page.locator('form[action$="/salir"]').evaluate(form => form.submit());
  await expect(page).toHaveURL(/\/login$/);
}
async function codeFromInbox(request, email, subject, exclude = '') {
  let message;
  await expect.poll(async () => {
    const response = await request.get(`${mailpit}/api/v1/messages`);
    const data = await response.json();
    message = data.messages.find(m => m.ID !== exclude && m.Subject.includes(subject) && m.To.some(to => to.Address === email));
    return Boolean(message);
  }, { timeout: 30000, message: 'Esperar el correo realmente capturado por SMTP en Mailpit' }).toBe(true);
  const response = await request.get(`${mailpit}/api/v1/message/${message.ID}`);
  const content = await response.json();
  const match = (content.Text || content.HTML).match(/\b\d{6}\b/);
  if (!match) throw new Error('El correo recibido no contiene un código de seis dígitos');
  return { code: match[0], id: message.ID };
}

test.describe.configure({ mode: 'serial' });

test('activación y recuperación OTP por SMTP real de Mailpit, sesiones y consumo concurrente', async ({ page, browser, request }) => {
  const account = { email: `otp.${Date.now()}@siclin.test`, password: `Initial-${crypto.randomBytes(10).toString('hex')}` };
  await login(page, credentials('admin'));
  await page.goto('/gestion/usuarios');
  const create = page.locator('form[action$="/gestion/usuarios"]');
  await create.locator('[name=name]').fill('Cuenta OTP de prueba');
  await create.locator('[name=email]').fill(account.email);
  await create.locator('[name=role]').selectOption('interno');
  await create.getByRole('button', { name: 'Guardar registro' }).click();
  await expect(page.getByRole('status')).toContainText('guardado');
  await logout(page);
  await page.goto('/recuperar');
  await page.locator('[name=email]').fill(account.email);
  await page.locator('[name=purpose]').selectOption('activation');
  await page.getByRole('button', { name: 'Enviar código' }).click();
  const activation = await codeFromInbox(request, account.email, 'activación');
  await page.locator('[name=code]').fill(activation.code);
  await page.getByRole('button', { name: 'Verificar código' }).click();
  await page.locator('[name=password]').fill(account.password);
  await page.locator('[name=password_confirmation]').fill(account.password);
  await page.getByRole('button', { name: 'Guardar contraseña' }).click();
  await expect(page).toHaveURL(/\/login$/);
  await login(page, account);
  const oldContext = await browser.newContext();
  const oldPage = await oldContext.newPage();
  await login(oldPage, account);
  await logout(page);
  mailWorker('stop');
  await page.getByRole('link', { name: 'Olvidé mi contraseña' }).click();
  await page.locator('[name=email]').fill(account.email);
  await page.getByRole('button', { name: 'Enviar código' }).click();
  await expect(page.getByRole('status')).toContainText('Si la cuenta está habilitada');
  const waitingInbox=await (await request.get(`${mailpit}/api/v1/messages`)).json();
  expect(waitingInbox.messages.filter(m=>m.Subject.includes('recuperación') && m.To.some(to=>to.Address===account.email))).toHaveLength(0);
  mailWorker('start');
  const recovery = await codeFromInbox(request, account.email, 'recuperación', activation.id);
  await page.locator('[name=code]').fill(recovery.code);
  await page.getByRole('button', { name: 'Verificar código' }).click();
  await expect(page).toHaveURL(/\/restablecer$/);
  const replacement = `Replacement-${crypto.randomBytes(12).toString('hex')}`;
  const csrf = await page.locator('form [name=_token]').inputValue();
  const form = { _token: csrf, password: replacement, password_confirmation: replacement };
  const responses = await Promise.all([page.request.post('/restablecer', { form, maxRedirects: 0 }), page.request.post('/restablecer', { form, maxRedirects: 0 })]);
  expect(responses.filter(r => r.headers().location?.endsWith('/login'))).toHaveLength(1);
  await oldPage.goto('/panel');
  await expect(oldPage).toHaveURL(/\/login$/);
  await page.goto('/login');
  await page.locator('[name=email]').fill(account.email);
  await page.locator('[name=password]').fill(account.password);
  await page.getByRole('button', { name: 'Ingresar al portal' }).click();
  await expect(page.getByRole('alert')).toContainText('Credenciales inválidas');
  await login(page, { ...account, password: replacement });
  await oldContext.close();
});

test('menús por rol y navegación responsive a 320, 390, 768 y 1280 px', async ({ browser }) => {
  for (const [user, menu] of [['admin','Usuarios y permisos'],['coordinador','Gestión académica y hospitalaria'],['interno','Mi perfil académico'],['tutor','Mis internos']]) {
    const context = await browser.newContext();
    const page = await context.newPage();
    await login(page, credentials(user));
    for (const width of [320,390,768,1280]) {
      await page.setViewportSize({ width, height: 900 });
      if (width <= 768) {
        await page.getByRole('button',{ name:'Menú de navegación' }).click();
        await expect(page.locator('#navigation')).toBeVisible();
        await expect(page.getByText(menu,{exact:true}).first()).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(page.locator('#navigation')).toBeHidden();
      } else await expect(page.getByText(menu,{exact:true}).first()).toBeVisible();
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
    }
    if (user === 'admin') expect((await page.goto('/seguimiento')).status()).toBe(403);
    if (user === 'interno') expect((await page.goto('/gestion/usuarios')).status()).toBe(403);
    await context.close();
  }
});

test('postulación, cálculo PHP en cola, revisión, publicación y documentos con permisos', async ({ page, browser }) => {
  page.on('dialog', dialog => dialog.accept());
  for (const name of ['interno','interno2','interno3']) {
    await login(page, credentials(name));
    await page.goto('/rotes');
    const application = page.locator('form[action$="/postular"]').first();
    await application.locator('xpath=..').locator('summary').click();
    const choices = await application.locator('select').first().locator('option[value]:not([value=""])').evaluateAll(nodes => nodes.map(n=>n.value));
    const order = name === 'interno2' ? choices.toReversed() : choices;
    for (let i=0;i<order.length;i++) await application.locator('select').nth(i).selectOption(order[i]);
    await application.getByRole('button',{name:'Confirmar postulación'}).click();
    await expect(page.getByRole('status')).toContainText('Postulación registrada');
    await logout(page);
  }
  await login(page,credentials('coordinador'));
  await page.goto('/escenarios');
  const policy=page.locator('form').filter({has:page.locator('select[name=chosen]')}).first();
  await policy.locator('[name=chosen]').selectOption('A1');
  await policy.locator('[name=justification]').fill('Demostración de política de preferencias con mérito explícito.');
  await policy.getByRole('button',{name:'Aprobar política'}).click();
  await page.goto('/rotes');
  await page.getByRole('button',{name:'Cerrar postulaciones'}).click();
  const generate=page.locator('form[action$="/optimizar"]').first();
  await generate.locator('select').selectOption({index:1});
  await generate.getByRole('button',{name:'Calcular propuesta'}).click();
  await expect(page.getByRole('button',{name:'Pasar a revisión'}).first()).toBeVisible({timeout:30000});
  await page.getByRole('button',{name:'Pasar a revisión'}).first().click();
  await page.getByRole('button',{name:'Aprobar plan',exact:true}).first().click();
  await page.getByRole('button',{name:'Publicar asignaciones'}).first().click();
  await expect(page.getByRole('status')).toContainText('Asignaciones publicadas');
  await page.goto('/reportes');
  await page.getByRole('button',{name:'Generar acta PDF'}).last().click();
  await expect(page.getByRole('status')).toContainText('Acta PDF generada');
  const downloadUrl=await page.getByRole('link',{name:'Descargar PDF'}).first().getAttribute('href');
  const response=await page.request.get(downloadUrl);
  expect(response.status()).toBe(200);
  expect((await response.body()).subarray(0,5).toString()).toBe('%PDF-');
  const verification=await page.getByRole('link',{name:'Verificar',exact:true}).first().getAttribute('href');
  const guest=await browser.newContext();const guestPage=await guest.newPage();
  await guestPage.goto(verification);
  await expect(guestPage.getByRole('heading',{name:'Documento registrado e íntegro'})).toBeVisible();
  await expect(guestPage.locator('body')).not.toContainText('Ana Demo');
  await guest.close();
  await logout(page);
  await login(page,credentials('interno'));
  await page.goto('/seguimiento');
  await expect(page.locator('main')).toContainText('Ana Demo');
  await expect(page.locator('main')).not.toContainText('Diego Demo');
  expect((await page.request.get(downloadUrl)).status()).toBe(403);
  const csv=await page.request.get('/reportes/csv');
  expect(await csv.text()).toContain('Ana Demo');
  expect(await csv.text()).not.toContain('Diego Demo');
});

test('tutor registra asistencia, rúbrica y guardia realizada; libreta PDF propia', async ({ page }) => {
  await login(page,credentials('tutor'));
  await page.goto('/seguimiento');
  const clinical=page.locator('section.card').filter({hasText:'Camila Clínica Demo'}).first();
  const record=clinical.locator('details').filter({has:page.locator('form[action$="/registro"]')});
  await record.locator('summary').click();
  await record.locator('[name=date]').fill(new Intl.DateTimeFormat('en-CA',{timeZone:'America/La_Paz',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date()));
  await record.locator('[name=attendance]').selectOption('presente');
  await record.locator('[data-rubric-toggle]').check();
  for(const [name,value] of Object.entries({conocimiento:80,procedimientos:60,comunicacion:100,profesionalismo:80})) await record.locator(`[name="competencies[${name}]"]`).fill(String(value));
  await record.getByRole('button',{name:'Guardar seguimiento'}).click();
  await expect(page.getByRole('status')).toContainText('Seguimiento registrado');
  await page.getByRole('button',{name:'Confirmar realizada'}).first().click();
  await expect(page.getByRole('status')).toContainText('Cumplimiento de guardia registrado');
  await page.goto('/reportes');
  await page.getByRole('button',{name:'Generar libreta PDF'}).click();
  const url=await page.getByRole('link',{name:'Descargar PDF'}).first().getAttribute('href');
  const response=await page.request.get(url);
  expect(response.status()).toBe(200);
  expect((await response.body()).subarray(0,5).toString()).toBe('%PDF-');
});
