// REQ-240 / BATCH-249 — varredura dos pilares do Design System nas telas administrativas Tailwind.
// Uso: node sdd/validation/req240-browser.cjs [--shots=<pasta>] [--only=<trecho-da-rota>]
// Descobre as rotas nos manifestos dos módulos (core e site), abre cada uma autenticada no Lab local
// e mede ícones, abas, selects, alertas, tabelas e overflow em 1366 e 390 px. Só leitura.
const {chromium} = require('../../node_modules/playwright');
const fs = require('node:fs'), path = require('node:path');

const core = path.resolve(__dirname, '../..'), site = path.resolve(core, '../conn2flow-site');
const arg = nome => (process.argv.find(a => a.startsWith('--' + nome + '=')) || '').split('=')[1] || '';
const shots = arg('shots') || path.join(__dirname, 'evidence-req240');
const only = arg('only');
fs.mkdirSync(shots, {recursive: true});
const base = 'https://conn2flow.local';

const jar = fs.readFileSync(path.join(core, 'temp/agent-cookies.txt'), 'utf8').split(/\r?\n/)
  .filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
    const p = l.replace(/^#HttpOnly_/, '').split('\t');
    return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
  });

function rotas() {
  const lista = [];
  for (const [repo, raiz] of [['core', core], ['site', site]]) {
    const dir = path.join(raiz, 'gestor/modulos');
    for (const modulo of fs.readdirSync(dir)) {
      const json = path.join(dir, modulo, modulo + '.json');
      if (!fs.existsSync(json)) continue;
      let dados;
      try { dados = JSON.parse(fs.readFileSync(json, 'utf8')); } catch (e) { continue; }
      for (const p of ((dados.resources || {})['pt-br'] || {}).pages || []) {
        if (p.layout !== 'layout-administrativo-tailwind' || !p.path) continue;
        lista.push({repo, modulo, id: p.id, rota: p.path, opcao: p.option || ''});
      }
    }
  }
  return lista.filter(r => !only || r.rota.includes(only));
}

