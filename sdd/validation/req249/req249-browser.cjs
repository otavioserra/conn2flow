// REQ-249 (core) e REQ-111 (site) — tudo que navega dentro de um widget do Dashboard sai para a página de fora,
// e a aba de widgets pode vir antes da de módulos. Usa os widgets que o administrador do Lab tiver no layout.
// Uso: C2F_PLAYWRIGHT=<pasta do playwright> C2F_COOKIES=<cookies do auth:cookie> node req249-browser.cjs
const fs = require('node:fs'), path = require('node:path');
const {chromium} = require(process.env.C2F_PLAYWRIGHT || 'playwright');
const base = process.env.C2F_BASE || 'https://conn2flow.local';
const saida = path.join(__dirname, 'evidencias');
fs.mkdirSync(saida, {recursive: true});
const jar = fs.readFileSync(process.env.C2F_COOKIES, 'utf8').split(/\r?\n/).filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
  const p = l.replace(/^#HttpOnly_/, '').split('\t');
  return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
});
let falhas = 0, total = 0;
const conferir = (nome, ok, extra) => { total++; if (!ok) falhas++; console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok || extra === undefined ? '' : ' ' + JSON.stringify(extra).slice(0, 700))); };

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 900}});
  await ctx.addCookies(jar);
  const page = await ctx.newPage();
  const foto = nome => page.screenshot({path: path.join(saida, nome + '.jpg'), type: 'jpeg', quality: 60});
  const abrir = async () => { await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'}); await page.waitForTimeout(1500); if (await page.evaluate(() => document.getElementById('dashboard-tab-widgets').classList.contains('hidden'))) await page.click('#dashboard-tab-btn-widgets'); await page.waitForTimeout(3500); };
  const gravar = (chave, valor) => page.evaluate(async ([c, v]) => { const p = new URLSearchParams({opcao: 'inicio', ajax: 'sim', ajaxOpcao: 'salvar-preferencias', chave: c, valor: v}); return (await (await fetch(gestor.raiz + 'dashboard/', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p})).json()).status; }, [chave, valor]);
  // Marca dentro do widget o primeiro elemento que casa com o filtro e devolve o que ele é.
  const quadroDe = async widget => { const el = await page.$('.dashboard-widget-card[data-widget-id="' + widget + '"] iframe'); return el ? {el, quadro: await el.contentFrame()} : null; };
  // Clica num elemento do widget e devolve onde a página de fora foi parar e quantos quadros navegaram por dentro.
  const clicar = async (widget, marcar) => {
    await abrir();
    const q = await quadroDe(widget);
    if (!q) return null;
    const alvo = await q.quadro.evaluate(marcar);
    if (!alvo) return {alvo: null};
    await q.el.scrollIntoViewIfNeeded();
    let dentro = 0;
    const escuta = f => { if (f !== page.mainFrame() && !/^about:/.test(f.url())) dentro++; };
    page.on('framenavigated', escuta);
    await Promise.all([page.waitForNavigation({timeout: 20000}).catch(() => null), q.quadro.click('[data-roteiro]')]);
    await page.waitForTimeout(700);
    page.off('framenavigated', escuta);
    return {alvo, fora: new URL(page.url()).pathname + new URL(page.url()).search, dentro};
  };
  const foiParaFora = (r, esperado) => r && r.alvo && r.dentro === 0 && r.fora !== '/dashboard/' && (esperado ? esperado(r) : new URL(r.alvo.href, base).pathname === r.fora.split('?')[0]);

  await abrir();
  const inicial = await page.evaluate(() => ({primeiro: gestor.dashboard_user_prefs.widgets.primeiro, aba: gestor.dashboard_user_prefs.aba_ativa, widgets: [...document.querySelectorAll('.dashboard-widget-card')].map(c => c.dataset.widgetId)}));
  try {
    // ----- Link com `target="_self"` (widget de menus)
    if (inicial.widgets.includes('menus')) {
      const r = await clicar('menus', () => { const a = [...document.querySelectorAll('a[href]')].find(x => x.getAttribute('target') === '_self' && x.getAttribute('href').charAt(0) === '/' && x.offsetParent !== null); if (!a) return null; a.setAttribute('data-roteiro', '1'); return {href: a.getAttribute('href'), target: a.getAttribute('target')}; });
      conferir('menus: link com target="_self" abre na página de fora', foiParaFora(r) && r.alvo.target === '_self', r);
      await foto('1-menus-fora');
    } else conferir('layout sem widget de menus: caso não exercitado', true);

    // ----- Link sem destino próprio (índice de páginas)
    if (inicial.widgets.includes('pages-index')) {
      const r = await clicar('pages-index', () => { const a = [...document.querySelectorAll('a[href]')].find(x => x.getAttribute('href').charAt(0) === '/' && x.offsetParent !== null); if (!a) return null; a.setAttribute('data-roteiro', '1'); return {href: a.getAttribute('href'), target: a.getAttribute('target')}; });
      conferir('índice de páginas: link abre na página de fora', foiParaFora(r), r);
    } else conferir('layout sem índice de páginas: caso não exercitado', true);

    // ----- Formulário (busca)
    // O formulário de teste do Lab tem campos obrigatórios que o navegador barraria antes do envio; a validação
    // nativa é desligada só para exercitar para onde o envio vai.
    if (inicial.widgets.includes('forms-search')) {
      const r = await clicar('forms-search', () => { const f = document.querySelector('form[action]'), b = f && f.querySelector('[type=submit]'); if (!b) return null; const campo = f.querySelector('input[type=search], input[type=text], input:not([type])'); if (campo) campo.value = 'teste'; const invalidos = f.querySelectorAll(':invalid').length; f.noValidate = true; b.setAttribute('data-roteiro', '1'); return {href: f.getAttribute('action'), invalidos}; });
      conferir('busca: enviar o formulário leva a página de fora ao resultado', foiParaFora(r), r);
    } else conferir('layout sem formulário de busca: caso não exercitado', true);

    // ----- Loja: imagem e botão de carrinho (REQ-111)
    if (inicial.widgets.includes('products-index')) {
      const img = await clicar('products-index', () => { const a = [...document.querySelectorAll('a[href^="/store/"]')].find(x => x.offsetParent !== null); if (!a) return null; a.setAttribute('data-roteiro', '1'); return {href: a.getAttribute('href')}; });
      conferir('loja: imagem do produto abre a página do produto fora', foiParaFora(img), img);
      const carrinho = await clicar('products-index', () => { const b = [...document.querySelectorAll('[data-store-add]')].find(x => x.offsetParent !== null); if (!b) return null; const cartao = b.closest('article, li, tr, [data-product-id]'), a = cartao && cartao.querySelector('a[href]'); b.setAttribute('data-roteiro', '1'); return {href: a ? a.getAttribute('href') : '/cart/', produto: b.getAttribute('data-store-add')}; });
      conferir('loja: adicionar ao carrinho leva a página de fora à página do produto', foiParaFora(carrinho), carrinho);
      await foto('2-loja-carrinho-fora');
    } else conferir('layout sem widget da loja: caso não exercitado', true);

    // ----- Âncora interna fica dentro
    await abrir();
    const primeiro = await quadroDe(inicial.widgets[0]);
    const ancora = await primeiro.quadro.evaluate(() => { const p = document.createElement('p'); p.id = 'roteiro-alvo'; p.textContent = 'alvo'; document.body.appendChild(p); const a = document.createElement('a'); a.href = '#roteiro-alvo'; a.textContent = 'âncora'; a.setAttribute('data-roteiro', '1'); a.style.cssText = 'position:fixed;top:4px;left:4px;z-index:99999;background:#fff;padding:4px'; document.body.appendChild(a); return true; });
    await primeiro.el.scrollIntoViewIfNeeded();
    await primeiro.quadro.click('[data-roteiro]'); await page.waitForTimeout(700);
    conferir('âncora interna não tira a página do Dashboard', ancora && new URL(page.url()).pathname === '/dashboard/' && await primeiro.quadro.evaluate(() => !!document.getElementById('roteiro-alvo')));

    // ----- Aba de widgets em primeiro
    const ordem = () => page.evaluate(() => [...document.querySelectorAll('#dashboard-tab-btn-modulos, #dashboard-tab-btn-widgets')].map(b => b.id.replace('dashboard-tab-btn-', '')));
    await gravar('dashboard_widgets_primeiro', '0'); await abrir();
    conferir('ordem padrão: módulos antes de widgets', (await ordem()).join() === 'modulos,widgets');
    await page.evaluate(() => { document.getElementById('dashboard-options').open = true; });
    await page.click('#dashboard-widgets-first'); await page.waitForTimeout(700);
    const depois = await page.evaluate(() => ({marcado: document.getElementById('dashboard-widgets-first').getAttribute('aria-checked'), painel: !document.getElementById('dashboard-tab-widgets').classList.contains('hidden')}));
    conferir('menu: widgets antes dos módulos troca a ordem das abas e abre a de widgets', (await ordem()).join() === 'widgets,modulos' && depois.marcado === 'true' && depois.painel, {ordem: await ordem(), depois});
    await foto('3-widgets-em-primeiro');
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'}); await page.waitForTimeout(1500);
    conferir('a ordem volta do servidor depois de recarregar', (await ordem()).join() === 'widgets,modulos' && await page.evaluate(() => gestor.dashboard_user_prefs.widgets.primeiro) === true, await ordem());
    await page.evaluate(() => { document.getElementById('dashboard-options').open = true; });
    await page.click('#dashboard-widgets-first'); await page.waitForTimeout(700);
    conferir('desligar devolve os módulos à frente', (await ordem()).join() === 'modulos,widgets');
  } finally {
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
    await gravar('dashboard_widgets_primeiro', inicial.primeiro ? '1' : '0');
    await gravar('dashboard_aba_ativa', inicial.aba || 'dashboard-tab-modulos');
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
    conferir('ordem e aba do administrador devolvidas ao estado da carga', await page.evaluate(() => gestor.dashboard_user_prefs.widgets.primeiro) === !!inicial.primeiro);
  }
  await browser.close();
  console.log('\n' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
