// REQ-250 — fase 1 da lousa na linha 3.1: objetos livres, esconder por largura, imagem de fundo e Google Fonts.
// Roda na instalação da 3.1; guarda layout e modo do usuário na carga e os devolve no fim.
// Uso: C2F_BASE=https://v3.1-conn2flow.local C2F_PLAYWRIGHT=<pasta> C2F_COOKIES=<cookies do auth:cookie> node req250-browser.cjs
const fs = require('node:fs'), path = require('node:path');
const {chromium} = require(process.env.C2F_PLAYWRIGHT || 'playwright');
const base = process.env.C2F_BASE || 'https://v3.1-conn2flow.local';
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
  const erros = [];
  page.on('pageerror', e => erros.push(e.message.slice(0, 160)));
  const foto = nome => page.screenshot({path: path.join(saida, nome + '.jpg'), type: 'jpeg', quality: 60});
  const abrir = async () => { await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'}); await page.waitForTimeout(1200); if (await page.evaluate(() => document.getElementById('dashboard-tab-widgets').classList.contains('hidden'))) await page.click('#dashboard-tab-btn-widgets'); await page.waitForTimeout(2500); };
  const gravar = (chave, valor) => page.evaluate(async ([c, v]) => { const p = new URLSearchParams({opcao: 'inicio', ajax: 'sim', ajaxOpcao: 'salvar-preferencias', chave: c, valor: v}); return (await (await fetch(gestor.raiz + 'dashboard/', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p})).json()).status; }, [chave, valor]);
  const menu = aberto => page.evaluate(a => { document.getElementById('dashboard-options').open = a; }, aberto);
  const item = async seletor => { await menu(true); await page.click(seletor); await page.waitForTimeout(500); await menu(false); };
  const edicao = async ligar => { if (await page.evaluate(() => document.getElementById('dashboard-widgets-grid').classList.contains('is-editing')) !== ligar) await item('#dashboard-edit-mode'); };
  // Preenche campos do pop-up (os selects do painel leem `value` e avisam por `change`).
  const preencher = campos => page.evaluate(c => { for (const [seletor, valor] of Object.entries(c)) { const el = document.querySelector('#dashboard-widget-config-modal ' + seletor); if (el.type === 'checkbox') el.checked = !!valor; else el.value = String(valor); el.dispatchEvent(new Event('input', {bubbles: true})); el.dispatchEvent(new Event('change', {bubbles: true})); } }, campos);
  const aplicar = async () => { await page.click('.dashboard-widget-config-save'); await page.waitForTimeout(700); };
  const config = async instancia => { await edicao(true); const b = page.locator('.dashboard-widget-card[data-widget-instance="' + instancia + '"] .dashboard-widget-config-btn'); await b.scrollIntoViewIfNeeded(); await b.click(); await page.waitForTimeout(400); };
  const O = n => '[data-object-option="' + n + '"]', W = n => '[data-widget-option="' + n + '"]';

  await abrir();
  const inicial = await page.evaluate(() => ({layout: JSON.stringify(gestor.dashboard_user_prefs.widgets_layout), modo: gestor.dashboard_user_prefs.widgets.modo}));
  const devolver = async () => { await gravar('dashboard_widgets_layout', inicial.layout); await gravar('dashboard_widgets_modo', inicial.modo || 'grade'); };
  try {
    if (inicial.modo !== 'lousa') { await item('#dashboard-widgets-mode'); await page.waitForTimeout(2500); }
    const antes = await page.evaluate(() => [...document.querySelectorAll('.dashboard-widget-card')].map(c => c.dataset.widgetInstance));
    conferir('área de widgets em modo lousa, com widgets', antes.length >= 2 && await page.evaluate(() => document.getElementById('dashboard-widgets-grid').classList.contains('is-board')), antes.length);

    // ----- Novo objeto: texto com fonte do Google
    await item('#dashboard-btn-add-object'); await page.waitForTimeout(1800);
    const novo = await page.evaluate(a => { const c = [...document.querySelectorAll('.dashboard-widget-card')].find(x => !a.includes(x.dataset.widgetInstance)); const m = document.getElementById('dashboard-widget-config-modal'); return {instancia: c && c.dataset.widgetInstance, objeto: c && c.classList.contains('is-object'), popup: !m.classList.contains('hidden'), secao: !m.querySelector('[data-object-section]').hidden, tipo: m.querySelector('[data-object-option="type"]').value, campos: [...m.querySelectorAll('[data-object-for]')].filter(r => !r.hidden).length}; }, antes);
    conferir('adicionar objeto cria a caixa e abre as configurações no tipo texto', novo.objeto && novo.popup && novo.secao && novo.tipo === 'text' && novo.campos === 6, novo);
    const OBJ = '.dashboard-widget-card[data-widget-instance="' + novo.instancia + '"]';
    await preencher({[O('text')]: 'Lousa <b>3.1</b>', [O('font')]: 'Poppins', [O('size')]: 40, [O('weight')]: 700, [O('color')]: '#be123c'});
    await foto('1-objeto-texto-popup');
    await aplicar();
    await page.waitForTimeout(1500);
    const texto = await page.evaluate(async sel => { const t = document.querySelector(sel + ' .dashboard-object-text'), s = getComputedStyle(t), l = document.getElementById('dashboard-google-fonts'); await document.fonts.ready; return {texto: t.textContent, negrito: t.querySelectorAll('b').length, tamanho: s.fontSize, cor: s.color, familia: s.fontFamily.replace(/["']/g, ''), folha: l && l.getAttribute('href'), carregada: document.fonts.check('700 40px "Poppins"'), quadros: document.querySelectorAll(sel + ' iframe').length}; }, OBJ);
    conferir('texto aparece como texto (sem virar HTML), no tamanho e na cor escolhidos, sem iframe', texto.texto === 'Lousa <b>3.1</b>' && texto.negrito === 0 && texto.tamanho === '40px' && texto.cor === 'rgb(190, 18, 60)' && texto.quadros === 0, texto);
    conferir('fonte do Google aplicada e só a família em uso pedida', /^Poppins/.test(texto.familia) && texto.folha === 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap', texto);
    conferir('a fonte chega do Google Fonts ao navegador do roteiro', texto.carregada, {carregada: texto.carregada});

    // ----- Forma
    await config(novo.instancia);
    await preencher({[O('type')]: 'shape', [O('shape')]: 'circle', [O('fill')]: '#16a34a'});
    await aplicar();
    const forma = await page.evaluate(sel => { const f = document.querySelector(sel + ' .dashboard-object-figure'); const s = f && getComputedStyle(f); return f ? {raio: s.borderTopLeftRadius, cor: s.backgroundColor} : null; }, OBJ);
    conferir('forma: círculo na cor escolhida', forma && forma.raio === '50%' && forma.cor === 'rgb(22, 163, 74)', forma);

    // ----- Ícone
    await config(novo.instancia);
    await preencher({[O('type')]: 'icon', [O('icon')]: 'rocket', [O('color')]: '#7c3aed'});
    await aplicar();
    const icone = await page.evaluate(sel => { const s = document.querySelector(sel + ' .dashboard-object-icon svg'); return s ? {classe: s.getAttribute('class') || '', cor: getComputedStyle(s).color, largura: Math.round(s.getBoundingClientRect().width)} : null; }, OBJ);
    conferir('ícone desenhado na cor escolhida', icone && /rocket/.test(icone.classe) && icone.cor === 'rgb(124, 58, 237)' && icone.largura > 20, icone);

    // ----- Imagem: seletor do gerenciador de arquivos e uma imagem do próprio painel
    await config(novo.instancia);
    await preencher({[O('type')]: 'image'});
    await page.click('[data-pick-image*="src"]'); await page.waitForTimeout(3500);
    const seletor = await page.evaluate(() => { const m = document.getElementById('dashboard-image-picker'), f = m.querySelector('iframe'); return {aberto: !m.classList.contains('hidden'), src: f.getAttribute('src')}; });
    const quadro = page.frames().find(f => /admin-arquivos\/\?paginaIframe=sim/.test(f.url()));
    const gerenciador = quadro ? await quadro.evaluate(() => ({lista: !!document.getElementById('c2f-files-list'), imagens: document.querySelectorAll('#c2f-files-list .c2f-item img.c2f-img').length})) : null;
    conferir('escolher imagem abre o gerenciador de arquivos do painel', seletor.aberto && /admin-arquivos\/\?paginaIframe=sim$/.test(seletor.src) && gerenciador && gerenciador.lista, {seletor, gerenciador});
    await foto('2-seletor-de-imagem');
    await page.click('.dashboard-image-picker-close'); await page.waitForTimeout(300);
    // A instalação nova não tem arquivo enviado: usa-se uma imagem que o próprio painel serve.
    const imagem = await page.evaluate(async () => { const candidatas = [...document.querySelectorAll('img')].map(i => { try { const u = new URL(i.src); return u.origin === location.origin ? u.pathname : ''; } catch (e) { return ''; } }).filter(p => /^\/[A-Za-z0-9_\-./%~]+\.(png|jpe?g|gif|webp|avif|svg)$/i.test(p) && !p.includes('..') && !p.includes('//')); for (const p of [...new Set(candidatas)]) { try { if ((await fetch(p)).ok) return p; } catch (e) { } } return ''; });
    conferir('há uma imagem do próprio painel para o teste', !!imagem, imagem);
    await page.evaluate(p => { document.querySelector('#dashboard-widget-config-modal [data-object-option="src"]').value = p; }, imagem);
    await preencher({[O('fit')]: 'contain', [O('alt')]: 'Imagem de teste'});
    await aplicar(); await page.waitForTimeout(1200);
    const img = await page.evaluate(sel => { const i = document.querySelector(sel + ' .dashboard-object-image img'); return i ? {src: new URL(i.src).pathname, natural: i.naturalWidth, encaixe: getComputedStyle(i).objectFit, alt: i.alt} : null; }, OBJ);
    conferir('imagem do painel aparece no objeto, com o encaixe escolhido', img && img.src === imagem && img.natural > 0 && img.encaixe === 'contain' && img.alt === 'Imagem de teste', img);

    // ----- Botão de chamada
    await config(novo.instancia);
    await preencher({[O('type')]: 'button', [O('text')]: 'Ver menus', [O('href')]: '/menus/', [O('fill')]: '#0284c7', [O('color')]: '#ffffff', [O('size')]: 20, [O('newTab')]: false});
    await aplicar();
    const botao = await page.evaluate(sel => { const a = document.querySelector(sel + ' a.dashboard-object-action'); return a ? {texto: a.textContent, href: a.getAttribute('href'), fundo: getComputedStyle(a).backgroundColor, cliqueNaEdicao: getComputedStyle(a).pointerEvents} : null; }, OBJ);
    conferir('botão de chamada com rótulo, destino e cor; não navega no modo de edição', botao && botao.texto === 'Ver menus' && botao.href === '/menus/' && botao.fundo === 'rgb(2, 132, 199)' && botao.cliqueNaEdicao === 'none', botao);
    await foto('3-objeto-botao');

    // ----- Imagem de fundo, opacidade e fonte do título num widget comum
    const widget = antes[0];
    const WID = '.dashboard-widget-card[data-widget-instance="' + widget + '"]';
    await config(widget);
    conferir('widget comum não mostra a seção de objeto', await page.evaluate(() => document.querySelector('#dashboard-widget-config-modal [data-object-section]').hidden));
    await page.evaluate(p => { document.querySelector('#dashboard-widget-config-modal [data-widget-option="bgImage"]').value = p; }, imagem);
    await preencher({[W('bgOpacity')]: 40, [W('bgFit')]: 'contain', [W('titleFont')]: 'Playfair Display'});
    await aplicar(); await page.waitForTimeout(900);
    const fundo = await page.evaluate(sel => { const c = document.querySelector(sel), b = getComputedStyle(c, '::before'), t = getComputedStyle(c.querySelector('.dashboard-widget-title')); return {imagem: b.backgroundImage, opacidade: b.opacity, tamanho: b.backgroundSize, titulo: t.fontFamily.replace(/["']/g, ''), folha: document.getElementById('dashboard-google-fonts').getAttribute('href'), quadro: !!c.querySelector('iframe')}; }, WID);
    conferir('imagem de fundo com opacidade e preenchimento escolhidos, sem recarregar o widget', fundo.imagem.includes(imagem) && fundo.opacidade === '0.4' && fundo.tamanho === 'contain' && fundo.quadro, fundo);
    conferir('fonte do título aplicada e somada à folha de fontes', /^Playfair Display/.test(fundo.titulo) && /family=Playfair\+Display/.test(fundo.folha), fundo);
    await foto('4-fundo-e-titulo');

    // ----- Esconder por largura
    await config(novo.instancia);
    await preencher({[W('hide')]: 'lg'});
    await aplicar();
    await edicao(false); await page.waitForTimeout(500);
    const visivel = sel => page.evaluate(s => getComputedStyle(document.querySelector(s)).display !== 'none', sel);
    const topo = () => page.evaluate(() => { const g = document.getElementById('dashboard-widgets-grid'), cs = [...g.querySelectorAll('.dashboard-widget-card')].filter(c => getComputedStyle(c).display !== 'none'); return {primeiro: Math.round(Math.min(...cs.map(c => c.getBoundingClientRect().top)) - g.getBoundingClientRect().top), lateral: document.documentElement.scrollWidth <= innerWidth + 1, visiveis: cs.length}; });
    conferir('em 1366 px o objeto marcado para sumir abaixo de 1280 aparece', await visivel(OBJ));
    // Fora da edição o botão navega: clique real, depois volta ao Dashboard.
    await page.locator(OBJ + ' a.dashboard-object-action').scrollIntoViewIfNeeded();
    await Promise.all([page.waitForNavigation({timeout: 20000}).catch(() => null), page.click(OBJ + ' a.dashboard-object-action')]);
    conferir('fora da edição o botão leva ao destino', new URL(page.url()).pathname === '/menus/', page.url());
    await abrir();
    await page.setViewportSize({width: 1100, height: 900}); await page.waitForTimeout(900);
    const estreito = await topo();
    conferir('em 1100 px ele some e a lousa não deixa o espaço dele', !(await visivel(OBJ)) && estreito.primeiro <= 1 && estreito.lateral, estreito);
    await edicao(true); await page.waitForTimeout(500);
    const marcado = await page.evaluate(s => { const c = document.querySelector(s), e = getComputedStyle(c); return {display: e.display, contorno: e.outlineStyle}; }, OBJ);
    conferir('no modo de edição ele aparece, marcado com contorno tracejado', marcado.display !== 'none' && marcado.contorno === 'dashed', marcado);
    await edicao(false);
    await page.setViewportSize({width: 390, height: 800}); await page.waitForTimeout(900);
    const celular = await topo();
    conferir('em 390 px: sem rolagem lateral e o objeto continua escondido', celular.lateral && !(await visivel(OBJ)), celular);
    await foto('5-celular');
    await page.setViewportSize({width: 1366, height: 900}); await page.waitForTimeout(700);

    // ----- Persistência
    await abrir();
    const salvo = await page.evaluate(([i, w]) => { const l = gestor.dashboard_user_prefs.widgets_layout, o = l.find(x => x.instance_id === i), c = l.find(x => x.instance_id === w); return {objeto: o && [o.id, o.object.type, o.object.text, o.object.href, o.options.hide], widget: c && [c.options.bgOpacity, c.options.bgFit, c.options.titleFont, c.options.bgImage]}; }, [novo.instancia, widget]);
    conferir('objeto e opções novas voltam do servidor depois de recarregar', salvo.objeto && salvo.objeto.join() === 'objeto,button,Ver menus,/menus/,lg' && salvo.widget && salvo.widget.join() === '40,contain,Playfair Display,' + imagem, salvo);
    conferir('depois de recarregar o objeto é desenhado de novo', await page.evaluate(s => !!document.querySelector(s + ' a.dashboard-object-action'), OBJ));
    conferir('nenhum erro de script na página', erros.length === 0, erros);
  } finally {
    await page.setViewportSize({width: 1366, height: 900});
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
    await devolver();
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
    const depois = await page.evaluate(() => ({layout: JSON.stringify(gestor.dashboard_user_prefs.widgets_layout), modo: gestor.dashboard_user_prefs.widgets.modo}));
    conferir('layout e modo do usuário devolvidos ao estado da carga', depois.layout === inicial.layout && depois.modo === (inicial.modo || 'grade'));
  }
  await browser.close();
  console.log('\n' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