// Medições feitas dentro da página (somente leitura).
function medir() {
  const main = document.querySelector('[data-admin-main]') || document.body;
  const visivel = n => !!(n.getClientRects().length && getComputedStyle(n).visibility !== 'hidden');
  const cor = v => v.replace(/\s+/g, '');
  const transparente = v => v === 'rgba(0,0,0,0)' || v === 'transparent';
  const r = {};

  r.overflow = document.documentElement.scrollWidth - innerWidth;
  r.circulos = [...main.querySelectorAll('svg.lucide-circle')].filter(visivel)
    .map(n => (n.closest('button,a,label,div') || n).className.toString().slice(0, 80));
  r.iconesNaoDesenhados = [...main.querySelectorAll('i[data-lucide], i.icon')].filter(n => visivel(n) || n.offsetParent)
    .map(n => (n.getAttribute('data-lucide') || n.className).toString().slice(0, 60));
  r.botoesSemDica = [...main.querySelectorAll('button, a')].filter(n => visivel(n) && n.querySelector('svg') && !n.textContent.trim()
    && !n.getAttribute('data-c2f-dica') && !n.getAttribute('data-tooltip') && !n.getAttribute('title') && !n.getAttribute('aria-label'))
    .map(n => (n.id || n.className.toString()).slice(0, 70));

  r.abas = [...main.querySelectorAll('.c2fc-abas-lista, [role="tablist"]')].filter(visivel).map(barra => {
    let painel = barra.nextElementSibling;
    while (painel && !visivel(painel)) painel = painel.nextElementSibling;
    const b = barra.getBoundingClientRect(), p = painel ? painel.getBoundingClientRect() : null;
    return {classe: barra.className.toString().slice(0, 70), gap: p ? Math.round(p.top - b.bottom) : null,
      canonica: barra.classList.contains('c2fc-abas-lista'), ativa: !!barra.querySelector('[aria-selected="true"], .active')};
  });

  r.selectsForaDoPadrao = [...main.querySelectorAll('select')].filter(n => visivel(n) && !n.classList.contains('c2fc-campo-selecao') && !n.classList.contains('c2fc-nativo'))
    .map(n => (n.id || n.name || '') + ' | ' + n.className.toString().slice(0, 60));

  r.listasComMarcador = [...main.querySelectorAll('ul')].filter(n => visivel(n) && getComputedStyle(n).listStyleType !== 'none'
    && /erro|error|message|alerta|aviso/i.test((n.parentElement || n).className.toString())).length;
  r.mensagensLegadas = [...main.querySelectorAll('.message')].filter(n => visivel(n) && !n.classList.contains('c2fc-alerta') && !n.classList.contains('c2fc-nota'))
    .map(n => n.className.toString().slice(0, 70));

  r.tabelas = [...main.querySelectorAll('table')].filter(visivel).map(t => {
    const th = t.querySelector('thead th'), caixa = t.parentElement.getBoundingClientRect();
    return {id: (t.id || t.className.toString()).slice(0, 50), cabecalho: th ? cor(getComputedStyle(th).backgroundColor) : null,
      cabecalhoLinha: th ? cor(getComputedStyle(th.parentElement).backgroundColor) : null,
      cabecalhoThead: th ? cor(getComputedStyle(th.closest('thead')).backgroundColor) : null,
      vaza: Math.round(t.getBoundingClientRect().right - Math.max(caixa.right, innerWidth)) > 1,
      rola: /(auto|scroll)/.test(getComputedStyle(t.parentElement).overflowX)};
  });

  // Cartão branco: o primeiro bloco de formulário/listagem precisa ter fundo sólido próprio ou herdado de um cartão.
  // Listagem primeiro: nas telas de listagem há um <form> auxiliar fora do cartão que não é o conteúdo.
  const alvo = [...main.querySelectorAll('#_gestor-interface-listar table')].find(visivel)
    || [...main.querySelectorAll('table')].find(visivel)
    || [...main.querySelectorAll('form')].find(f => visivel(f) && [...f.querySelectorAll('input:not([type="hidden"]), select, textarea')].some(visivel));
  let fundo = null;
  for (let n = alvo; n && n !== main; n = n.parentElement) {
    const bg = cor(getComputedStyle(n).backgroundColor);
    if (!transparente(bg)) { fundo = bg; break; }
  }
  r.cartao = alvo ? (fundo || 'transparente') : 'sem-alvo';

  r.classeSemRegra = [...new Set([...main.querySelectorAll('[class*="bg-"]')].filter(n => visivel(n)
    && [...n.classList].some(c => /^bg-(white|slate|sky|red|emerald|amber|gray)-?\d*$/.test(c))
    && transparente(cor(getComputedStyle(n).backgroundColor))).map(n => [...n.classList].filter(c => c.startsWith('bg-')).join(' ')))].slice(0, 6);

  const texto = main.innerText || '';
  r.marcadores = (texto.match(/#[a-z][a-z0-9_-]{2,}#|@\[\[[^\]]+\]\]@|\[\[[a-z#_-]+\]\]/gi) || []).slice(0, 5);
  r.entradaOculta = [...main.querySelectorAll('input[type="file"]')].filter(n => n.getBoundingClientRect().width > innerWidth).length;
  return r;
}

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 900}});
  await ctx.addCookies(jar);
  const page = await ctx.newPage();
  let erros = [];
  page.on('pageerror', e => erros.push(e.message.slice(0, 200)));
  page.on('console', m => { if (m.type() === 'error') erros.push('console: ' + m.text().slice(0, 200)); });

  const fila = rotas(), resultados = [], vistos = new Set();
  const abrir = async (item, url) => {
    if (vistos.has(url)) return null;
    vistos.add(url);
    erros = [];
    await page.setViewportSize({width: 1366, height: 900});
    let resposta;
    try { resposta = await page.goto(base + '/' + url, {waitUntil: 'networkidle', timeout: 45000}); }
    catch (e) { return {...item, url, falha: e.message.slice(0, 120)}; }
    await page.waitForTimeout(350);
    const final = page.url().replace(base + '/', '');
    const reg = {...item, url, status: resposta ? resposta.status() : 0, final: final === url ? undefined : final};
    reg.modalErro = await page.evaluate(() => { const m = document.querySelector('[data-c2f-alerta-modal]:not(.hidden), .c2fc-dialogo'); return m && m.getClientRects().length ? (m.innerText || '').slice(0, 120) : null; });
    reg.desktop = await page.evaluate(medir);
    reg.errosJs = erros.slice(0, 5);
    const nome = url.replace(/[\/?=&]/g, '-').replace(/-+$/, '');
    await page.screenshot({path: path.join(shots, nome + '-desktop.jpg'), type: 'jpeg', quality: 55, fullPage: true}).catch(() => {});
    reg.editar = await page.evaluate(() => { const a = document.querySelector('[data-admin-main] a[href*="/editar/?"], [data-admin-main] a[href*="/edit/?"]'); return a ? a.getAttribute('href') : null; });
    await page.setViewportSize({width: 390, height: 844});
    await page.waitForTimeout(300);
    const fechar = page.locator('[data-admin-fechar]');
    if (await fechar.count() && await fechar.first().isVisible().catch(() => false)) { await fechar.first().click().catch(() => {}); await page.waitForTimeout(250); }
    reg.mobile = await page.evaluate(medir);
    await page.screenshot({path: path.join(shots, nome + '-mobile.jpg'), type: 'jpeg', quality: 55, fullPage: true}).catch(() => {});
    return reg;
  };

  for (const item of fila) {
    const reg = await abrir(item, item.rota);
    if (!reg) continue;
    resultados.push(reg);
    process.stdout.write('.');
    // Tela de edição: chega-se a ela pelo primeiro registro da listagem.
    if (reg.editar) {
      const url = reg.editar.replace(/^https?:\/\/[^/]+\//, '').replace(/^\//, '');
      const edicao = await abrir({...item, id: item.id + ' (editar via listagem)'}, url);
      if (edicao) { resultados.push(edicao); process.stdout.write('e'); }
    }
  }
  await browser.close();

  for (const r of resultados) delete r.editar;
  fs.writeFileSync(path.join(__dirname, 'req240-browser-results.json'), JSON.stringify({gerado: new Date().toISOString(), base, total: resultados.length, resultados}, null, 1));
  console.log('\ntelas: ' + resultados.length);
})();
