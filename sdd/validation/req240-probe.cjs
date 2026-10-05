// REQ-240 — sonda pontual: respostas com falha e medições livres numa rota autenticada do Lab local.
// Uso: node sdd/validation/req240-probe.cjs <rota> [<rota> ...] [--eval=<arquivo.js com expressão>] [--largura=390]
const {chromium} = require('../../node_modules/playwright');
const fs = require('node:fs'), path = require('node:path');
const core = path.resolve(__dirname, '../..');
const base = 'https://conn2flow.local';
const opcao = nome => (process.argv.find(a => a.startsWith('--' + nome + '=')) || '').split('=').slice(1).join('=');
const rotas = process.argv.slice(2).filter(a => !a.startsWith('--'));
const expressao = opcao('eval') ? fs.readFileSync(opcao('eval'), 'utf8') : '';
const largura = parseInt(opcao('largura') || '1366', 10);
const captura = opcao('shot');

const jar = fs.readFileSync(path.join(core, 'temp/agent-cookies.txt'), 'utf8').split(/\r?\n/)
  .filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
    const p = l.replace(/^#HttpOnly_/, '').split('\t');
    return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
  });

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: largura, height: 900}});
  await ctx.addCookies(jar);
  const page = await ctx.newPage();
  for (const rota of rotas) {
    const falhas = [], erros = [];
    const ouvir = r => { if (r.status() >= 400) falhas.push(r.status() + ' ' + r.request().method() + ' ' + r.url().replace(base, '').slice(0, 160)); };
    const erro = e => erros.push(e.message.slice(0, 200));
    page.on('response', ouvir); page.on('pageerror', erro);
    const resposta = await page.goto(base + '/' + rota, {waitUntil: 'networkidle', timeout: 45000}).catch(e => ({status: () => 'ERRO ' + e.message.slice(0, 80)}));
    await page.waitForTimeout(400);
    if (largura < 800) { const f = page.locator('[data-admin-fechar]'); if (await f.count() && await f.first().isVisible().catch(() => false)) { await f.first().click().catch(() => {}); await page.waitForTimeout(250); } }
    console.log('### ' + rota + ' -> ' + resposta.status());
    for (const f of falhas) console.log('   rede: ' + f);
    for (const e of erros) console.log('   js: ' + e);
    if (expressao) console.log('   eval: ' + JSON.stringify(await page.evaluate(expressao).catch(e => 'ERRO ' + e.message.slice(0, 160)), null, 1).slice(0, 3500));
    if (captura) await page.screenshot({path: captura, type: 'jpeg', quality: 60, fullPage: true});
    page.off('response', ouvir); page.off('pageerror', erro);
  }
  await browser.close();
})();
