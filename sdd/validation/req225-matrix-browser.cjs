const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const net = require('node:net');

const ROOT = path.resolve(__dirname, '../..');
const BASE = process.env.REQ225_URL || 'https://c2f-teste.local:8443/';
const OUT = path.join(__dirname, 'req225-matrix-evidence');
fs.mkdirSync(OUT, { recursive: true });
const previous = process.env.REQ225_RESUME === '1' && fs.existsSync(path.join(OUT, 'results.json'))
  ? JSON.parse(fs.readFileSync(path.join(OUT, 'results.json'), 'utf8')) : null;
const report = { checks: [], pages: previous ? previous.pages.filter(p => p.runtime === 'passed') : [], errors: [] };
function check(name, ok, data) {
  report.checks.push({ name, ok: !!ok, data });
  console.log(`${ok ? 'OK' : 'FAIL'} ${name}${ok ? '' : ' ' + JSON.stringify(data)}`);
}
function cookies() {
  return fs.readFileSync(path.join(ROOT, 'temp/agent-cookies.txt'), 'utf8').split(/\r?\n/)
    .filter(l => l && (!l.startsWith('#') || l.startsWith('#HttpOnly_')))
    .map(l => ({ httpOnly: l.startsWith('#HttpOnly_'), p: l.replace(/^#HttpOnly_/, '').split('\t') }))
    .filter(x => x.p.length >= 7).map(({ p, httpOnly }) => ({
      name: p[5], value: p[6], domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly
    }));
}
async function proxy() {
  const server = http.createServer((req, res) => { res.writeHead(502); res.end(); });
  server.req225Sockets = new Set();
  server.on('connection', socket => { server.req225Sockets.add(socket); socket.on('close', () => server.req225Sockets.delete(socket)); });
  server.on('connect', (req, client, head) => {
    const [host, port] = req.url.split(':');
    const upstream = net.connect(Number(port) || 443, host === new URL(BASE).hostname ? '127.0.0.1' : host, () => {
      client.write('HTTP/1.1 200 Connection Established\r\n\r\n');
      if (head.length) upstream.write(head);
      upstream.pipe(client); client.pipe(upstream);
    });
    server.req225Sockets.add(upstream);
    upstream.on('close', () => server.req225Sockets.delete(upstream));
    const close = () => { upstream.destroy(); client.destroy(); };
    upstream.on('error', close); client.on('error', close);
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  return server;
}
(async () => {
  const server = await proxy();
  let browser;
  try {
    browser = await chromium.launch({ channel: 'chrome', headless: true, proxy: { server: 'http://127.0.0.1:' + server.address().port } });
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    await context.addCookies(cookies());
    const page = await context.newPage();
    page.setDefaultTimeout(15000);
    let errors = [], assets = [];
    page.on('pageerror', e => errors.push(e.message));
    page.on('console', m => { if (m.type() === 'error') errors.push(m.text()); });
    page.on('dialog', async d => { errors.push('native dialog: ' + d.type()); await d.dismiss(); });
    page.on('request', r => { if (r.frame() === page.mainFrame() && /(?:semantic|fomantic)(?:[.-]|\/)/i.test(r.url())) assets.push(new URL(r.url()).pathname); });
    page.on('response', r => { if (r.status() >= 400 && r.request().frame() === page.mainFrame()) errors.push('HTTP ' + r.status() + ': ' + new URL(r.url()).pathname); });
    const rows = JSON.parse(fs.readFileSync(path.join(__dirname, 'req225-inventory.json'), 'utf8'));
    const fixtures = fs.existsSync(path.join(ROOT, 'temp/req225-fixtures.json'))
      ? JSON.parse(fs.readFileSync(path.join(ROOT, 'temp/req225-fixtures.json'), 'utf8')) : {};
    const only = process.env.REQ225_MODULES?.split(',');
    for (const row of rows.filter(r => !only || only.includes(r.module))) {
      if (report.pages.some(p => p.project === row.project && p.id === row.id && p.language === row.language && p.runtime === 'passed')) continue;
      if (row.module === 'modulos-grupos-distribuido') {
        report.pages.push({ project: row.project, id: row.id, language: row.language, route: row.path,
          runtime: 'excluded', reason: 'Piloto antigo excluído da homologação por orientação humana em 2026-10-04.', checks: [], errors: [], assets: [] });
        continue;
      }
      errors = []; assets = [];
      const lang = row.language === 'pt-br' ? 'pt-br/' : row.language + '/';
      const route = lang + row.path.replace(/^\//, '');
      const item = { project: row.project, id: row.id, language: row.language, route, checks: [], errors: [], assets: [] };
      const verify = (name, ok, data) => item.checks.push({ name, ok: !!ok, data });
      try {
        await page.setViewportSize({ width: 1366, height: 900 });
        const requiresRecord = ['editar','editar-servidor','clonar','visualizar','details','variants','page','adicionar-filho','buyer-form','executar','variaveis'].includes(row.option);
        const target = new URL(route, BASE);
        const fixture = fixtures[row.module]?.[row.language];
        if (requiresRecord && fixture) {
          target.searchParams.set(fixture.key, fixture.value);
          target.searchParams.set('id', fixture.value);
        }
        let response = await page.goto(target.href, { waitUntil: 'networkidle', timeout: 30000 });
        if (requiresRecord && new URL(page.url()).pathname !== new URL(route, BASE).pathname) {
          const listing = lang + row.path.split('/')[0] + '/';
          await page.goto(new URL(listing, BASE).href, { waitUntil: 'networkidle', timeout: 30000 });
          const action = row.path.split('/').filter(Boolean).at(-1);
          const link = page.locator('#_gestor-interface-listar a[href*="' + action + '/?"]').first();
          if (await link.count()) response = await page.goto(new URL(await link.getAttribute('href'), page.url()).href, { waitUntil: 'networkidle', timeout: 30000 });
          else { item.runtime = 'missing-record'; verify('record prerequisite', false); report.pages.push(item); console.log('PENDING ' + route + ' missing-record'); continue; }
        }
        await page.waitForTimeout(300);
        verify('requested route', response?.status() === 200 && new URL(page.url()).pathname === new URL(route, BASE).pathname, { status: response?.status(), finalPath: new URL(page.url()).pathname });
        const state = await page.evaluate(() => ({ controls: !!window.c2fControles, bridge: !!window.c2fControles?.ponteAtiva, main: !!document.querySelector('main'), language: document.documentElement.lang,
          nativeButtonTitles: Array.from(document.querySelectorAll('button[title]')).map(e => e.getAttribute('title')),
          selectMarkers: /#select-[^#]+#/.test(document.querySelector('main')?.textContent || ''),
          markup: Array.from(document.querySelectorAll('main [class]')).filter(e => /^ui(?:\s|$)/.test(e.className || '') && !e.c2fPonteSelect && !e.closest('.c2fc-select, [data-c2fc-ponte-modal], .c2fc-ponte-checkbox, .ui.modal, .ui.dimmer') && e.tagName !== 'SELECT').map(e => e.className).slice(0, 8) }));
        verify('published panel', state.controls && state.bridge && state.main, state);
        verify('no legacy visual markup', state.markup.length === 0, state.markup);
        verify('no native button titles', state.nativeButtonTitles.length === 0, state.nativeButtonTitles);
        verify('dynamic selects rendered', !state.selectMarkers);
        const listFailed = await page.evaluate(() => {
          const list = document.querySelector('[data-c2f-listar]');
          return !!list && list.querySelector('[data-lista-mensagem]')?.textContent.trim() === list.dataset.erro;
        });
        verify('list data loaded', !listFailed);
        for (const width of [1366, 390]) {
          await page.setViewportSize({ width, height: 900 }); await page.waitForTimeout(150);
          const overflow = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
          verify('no page overflow at ' + width, overflow <= 1, overflow);
        }
        verify('no Fomantic asset in main frame', assets.length === 0, assets);
        verify('no script or console error', errors.length === 0, errors);
        item.errors = [...errors]; item.assets = [...assets];
        item.runtime = item.checks.every(c => c.ok) ? 'passed' : 'failed';
        if (item.runtime === 'failed') await page.screenshot({ path: path.join(OUT, row.project + '-' + row.id + '-' + row.language + '.png') });
      } catch (error) { item.runtime = 'failed'; item.errors.push(error.message); }
      report.pages.push(item);
      fs.writeFileSync(path.join(OUT, 'results.json'), JSON.stringify(report, null, 2));
      console.log(item.runtime.toUpperCase() + ' ' + route + ' ' + item.checks.filter(c => !c.ok).map(c => c.name).join(', '));
    }
    await context.close();
  } finally {
    fs.writeFileSync(path.join(OUT, 'results.json'), JSON.stringify(report, null, 2));
    if (browser) await browser.close();
    server.close();
    server.req225Sockets.forEach(socket => socket.destroy());
  }
  if (report.pages.some(p => !['passed', 'excluded'].includes(p.runtime))) process.exitCode = 1;
})().catch(error => { console.error(error.message); process.exitCode = 1; });
