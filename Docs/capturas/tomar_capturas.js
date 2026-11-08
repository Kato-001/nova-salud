// Toma las capturas de pantalla del sistema para el informe (Docs/capturas/*.png).
// Uso:  BASE=http://127.0.0.1:8099 NODE_PATH=<carpeta con puppeteer-core> node tomar_capturas.js
// Requiere puppeteer-core y Google Chrome (ruta en CHROME). El servidor local debe
// tener un SMTP configurado para la captura de "recuperar contraseña".
const puppeteer = require('puppeteer-core');
const path = require('path');
const fs = require('fs');

const BASE = process.env.BASE || 'http://127.0.0.1:8099';
const OUT = __dirname;
const CHROME = process.env.CHROME || 'C:/Users/Sergio Estaca Paco/.cache/puppeteer/chrome/win64-131.0.6778.204/chrome-win64/chrome.exe';
const CLAVE = 'HeadSalud2026';
// FECHA: día con citas para las agendas · CITA: cita pendiente del médico · ATENDIDA: cita ya atendida
const FECHA = process.env.FECHA ? '?fecha=' + process.env.FECHA : '';
const F2 = process.env.FECHA ? '&fecha=' + process.env.FECHA : '';
const espera = (ms) => new Promise((r) => setTimeout(r, ms));

async function pagina(browser, ancho = 1440, alto = 900, escala = 1, tema = 'light') {
  const ctx = await browser.createBrowserContext();
  const p = await ctx.newPage();
  await p.setViewport({ width: ancho, height: alto, deviceScaleFactor: escala });
  await p.evaluateOnNewDocument((t) => { try { localStorage.setItem('hs-tema', t); } catch (e) {} }, tema);
  return p;
}
async function ingresar(p, usuario) {
  await p.goto(BASE + '/login', { waitUntil: 'networkidle0' });
  await p.type('input[name=login]', usuario);
  await p.type('input[name=password]', CLAVE);
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle0' }), p.click('button[type=submit]')]);
}
async function foto(p, nombre, ruta, o = {}) {
  if (ruta) await p.goto(BASE + ruta, { waitUntil: 'networkidle0' });
  await espera(o.espera || 1700);                      // deja terminar las animaciones
  await p.evaluate(() => document.querySelectorAll('.toast').forEach((t) => t.remove()));
  if (o.completa) {
    // El menú lateral es fijo: en vez de fullPage se agranda la ventana al alto de la página
    const vp = p.viewport();
    const alto = await p.evaluate(() => document.documentElement.scrollHeight);
    await p.setViewport({ ...vp, height: Math.max(vp.height, alto) });
    await espera(300);
    await p.screenshot({ path: path.join(OUT, nombre + '.png') });
    await p.setViewport(vp);
  } else {
    await p.screenshot({ path: path.join(OUT, nombre + '.png') });
  }
  console.log('✓', nombre);
}
async function clic(p, selector) { await p.evaluate((s) => document.querySelector(s).click(), selector); }
async function primerId(p, ruta, patron) {
  return p.evaluate(async (r, pat) => { const t = await (await fetch(r)).text(); const m = t.match(new RegExp(pat)); return m ? m[1] : null; }, ruta, patron);
}

