// REQ-242 (core) e REQ-107 (site) — roteiro de navegador no Lab local: confere os 18 grupos da auditoria humana.
// Uso: node sdd/validation/req242/req242-browser.cjs [--saida=<pasta de evidências>] [--so=<trecho do nome do grupo>]
// Playwright: C2F_PLAYWRIGHT aponta para a pasta do pacote quando ele não está no node_modules local.
// Só lê e interage com telas; não salva formulário nenhum. O cookie vem de `c2f auth:cookie --project=conn2flow-site-local`.
const {chromium} = require(process.env.C2F_PLAYWRIGHT || '../../../node_modules/playwright');
const fs = require('node:fs'), path = require('node:path');
const core = path.resolve(__dirname, '../../..');
const base = process.env.C2F_BASE || 'https://conn2flow.local';
const opcao = nome => (process.argv.find(a => a.startsWith('--' + nome + '=')) || '').split('=').slice(1).join('=');
const saida = opcao('saida') ? path.resolve(opcao('saida')) : path.join(__dirname, 'evidencias');
const so = opcao('so');
fs.mkdirSync(saida, {recursive: true});
const jar = fs.readFileSync(path.join(core, 'temp/agent-cookies.txt'), 'utf8').split(/\r?\n/)
  .filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
    const p = l.replace(/^#HttpOnly_/, '').split('\t');
    return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
  });

