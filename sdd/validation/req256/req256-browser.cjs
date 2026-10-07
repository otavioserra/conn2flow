// REQ-256 — as miniaturas dos modelos aparecem no painel: na aba Modelos do editor de vários módulos, a imagem de
// cada modelo carrega (não é a imagem padrão) e tem a proporção esperada.
// Uso: C2F_BASE=https://conn2flow.local C2F_PLAYWRIGHT=<pasta> C2F_COOKIES=<cookies do administrador> node req256-browser.cjs
const fs = require('node:fs'), path = require('node:path');
const {chromium} = require(process.env.C2F_PLAYWRIGHT || 'playwright');
const base = process.env.C2F_BASE || 'https://conn2flow.local';
const saida = path.join(__dirname, 'evidencias');
fs.mkdirSync(saida, {recursive: true});
const ler = arquivo => fs.readFileSync(arquivo, 'utf8').split(/\r?\n/).filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
  const p = l.replace(/^#HttpOnly_/, '').split('\t');
  return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
});
let falhas = 0, total = 0;
const conferir = (nome, ok, extra) => { total++; if (!ok) falhas++; console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok || extra === undefined ? '' : ' ' + JSON.stringify(extra).slice(0, 700))); };
// Módulo com editor → quantos modelos do alvo dele o painel deve listar com miniatura.
// Layouts e componentes escolhem modelo pela aba Modelos do editor. Os módulos com seletor próprio de modelo (menus,
// índices, formulários) não mostram essa aba: para eles a conferência é no cadastro de modelos.
const TELAS = [['admin-layouts/adicionar/', 5], ['admin-componentes/adicionar/', 5]];
const MODELOS = ['menus-horizontal-navbar', 'publisher-index-grid', 'publisher-highlights-noticias-grid-cards', 'pages-index-lista', 'forms-contato-basico', 'forms-search-contato-basico', 'galleries-grid', 'cookie-consent-card',
  'subscriptions-plans-modern-dark', 'products-index-grid', 'presentations-deck', 'presentations-slide-opening'];

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 1000}});
  await ctx.addCookies(ler(process.env.C2F_COOKIES));
  const page = await ctx.newPage();
  for (const [tela, minimo] of TELAS) {
    const r = await page.goto(base + '/' + tela, {waitUntil: 'networkidle'});
    const aba = await page.$('a[data-tab="modelos"]');
    if (!aba) { conferir(tela + ': aba Modelos presente', false, r.status()); continue; }
    // Em alguns módulos o editor fica dentro de uma seção recolhida: o clique vai pelo próprio elemento.
    await page.evaluate(() => document.querySelector('a[data-tab="modelos"]').click()); await page.waitForTimeout(3500);
    const fotos = await page.evaluate(() => [...document.querySelectorAll('div[data-tab="modelos"] img')].map(i => ({src: i.getAttribute('src'), ok: i.complete && i.naturalWidth > 0, w: i.naturalWidth, h: i.naturalHeight})));
    const boas = fotos.filter(f => f.ok && /templates\/images\/[a-z-]+\/[a-z0-9-]+\.webp/.test(f.src || ''));
    const padrao = fotos.filter(f => /imagem-padrao/.test(f.src || ''));
    conferir(tela + ': ' + boas.length + ' modelos com miniatura carregada, nenhum com a imagem padrão', boas.length >= minimo && padrao.length === 0 && fotos.every(f => f.ok), {total: fotos.length, boas: boas.length, padrao: padrao.map(f => f.src), quebradas: fotos.filter(f => !f.ok).map(f => f.src)});
    conferir(tela + ': proporção das miniaturas', boas.every(f => Math.abs(f.w / f.h - 290 / 197) < 0.02), boas.map(f => [f.w, f.h]).slice(0, 4));
    if (tela.startsWith('admin-layouts')) await page.screenshot({path: path.join(saida, tela.split('/')[0] + '-modelos.jpg'), type: 'jpeg', quality: 60, fullPage: true});
  }
  for (const id of MODELOS) {
    const r = await page.goto(base + '/admin-templates/editar/?id=' + encodeURIComponent(id), {waitUntil: 'networkidle'});
    await page.waitForTimeout(800);
    const foto = await page.evaluate(i => { const im = [...document.querySelectorAll('img')].find(x => (x.getAttribute('src') || '').includes('templates/images/') && (x.getAttribute('src') || '').includes('/' + i + '.webp')); return im ? {src: im.getAttribute('src'), ok: im.complete && im.naturalWidth > 0, w: im.naturalWidth, h: im.naturalHeight} : null; }, id);
    conferir('cadastro de modelos, ' + id + ': miniatura mostrada na edição', r.status() === 200 && !!foto && foto.ok && foto.w === 580 && foto.h === 394, foto);
    if (id === 'menus-horizontal-navbar' || id === 'subscriptions-plans-modern-dark') await page.screenshot({path: path.join(saida, 'cadastro-' + id + '.jpg'), type: 'jpeg', quality: 60});
  }
  await page.goto(base + '/admin-templates/', {waitUntil: 'networkidle'}); await page.waitForTimeout(2000);
  await page.screenshot({path: path.join(saida, 'cadastro-listagem.jpg'), type: 'jpeg', quality: 60, fullPage: true});
  await browser.close();
  console.log('\n' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
