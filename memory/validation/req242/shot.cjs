// REQ-242 — captura com passos: abre uma rota autenticada do Lab local, executa passos e fotografa.
// Uso: node sdd/validation/req242/shot.cjs <rota> <saida.jpg> [passo ...] [--largura=1366] [--inteira] [--eval=<arquivo.js>]
// Passos: click:<seletor> | wait:<ms> | hover:<seletor> | first:<seletor> (clica o primeiro) | goto-first:<seletor de link>
//         | scroll:<seletor> | key:<tecla> | frame-click:<seletor do iframe>|<seletor interno>
// Playwright: C2F_PLAYWRIGHT aponta para a pasta do pacote quando ele não está no node_modules local.
const {chromium} = require(process.env.C2F_PLAYWRIGHT || '../../../node_modules/playwright');
const fs = require('node:fs'), path = require('node:path');
const core = path.resolve(__dirname, '../../..');
const base = process.env.C2F_BASE || 'https://conn2flow.local';
const args = process.argv.slice(2);
const opcao = nome => (args.find(a => a.startsWith('--' + nome + '=')) || '').split('=').slice(1).join('=');
const livres = args.filter(a => !a.startsWith('--'));
const [rota, saida, ...passos] = livres;
const largura = parseInt(opcao('largura') || '1366', 10);
const jar = fs.readFileSync(opcao('jar') ? path.resolve(opcao('jar')) : path.join(core, 'temp/agent-cookies.txt'), 'utf8').split(/\r?\n/)
  .filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
    const p = l.replace(/^#HttpOnly_/, '').split('\t');
    return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
  });

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: largura, height: parseInt(opcao('altura') || '900', 10)}});
  await ctx.addCookies(jar);
  const page = await ctx.newPage();
  const erros = [];
  page.on('pageerror', e => erros.push('js: ' + e.message.slice(0, 200)));
  page.on('response', r => { if (r.status() >= 400 && r.request().frame() === page.mainFrame()) erros.push('http ' + r.status() + ' ' + r.url().replace(base, '').slice(0, 120)); });
  const resposta = await page.goto(base + '/' + rota, {waitUntil: 'networkidle', timeout: 60000});
  await page.waitForTimeout(500);
  if (largura < 800) { const f = page.locator('[data-admin-fechar]'); if (await f.count() && await f.first().isVisible().catch(() => false)) { await f.first().click().catch(() => {}); await page.waitForTimeout(250); } }
  console.log('### ' + rota + ' -> ' + resposta.status());
  for (const passo of passos) {
    const i = passo.indexOf(':'), tipo = passo.slice(0, i), alvo = passo.slice(i + 1);
    try {
      if (tipo === 'click') await page.click(alvo, {timeout: 8000});
      else if (tipo === 'first') await page.locator(alvo).first().click({timeout: 8000});
      else if (tipo === 'hover') await page.locator(alvo).first().hover({timeout: 8000});
      else if (tipo === 'wait') await page.waitForTimeout(parseInt(alvo, 10));
      else if (tipo === 'key') await page.keyboard.press(alvo);
      else if (tipo === 'scroll') await page.locator(alvo).first().scrollIntoViewIfNeeded({timeout: 8000});
      else if (tipo === 'goto-first') { const href = await page.locator(alvo).first().getAttribute('href'); await page.goto(new URL(href, page.url()).href, {waitUntil: 'networkidle', timeout: 60000}); console.log('   -> ' + page.url().replace(base, '')); }
      else if (tipo === 'frame-click') { const [fr, dentro] = alvo.split('|'); await page.frameLocator(fr).locator(dentro).first().click({timeout: 8000}); }
      else console.log('   passo desconhecido: ' + passo);
    } catch (e) { console.log('   FALHOU ' + passo + ': ' + e.message.split('\n')[0].slice(0, 140)); }
  }
  await page.waitForTimeout(350);
  if (opcao('eval')) console.log('   eval: ' + JSON.stringify(await page.evaluate(fs.readFileSync(opcao('eval'), 'utf8')).catch(e => 'ERRO ' + e.message.slice(0, 200)), null, 1).slice(0, 4000));
  for (const e of erros) console.log('   ' + e);
  if (saida && saida !== '-') await page.screenshot({path: saida, type: 'jpeg', quality: 65, fullPage: args.includes('--inteira')});
  await browser.close();
})();