const resultados = [];
let grupoAtual = '';
function conferir(nome, ok, detalhe) {
  resultados.push({grupo: grupoAtual, nome, ok: !!ok, detalhe: detalhe === undefined ? '' : detalhe});
  console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok ? '' : ' -> ' + String(JSON.stringify(detalhe === undefined ? '' : detalhe)).slice(0, 300)));
}

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const novo = async (largura, altura) => {
    const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: largura, height: altura || 900}});
    await ctx.addCookies(jar);
    const page = await ctx.newPage();
    page._erros = [];
    page.on('pageerror', e => page._erros.push(e.message.slice(0, 160)));
    return page;
  };
  const abrir = async (page, rota) => {
    page._erros.length = 0;
    const r = await page.goto(base + '/' + rota, {waitUntil: 'networkidle', timeout: 60000});
    await page.waitForTimeout(400);
    return r.status();
  };
  const foto = (page, nome, inteira) => page.screenshot({path: path.join(saida, nome + '.jpg'), type: 'jpeg', quality: 60, fullPage: !!inteira}).catch(() => {});
  const primeiroLink = async (page, rota, filtro) => {
    await abrir(page, rota);
    return page.evaluate(f => { const a = [...document.querySelectorAll('a[href]')].find(x => x.getAttribute('href').includes(f)); return a ? new URL(a.href).pathname.slice(1) + new URL(a.href).search : null; }, filtro);
  };
  const grupo = async (nome, fn) => {
    if (so && !nome.toLowerCase().includes(so.toLowerCase())) return;
    grupoAtual = nome; console.log('### ' + nome);
    try { await fn(); } catch (e) { conferir('roteiro sem exceção', false, e.message.split('\n')[0]); }
  };
  const page = await novo(1366, 900);

  // ---------- 1 e 2: forms-search e forms
  for (const m of ['forms-search', 'forms']) {
    await grupo(`${m === 'forms-search' ? 1 : 2}. ${m}/adicionar`, async () => {
      conferir('HTTP 200', await abrir(page, m + '/adicionar/') === 200);
      const abas = await page.evaluate(() => [...document.querySelectorAll('a.c2fc-aba[data-tab]')].map(a => ({tab: a.getAttribute('data-tab'), dica: a.getAttribute('data-c2f-dica') || '', fundo: getComputedStyle(a).backgroundColor, ativa: a.classList.contains('active')})));
      conferir('aba ativa sem fundo colorido', abas.filter(a => a.ativa).every(a => /rgba\(0, 0, 0, 0\)|transparent/.test(a.fundo)), abas.filter(a => a.ativa));
      conferir('todas as abas com dica', abas.length >= 5 && abas.every(a => a.dica.length > 5), abas.filter(a => !a.dica));
      await page.click(`a[data-tab="${m}-widget"]`); await page.waitForTimeout(300);
      const copiar = await page.evaluate(m => { const b = document.getElementById('btn-copy-' + m + '-widget-val'); const r = b.getBoundingClientRect(); const i = document.getElementById(m + '-widget-val').getBoundingClientRect(); return {h: r.height, w: r.width, ws: getComputedStyle(b).whiteSpace, mesmaLinha: Math.abs((r.top + r.height / 2) - (i.top + i.height / 2)) < 6, dica: b.getAttribute('data-c2f-dica')}; }, m);
      conferir('botão Copiar numa linha só, ao lado do campo', copiar.h <= 40 && copiar.ws === 'nowrap' && copiar.mesmaLinha && !!copiar.dica, copiar);
      await page.click(`a[data-tab="${m}-fields"]`); await page.waitForTimeout(300);
      await page.click('#btn-add-form-field'); await page.click('#btn-add-form-field'); await page.waitForTimeout(400);
      const tabela = '#' + m + '-fields-table';
      const linha = await page.evaluate(t => { const tds = [...document.querySelectorAll(t + ' tbody tr:first-child td')]; return tds.map(td => getComputedStyle(td).paddingLeft); }, tabela);
      conferir('campos da linha com respiro', linha.length >= 7 && linha.every(p => parseFloat(p) >= 6), linha);
      await page.locator(tabela + ' tbody tr').last().locator('.c2fc-select-gatilho').click(); await page.waitForTimeout(300);
      const sel = await page.evaluate(t => { const caixa = document.querySelector(t).parentElement; const p = [...document.querySelectorAll(t + ' .c2fc-select-painel')].find(x => !x.classList.contains('c2fc-oculto')); if (!p) return null; const r = p.getBoundingClientRect(); return {flutuante: p.classList.contains('c2fc-flutuante'), pos: getComputedStyle(p).position, rolagemVertical: caixa.scrollHeight - caixa.clientHeight, visivel: r.height > 60 && r.bottom <= innerHeight + 1 && r.top >= 0, opcoes: p.querySelectorAll('.c2fc-select-opcao').length}; }, tabela);
      conferir('select de tipo flutua fora do container, sem barra de rolagem', sel && sel.flutuante && sel.pos === 'fixed' && sel.rolagemVertical <= 1 && sel.visivel && sel.opcoes >= 10, sel);
      await foto(page, m + '-campos-select');
      await page.mouse.wheel(0, 120); await page.waitForTimeout(250);
      conferir('rolar a página não fecha o select', await page.evaluate(t => [...document.querySelectorAll(t + ' .c2fc-select-painel')].some(x => !x.classList.contains('c2fc-oculto')), tabela));
      await page.keyboard.press('Escape');
      conferir('sem erro de JavaScript', page._erros.length === 0, page._erros);
    });
  }

  // ---------- 3: forms-submissions
  await grupo('3. forms-submissions/view', async () => {
    const rota = await primeiroLink(page, 'forms-submissions/', 'forms-submissions/view/');
    if (!rota) { conferir('há um envio no Lab para abrir', false, 'listagem vazia'); return; }
    conferir('HTTP 200', await abrir(page, rota) === 200, rota);
    const abas = await page.evaluate(() => [...document.querySelectorAll('[data-c2f-aba]')].map(a => a.getAttribute('data-c2f-dica') || ''));
    conferir('três abas com dica', abas.length === 3 && abas.every(d => d.length > 5 && !d.includes('@[[')), abas);
    const select = await page.evaluate(() => { const s = document.getElementById('form-status-select'); return {classe: s.className, estilo: s.getAttribute('style'), borda: getComputedStyle(s).borderRadius}; });
    conferir('select de status no padrão c2fc-campo-selecao', /c2fc-campo-selecao/.test(select.classe) && !select.estilo && select.borda === '8px', select);
    await page.click('[data-c2f-aba="submission-json"]'); await page.waitForTimeout(700);
    const json = await page.evaluate(() => { const c = document.querySelector('.forms-submissions-json .CodeMirror'); if (!c) return null; const cm = c.CodeMirror; return {modo: cm.getOption('mode'), leitura: cm.getOption('readOnly'), tema: cm.getOption('theme'), linhas: cm.lineCount(), realce: c.querySelectorAll('.cm-property, .cm-string').length, valido: (() => { try { JSON.parse(cm.getValue()); return true; } catch (e) { return false; } })(), textareaVisivel: !!document.querySelector('textarea.codemirror-json') && document.querySelector('textarea.codemirror-json').offsetParent !== null, fundo: getComputedStyle(c).backgroundColor}; });
    conferir('JSON em CodeMirror somente leitura, formatado e com realce', json && json.modo === 'application/json' && json.leitura === true && json.tema === 'default' && json.linhas > 1 && json.realce > 0 && json.valido && !json.textareaVisivel, json);
    await foto(page, 'forms-submissions-json');
    conferir('sem erro de JavaScript', page._erros.length === 0, page._erros);
  });

  // ---------- 4: presentations (site)
  await grupo('4. presentations/editar', async () => {
    const rota = await primeiroLink(page, 'presentations/', 'presentations/editar/');
    if (!rota) { conferir('há uma apresentação no Lab', false); return; }
    conferir('HTTP 200', await abrir(page, rota) === 200, rota);
    await page.waitForTimeout(1800);
    const quadro = await page.evaluate(() => ({cartoes: document.querySelectorAll('.c2f-slide-card').length, visual: document.querySelectorAll('[data-slide-action="visual"]').length, opcoes: [...document.querySelectorAll('#slide-scope-select option')].map(o => o.textContent), dicas: [...document.querySelectorAll('.c2f-slide-card:first-child [data-slide-action]')].every(b => (b.getAttribute('data-c2f-dica') || '').length > 2)}));
    conferir('select de conteúdo lista os slides com nome e identificador', quadro.cartoes > 0 && quadro.opcoes.length === quadro.cartoes && quadro.opcoes.every(o => /#slide-\d+\)$/.test(o)), quadro.opcoes.slice(0, 3));
    conferir('botão "Editar visual" em cada cartão, com dica', quadro.visual === quadro.cartoes && quadro.dicas, quadro);
    const abaAntes = await page.evaluate(() => document.querySelector('.menuConteudoWidget .c2fc-aba.active').getAttribute('data-tab'));
    // o cartão é trazido à vista antes de medir: o que se confere é que fechar o diálogo não desloca a tela
    await page.locator('.c2f-slide-card').first().scrollIntoViewIfNeeded(); await page.waitForTimeout(300);
    const rolagemAntes = await page.evaluate(() => scrollY);
    await page.locator('.c2f-slide-card').first().locator('[data-slide-action="edit"]').click(); await page.waitForTimeout(500);
    conferir('texto de ajuda do quadro com ícone e texto lado a lado', await page.evaluate(() => [...document.querySelectorAll('.flex.items-start.gap-2.rounded-lg.bg-slate-50')].every(d => d.children.length === 2 && d.children[1].tagName === 'SPAN')));
    conferir('diálogo do slide abre com "Editar visual", Cancelar e Salvar', await page.evaluate(() => { const m = document.querySelector('.c2f-slide-modal'); return !!m && !!m.querySelector('.c2f-slide-modal-visual') && !!m.querySelector('.cancel') && m.querySelectorAll('.approve').length === 2; }));
    await foto(page, 'presentations-dialogo');
    await page.locator('.c2f-slide-modal .cancel').click(); await page.waitForTimeout(700);
    const depoisCancelar = await page.evaluate(() => ({aba: document.querySelector('.menuConteudoWidget .c2fc-aba.active').getAttribute('data-tab'), rolagem: scrollY, modal: !!document.querySelector('.c2f-slide-modal')}));
    conferir('Cancelar fica no quadro de slides (mesma aba, mesma rolagem)', depoisCancelar.aba === abaAntes && Math.abs(depoisCancelar.rolagem - rolagemAntes) < 80 && !depoisCancelar.modal, {abaAntes, rolagemAntes, depoisCancelar});
    await page.locator('.c2f-slide-card').first().locator('[data-slide-action="edit"]').click(); await page.waitForTimeout(500);
    await page.locator('.c2f-slide-modal .primary.approve').click(); await page.waitForTimeout(900);
    const depoisSalvar = await page.evaluate(() => ({aba: document.querySelector('.menuConteudoWidget .c2fc-aba.active').getAttribute('data-tab'), rolagem: scrollY, cartoes: document.querySelectorAll('.c2f-slide-card').length}));
    conferir('Salvar aplica e fica no quadro de slides', depoisSalvar.aba === abaAntes && Math.abs(depoisSalvar.rolagem - rolagemAntes) < 80 && depoisSalvar.cartoes === quadro.cartoes, depoisSalvar);
    await page.locator('.c2f-slide-card').first().locator('[data-slide-action="visual"]').click(); await page.waitForTimeout(900);
    conferir('"Editar visual" leva ao editor, por escolha', await page.evaluate(() => document.querySelector('.menuConteudoWidget .c2fc-aba.active').getAttribute('data-tab')) === 'hep-editor');
    conferir('sem erro de JavaScript', page._erros.length === 0, page._erros);
  });

  // ---------- 5, 6 e 7: dashboard
  await grupo('5-7. dashboard', async () => {
    conferir('HTTP 200', await abrir(page, 'dashboard/') === 200);
    await page.click('#dashboard-tab-btn-modulos'); await page.waitForTimeout(500);
    const cartoes = await page.evaluate(async () => {
      const visivel = e => !!e && getComputedStyle(e).display !== 'none' && e.getBoundingClientRect().width > 0;
      for (const img of document.querySelectorAll('img.dashboard-module-cover')) { img.loading = 'eager'; }
      await new Promise(r => setTimeout(r, 1500));
      return [...document.querySelectorAll('.dashboard-module-card')].map(c => ({id: c.dataset.moduleId, capa: visivel(c.querySelector('.dashboard-card-cover-wrapper')) && !!c.querySelector('img.dashboard-module-cover') && c.querySelector('img.dashboard-module-cover').naturalWidth > 0, icone: visivel(c.querySelector('.dashboard-card-svg'))}));
    });
    const ambos = cartoes.filter(c => c.capa && c.icone), nenhum = cartoes.filter(c => !c.capa && !c.icone);
    conferir(`cards com capa OU ícone, nunca os dois (${cartoes.length} cards, ${cartoes.filter(c => c.capa).length} com capa)`, cartoes.length > 10 && ambos.length === 0 && nenhum.length === 0, {ambos: ambos.slice(0, 5), nenhum: nenhum.slice(0, 5)});
    await foto(page, 'dashboard-modulos');
    const hover = async (id) => { await page.hover('#' + id); await page.waitForTimeout(250); const v = await page.evaluate(i => { const c = getComputedStyle(document.getElementById(i)); return {borda: c.borderBottomColor, cor: c.color, peso: c.fontWeight, largura: c.borderBottomWidth}; }, id); await page.mouse.move(700, 600); await page.waitForTimeout(200); return v; };
    const repouso = id => page.evaluate(i => { const c = getComputedStyle(document.getElementById(i)); return {borda: c.borderBottomColor, cor: c.color, peso: c.fontWeight, largura: c.borderBottomWidth}; }, id);
    const hWidgets = await hover('dashboard-tab-btn-widgets'), rWidgets = await repouso('dashboard-tab-btn-widgets'), aModulos = await repouso('dashboard-tab-btn-modulos');
    await page.click('#dashboard-tab-btn-widgets'); await page.waitForTimeout(600);
    const hModulos = await hover('dashboard-tab-btn-modulos'), rModulos = await repouso('dashboard-tab-btn-modulos'), aWidgets = await repouso('dashboard-tab-btn-widgets');
    conferir('hover, repouso e ativa iguais nas duas abas', JSON.stringify(hWidgets) === JSON.stringify(hModulos) && JSON.stringify(rWidgets) === JSON.stringify(rModulos) && JSON.stringify(aModulos) === JSON.stringify(aWidgets) && hWidgets.borda !== rWidgets.borda, {hWidgets, hModulos, rWidgets, rModulos, aModulos, aWidgets});
    await page.click('#dashboard-options summary'); await page.waitForTimeout(300);
    const menu = await page.evaluate(() => { const m = document.querySelector('.dashboard-options-menu'); const c = getComputedStyle(m); const itens = [...m.querySelectorAll('.dashboard-menu-item')]; return {raio: c.borderRadius, fonte: c.fontSize, padding: c.padding, sombra: c.boxShadow !== 'none', itens: itens.length, alinhados: itens.every(i => getComputedStyle(i).textAlign === 'left' && i.querySelector('svg')), altura: itens.map(i => Math.round(i.getBoundingClientRect().height))}; });
    conferir('menu de opções em linhas, compacto (raio 8 px, texto 12 px, ícones)', menu.raio === '8px' && menu.fonte === '12px' && menu.sombra && menu.itens === 3 && menu.alinhados && menu.altura.every(h => h <= 40), menu);
    await foto(page, 'dashboard-opcoes');
    const semWidget = await page.evaluate(() => document.querySelectorAll('.dashboard-widget-card').length === 0);
    if (semWidget) { conferir('há um widget no painel do Lab para conferir', false, 'painel de widgets vazio'); return; }
    if (!(await page.evaluate(() => document.getElementById('dashboard-widgets-grid').classList.contains('is-editing')))) await page.click('#dashboard-edit-mode');
    await page.keyboard.press('Escape'); await page.waitForTimeout(500);
    const quadro = await page.evaluate(() => { const f = document.querySelector('.dashboard-widget-frame'); const g = document.getElementById('dashboard-widgets-grid'); const antes = getComputedStyle(g, '::before'); return {allow: f && f.getAttribute('allow'), tela: f && f.hasAttribute('allowfullscreen'), sandbox: f && f.getAttribute('sandbox'), malha: antes.backgroundImage, tamanho: antes.backgroundSize, botoes: [...document.querySelectorAll('.dashboard-widget-card-header button')].map(b => b.getAttribute('data-c2f-dica') || '')}; });
    conferir('widget isolado pode pedir tela cheia (sandbox mantido)', quadro.sandbox === 'allow-scripts' && quadro.allow === 'fullscreen' && quadro.tela, quadro);
    conferir('botões do widget com dica', quadro.botoes.length >= 3 && quadro.botoes.every(d => d.length > 2), quadro.botoes);
    conferir('malha pontilhada de 20 px, sem as linhas de coluna', /radial-gradient/.test(quadro.malha) && !/repeating-linear-gradient/.test(quadro.malha) && /^20px 20px/.test(quadro.tamanho), {malha: quadro.malha.slice(0, 160), tamanho: quadro.tamanho});
    const frame = page.frames().find(f => f !== page.mainFrame());
    if (frame) conferir('documento do widget com CSS e tela cheia habilitada', await frame.evaluate(() => document.styleSheets.length >= 2 && document.fullscreenEnabled === true).catch(() => false));
    const alca = page.locator('.dashboard-widget-resize-handle').first();
    const cx = await alca.boundingBox(); const alturaInicial = await page.evaluate(() => document.querySelector('.dashboard-widget-card').style.height);
    await page.mouse.move(cx.x + cx.width / 2, cx.y + cx.height / 2); await page.mouse.down();
    await page.mouse.move(cx.x + cx.width / 2, cx.y - 900, {steps: 12}); await page.waitForTimeout(200);
    const minimo = await page.evaluate(() => { const c = document.querySelector('.dashboard-widget-card'); const g = document.getElementById('dashboard-widgets-grid'); return {altura: c.style.height, opacidade: getComputedStyle(g, '::before').opacity, interagindo: g.classList.contains('is-interacting'), rotulo: c.getAttribute('data-resize-size')}; });
    await foto(page, 'dashboard-resize-minimo');
    await page.mouse.move(cx.x + cx.width / 2, cx.y + 137, {steps: 6}); await page.waitForTimeout(150);
    const passo = await page.evaluate(() => parseInt(document.querySelector('.dashboard-widget-card').style.height, 10));
    await page.mouse.move(cx.x + cx.width / 2, cx.y + 2500, {steps: 10}); await page.waitForTimeout(150);
    const maximo = await page.evaluate(() => document.querySelector('.dashboard-widget-card').style.height);
    await page.keyboard.press('Escape');
    await page.evaluate(() => document.querySelector('.dashboard-widget-resize-handle').dispatchEvent(new PointerEvent('pointercancel', {bubbles: true, pointerId: 1})));
    await page.mouse.up(); await page.waitForTimeout(300);
    conferir('altura mínima de 120 px com a malha visível durante o arrasto', minimo.altura === '120px' && minimo.interagindo && parseFloat(minimo.opacidade) === 1 && /× 120 px/.test(minimo.rotulo || ''), minimo);
    conferir('passos verticais de 20 px e teto de 960 px', passo % 20 === 0 && maximo === '960px', {passo, maximo});
    // devolve a altura que estava (o roteiro não deixa o painel do Lab diferente)
    await abrir(page, 'dashboard/');
    conferir('sem erro de JavaScript', page._erros.length === 0, page._erros);
    void alturaInicial;
  });

  // ---------- 8 e 9: sidebar e topbar em vários módulos
  const modulos = ['dashboard/', 'admin-modos-ia/', 'admin-prompts-ia/', 'admin-ia-servidores/', 'forms/', 'galleries/', 'presentations/', 'subscriptions/', 'subscriptions-config/', 'subscriptions-plans/', 'coupons/', 'gateways-pagamentos/', 'analytics-manager/', 'product-reviews/', 'affiliates/', 'orders/', 'products/', 'admin-paginas/', 'usuarios/'];
  await grupo('8-9. sidebar e topbar', async () => {
    const medidas = [];
    for (const rota of modulos) {
      const status = await abrir(page, rota);
      if (status !== 200) { medidas.push({rota, status}); continue; }
      medidas.push(Object.assign({rota, status}, await page.evaluate(() => {
        const e = s => { const x = document.querySelector(s); if (!x) return null; const c = getComputedStyle(x); return [c.fontFamily, c.fontSize, c.fontWeight, c.lineHeight, c.letterSpacing].join('|'); };
        const mais = document.querySelector('[data-topbar-toggle="topbar-more"]'), perfil = document.querySelector('.c2fc-topbar-user-btn'), barra = document.querySelector('[data-admin-topbar]');
        const rm = mais.getBoundingClientRect(), rp = perfil.getBoundingClientRect(), rb = barra.getBoundingClientRect();
        return {item: e('[data-admin-sidebar] [data-menu-item]:not([aria-current])'), ativo: e('[data-admin-sidebar] [data-menu-item][aria-current]'), grupo: e('[data-menu-grupo-titulo]'), topbar: e('.c2fc-topbar-user-name'), folgaMaisPerfil: Math.round(rp.left - rm.right), folgaPerfilBorda: Math.round(rb.right - rp.right),
          menu: [...document.querySelectorAll('[data-admin-sidebar] [data-menu-item]')].map(a => a.getAttribute('href') || '')};
      })));
    }
    const boas = medidas.filter(m => m.status === 200), ref = boas[0];
    conferir(`${boas.length} módulos abertos`, boas.length >= 15, medidas.filter(m => m.status !== 200));
    for (const campo of ['item', 'ativo', 'grupo', 'topbar']) {
      const fora = boas.filter(m => m[campo] && m[campo] !== ref[campo]);
      conferir(`tipografia igual em todos os módulos: ${campo} (${ref[campo]})`, fora.length === 0, fora.map(m => [m.rota, m[campo]]));
    }
    conferir('item do menu com peso 500 e ativo com 600', /\|14px\|500\|/.test(ref.item) && /\|14px\|600\|/.test(ref.ativo), [ref.item, ref.ativo]);
    const fora = boas.filter(m => m.folgaMaisPerfil < 0 || m.folgaMaisPerfil > 24 || m.folgaPerfilBorda !== ref.folgaPerfilBorda);
    conferir('"mais atalhos" encostado no perfil, à direita, em todos', fora.length === 0, fora.map(m => [m.rota, m.folgaMaisPerfil, m.folgaPerfilBorda]));
    const publicos = ref.menu.filter(h => /\/(store|my-orders|affiliate)\/?$/.test(h));
    conferir('loja, meus pedidos e área do associado fora do menu do painel', publicos.length === 0, publicos);
    conferir('gestão de associados continua no menu', ref.menu.some(h => /\/affiliates\/$/.test(h)));
  });

  // ---------- 10: subscriptions/view
  await grupo('10. subscriptions/view', async () => {
    const rota = await primeiroLink(page, 'subscriptions/', 'subscriptions/view/');
    if (!rota) { conferir('há uma assinatura no Lab', false); return; }
    conferir('HTTP 200', await abrir(page, rota) === 200, rota);
    const dicas = await page.evaluate(() => ({abas: [...document.querySelectorAll('#subscriptions-tabs .c2fc-aba')].map(a => a.getAttribute('data-c2f-dica') || ''), botoes: ['btn-save-status', 'btn-reset-limits', 'btn-send-reply', 'btn-update-service'].filter(i => document.getElementById(i)).map(i => document.getElementById(i).getAttribute('data-c2f-dica') || ''), fomantic: document.querySelectorAll('[data-tooltip]').length}));
    conferir('abas e botões com dica do painel', dicas.abas.length >= 3 && dicas.abas.every(d => d.length > 5) && dicas.botoes.length >= 1 && dicas.botoes.every(d => d.length > 5) && dicas.fomantic === 0, dicas);
    await page.locator('.c2fc-select:has(#form-status-select) .c2fc-select-gatilho').click(); await page.waitForTimeout(300);
    const sel = await page.evaluate(() => { const raiz = document.getElementById('form-status-select').closest('.c2fc-select'); const p = raiz.querySelector('.c2fc-select-painel'); const caixa = raiz.closest('.c2fc-tabela-caixa'); const r = p.getBoundingClientRect(); return {flutuante: p.classList.contains('c2fc-flutuante'), rolagem: caixa.scrollHeight - caixa.clientHeight, visivel: r.height > 40 && r.bottom <= innerHeight + 1, z: getComputedStyle(p).zIndex}; });
    conferir('select de status aberto por inteiro, sem rolagem no container', sel.flutuante && sel.rolagem <= 1 && sel.visivel, sel);
    await foto(page, 'subscriptions-view-status');
    await page.keyboard.press('Escape');
    const comunicacao = page.locator('#subscriptions-tabs [data-tab="tab-communication"]');
    if (await comunicacao.count()) {
      await comunicacao.click(); await page.waitForTimeout(400);
      const icone = await page.evaluate(() => [...document.querySelectorAll('.sub-campo-icone')].filter(c => c.offsetParent).map(c => { const i = c.querySelector('input').getBoundingClientRect(), s = c.querySelector('svg').getBoundingClientRect(); const pr = parseFloat(getComputedStyle(c.querySelector('input')).paddingRight); return {direita: s.left > i.left + i.width / 2, dentro: s.right <= i.right, reserva: pr >= (i.right - s.left)}; }));
      conferir('ícone do campo à direita, fora da área do texto', icone.length >= 1 && icone.every(x => x.direita && x.dentro && x.reserva), icone);
    }
    conferir('sem erro de JavaScript', page._erros.length === 0, page._erros);
  });

  // ---------- 11: subscriptions-config (modal do logotipo)
  await grupo('11. subscriptions-config', async () => {
    const baixa = await novo(1366, 700);
    conferir('HTTP 200', await abrir(baixa, 'subscriptions-config/') === 200);
    await baixa.locator('._gestor-widgetImage-btn-add').first().click(); await baixa.waitForTimeout(2500);
    const modal = await baixa.evaluate(() => { const m = [...document.querySelectorAll('.c2fc-ponte-modal')].find(e => e.offsetParent); if (!m) return null; const c = m.querySelector('.content'), f = m.querySelector('iframe'); const r = m.getBoundingClientRect(), a = m.querySelector('.actions').getBoundingClientRect(); return {rolagemModal: m.scrollHeight - m.clientHeight, rolagemConteudo: c.scrollHeight - c.clientHeight, overflow: getComputedStyle(m).overflowY, cabe: r.bottom <= innerHeight && r.top >= 0, acoesVisiveis: a.bottom <= innerHeight, iframe: Math.round(f.getBoundingClientRect().height)}; });
    conferir('modal do seletor sem rolagem própria a 700 px de altura', modal && modal.rolagemModal <= 1 && modal.rolagemConteudo <= 1 && modal.cabe && modal.acoesVisiveis && modal.iframe > 300, modal);
    const quadro = baixa.frames().find(f => /admin-arquivos/.test(f.url()));
    if (quadro) {
      const botoes = await quadro.evaluate(() => [...document.querySelectorAll('.c2f-folder-tools > a, .c2f-folder-tools > button, .c2f-view-btn, .c2f-item .c2f-actions button')].slice(0, 12).map(b => getComputedStyle(b).borderRadius));
      conferir('botões do gerenciador arredondados (8 px)', botoes.length >= 4 && botoes.every(r => r === '8px'), botoes);
      conferir('fora do modo múltiplo o título é o do gerenciador', await quadro.evaluate(() => !/uma ou mais/i.test(document.getElementById('c2f-files-title').textContent)));
    } else conferir('iframe do gerenciador carregado', false);
    await foto(baixa, 'subscriptions-config-modal');
    await baixa.context().close();
  });

  // ---------- 12: galleries + seletor
  await grupo('12. galleries/editar e seletor de arquivos', async () => {
    const rota = await primeiroLink(page, 'galleries/', 'galleries/editar/');
    conferir('HTTP 200', await abrir(page, rota || 'galleries/adicionar/') === 200, rota);
    const ajuda = await page.evaluate(() => [...document.querySelectorAll('.req222-page .inline-flex.items-center.gap-2.text-xs')].map(d => { const s = d.querySelector(':scope > svg').getBoundingClientRect(), t = d.querySelector(':scope > span').getBoundingClientRect(); return {exibicao: getComputedStyle(d).display, lado: s.right <= t.left + 1, alinhado: s.top >= t.top - 2 && s.bottom <= t.bottom + 2, fonte: getComputedStyle(d).fontSize}; }));
    conferir('textos de ajuda com ícone e texto na mesma linha', ajuda.length >= 1 && ajuda.every(a => /flex$/.test(a.exibicao) && a.lado && a.alinhado && a.fonte === '12px'), ajuda);
    const grade = await page.evaluate(() => { const col = e => getComputedStyle(e).gridTemplateColumns.split(' ').length; const campos = document.querySelector('.gallery-display-fields'); const chaves = campos.previousElementSibling; return {chaves: col(chaves), campos: col(campos), larguraCartao: Math.round(campos.parentElement.getBoundingClientRect().width), larguraCampos: Math.round(campos.getBoundingClientRect().width)}; });
    conferir('controles de exibição em grade horizontal (4 colunas no desktop)', grade.chaves === 4 && grade.campos === 4 && grade.larguraCampos > grade.larguraCartao * 0.9, grade);
    const cursor = await page.evaluate(() => [...document.querySelectorAll('.req222-page .c2fc-chave label')].map(l => getComputedStyle(l).cursor));
    conferir('rótulos das chaves com cursor de clique', cursor.length >= 4 && cursor.every(c => c === 'pointer'), cursor);
    const abas = await page.evaluate(() => [...document.querySelectorAll('.menuConteudoGaleria .c2fc-aba')].map(a => ({dica: a.getAttribute('data-c2f-dica') || '', fundo: getComputedStyle(a).backgroundColor})));
    conferir('abas do conteúdo limpas e com dica', abas.length === 3 && abas.every(a => a.dica.length > 5 && /rgba\(0, 0, 0, 0\)|transparent/.test(a.fundo)), abas);
    await foto(page, 'galleries-editar', true);
    await page.click('a[data-tab="hep-editor"]'); await page.waitForTimeout(600);
    const variaveis = page.locator('a[data-tab="publisher-variables"]');
    if (await variaveis.count() && await variaveis.first().isVisible()) {
      await variaveis.first().click(); await page.waitForTimeout(700);
      const espaco = await page.evaluate(() => { const b = document.querySelector('.hep-variables-toolbar'); const linhas = [...document.querySelectorAll('.hep-val-options-buttons')].filter(x => x.offsetParent && x.children.length > 1); return {barra: b ? getComputedStyle(b).gap : null, margem: b ? getComputedStyle(b).marginBottom : null, linhas: linhas.slice(0, 3).map(l => Math.round(l.children[1].getBoundingClientRect().left - l.children[0].getBoundingClientRect().right))}; });
      conferir('sub-aba Variáveis com espaço entre os ícones', espaco.barra === '8px' && parseFloat(espaco.margem) >= 12 && espaco.linhas.every(g => g >= 8), espaco);
      await foto(page, 'galleries-variaveis');
    }
    await page.evaluate(() => scrollTo(0, 0));
    await page.click('#btn-select-images'); await page.waitForTimeout(3000);
    const quadro = page.frames().find(f => /admin-arquivos/.test(f.url()));
    if (!quadro) { conferir('seletor de arquivos abriu em iframe', false); return; }
    conferir('seletor aberto em modo múltiplo', /multiplo=sim/.test(quadro.url()), quadro.url());
    conferir('cabeçalho do modal pede uma ou mais imagens', await page.evaluate(() => { const m = [...document.querySelectorAll('.c2fc-ponte-modal')].find(e => e.offsetParent); return m ? m.querySelector('.header').textContent.trim() : ''; }) === 'Selecione uma ou mais imagens abaixo...');
    conferir('título dinâmico "Selecione uma ou mais imagens abaixo..."', await quadro.evaluate(() => document.getElementById('c2f-files-title').textContent.trim()) === 'Selecione uma ou mais imagens abaixo...', await quadro.evaluate(() => document.getElementById('c2f-files-title').textContent.trim()));
    // entra em pastas até achar duas imagens
    for (let i = 0; i < 4 && await quadro.evaluate(() => document.querySelectorAll('.c2f-item.c2f-file').length) < 2; i++) {
      const pasta = quadro.locator('.c2f-item.c2f-folder .c2f-thumb, .c2f-item.c2f-folder .c2f-name').nth(i === 0 ? 0 : 0);
      if (!(await pasta.count())) break;
      await pasta.first().click(); await page.waitForTimeout(1500);
    }
    const arquivos = await quadro.evaluate(() => document.querySelectorAll('.c2f-item.c2f-file').length);
    if (arquivos < 2) { conferir('há ao menos dois arquivos numa pasta do Lab para selecionar', false, arquivos); return; }
    conferir('bandeja escondida sem seleção', await quadro.evaluate(() => document.getElementById('c2f-pick-tray').classList.contains('hidden')));
    await quadro.locator('.c2f-item.c2f-file .c2f-sel').nth(0).check({force: true});
    await quadro.locator('.c2f-item.c2f-file .c2f-sel').nth(1).check({force: true}); await page.waitForTimeout(300);
    const bandeja = await quadro.evaluate(() => { const b = document.getElementById('c2f-pick-tray'); const r = b.getBoundingClientRect(); return {visivel: !b.classList.contains('hidden'), miniaturas: b.querySelectorAll('.c2f-pick-thumb').length, imagens: [...b.querySelectorAll('.c2f-pick-thumb img')].every(i => !!i.getAttribute('src')), contagem: document.getElementById('c2f-pick-tray-count').textContent, presa: getComputedStyle(b).position === 'sticky' && r.bottom <= innerHeight + 1, botoes: !!document.getElementById('c2f-pick-tray-cancel') && !!document.getElementById('c2f-pick-tray-confirm'), mesmaLinha: Math.abs(document.getElementById('c2f-pick-tray-confirm').getBoundingClientRect().top - document.getElementById('c2f-pick-tray-cancel').getBoundingClientRect().top) < 4}; });
    conferir('bandeja com as miniaturas ao lado de Cancelar e Incluir Selecionados', bandeja.visivel && bandeja.miniaturas === 2 && bandeja.imagens && bandeja.contagem === '2' && bandeja.presa && bandeja.botoes && bandeja.mesmaLinha, bandeja);
    await foto(page, 'seletor-bandeja');
    await quadro.locator('.c2f-pick-thumb-remove').first().click(); await page.waitForTimeout(300);
    const depois = await quadro.evaluate(() => ({miniaturas: document.querySelectorAll('.c2f-pick-thumb').length, marcados: document.querySelectorAll('.c2f-item .c2f-sel:checked').length, destacados: document.querySelectorAll('.c2f-item.c2f-selecionado').length}));
    conferir('o "×" da miniatura desmarca o arquivo', depois.miniaturas === 1 && depois.marcados === 1 && depois.destacados === 1, depois);
    await quadro.locator('#c2f-pick-tray-cancel').click(); await page.waitForTimeout(300);
    conferir('Cancelar limpa a seleção e esconde a bandeja', await quadro.evaluate(() => document.getElementById('c2f-pick-tray').classList.contains('hidden') && document.querySelectorAll('.c2f-item .c2f-sel:checked').length === 0));
    // a entrega é conferida no canal (uma mensagem por arquivo), sem depender do que a galeria faz com ela
    await page.evaluate(() => { window.__req242 = []; window.addEventListener('message', e => { try { const d = JSON.parse(e.data); if (d.moduloId === 'admin-arquivos') window.__req242.push(JSON.parse(decodeURI(d.data))); } catch (x) { /* outra origem */ } }); });
    await quadro.locator('.c2f-item.c2f-file .c2f-sel').nth(0).check({force: true});
    await quadro.locator('.c2f-item.c2f-file .c2f-sel').nth(1).check({force: true}); await page.waitForTimeout(200);
    const escolhidos = await quadro.evaluate(() => [...document.querySelectorAll('.c2f-item.c2f-selecionado')].map(i => i.getAttribute('data-caminho')));
    await quadro.locator('#c2f-pick-tray-confirm').click(); await page.waitForTimeout(700);
    const recebidos = await page.evaluate(() => window.__req242.map(m => m.caminho));
    conferir('Incluir Selecionados entrega os arquivos marcados a quem abriu o seletor', recebidos.length === 2 && escolhidos.every(c => recebidos.includes(c)), {escolhidos, recebidos});
    conferir('depois de incluir, a seleção e a bandeja zeram', await quadro.evaluate(() => document.getElementById('c2f-pick-tray').classList.contains('hidden') && document.querySelectorAll('.c2f-item .c2f-sel:checked').length === 0));
    conferir('sem erro de JavaScript', page._erros.length === 0, page._erros);
  });

  // ---------- 13: estágios e planos
  await grupo('13. subscriptions-service-stages e subscriptions-plans', async () => {
    conferir('HTTP 200', await abrir(page, 'subscriptions-service-stages/') === 200);
    const tabela = await page.evaluate(() => ({cabecalho: [...document.querySelectorAll('#_gestor-interface-listar thead th')].map(t => Math.round(t.getBoundingClientRect().height)), rotulos: [...document.querySelectorAll('#_gestor-interface-listar tbody .c2fc-rotulo')].map(r => Math.round(r.getBoundingClientRect().height)), rolagem: document.documentElement.scrollWidth - innerWidth}));
    conferir('cabeçalho numa linha e rótulos sem palavra partida', tabela.cabecalho.length >= 5 && new Set(tabela.cabecalho).size === 1 && tabela.cabecalho[0] <= 50 && tabela.rotulos.length > 0 && tabela.rotulos.every(h => h <= 26) && tabela.rolagem <= 0, {cabecalho: tabela.cabecalho, maior: Math.max(...tabela.rotulos), rolagem: tabela.rolagem});
    await foto(page, 'service-stages-lista');
    const rota = await primeiroLink(page, 'subscriptions-plans/', 'subscriptions-plans/editar/');
    conferir('plano abre com HTTP 200', await abrir(page, rota) === 200, rota);
    const abas = await page.evaluate(() => [...document.querySelectorAll('a.c2fc-aba[data-tab^="sp-"]')].map(a => a.getAttribute('data-c2f-dica') || ''));
    conferir('seis abas do plano com dica', abas.length === 6 && abas.every(d => d.length > 5 && !d.includes('@[[')), abas);
    await page.click('a[data-tab="sp-widget"]'); await page.waitForTimeout(300);
    const copiar = await page.evaluate(() => { const b = document.getElementById('btn-copy-subscriptions-plans-widget-val').getBoundingClientRect(), i = document.getElementById('subscriptions-plans-widget-val').getBoundingClientRect(); return {altura: Math.round(b.height), largura: Math.round(b.width), mesmaLinha: Math.abs((b.top + b.height / 2) - (i.top + i.height / 2)) < 6, campo: Math.round(i.height)}; });
    conferir('variável do widget numa linha, com Copiar compacto ao lado', copiar.mesmaLinha && copiar.altura <= 36 && copiar.largura <= 110 && copiar.campo <= 44, copiar);
    await page.click('a[data-tab="sp-pricing"]'); await page.waitForTimeout(400);
    const selects = await page.evaluate(() => [...document.querySelectorAll('[data-tab="sp-pricing"] select')].map(s => /c2fc-campo-selecao/.test(s.className)));
    conferir('todos os selects de Preços e Perfil no padrão', selects.length >= 4 && selects.every(Boolean), selects);
    await foto(page, 'plans-precos');
    conferir('sem erro de JavaScript', page._erros.length === 0, page._erros);
  });

  // ---------- 14: product-reviews
  await grupo('14. product-reviews', async () => {
    for (const rota of ['product-reviews/', 'product-reviews/settings/']) {
      conferir(rota + ' HTTP 200', await abrir(page, rota) === 200);
      const tela = await page.evaluate(() => ({h1: document.querySelectorAll('[data-admin-main] h1').length, botoes: [...document.querySelectorAll('[data-admin-main] button, [data-admin-main] a.c2fc-botao')].map(b => /c2fc-(botao|acao)/.test(b.className)), selects: [...document.querySelectorAll('[data-admin-main] select')].map(s => /c2fc-campo-selecao/.test(s.className)), cru: document.body.innerText.includes('@[['), rolagem: document.documentElement.scrollWidth - innerWidth}));
      conferir(rota + ' com título, botões e selects do painel', tela.h1 === 1 && tela.botoes.length >= 1 && tela.botoes.every(Boolean) && tela.selects.every(Boolean) && !tela.cru && tela.rolagem <= 0, tela);
      await foto(page, rota.replace(/\//g, '-').replace(/-$/, ''));
    }
  });

  // ---------- 15: coupons
  await grupo('15. coupons/edit', async () => {
    const rota = await primeiroLink(page, 'coupons/', 'coupons/edit/');
    conferir('HTTP 200', await abrir(page, rota || 'coupons/add/') === 200, rota);
    const tela = await page.evaluate(() => ({nativos: [...document.querySelectorAll('[data-admin-main] select')].map(s => !!s.closest('.c2fc-select')), multiplos: [...document.querySelectorAll('[data-admin-main] select[multiple]')].map(s => ({busca: s.hasAttribute('data-c2f-busca'), controle: !!s.closest('.c2fc-select')})), cursor: [...document.querySelectorAll('[data-admin-main] label:has(input[type="checkbox"])')].map(l => getComputedStyle(l).cursor)}));
    conferir('todos os selects com o controle do painel', tela.nativos.length === 7 && tela.nativos.every(Boolean), tela.nativos);
    conferir('rótulo do checkbox com cursor de clique', tela.cursor.length >= 1 && tela.cursor.every(c => c === 'pointer'), tela.cursor);
    const escopo = await page.evaluate(() => { const s = document.getElementById('coupon-applies_to'); return [...s.options].map(o => o.value); });
    await page.evaluate(v => window.c2fControles.de(document.getElementById('coupon-applies_to')).definir(v), escopo.find(v => v !== 'subscriptions') || escopo[0]); await page.waitForTimeout(300);
    const raiz = page.locator('.c2fc-select:has(#coupon-product_ids)');
    await raiz.locator('.c2fc-select-gatilho').click(); await page.waitForTimeout(300);
    const opcoes = raiz.locator('.c2fc-select-opcao');
    if (await opcoes.count() >= 2) {
      const marcadosAntes = await page.evaluate(() => [...document.getElementById('coupon-product_ids').selectedOptions].length);
      conferir('"Onde vale" com busca na lista', await raiz.locator('.c2fc-select-busca').count() === 1);
      const livre = await page.evaluate(() => [...document.getElementById('coupon-product_ids').options].findIndex(o => !o.selected));
      await opcoes.nth(Math.max(0, livre)).dispatchEvent('mousedown'); await page.waitForTimeout(200);
      const fichas = await raiz.locator('.c2fc-ficha').count();
      conferir('escolha vira ficha removível', fichas === marcadosAntes + 1, {fichas, marcadosAntes});
      await foto(page, 'coupons-onde-vale');
      await raiz.locator('.c2fc-ficha button').last().click(); await page.waitForTimeout(200);
      conferir('o "×" da ficha tira o item', await raiz.locator('.c2fc-ficha').count() === marcadosAntes);
    } else conferir('há produtos no Lab para escolher em "Onde vale"', false, await opcoes.count());
    await page.keyboard.press('Escape');
    conferir('sem erro de JavaScript', page._erros.length === 0, page._erros);
  });

  // ---------- 16: gateways
  await grupo('16. gateways-pagamentos/editar', async () => {
    await abrir(page, 'gateways-pagamentos/');
    const rotas = await page.evaluate(() => [...new Set([...document.querySelectorAll('a[href*="gateways-pagamentos/editar/"]')].map(a => new URL(a.href).pathname.slice(1) + new URL(a.href).search))]);
    let padrao = 0, comum = 0;
    for (const rota of rotas.slice(0, 6)) {
      await abrir(page, rota);
      const b = await page.evaluate(() => { const x = document.querySelector('.btn-definir-padrao'); if (!x) return null; const c = getComputedStyle(x), s = x.querySelector('svg'), aviso = [...document.querySelectorAll('.c2fc-nota-alerta')].find(n => n.querySelector('.c2fc-cor-yellow')); const anterior = aviso ? aviso.previousElementSibling : null; return {destaque: x.classList.contains('c2fc-botao-destaque'), fundo: c.backgroundColor, cor: c.color, cheia: s ? getComputedStyle(s).fill : '', dica: x.getAttribute('data-c2f-dica'), rotulo: x.textContent.trim(), aviso: !!aviso, folga: aviso && anterior ? Math.round(aviso.getBoundingClientRect().top - anterior.getBoundingClientRect().bottom) : null}; });
      if (!b) continue;
      if (b.aviso) {
        padrao++;
        conferir(`padrão ativo em âmbar com estrela cheia (${rota})`, b.destaque && b.fundo === 'rgb(245, 158, 11)' && b.cor === 'rgb(255, 255, 255)' && b.cheia !== 'none' && !!b.dica, b);
        conferir('aviso "Gateway padrão" com respiro em cima', b.folga >= 12, b.folga);
        await foto(page, 'gateway-padrao', true);
      } else {
        comum++;
        conferir(`gateway comum com botão neutro e estrela vazia (${rota})`, !b.destaque && b.fundo === 'rgb(255, 255, 255)' && b.cheia === 'none' && !!b.dica, b);
      }
    }
    conferir('pelo menos um gateway padrão conferido', padrao >= 1, {padrao, comum, rotas: rotas.length});
  });

  // ---------- 17: analytics
  await grupo('17. analytics-manager', async () => {
    conferir('HTTP 200', await abrir(page, 'analytics-manager/') === 200);
    await page.waitForTimeout(800);
    const tela = await page.evaluate(() => { const visiveis = [...document.querySelectorAll('[data-admin-main] button')].filter(b => b.offsetParent); return {botoes: visiveis.length, padrao: visiveis.every(b => /c2fc-(botao|acao)/.test(b.className) || b.matches('[data-am-action="token-insert"],[data-am-action="route-pick"],[data-am-action="event-insert"]')), dicas: visiveis.filter(b => b.dataset.amAction && b.dataset.amAction.startsWith('pipeline-')).every(b => (b.getAttribute('data-c2f-dica') || '').length > 5), cartoes: [...document.querySelectorAll('[data-admin-main] section, [data-admin-main] article')].filter(s => s.offsetParent).map(s => { const c = getComputedStyle(s); return [c.borderRadius, c.padding, c.backgroundColor]; }), selects: [...document.querySelectorAll('[data-admin-main] select')].every(s => /c2fc-campo-selecao/.test(s.className)), cru: document.body.innerText.includes('@[[')}; });
    conferir('botões no padrão, com dica nas ações de pipeline', tela.botoes >= 3 && tela.padrao && tela.dicas && !tela.cru, tela);
    conferir('cartões brancos (raio 12 px, padding 24 px) e selects padronizados', tela.cartoes.length >= 1 && tela.cartoes.every(c => c[0] === '12px' && c[1] === '24px' && c[2] === 'rgb(255, 255, 255)') && tela.selects, tela.cartoes.slice(0, 3));
    await foto(page, 'analytics-manager', true);
    const editar = page.locator('[data-am-action="pipeline-edit"]').first();
    if (await editar.count() && await editar.isVisible()) {
      await editar.click(); await page.waitForLoadState('networkidle'); await page.waitForTimeout(900);
      const pipe = await page.evaluate(() => { const visiveis = [...document.querySelectorAll('[data-admin-main] button')].filter(b => b.offsetParent); return {botoes: visiveis.length, padrao: visiveis.every(b => /c2fc-(botao|acao)/.test(b.className) || b.matches('[data-am-action="token-insert"],[data-am-action="route-pick"],[data-am-action="event-insert"]')), fora: visiveis.filter(b => !/c2fc-(botao|acao)/.test(b.className)).map(b => b.dataset.amAction).slice(0, 5)}; });
      conferir('tela do pipeline com botões no padrão', pipe.botoes >= 1 && pipe.padrao, pipe);
      await foto(page, 'analytics-pipeline', true);
    }
    conferir('sem erro de JavaScript', page._erros.length === 0, page._erros);
  });

  // ---------- 390 px: nada de rolagem horizontal nas telas tocadas
  await grupo('390 px', async () => {
    const movel = await novo(390, 844);
    const rotas = ['dashboard/', 'forms/adicionar/', 'forms-search/adicionar/', 'galleries/adicionar/', 'coupons/add/', 'product-reviews/', 'analytics-manager/', 'subscriptions-config/', 'subscriptions-service-stages/', 'presentations/adicionar/', 'gateways-pagamentos/'];
    for (const rota of rotas) {
      const status = await abrir(movel, rota);
      const f = movel.locator('[data-admin-fechar]'); if (await f.count() && await f.first().isVisible().catch(() => false)) { await f.first().click().catch(() => {}); await movel.waitForTimeout(250); }
      const sobra = await movel.evaluate(() => document.documentElement.scrollWidth - innerWidth);
      conferir(`${rota} sem rolagem horizontal`, status === 200 && sobra <= 0, {status, sobra});
    }
    await foto(movel, 'movel-galleries');
    await movel.context().close();
  });

  await browser.close();
  const falhas = resultados.filter(r => !r.ok);
  fs.writeFileSync(path.join(saida, 'resultado.json'), JSON.stringify({data: new Date().toISOString(), base, total: resultados.length, aprovados: resultados.length - falhas.length, falhas: falhas.length, resultados}, null, 1));
  console.log(`\n${resultados.length - falhas.length}/${resultados.length} conferências aprovadas`);
  process.exit(falhas.length ? 1 : 0);
})();
