/** Repetível: somente project-test (tenant isolado, local:true). Cookies nunca entram na evidência. */
const fs = require('node:fs');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const origin = 'https://c2f-teste.local:8443/';
const results = [], screenshots = [], errors = [];
const out = 'sdd/validation/assets/req220';
fs.mkdirSync(out, { recursive: true });
function record(name, detail = {}) { results.push({ name, ok: true, ...detail }); }
async function main() {
 const browser = await chromium.launch({ headless: true, args: ['--host-resolver-rules=MAP c2f-teste.local 127.0.0.1'] });
 const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1280, height: 900 } });
 const cookies = fs.readFileSync('temp/req220-cookies.txt', 'utf8').split(/\r?\n/).filter(l => l && !l.startsWith('#')).map(l => {
   const [domain,,path,,expiry,name,value] = l.split('\t');
   return { domain, path, name, value, expires: Number(expiry), secure: true, httpOnly: true };
 });
 await context.addCookies(cookies);
 const page = await context.newPage();
 page.on('pageerror', e => errors.push(e.message));
 page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text().replace(/_csrf_token=[^&\s]+/g, '_csrf_token=[oculto]')); });
 const created = [];
 let primaryError, baseline, previousTests = 0;
 async function goto(path) {
   const r = await page.goto(new URL(path,origin).href, { waitUntil:'networkidle' });
   assert.equal(r.status(), 200, path);
 }
 async function ready() { await page.locator('[data-c2f-listar][aria-busy="false"]').waitFor(); }
 async function search(term) {
   const response = page.waitForResponse(r => r.request().method() === 'POST' && r.url().includes('/modulos-grupos/'));
   await page.locator('[data-lista-busca]').fill(term);
   await page.locator('[data-lista-busca]').press('Enter');
   await response; await ready();
 }
 async function inspect(name, tailwind = true) {
   // O shell administra uma transição de largura ao mudar o breakpoint.
   await page.waitForFunction(() => document.documentElement.scrollWidth <= innerWidth);
   const metrics = await page.evaluate(() => ({
     overflow: document.documentElement.scrollWidth - innerWidth,
     assets: Array.from(document.querySelectorAll('script[src],link[rel="stylesheet"]')).map(e => e.src || e.href),
     raw: /\[\[|\]\]@|#[a-z][a-z-]*#/.test(document.querySelector('main')?.innerText || ''),
     width: innerWidth,
   }));
   assert(metrics.overflow <= 0, `${name}: overflow ${metrics.overflow}`);
   if (tailwind) assert(!metrics.assets.some(a => /semantic|fomantic|datatables/i.test(a)), `${name}: Fomantic asset`);
   assert(!metrics.raw, `${name}: unresolved marker`);
   const shot = `${out}/${name}.png`; await page.screenshot({ path:shot, fullPage:true }); screenshots.push(shot);
   record(name, { width: metrics.width, overflow:metrics.overflow, assets: metrics.assets.map(a => new URL(a).pathname) });
 }
 try {
   await goto('modulos-grupos/'); await ready();
   baseline = await page.evaluate(() => document.querySelector('[data-c2f-listar]').c2fLista.estado.total);
   record('dados-antes', { activeGroups: baseline });
   await search('Req220 Teste');
   const leftovers = await page.locator('[data-lista-acao="editar"]').evaluateAll(links => links.map(e => new URL(e.href).searchParams.get('id')));
   created.push(...leftovers);
   previousTests = leftovers.length;
   await search('');
   await inspect('lista-desktop');
   await page.setViewportSize({ width:390,height:844 }); await page.reload({waitUntil:'networkidle'}); await ready(); await inspect('lista-mobile');
   await search('req220-inexistente'); assert.equal(await page.locator('[data-lista-linhas] tr').count(), 0); record('busca-vazia');
   await page.setViewportSize({ width:1280,height:900 });
   // Registros de teste suficientes para exercitar paginação real de dez linhas.
   for (let i=0;i<Math.max(1,11-baseline);i++) {
     await goto('modulos-grupos/adicionar/');
     await page.locator('input[name="nome"]').fill(`Req220 Teste ${i}`);
     await page.locator('input[name="host"]').check();
     await page.locator('input[name="menu_label"]').fill(`Req220 ${i}`);
     await page.locator('input[name="ordemMenu"]').fill(String(i+1));
     await Promise.all([page.waitForURL(/editar/),page.locator('#_gestor-interface-insert-button').click()]);
     created.push(new URL(page.url()).searchParams.get('id'));
   }
   record('adicionar', { created: created.length });
   await page.setViewportSize({ width:390,height:844 }); await page.reload({waitUntil:'networkidle'}); await inspect('editar-mobile');
   await page.locator('input[name="menu_label"]').fill('Req220 Alterado');
   await Promise.all([page.waitForResponse(r => r.request().method()==='POST' && r.url().includes('/editar/')),page.locator('#_gestor-interface-edit-button').click()]);
   await page.waitForLoadState('domcontentloaded');
   assert.equal(await page.locator('input[name="menu_label"]').inputValue(),'Req220 Alterado'); record('editar');
   await goto(`modulos-grupos/clonar/?id=${encodeURIComponent(created.at(-1))}`);
   await inspect('clonar-mobile');
   await page.locator('input[name="nome"]').fill('Req220 Teste Clone');
   await Promise.all([page.waitForURL(/editar/),page.locator('#_gestor-interface-insert-button').click()]);
   created.push(new URL(page.url()).searchParams.get('id')); record('clonar');
   await goto('modulos-grupos/'); await ready();
   let response = page.waitForResponse(r => r.request().method()==='POST' && r.url().includes('/modulos-grupos/'));
   await page.locator('[data-lista-quantidade]').selectOption('10'); await response; await ready();
   assert.equal(await page.locator('[data-lista-linhas] tr').count(),10);
   response = page.waitForResponse(r => r.request().method()==='POST' && r.url().includes('/modulos-grupos/'));
   await page.locator('[data-lista-proxima]').click(); await response; await ready();
   assert.equal(await page.evaluate(() => document.querySelector('[data-c2f-listar]').c2fLista.estado.inicio),10); record('paginacao');
   await search('Req220'); assert((await page.locator('[data-lista-linhas] tr').count()) > 0); record('busca');
   response = page.waitForResponse(r => r.request().method()==='POST' && r.url().includes('/modulos-grupos/'));
   await page.locator('[data-lista-colunas] th').nth(1).locator('button').click(); await response; await ready();
   assert.equal(await page.locator('[data-lista-colunas] th').nth(1).getAttribute('aria-sort'),'descending'); record('ordenacao');
   await inspect('lista-mobile-acoes');
   await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('[data-lista-acao="desativar"]').first().click()]);
   await ready(); await search('Req220');
   assert((await page.locator('[data-lista-acao="ativar"]').count()) > 0); record('desativar');
   await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('[data-lista-acao="ativar"]').first().click()]); await ready(); record('ativar');
   await search('Req220'); await page.locator('[data-lista-acao="excluir"]').first().click();
   await page.locator('[data-c2fc-ok]').waitFor();
   assert(await page.locator('[data-c2fc-ok]').evaluate(e => e.classList.contains('c2fc-botao-perigo')));
   await inspect('confirmacao-mobile');
   await page.locator('[data-c2fc-cancelar]').click(); assert.equal(await page.locator('[data-c2fc-ok]').count(),0); record('cancelar-exclusao');
   await page.locator('[data-lista-acao="excluir"]').first().click();
   await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('[data-c2fc-ok]').click()]); await ready(); record('excluir');
   // Fomantic preservado nas duas listagens exigidas pelo aceite.
   await page.setViewportSize({width:1280,height:900});
   for(const path of ['admin-paginas/','admin-layouts/']) {
     await goto(path); await page.locator('#_gestor-interface-lista-tabela').waitFor();
     const dt = await page.evaluate(() => !!window.jQuery?.fn.dataTable?.isDataTable('#_gestor-interface-lista-tabela'));
     assert(dt,`${path}: DataTables ausente`); record(`legado-${path.replace('/','')}`);
   }
 } catch (error) {
   primaryError = error;
   await page.screenshot({path:`${out}/failure.png`,fullPage:true});
   const fields = await page.locator('form input:not([type="hidden"])').evaluateAll(inputs => inputs.map(e => ({name:e.name,valid:e.validity.valid,message:e.validationMessage})));
   console.log(JSON.stringify({stage:results.at(-1)?.name,fields}));
   throw error;
 } finally {
  try {
   // Remove apenas os IDs que este roteiro criou (soft-delete, sem tocar grupos do tenant).
   await goto('modulos-grupos/'); await ready();
   const token = await page.evaluate(() => window.gestor.csrfToken || document.querySelector('meta[name="csrf-token"]').content);
   for(const id of created) {
     const r = await page.goto(new URL(`modulos-grupos/?opcao=excluir&id=${encodeURIComponent(id)}&_csrf_token=${encodeURIComponent(token)}`,origin).href,{waitUntil:'networkidle'});
     assert.equal(r.status(),200,'cleanup');
   }
   await goto('modulos-grupos/'); await ready();
   const after = await page.evaluate(() => document.querySelector('[data-c2f-listar]').c2fLista.estado.total);
   if (!primaryError) assert.equal(after, baseline - previousTests, 'grupos reais preservados');
   record('dados-depois', { activeGroups: after, removedPreviousTests: previousTests });
  } catch (cleanupError) { if (!primaryError) throw cleanupError; }
  finally { await browser.close(); }
 }
 assert.equal(errors.length,0,errors.join('\n'));
 fs.writeFileSync('sdd/validation/req220-browser-results.json',JSON.stringify({ results, screenshots, errors },null,2)+'\n');
 console.log(JSON.stringify({passed:results.length,errors:errors.length,screenshots}));
}
main().catch(e => {
 const failure = e.message.split('\n')[0].replace(/_csrf_token=[^&\s]+/g,'_csrf_token=[oculto]');
 fs.writeFileSync('sdd/validation/req220-browser-results.json',JSON.stringify({results,screenshots,errors,failure},null,2)+'\n');
 console.error(failure); process.exitCode=1;
});
