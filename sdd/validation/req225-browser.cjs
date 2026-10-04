const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const net = require('node:net');

const ROOT = path.resolve(__dirname, '../..');
const BASE = process.env.REQ225_URL || 'https://c2f-teste.local:8443/';
const OUT = path.join(__dirname, 'req225-evidence');
fs.mkdirSync(OUT, { recursive: true });
const report = { checks: [], pages: [], errors: [] };
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
    page.on('pageerror', e => report.errors.push({ url: page.url(), message: e.message }));
    page.on('console', m => { if (m.type() === 'error') report.errors.push({ url: page.url(), message: m.text() }); });
    page.on('dialog', async d => { check('no native dialog', false, d.type()); await d.dismiss(); });
    let fomantic = [];
    page.on('request', request => {
      if (request.frame() === page.mainFrame() && /(?:semantic|fomantic)(?:[.-]|\/)/i.test(request.url())) fomantic.push(new URL(request.url()).pathname);
    });
    const routes = [
      ['admin-cron/', '#admin-cron-painel'],
      ['perfil-usuario/?mudar-nome=sim', '#perfil-usuario-painel'],
      ['analytics-manager/', '[data-am-root]'],
      ['sales-reports/', 'input[name="start"]'],
      ['shipping-methods/', 'input[name="origin_cep"]'],
      ['product-reviews/', 'select[name="status"]'],
      ['product-reviews/settings/', '[data-review-settings]']
    ];
    for (const width of [1366, 390]) {
      await page.setViewportSize({ width, height: 900 });
      for (const [route, marker] of routes) {
        const startErrors = report.errors.length;
        fomantic = [];
        const response = await page.goto(new URL(route, BASE).href, { waitUntil: 'networkidle' });
        const name = route.split('/')[0] + '-' + (route.includes('settings') ? 'settings-' : '') + width;
        const found = await page.locator(marker).count();
        check(name + ': published body', response.status() === 200 && found > 0, { status: response.status(), finalPath: new URL(page.url()).pathname, found });
        if (!found) continue;
        check(name + ': controls loaded', await page.evaluate(() => !!window.c2fControles));
        check(name + ': no Fomantic asset', fomantic.length === 0, fomantic);
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
        check(name + ': no horizontal page scroll', overflow <= 0, overflow);
        check(name + ': no script/console error', report.errors.length === startErrors, report.errors.slice(startErrors));
        const field = page.locator('.c2fc-campo-entrada:visible').first();
        if (await field.count()) {
          await field.focus();
          const measure = await field.evaluate(el => {
            const label = el.closest('.c2fc-campo')?.querySelector('.c2fc-campo-rotulo');
            return { border: getComputedStyle(el).borderColor, shadow: getComputedStyle(el).boxShadow, label: label && getComputedStyle(label).color };
          });
          check(name + ': standard field focus', measure.shadow !== 'none', measure);
          if (measure.label) check(name + ': highlighted field label', measure.label === 'rgb(3, 105, 161)', measure);
        }
        if (route === 'admin-cron/') {
          await page.locator('#cron-btn-new').click();
          const modal = page.locator('#cron-modal');
          check(name + ': cron modal opens', await modal.isVisible());
          await page.locator('#cron-form-nome').focus();
          check(name + ': modal field uses library', await page.locator('#cron-form-nome').evaluate(el => el.classList.contains('c2fc-campo-entrada')));
          await page.screenshot({ path: path.join(OUT, name + '-modal.png') });
          await page.keyboard.press('Escape');
          check(name + ': cron modal closes', !await modal.isVisible());
          check(name + ': focus restored', await page.locator('#cron-btn-new').evaluate(el => document.activeElement === el));
        }
        if (route === 'analytics-manager/') {
          await page.locator('[data-am-action="pipeline-new"]').click();
          check(name + ': analytics dialog opens', await page.locator('dialog[data-am-dialog="pipeline"]').isVisible());
          check(name + ': dialog fields use library', await page.locator('dialog[data-am-dialog="pipeline"] input[name="nome"]').evaluate(el => el.classList.contains('c2fc-campo-entrada')));
          await page.screenshot({ path: path.join(OUT, name + '-modal.png') });
          await page.keyboard.press('Escape');
        }
        await page.screenshot({ path: path.join(OUT, name + '.png'), fullPage: true });
        report.pages.push({ route, width, screenshot: name + '.png' });
      }
    }
    await context.close();
  } finally {
    fs.writeFileSync(path.join(OUT, 'results.json'), JSON.stringify(report, null, 2));
    if (browser) await browser.close();
    server.close();
    server.req225Sockets.forEach(socket => socket.destroy());
  }
  if (report.checks.some(x => !x.ok) || report.errors.length) process.exitCode = 1;
})().catch(error => { console.error(error.message); process.exitCode = 1; });
