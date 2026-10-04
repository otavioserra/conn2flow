const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const {chromium} = require('playwright');
const core = path.resolve(__dirname, '../..');
const records = JSON.parse(execFileSync('php', [path.join(__dirname, 'req227-render-covers.php')], {encoding: 'utf8'}));
const artifacts = path.join(__dirname, 'req227-evidence');
fs.mkdirSync(artifacts, {recursive: true});
// Validation fixture only: uses the real PHP SVG output and real installed WebP files.
const html = `<!doctype html><meta charset="utf-8"><title>REQ-227 / REQ-102</title>
<style>body{margin:0;background:#071633;color:#e7edff;font:14px system-ui;padding:24px}h1{font-size:24px}main{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:16px}article{background:#10244a;border-radius:16px;overflow:hidden}img{width:100%;aspect-ratio:1;display:block}p{padding:0 12px;overflow-wrap:anywhere}.slot{width:80px;height:80px;margin:12px}.slot svg{width:100%;height:100%}</style>
<h1>Conn2Flow · 37 capas</h1><main>${records.map(r => `<article data-id="${r.id}"><img src="${r.url}" alt="${r.id}"><p>${r.id}</p><div class="slot">${r.svg}</div></article>`).join('')}</main>`;
const server = http.createServer((req, res) => {
  const url = new URL(req.url, 'http://localhost');
  if (url.pathname === '/') {res.setHeader('Content-Type', 'text/html; charset=utf-8');res.end(html);return;}
  const match = url.pathname.match(/^\/(core|site)\/assets\/modulos\/covers\/([a-z0-9-]+)\.webp$/);
  if (!match) {res.writeHead(404);res.end();return;}
  const repo = match[1] === 'core' ? core : path.join(core, '../conn2flow-site');
  const file = path.join(repo, 'gestor/assets/modulos/covers', match[2] + '.webp');
  if (!fs.existsSync(file)) {res.writeHead(404);res.end();return;}
  res.setHeader('Content-Type', 'image/webp');res.end(fs.readFileSync(file));
});
(async () => {
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  let browser;
  try {
    browser = await chromium.launch({headless: true});
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('requestfailed', req => errors.push(req.url()));
    const results = [];
    for (const width of [1440, 390]) {
      await page.setViewportSize({width, height: 900});
      await page.goto(`http://127.0.0.1:${server.address().port}/`, {waitUntil: 'networkidle'});
      await page.evaluate(() => Promise.all([...document.images].map(img => img.decode())));
      const result = await page.evaluate(() => ({
        count: document.images.length,
        decoded: [...document.images].every(img => img.naturalWidth === 1024 && img.naturalHeight === 1024),
        slots: [...document.querySelectorAll('.slot svg')].every(svg => svg.getBoundingClientRect().width === 80 && svg.getBoundingClientRect().height === 80),
        overflow: document.documentElement.scrollWidth > innerWidth,
      }));
      assert.equal(result.count, 37);assert.ok(result.decoded);assert.ok(result.slots);assert.ok(!result.overflow);
      await page.screenshot({path: path.join(artifacts, `catalog-${width}.png`), fullPage: width === 1440});
      results.push({width, ...result});
    }
    assert.deepEqual(errors, []);
    const evidence = {scope: 'isolated local fixture; real assets and PHP renderer, not authenticated dashboard or A-Frame GPU scene', results, errors};
    fs.writeFileSync(path.join(artifacts, 'browser.json'), JSON.stringify(evidence, null, 2) + '\n');
    console.log(JSON.stringify(evidence, null, 2));
  } finally {if (browser) await browser.close();await new Promise(resolve => server.close(resolve));}
})().catch(error => {console.error(error);process.exitCode = 1;});
