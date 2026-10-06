// REQ-246 / REQ-110: conteúdo novo servido pelo SQL, links e layout desktop/mobile.
// Somente no Lab local. Não imprime cookies e não altera conteúdo ou preferências.
const fs = require('node:fs'), path = require('node:path');
const {chromium} = require('../../../node_modules/playwright');
const base = 'https://conn2flow.local';
const output = path.join(__dirname, 'docs-browser');
fs.mkdirSync(output, {recursive: true});
const targets = [
  ['reference/libraries/atualizacoes-automatica', 'ultima_automatica'],
  ['reference/libraries/controles', 'observarSelects'],
  ['reference/libraries/admin-topbar', 'admin_topbar'],
  ['reference/libraries/interface-listar-tailwind', 'interface_listar'],
  ['reference/modules/dashboard', '960'],
  ['reference/modules/admin-atualizacoes', 'atualizacao-automatica.json'],
  ['reference/modules/publisher-pages', 'textarea'],
  ['guides/site-administration', 'discount_value'],
  ['concepts/admin-interface', 'c2fc-campo']
];
(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});
  const context = await browser.newContext({ignoreHTTPSErrors: true});
  const checks = [], links = new Set();
  for (const lang of ['pt-br', 'en']) {
    for (const width of [1280, 390]) {
      const page = await context.newPage();
      await page.setViewportSize({width, height: 900});
      for (const [route, needle] of targets) {
        const url = base + '/' + (lang === 'en' ? 'en/' : '') + 'docs/' + route + '/';
        const errors = [];
        const onError = e => errors.push(e.message.slice(0, 180));
        const onConsole = m => { if (m.type() === 'error') errors.push(m.text().slice(0, 180)); };
        page.on('pageerror', onError); page.on('console', onConsole);
        const response = await page.goto(url, {waitUntil: 'networkidle', timeout: 60000});
        const data = await page.evaluate(needle => {
          const main = document.querySelector('main') || document.body;
          return {
            contains: main.innerText.includes(needle),
            overflow: document.documentElement.scrollWidth - innerWidth,
            headings: [...main.querySelectorAll('h1,h2')].map(e => e.textContent),
            links: [...main.querySelectorAll('a[href]')].map(a => a.href).filter(h => h.includes('/docs/'))
          };
        }, needle);
        data.links.forEach(h => links.add(h.split('#')[0])); delete data.links;
        const ok = response.status() === 200 && data.contains && data.overflow <= 1 && errors.length === 0;
        checks.push({url, width, status: response.status(), needle, ...data, errors, ok});
        console.log((ok ? 'OK ' : 'FAIL ') + lang + ' ' + width + ' ' + route);
        if (route === 'guides/site-administration' || route === 'reference/modules/dashboard')
          await page.screenshot({path: path.join(output, lang + '-' + width + '-' + route.split('/').pop() + '.png')});
        page.off('pageerror', onError); page.off('console', onConsole);
      }
      await page.close();
    }
  }
  const linkChecks = [];
  for (const url of links) {
    if (new URL(url).origin !== base) continue;
    const response = await context.request.get(url);
    linkChecks.push({url, status: response.status(), ok: response.ok()});
  }
  const report = {checks, linkChecks, passed: checks.filter(c => c.ok).length, total: checks.length, brokenLinks: linkChecks.filter(c => !c.ok)};
  fs.writeFileSync(path.join(output, 'result.json'), JSON.stringify(report, null, 2) + '\n');
  await browser.close();
  console.log(report.passed + '/' + report.total + ', ' + linkChecks.length + ' links, ' + report.brokenLinks.length + ' broken');
  process.exit(checks.some(c => !c.ok) || report.brokenLinks.length ? 1 : 0);
})().catch(e => { console.error(e.message); process.exit(2); });
