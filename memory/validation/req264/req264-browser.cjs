const fs = require('fs');
const path = require('path');
const {chromium} = require('@playwright/test');
const out = __dirname;
const results = [];
function check(name, ok, detail) { results.push({name, ok: !!ok, detail}); }
(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP meusite.local 127.0.0.1']});
  try {
    for (const width of [1280, 390]) {
      const context = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width, height: 900}});
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', e => errors.push(e.message));
      const response = await page.goto('https://meusite.local/', {waitUntil: 'networkidle'});
      check(`${width}: HTTP 200`, response.status() === 200);
      const data = await page.evaluate(() => ({
        title: document.querySelector('.c2f-welcome h1')?.textContent,
        template: document.querySelector('.c2f-welcome-note')?.textContent,
        login: document.querySelector('.c2f-welcome-primary')?.getAttribute('href'),
        dashboard: document.querySelector('.c2f-welcome-secondary')?.getAttribute('href'),
        overflow: document.documentElement.scrollWidth - innerWidth,
        background: getComputedStyle(document.body).backgroundColor,
        unresolved: document.body.innerText.includes('@[['),
      }));
      check(`${width}: template rendered`, /Seu site está no ar/.test(data.title || '') && /template/.test(data.template || ''), data.title);
      check(`${width}: login and dashboard routes`, data.login === '/signin/' && data.dashboard === '/dashboard/', {login: data.login, dashboard: data.dashboard});
      check(`${width}: no unresolved variables`, !data.unresolved);
      check(`${width}: no horizontal overflow`, data.overflow <= 0, data.overflow);
      check(`${width}: navy background`, data.background === 'rgb(10, 20, 40)', data.background);
      check(`${width}: supplied logo loaded`, await page.locator('.c2f-welcome-brand img').evaluate(img => img.complete && img.naturalWidth > 0 && img.getAttribute('src').includes('logo-principal.png')));
      check(`${width}: no browser errors`, errors.length === 0, errors);
      await page.screenshot({path: path.join(out, `homepage-${width}.png`), fullPage: true});
      for (const route of ['signin/', 'dashboard/']) {
        const r = await page.goto('https://meusite.local/' + route, {waitUntil: 'domcontentloaded'});
        check(`${width}: ${route} responds`, r.status() === 200, {status: r.status(), path: new URL(page.url()).pathname});
      }
      await context.close();
    }
  } finally { await browser.close(); }
  fs.writeFileSync(path.join(out, 'browser-results.json'), JSON.stringify(results, null, 2) + '\n');
  console.log(`${results.filter(r => r.ok).length}/${results.length} checks passed.`);
  for (const result of results.filter(r => !r.ok)) console.log(result);
  process.exitCode = results.some(r => !r.ok) ? 1 : 0;
})().catch(e => { console.error(e.message); process.exitCode = 1; });