(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, args: ['--lang=es-BO'] });

  // ------------------------------------------------------------ Público
  let p = await pagina(browser);
  await foto(p, '09_publico_inicio', '/');
  await p.evaluate(() => document.getElementById('especialidades').scrollIntoView());
  await foto(p, '09b_publico_especialidades', null, { espera: 900 });
  await p.evaluate(() => document.getElementById('medicos').scrollIntoView());
  await foto(p, '09c_publico_medicos', null, { espera: 900 });
  await foto(p, '01_login', '/login');
  await foto(p, '02_registro_paciente', '/registro', { completa: true });
  await p.goto(BASE + '/login', { waitUntil: 'networkidle0' });
  await p.type('input[name=login]', 'admin');
  await p.type('input[name=password]', 'clave-incorrecta1');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle0' }), p.click('button[type=submit]')]);
  await espera(600);
  await p.screenshot({ path: path.join(OUT, '03_login_error.png') }); console.log('✓ 03_login_error');
  await p.goto(BASE + '/pacientes', { waitUntil: 'networkidle0' });
  await espera(600);
  await p.screenshot({ path: path.join(OUT, '04_acceso_sin_sesion.png') }); console.log('✓ 04_acceso_sin_sesion');

  // Recuperar contraseña
  p = await pagina(browser);
  await foto(p, '06_recuperar', '/recuperar');
  await p.type('input[name=email]', 'jperez@correo.com');
  await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle0' }), p.click('button.btn-primary')]);
  if (await p.$('input[name=codigo]')) {
    await p.type('input[name=codigo]', '482913');
    await p.type('input[name=password]', 'NuevaClave2026');
    await p.type('input[name=password_confirmacion]', 'NuevaClave2026');
    await foto(p, '07_recuperar_codigo', null, { espera: 800 });
  }
  const correo = path.join(OUT, 'correo.html');
  if (fs.existsSync(correo)) {
    await p.setViewport({ width: 760, height: 640 });
    await p.setContent(fs.readFileSync(correo, 'utf8'), { waitUntil: 'load' });
    await p.screenshot({ path: path.join(OUT, '08_correo_codigo.png') }); console.log('✓ 08_correo_codigo');
  }
  p = await pagina(browser, 390, 844, 2);
  await foto(p, '09d_publico_celular', '/');

  // ------------------------------------------------------- Administrador
  p = await pagina(browser);
  await ingresar(p, 'admin');
  for (const [n, r, c] of [['10_panel_admin', '/panel', 1], ['11_usuarios', '/usuarios'], ['12_usuario_form', '/usuarios/nuevo'],
    ['13_roles_permisos', '/roles', 1], ['14_configuracion', '/configuracion'], ['15_bitacora', '/bitacora'],
    ['20_especialidades', '/especialidades'], ['21_medicos', '/medicos'], ['22_medico_form', '/medicos/nuevo', 1],
    ['23_medico_ficha', '/medicos/ver?id=1', 1], ['24_horarios', '/horarios?medico=2', 1], ['55_catalogo_estudios', '/estudios/catalogo'],
    ['60_reportes_resumen', '/reportes', 1], ['61_reportes_medico', '/reportes?tipo=medico'], ['62_reportes_fecha', '/reportes?tipo=fecha']]) {
    await foto(p, n, r, { completa: !!c });
  }
  await p.goto(BASE + '/usuarios', { waitUntil: 'networkidle0' });
  await espera(1200);
  await clic(p, 'form[action="/usuarios/estado"] button');
  await espera(700);
  await p.screenshot({ path: path.join(OUT, '16_modal_confirmacion.png') }); console.log('✓ 16_modal_confirmacion');

  // ------------------------------------------------------- Recepcionista
  p = await pagina(browser);
  await ingresar(p, 'mrojas');
  for (const [n, r, c] of [['30_pacientes', '/pacientes'], ['31_paciente_form', '/pacientes/nuevo', 1], ['32_paciente_ficha', '/pacientes/ver?id=1', 1],
    ['33_paciente_historia', '/pacientes/ver?id=1&tab=clinica', 1], ['40_citas', '/citas'], ['54_estudios_bandeja', '/estudios'],
    ['47_agenda_todos', '/agenda' + FECHA, 1], ['48_agenda_medico', '/agenda?medico=3' + F2, 1]]) {
    await foto(p, n, r, { completa: !!c });
  }
  await p.goto(BASE + '/pacientes/nuevo', { waitUntil: 'networkidle0' });
  await p.type('input[name=ci]', 'ABC');
  await p.click('button.btn-primary');
  await espera(800);
  await p.screenshot({ path: path.join(OUT, '34_validacion_cliente.png') }); console.log('✓ 34_validacion_cliente');

  await p.goto(BASE + '/citas/nueva', { waitUntil: 'networkidle0' });
  await espera(800);
  await p.type('#buscar-paciente', 'Pérez');
  await p.waitForSelector('.ac-item', { timeout: 10000 });
  await espera(400);
  await p.screenshot({ path: path.join(OUT, '41_cita_buscar_paciente.png') }); console.log('✓ 41_cita_buscar_paciente');
  await p.evaluate(() => document.querySelector('.ac-item').dispatchEvent(new MouseEvent('mousedown')));
  await p.select('select[name=idespecialidad]', '1');
  await p.waitForFunction(() => document.querySelectorAll('select[name=idmedico] option').length > 1, { timeout: 10000 });
  await p.select('select[name=idmedico]', '1');
  await p.evaluate(() => document.querySelector('select[name=idmedico]').dispatchEvent(new Event('change')));
  await p.waitForSelector('.dia:not([disabled])');
  await p.evaluate(() => { const d = [...document.querySelectorAll('.dia:not([disabled])')]; d[Math.min(2, d.length - 1)].click(); });
  await p.waitForSelector('.slot', { timeout: 10000 });
  await espera(900);
  await p.evaluate(() => { const s = [...document.querySelectorAll('.slot:not([disabled])')]; s[Math.min(3, s.length - 1)].click(); });
  await foto(p, '42_cita_programar', null, { completa: true, espera: 600 });

  const fut = await primerId(p, '/citas?estado=CONFIRMADA', 'citas/ver\\?id=(\\d+)');
  if (fut) {
    await foto(p, '43_cita_detalle', '/citas/ver?id=' + fut, { completa: true });
    await foto(p, '44_cita_reprogramar', '/citas/reprogramar?id=' + fut);
    await p.goto(BASE + '/citas/ver?id=' + fut, { waitUntil: 'networkidle0' });
    await espera(1000);
    await clic(p, 'form[action="/citas/cancelar"] button');
    await espera(700);
    await p.screenshot({ path: path.join(OUT, '45_cita_cancelar_motivo.png') }); console.log('✓ 45_cita_cancelar_motivo');
  }
  const can = await primerId(p, '/citas?estado=CANCELADA', 'citas/ver\\?id=(\\d+)');
  if (can) await foto(p, '46_cita_cancelada_historial', '/citas/ver?id=' + can, { completa: true });
  const est = await primerId(p, '/estudios?estado=SOLICITADO', 'resultado\\?id=(\\d+)');
  if (est) await foto(p, '56_estudio_resultado', '/estudios/resultado?id=' + est);
  await foto(p, '05_error_403', '/usuarios');

  // ---------------------------------------------------------- Médico
  p = await pagina(browser);
  await ingresar(p, process.env.MEDICO || 'rsuarez');
  await foto(p, '50_panel_medico', '/panel', { completa: true });
  await foto(p, '51_agenda_medico_propia', '/agenda' + FECHA, { completa: true });
  const at = process.env.CITA || await primerId(p, '/agenda' + FECHA, 'citas/ver\\?id=(\\d+)" class="agenda-item (?:PROGRAMADA|CONFIRMADA)');
  if (at) {
    await p.goto(BASE + '/atencion?cita=' + at, { waitUntil: 'networkidle0' });
    await p.type('input[name=presionarterial]', '120/80');
    await p.type('input[name=temperatura]', '36.7');
    await p.type('textarea[name=diagnostico]', 'Control odontológico sin hallazgos');
    const b = await p.$('[data-agregar=estudios]'); if (b) await b.click();
    await foto(p, '52_consulta_registro', null, { completa: true });
  }
  const atd = process.env.ATENDIDA || await primerId(p, '/agenda?fecha=' + new Date(Date.now() - 864e5 * 2).toISOString().slice(0, 10), 'citas/ver\\?id=(\\d+)" class="agenda-item ATENDIDA');
  if (atd) await foto(p, '53_cita_atendida', '/citas/ver?id=' + atd, { completa: true });

  // -------------------------------------------------------- Paciente
  p = await pagina(browser);
  await ingresar(p, 'jperez');
  await foto(p, '70_portal_inicio', '/portal', { completa: true });
  await p.goto(BASE + '/portal/reservar', { waitUntil: 'networkidle0' });
  await p.select('select[name=idespecialidad]', '4');
  await p.waitForFunction(() => document.querySelectorAll('select[name=idmedico] option').length > 1, { timeout: 10000 });
  await p.waitForSelector('.dia:not([disabled])');
  await p.evaluate(() => { const d = [...document.querySelectorAll('.dia:not([disabled])')]; d[Math.min(1, d.length - 1)].click(); });
  await p.waitForSelector('.slot', { timeout: 10000 });
  await espera(900);
  await p.evaluate(() => { const s = [...document.querySelectorAll('.slot:not([disabled])')]; s[Math.min(2, s.length - 1)].click(); });
  await foto(p, '71_portal_reservar', null, { completa: true });
  for (const [n, r, c] of [['72_portal_mis_citas', '/portal/citas?ver=todas', 1], ['73_portal_perfil', '/portal/perfil', 1],
    ['74_portal_info_medica', '/portal/perfil?seccion=medica'], ['75_portal_historial', '/portal/historial', 1]]) {
    await foto(p, n, r, { completa: !!c });
  }

  // ------------------------------------------- Modo oscuro y celular
  p = await pagina(browser, 1440, 900, 1, 'dark');
  await ingresar(p, 'admin');
  await foto(p, '80_modo_oscuro', '/panel');
  p = await pagina(browser, 390, 844, 2);
  await ingresar(p, 'mrojas');
  await foto(p, '81_movil_agenda', '/agenda' + FECHA);
  await clic(p, '[data-menu]');
  await foto(p, '82_movil_menu', null, { espera: 700 });

  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
