// REQ-243 (core) e REQ-108 (site) — roteiro de navegador no Lab local: confere os 27 itens da rodada 2 da auditoria humana.
// Uso: node sdd/validation/req243/req243-browser.cjs [--saida=<pasta de evidências>] [--so=<trecho do nome do grupo>]
// Playwright: C2F_PLAYWRIGHT aponta para a pasta do pacote quando ele não está no node_modules local.
// Só lê e interage com telas; não salva formulário nenhum. O cookie vem de `c2f auth:cookie --project=conn2flow-site-local`
// (C2F_COOKIES aponta o arquivo quando ele não está em temp/ desta árvore).
const {chromium} = require(process.env.C2F_PLAYWRIGHT || '../../../node_modules/playwright');
const fs = require('node:fs'), path = require('node:path');
const core = path.resolve(__dirname, '../../..');
const base = process.env.C2F_BASE || 'https://conn2flow.local';
const opcao = nome => (process.argv.find(a => a.startsWith('--' + nome + '=')) || '').split('=').slice(1).join('=');
const saida = opcao('saida') ? path.resolve(opcao('saida')) : path.join(__dirname, 'evidencias');
const so = opcao('so');
fs.mkdirSync(saida, {recursive: true});
const jar = fs.readFileSync(process.env.C2F_COOKIES || path.join(core, 'temp/agent-cookies.txt'), 'utf8').split(/\r?\n/)
  .filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
    const p = l.replace(/^#HttpOnly_/, '').split('\t');
    return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
  });

const resultados = [];
let grupoAtual = '';
function conferir(nome, ok, detalhe) {
  resultados.push({grupo: grupoAtual, nome, ok: !!ok, detalhe: detalhe === undefined ? '' : detalhe});
  console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok ? '' : ' -> ' + String(JSON.stringify(detalhe === undefined ? '' : detalhe)).slice(0, 400)));
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
  // Medidas reutilizadas dentro da página.
  const selectsSemControle = page => page.evaluate(() => [...document.querySelectorAll('select')].filter(s => !s.closest('template') && !s.closest('.c2fc-select') && s.offsetParent !== null).map(s => s.id || s.name || s.className.slice(0, 40)));
  const selectsMontados = page => page.evaluate(() => document.querySelectorAll('.c2fc-select > select').length);
  const caixasEmLinha = page => page.evaluate(() => [...document.querySelectorAll('.c2fc-rotulo.label.inline-flex')].filter(c => c.offsetParent !== null).map(c => {
    const i = c.querySelector(':scope > svg, :scope > i'), s = c.querySelector(':scope > span');
    if (!i || !s) return {ok: false, motivo: 'sem ícone ou span'};
    const ri = i.getBoundingClientRect(), rs = s.getBoundingClientRect();
    // Dentro de um campo em coluna a caixa é item flexível e o navegador informa `flex`.
    return {ok: /^(inline-)?flex$/.test(getComputedStyle(c).display) && getComputedStyle(c).flexDirection === 'row' && ri.right <= rs.left + 1 && ri.top >= rs.top - 4 && ri.bottom <= rs.bottom + 4, icone: [Math.round(ri.left), Math.round(ri.top)], texto: [Math.round(rs.left), Math.round(rs.top)]};
  }));
  const semRolagemLateral = page => page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
  const textoCru = page => page.evaluate(() => { const alvo = document.querySelector('#c2f-admin-conteudo, main') || document.body; const copia = alvo.cloneNode(true); copia.querySelectorAll('.CodeMirror, textarea, input, code, kbd, script, style, template, iframe, [id$="-fields-list"]').forEach(n => n.remove()); return (copia.innerText.match(/@?\[\[[a-zA-Z0-9#_ÁÉÍÓÚÃÕÇ-]+\]\]@?/g) || []).filter(t => !/^\[\[(publisher|item|product)#/.test(t)).slice(0, 8); });
  const mapa = page => page.evaluate(() => {
    const m = document.querySelector('.c2fc-mapa'); if (!m) return null;
    const escondidos = []; for (let n = m; n && n !== document.body; n = n.parentElement) { if (getComputedStyle(n).display === 'none') { escondidos.push([n, n.style.display, n.className]); n.classList.remove('hidden'); n.style.display = 'block'; } }
    const colunas = [...m.children].map(c => c.getBoundingClientRect());
    const titulos = [...m.querySelectorAll('.c2fc-mapa-titulo')].map(t => ({texto: t.textContent.trim().slice(0, 40), fundo: getComputedStyle(t).backgroundColor, peso: getComputedStyle(t).fontWeight}));
    const r = {n: colunas.length, larguras: colunas.map(c => Math.round(c.width)), mesmaLinha: colunas.every(c => Math.abs(c.top - colunas[0].top) < 2), largura: Math.round(m.getBoundingClientRect().width), pai: Math.round(m.parentElement.getBoundingClientRect().width), titulos};
    escondidos.forEach(([n, d, c]) => { n.style.display = d; n.className = c; });
    return r;
  });
  const copiarEmLinha = page => page.evaluate(() => { const b = document.getElementById('btn-copy-widget-val'), i = document.getElementById('hep-widget-val'); if (!b || !i) return null; const rb = b.getBoundingClientRect(), ri = i.getBoundingClientRect(); return {compacto: b.classList.contains('c2fc-botao-compacto'), mesmaLinha: Math.abs((rb.top + rb.height / 2) - (ri.top + ri.height / 2)) < 6 && rb.left >= ri.right - 1, altura: Math.round(rb.height), visivel: rb.width > 0}; });

  const page = await novo(1366, 900);

  // ================================================================ CORE
  await grupo('01. select: clique na opção não aciona o que está atrás', async () => {
    const rota = await primeiroLink(page, 'subscriptions-plans/', 'subscriptions-plans/editar/') || await primeiroLink(page, 'admin-paginas/', 'admin-paginas/editar/');
    conferir('HTTP 200', await abrir(page, rota) === 200, rota);
    const r = await page.evaluate(async () => {
      const raiz = [...document.querySelectorAll('.c2fc-select')].find(x => x.offsetParent !== null && x.querySelectorAll('select option').length > 1 && !x.querySelector('select').multiple && !x.querySelector('select').disabled && !x.closest('header, aside, nav, dialog, [id*="topbar"]') && x.querySelector('.c2fc-select-gatilho').getBoundingClientRect().width > 200);
      if (!raiz) return null;
      window.__vazou = [];
      const ouvir = ev => { if (!ev.target.closest('.c2fc-select')) window.__vazou.push(ev.type + ':' + (ev.target.tagName || '') + '.' + String(ev.target.className || '').slice(0, 40)); };
      document.addEventListener('click', ouvir); document.addEventListener('mouseup', ouvir);
      raiz.scrollIntoView({block: 'center'});
      raiz.setAttribute('data-roteiro', '1');
      return {opcoes: raiz.querySelectorAll('select option').length};
    });
    conferir('há select com opções na tela', !!r, rota);
    if (!r) return;
    const url = page.url();
    await page.click('[data-roteiro] .c2fc-select-gatilho'); await page.waitForTimeout(250);
    const alvo = page.locator('[data-roteiro] .c2fc-select-opcao').last();
    const antes = await page.evaluate(() => document.querySelector('[data-roteiro] select').value);
    await alvo.click(); await page.waitForTimeout(500);
    const depois = await page.evaluate(() => ({valor: document.querySelector('[data-roteiro] select').value, vazou: window.__vazou, fechado: document.querySelector('[data-roteiro] .c2fc-select-painel').classList.contains('c2fc-oculto')}));
    conferir('a opção foi escolhida e o painel fechou', depois.fechado && (depois.valor !== antes || r.opcoes === 1), {antes, depois});
    conferir('nenhum mouseup ou click chegou a elemento fora do select', depois.vazou.length === 0, depois.vazou);
    conferir('a página não navegou', page.url() === url, page.url());
  });

  await grupo('02. histórico de alterações quebra linha', async () => {
    for (const [lista, filtro] of [['admin-paginas/', 'admin-paginas/editar/'], ['products/', 'products/edit/']]) {
      const rota = await primeiroLink(page, lista, filtro);
      await abrir(page, rota);
      const h = await page.evaluate(() => { const c = document.querySelector('[data-c2f-historico]'); if (!c) return null; const t = c.querySelector('table'); return {sw: c.scrollWidth, cw: c.clientWidth, layout: getComputedStyle(t).tableLayout, itens: c.querySelectorAll('.item').length}; });
      if (!h) { conferir(filtro + ': registro com histórico', true); continue; }
      conferir(filtro + ': histórico sem rolagem horizontal', h.sw <= h.cw + 1 && h.layout === 'fixed', h);
    }
    await foto(page, '02-historico');
  });

  let paginaEditar = null;
  await grupo('03. editor HTML: cartões de modelos compactos', async () => {
    conferir('HTTP 200', await abrir(page, 'admin-paginas/adicionar/') === 200);
    await page.click('a[data-tab="modelos"], [data-tab="modelos"].item').catch(() => {});
    await page.waitForSelector('#modelos-cards .modelo-card', {timeout: 20000}).catch(() => {});
    await page.waitForTimeout(600);
    const g = await page.evaluate(() => { const c = document.getElementById('modelos-cards'); const cartoes = [...c.querySelectorAll('.modelo-card')].filter(x => x.offsetParent !== null).map(x => x.getBoundingClientRect()); return {display: getComputedStyle(c).display, n: cartoes.length, maior: Math.round(Math.max(0, ...cartoes.map(r => r.width))), maiorAltura: Math.round(Math.max(0, ...cartoes.map(r => r.height))), porLinha: cartoes.filter(r => Math.abs(r.top - cartoes[0].top) < 2).length, largura: Math.round(c.getBoundingClientRect().width)}; });
    conferir('grade de modelos com cartões de até 240 px', g.display === 'grid' && g.n > 0 && g.maior <= 241 && g.maiorAltura <= 345, g);
    conferir('vários cartões por linha', g.porLinha >= Math.min(g.n, 3), g);
    await foto(page, '03-modelos');
  });

  await grupo('04. editor visual: palco com a altura útil', async () => {
    paginaEditar = await primeiroLink(page, 'admin-paginas/', 'admin-paginas/editar/');
    conferir('HTTP 200', await abrir(page, paginaEditar) === 200, paginaEditar);
    await page.evaluate(() => { try { Object.keys(localStorage).filter(k => /tab|aba/i.test(k)).forEach(k => localStorage.removeItem(k)); } catch (e) {} });
    await abrir(page, paginaEditar);
    const botao = page.locator('.previsualizarEditorVisual, .html-editor-visual-btn, button:has-text("Editor Visual"), a:has-text("Editor Visual")').first();
    conferir('botão do editor visual na tela', await botao.count() > 0);
    await botao.click({timeout: 10000}); await page.waitForTimeout(2500);
    const p = await page.evaluate(() => { const i = document.getElementById('iframe-preview'); const m = i && i.closest('.modal, .c2fc-ponte-modal'); const r = i.getBoundingClientRect(); return {altura: Math.round(r.height), esperado: innerHeight - 220, visivel: r.width > 0, modal: m ? m.className.slice(0, 80) : null, regra: getComputedStyle(i).height}; });
    conferir('#iframe-preview com a altura de calc(100vh - 220px)', p.visivel && Math.abs(p.altura - p.esperado) <= 4, p);
    await foto(page, '04-editor-visual');
  });

  await grupo('05. alerta com ênfase interpretada', async () => {
    await abrir(page, paginaEditar || 'admin-paginas/adicionar/');
    const a = await page.evaluate(() => { const msg = (window.gestor && gestor.interface && gestor.interface.imagepick && gestor.interface.imagepick.alertas && gestor.interface.imagepick.alertas.naoImagem) || 'O arquivo selecionado <b>NÃO</b> é uma imagem. <img src=x onerror="window.__xss=1">'; c2fControles.dialogo.alerta(msg + '<img src=x onerror="window.__xss=1">', {formatado: true}); const d = document.querySelector('.c2fc-dialogo'); return {msg, negrito: (d.querySelector('b') || {}).textContent, texto: d.querySelector('p').textContent, cru: /<\/?b>/.test(d.querySelector('p').textContent), img: !!d.querySelector('img')}; });
    await page.waitForTimeout(300);
    conferir('<b> vira negrito e nenhuma tag aparece como texto', /NÃO|NOT/.test(a.negrito || '') && !a.cru, a);
    conferir('marcação fora da lista não vira elemento nem executa', !a.img && !(await page.evaluate(() => window.__xss)), a);
    await foto(page, '05-alerta');
  });

  await grupo('06. admin-arquivos: dicas e proporção dos botões', async () => {
    conferir('HTTP 200', await abrir(page, 'admin-arquivos/') === 200);
    const b = await page.evaluate(() => { const h = s => { const e = document.querySelector(s); return e ? {a: Math.round(e.getBoundingClientRect().height), dica: e.getAttribute('data-c2f-dica') || ''} : null; }; return {adicionar: h('.c2f-folder-tools > a'), pasta: h('#c2f-new-folder'), todos: h('.c2f-select-all-box'), visao: h('.c2f-view-btn')}; });
    conferir('dica em Adicionar arquivos, Nova pasta e Selecionar todos', [b.adicionar, b.pasta, b.todos].every(x => x && x.dica.length > 10 && !/\[\[/.test(x.dica)), b);
    conferir('botões de ação e de visualização na mesma altura', Math.abs(b.adicionar.a - b.visao.a) <= 2 && Math.abs(b.pasta.a - b.visao.a) <= 2 && Math.abs(b.todos.a - b.visao.a) <= 2, b);
    conferir('fora do seletor a bandeja não aparece', await page.evaluate(() => document.getElementById('c2f-pick-tray').classList.contains('hidden')));
    await foto(page, '06-admin-arquivos');
  });

  await grupo('07. admin-categorias: adicionar filho', async () => {
    const editar = await primeiroLink(page, 'admin-categorias/', 'admin-categorias/editar/');
    await abrir(page, editar);
    const filho = await page.evaluate(() => { const a = document.querySelector('a[href*="adicionar-filho"]'); return a ? new URL(a.href).pathname.slice(1) + new URL(a.href).search : null; });
    conferir('link de adicionar filho', !!filho, editar);
    if (!filho) return;
    conferir('HTTP 200', await abrir(page, filho) === 200, filho);
    const f = await page.evaluate(() => { const n = document.querySelector('input[name="nome"]'), b = document.getElementById('_gestor-interface-insert-button'); const eb = b && getComputedStyle(b); return {entrada: n && n.className, borda: n && getComputedStyle(n).borderRadius, botao: b && b.className.slice(0, 60), fundo: eb && eb.backgroundColor, tipo: b && b.type, fomantic: !!document.querySelector('form.ui.form'), pai: !!document.querySelector('input[name="id_pai"]')}; });
    conferir('campo Nome com c2fc-campo-entrada', /c2fc-campo-entrada/.test(f.entrada || '') && f.borda === '8px', f);
    conferir('botão de envio estilizado (c2fc-botao c2fc-botao-primario)', /c2fc-botao c2fc-botao-primario/.test(f.botao || '') && !/rgba\(0, 0, 0, 0\)|transparent/.test(f.fundo) && !f.fomantic && f.pai, f);
    await foto(page, '07-categoria-filho');
  });

  await grupo('08. cookie-consent e menus: mensagens em linha e rádios na horizontal', async () => {
    for (const modulo of ['cookie-consent', 'menus']) {
      const rota = await primeiroLink(page, modulo + '/', modulo + '/editar/') || modulo + '/adicionar/';
      conferir(modulo + ': HTTP 200', await abrir(page, rota) === 200, rota);
      const caixas = await caixasEmLinha(page);
      conferir(modulo + ': caixas informativas com ícone e texto na mesma linha', caixas.length > 0 && caixas.every(c => c.ok), caixas.filter(c => !c.ok));
      conferir(modulo + ': nenhum erro de script', page._erros.length === 0, page._erros);
      await foto(page, '08-' + modulo);
      if (modulo !== 'menus') continue;
      const r = await page.evaluate(() => { const w = document.getElementById('page-type-filter-wrapper'); w.style.display = 'block'; const itens = [...w.querySelectorAll('.radio.checkbox')].map(x => x.getBoundingClientRect()); const d = w.querySelector('.radio.checkbox'); return {n: itens.length, mesmaLinha: itens.every(i => Math.abs(i.top - itens[0].top) < 2), larguras: itens.map(i => Math.round(i.width)), pos: d.getAttribute('data-c2f-dica-pos'), container: Math.round(w.getBoundingClientRect().width)}; });
      conferir('menus: três opções de tipo na mesma linha', r.n === 3 && r.mesmaLinha, r);
      conferir('menus: dica ancorada na própria opção (largura do item, à esquerda)', r.pos === 'top left' && r.larguras.every(l => l < 200), r);
      await page.hover('#page-type-filter-wrapper .radio.checkbox:nth-of-type(2)'); await page.waitForTimeout(200);
      await foto(page, '08-menus-radios');
    }
  });

  await grupo('09. galleries: alça sólida e seletor com bandeja e conclusão', async () => {
    const rota = await primeiroLink(page, 'galleries/', 'galleries/editar/') || 'galleries/adicionar/';
    conferir('HTTP 200', await abrir(page, rota) === 200, rota);
    const itens = await page.evaluate(() => { const lista = document.getElementById('gallery-items'); const alcas = [...document.querySelectorAll('#gallery-items .gallery-item-handle')].map(h => { const e = getComputedStyle(h), o = h.parentElement.querySelector('.gallery-item-remove'); return {opacidade: e.opacity, altura: Math.round(h.getBoundingClientRect().height), outra: o ? Math.round(o.getBoundingClientRect().height) : null, topo: Math.round(h.getBoundingClientRect().top), topoOutra: o ? Math.round(o.getBoundingClientRect().top) : null}; }); return {n: lista ? lista.querySelectorAll('.gallery-item').length : 0, alcas: alcas.slice(0, 3), regra: [...document.querySelectorAll('style')].some(s => s.textContent.includes('.gallery-item-handle{cursor:grab;opacity:1;}'))}; });
    conferir('folha da galeria com a alça em opacidade 1', itens.regra, itens);
    if (itens.alcas.length) conferir('alça sólida e alinhada aos demais botões', itens.alcas.every(a => a.opacidade === '1' && (a.outra === null || (Math.abs(a.altura - a.outra) <= 2 && Math.abs(a.topo - a.topoOutra) <= 2))), itens.alcas);
    await page.locator('#btn-select-images').scrollIntoViewIfNeeded();
    await page.locator('#btn-select-images').dispatchEvent('mouseup'); await page.waitForTimeout(600);
    const quadro = page.frameLocator('.modal.iframePagina iframe');
    await quadro.locator('#c2f-files-app').waitFor({timeout: 30000});
    await page.waitForTimeout(1500);
    const vazia = await quadro.locator('body').evaluate(() => { const t = document.getElementById('c2f-pick-tray'), c = document.getElementById('c2f-pick-tray-confirm'), v = document.getElementById('c2f-pick-tray-empty'); return {visivel: !t.classList.contains('hidden') && t.getBoundingClientRect().height > 20, desligado: c.disabled, texto: c.textContent.trim(), orientacao: v.offsetParent !== null && v.textContent.trim().length > 10, cancelar: !!document.getElementById('c2f-pick-tray-cancel')}; });
    conferir('bandeja à vista sem seleção, com orientação e Concluir desligado', vazia.visivel && vazia.desligado && vazia.orientacao && vazia.cancelar && /Concluir|Finish/.test(vazia.texto), vazia);
    await foto(page, '09-seletor-vazio');
    const imagem = quadro.locator('.c2f-item:has(img) .c2f-sel').first();
    if (await imagem.count() === 0) { conferir('há imagem na pasta para marcar', true, 'pasta sem imagens: conclusão não exercitada'); await page.keyboard.press('Escape'); return; }
    // A galeria ignora imagem que já está nela: marca todas as da pasta para garantir ao menos uma nova.
    const total = await quadro.locator('.c2f-item:has(img) .c2f-sel').count();
    const rodape = await page.evaluate(() => { const a = document.querySelector('.modal.iframePagina > .actions'); return a ? getComputedStyle(a).display : 'ausente'; });
    conferir('um Cancelar só: o rodapé do modal sai de cena no seletor múltiplo', rodape === 'none' || rodape === 'ausente', rodape);
    // Marca pelos botões Selecionar de cada arquivo, que é como o seletor é usado na galeria.
    await quadro.locator('body').evaluate(() => document.querySelectorAll('.c2f-item').forEach(i => { const b = i.querySelector('.c2f-select'); if (i.querySelector('img') && b && !i.querySelector('.c2f-sel').checked) b.click(); }));
    await page.waitForTimeout(300);
    const aindaFechada = await page.evaluate(n => document.querySelectorAll('#gallery-items .gallery-item').length === n, itens.n);
    conferir('o botão Selecionar marca o arquivo sem enviá-lo na hora', aindaFechada);
    await page.waitForTimeout(400);
    const marcada = await quadro.locator('body').evaluate(() => ({miniaturas: document.querySelectorAll('#c2f-pick-tray-thumbs .c2f-pick-thumb').length, ligado: !document.getElementById('c2f-pick-tray-confirm').disabled, remover: !!document.querySelector('.c2f-pick-thumb-remove')}));
    conferir('uma miniatura por arquivo marcado, com × e Concluir ligado', marcada.miniaturas >= 1 && marcada.miniaturas <= total && marcada.ligado && marcada.remover, {total, marcada});
    await foto(page, '09-seletor-marcado');
    await quadro.locator('#c2f-pick-tray-confirm').click(); await page.waitForTimeout(1200);
    const depois = await page.evaluate(() => { const m = document.querySelector('.modal.iframePagina'); return {aberto: !!m && m.offsetParent !== null && !m.classList.contains('hidden'), n: document.querySelectorAll('#gallery-items .gallery-item').length}; });
    conferir('Concluir aplica a seleção na galeria e fecha o modal', !depois.aberto && (depois.n > itens.n || total <= itens.n), {antes: itens.n, total, depois});
    await foto(page, '09-galeria-depois');
  });

  for (const [n, modulo, widget] of [['10', 'pages-index', true], ['11', 'publisher', false], ['12', 'publisher-highlights', true], ['13', 'publisher-index', true]]) {
    await grupo(`${n}. ${modulo}: mapeamento em 3 colunas, selects e mensagens`, async () => {
      const rota = await primeiroLink(page, modulo + '/', modulo + '/editar/') || modulo + '/adicionar/';
      conferir('HTTP 200', await abrir(page, rota) === 200, rota);
      await page.waitForTimeout(800);
      const m = await mapa(page);
      conferir('três colunas iguais, lado a lado, na largura do container', m && m.n === 3 && m.mesmaLinha && Math.max(...m.larguras) - Math.min(...m.larguras) <= 2 && m.largura >= m.pai - 40 && m.largura > 600, m);
      conferir('cabeçalhos em destaque', m && m.titulos.length === 3 && m.titulos.every(t => Number(t.peso) >= 700 && !/rgba\(0, 0, 0, 0\)/.test(t.fundo)), m && m.titulos);
      if (modulo === 'publisher') conferir('títulos pedidos', m.titulos.map(t => t.texto).join('|').toLowerCase() === 'variáveis do modelo|campos do publicador|vinculados (variável/campo)', m.titulos);
      conferir('selects de modelo e de regra com o controle do painel', (await selectsSemControle(page)).length === 0 && await page.evaluate(() => !!document.querySelector('.c2fc-select > #template_id')), await selectsSemControle(page));
      if (modulo !== 'publisher') { const caixas = await caixasEmLinha(page); conferir('caixas informativas em linha', caixas.every(c => c.ok), caixas.filter(c => !c.ok)); }
      if (widget) {
        await page.click('a[data-tab="hep-widget"]'); await page.waitForTimeout(400);
        const c = await copiarEmLinha(page);
        conferir('Copiar compacto ao lado do campo, na aba do widget', c && c.visivel && c.compacto && c.mesmaLinha && c.altura <= 36, c);
        await foto(page, n + '-' + modulo + '-widget');
      } else {
        const acoes = await page.evaluate(() => { const l = document.querySelector('#fields-schema-container .field-row'); if (!l) return null; const g = l.querySelector('.fields > .two.wide'); const b = [...g.querySelectorAll('.c2fc-botao')].filter(x => x.offsetParent !== null).map(x => x.getBoundingClientRect()); const cx = l.querySelector('.checkbox'); const ci = cx.querySelector('input').getBoundingClientRect(), cl = cx.querySelector('label').getBoundingClientRect(); return {direcao: getComputedStyle(g).flexDirection, mesmaLinha: b.every(r => Math.abs(r.top - b[0].top) < 2), n: b.length, simAlinhado: Math.abs((ci.top + ci.height / 2) - (cl.top + cl.height / 2)) < 5}; });
        if (acoes) { conferir('ações do campo personalizado lado a lado', acoes.direcao === 'row' && acoes.mesmaLinha, acoes); conferir('caixa e "Sim" alinhados', acoes.simAlinhado, acoes); }
        else conferir('publicador sem campos personalizados para medir', true);
      }
      conferir('nenhum erro de script', page._erros.length === 0, page._erros);
      await foto(page, n + '-' + modulo, true);
    });
  }

  await grupo('12b. publisher-highlights/adicionar: selects do painel', async () => {
    conferir('HTTP 200', await abrir(page, 'publisher-highlights/adicionar/') === 200);
    const s = await page.evaluate(() => ['publisher_id', 'template_id'].map(id => { const e = document.getElementById(id); return {id, controle: !!e.closest('.c2fc-select'), classe: e.classList.contains('c2fc-campo-selecao'), nativoVisivel: getComputedStyle(e).opacity !== '0' && e.getBoundingClientRect().height > 10 && !e.classList.contains('c2fc-nativo')}; }));
    conferir('Publicador Origem e Modelo com c2fc-campo-selecao e controle montado', s.every(x => x.controle && x.classe && !x.nativoVisivel), s);
    await foto(page, '12b-highlights-adicionar');
  });

  await grupo('14. publisher-pages: listagem e edição', async () => {
    conferir('HTTP 200', await abrir(page, 'publisher-pages/') === 200);
    const f = await page.evaluate(() => { const r = [...document.querySelectorAll('input[name="tipo"]')].map(i => i.closest('label').getBoundingClientRect()); const ref = document.querySelector('fieldset'); return {n: r.length, mesmaLinha: r.every(x => Math.abs(x.top - r[0].top) < 2), fieldset: !!ref, larguras: r.map(x => Math.round(x.width))}; });
    conferir('filtro de tipo com três opções numa linha, no padrão do admin-paginas', f.n === 3 && f.mesmaLinha && f.fieldset && f.larguras.every(l => l < 160), f);
    conferir('selects do filtro com o controle do painel', (await selectsSemControle(page)).length === 0, await selectsSemControle(page));
    await foto(page, '14-publisher-pages-lista');
    await page.check('input[name="tipo"][value="ambos"]').catch(() => {});
    await page.waitForLoadState('networkidle').catch(() => {});
    const rota = await page.evaluate(() => { const a = [...document.querySelectorAll('a[href]')].find(x => x.getAttribute('href').includes('publisher-pages/editar/')); return a ? new URL(a.href).pathname.slice(1) + new URL(a.href).search : null; }) || await primeiroLink(page, 'publisher-pages/', 'publisher-pages/editar/');
    // Página de documentação com exemplos de código: é o conteúdo que quebrava o editor quando ia sem escape.
    const comCodigo = 'publisher-pages/editar/?id=docs-sdd-00-baseline-architecture';
    const alvo = (await abrir(page, comCodigo) === 200 && await page.locator('.publisher-pages-form').count()) ? comCodigo : rota;
    conferir('HTTP 200 na edição', await abrir(page, alvo) === 200, alvo);
    await page.waitForTimeout(1500);
    const e = await page.evaluate(() => { const sel = document.querySelector('.publisher-pages-form .c2fc-campo'); const botoes = document.querySelector('.publisher-pages-form a[href*="publisher/editar/"]'); const mover = document.querySelector('.mover-publicador-btn'); const rs = sel.getBoundingClientRect(), rb = botoes ? botoes.getBoundingClientRect() : null, rm = mover ? mover.getBoundingClientRect() : null; const cont = document.querySelector('.publisher-pages-form'); const fim = cont.getBoundingClientRect().bottom; const fora = [...document.querySelectorAll('#_gestor-interface-edit-dados iframe, #_gestor-interface-edit-dados pre, #_gestor-interface-edit-dados .CodeMirror')].filter(x => x.offsetParent !== null && x.getBoundingClientRect().top > fim + 2).map(x => x.tagName + '#' + x.id); const editor = document.querySelector('.publisher-pages-form .menuContainerPagina, .publisher-pages-form [data-tab]'); return {distancia: rb ? Math.round(rb.top - rs.bottom) : null, lado: rb && rm ? Math.round(rm.left - rb.right) : null, mesmaLinha: rb && rm ? Math.abs(rb.top - rm.top) < 2 : null, fora, editorDentro: !!editor, larguraPagina: document.documentElement.scrollWidth <= innerWidth + 1}; });
    if (e.distancia !== null) conferir('Editar publicador e Mover publicação com respiro do select e entre si', e.distancia >= 8 && (e.lado === null || (e.lado >= 6 && e.mesmaLinha)), e);
    conferir('nada do editor (iframe, código) aparece fora do formulário da página', e.fora.length === 0 && e.editorDentro && e.larguraPagina, e);
    const estrutura = await page.evaluate(() => { const comp = document.querySelector('.publisher-pages-form .html-editor-component'); const seo = [...document.querySelectorAll('[data-tab="seo-compartilhamento"]')].find(x => x.tagName !== 'A'); const h = [...document.querySelectorAll('.publisher-pages-form h4.publisher-fields-header')].map(x => { const s = x.querySelector('svg').getBoundingClientRect(); return Math.round(x.getBoundingClientRect().height) <= 36 && s.width > 0; }); const etiquetas = [...document.querySelectorAll('.publisher-pages-form .field-variable > span')].filter(x => x.offsetParent !== null).map(x => Math.round(x.getBoundingClientRect().height)); return {seoDentro: !!seo && !!comp && comp.contains(seo), modalDentro: !!comp && !!comp.querySelector('.html-editor-container'), titulos: h, etiquetas}; });
    conferir('o editor chega inteiro: painel de SEO e contêiner dentro do componente', estrutura.seoDentro && estrutura.modalDentro, estrutura);
    conferir('títulos e etiquetas de variável com ícone e texto na mesma linha', estrutura.titulos.length === 2 && estrutura.titulos.every(Boolean) && estrutura.etiquetas.length > 0 && estrutura.etiquetas.every(a => a <= 24), estrutura);
    const visiveis = () => page.evaluate(() => { const comp = document.querySelector('.publisher-pages-form .html-editor-component'); const ativa = comp.querySelector('.menuContainerPagina a.active'); return {ativa: ativa && ativa.getAttribute('data-tab'), paineis: [...comp.querySelectorAll(':scope > [data-tab]')].filter(x => x.offsetParent !== null).map(x => x.getAttribute('data-tab'))}; });
    const trocas = [];
    for (const aba of ['modelos', 'assistente-ia', 'visualizacao-codigo', 'seo-compartilhamento', 'visualizacao-pagina', 'seo-compartilhamento']) {
      await page.click('.publisher-pages-form .menuContainerPagina a[data-tab="' + aba + '"]'); await page.waitForTimeout(500);
      const v = await visiveis();
      if (v.ativa !== aba || v.paineis.length !== 1 || v.paineis[0] !== aba) trocas.push({aba, v});
    }
    conferir('cada aba do editor mostra só o próprio painel (inclusive SEO)', trocas.length === 0, trocas);
    await page.reload({waitUntil: 'networkidle'}); await page.waitForTimeout(1500);
    const recarga = await visiveis();
    conferir('depois de recarregar, a aba marcada e o painel visível são os mesmos', recarga.paineis.length === 1 && recarga.paineis[0] === recarga.ativa, recarga);
    const caixas = await caixasEmLinha(page);
    conferir('mensagens informativas em linha', caixas.every(c => c.ok), caixas.filter(c => !c.ok));
    conferir('nenhuma tag de variável crua na tela', (await textoCru(page)).length === 0, await textoCru(page));
    conferir('nenhum erro de script', page._erros.length === 0, page._erros);
    await foto(page, '14-publisher-pages-editar', true);
  });

  await grupo('15. variables: sem tag crua e ícones sem contorno', async () => {
    conferir('HTTP 200', await abrir(page, 'variables/') === 200);
    await page.waitForTimeout(600);
    conferir('nenhuma variável de sistema crua na tela', (await textoCru(page)).length === 0, await textoCru(page));
    const v = await page.evaluate(() => { const dica = s => [...document.querySelectorAll(s)].slice(0, 3).map(b => { const i = b.querySelector('svg'); return {dica: b.getAttribute('data-c2f-dica') || '', rotulo: b.getAttribute('aria-label') || '', contorno: i ? getComputedStyle(i).outlineStyle + ' ' + getComputedStyle(i).outlineWidth : 'sem svg', classe: b.className.slice(0, 50)}; }); return {editar: dica('.variaveisCont .variavelBtnEditar'), excluir: dica('.variaveisCont .variavelBtnExcluir'), adicionar: (document.querySelector('.variavelBtnAdicionar') || {}).textContent, fomantic: document.querySelectorAll('#_gestor-configuracao-administracao i.icon').length}; });
    const botoes = v.editar.concat(v.excluir);
    if (botoes.length) {
      conferir('editar e excluir com dica resolvida', botoes.every(b => b.dica.length > 3 && !/\[\[/.test(b.dica + b.rotulo)), botoes);
      conferir('ícones de editar e excluir sem contorno', botoes.every(b => /^none|0px$/.test(b.contorno) || b.contorno.startsWith('none')), botoes);
      conferir('botões no padrão c2fc-botao', botoes.every(b => /c2fc-botao/.test(b.classe)), botoes);
    } else conferir('módulo selecionado sem variáveis para medir', true);
    conferir('botão Adicionar com texto resolvido', !!v.adicionar && !/\[\[/.test(v.adicionar), v.adicionar);
    await foto(page, '15-variables', true);
  });

  await grupo('16. admin-cron e admin-environment: selects, botões e dicas', async () => {
    for (const modulo of ['admin-cron', 'admin-environment']) {
      conferir(modulo + ': HTTP 200', await abrir(page, modulo + '/') === 200);
      await page.waitForTimeout(800);
      const s = await page.evaluate(() => { const doConteudo = [...document.querySelectorAll('select')].filter(x => !x.closest('header, aside, nav, dialog, [id*="topbar"], [id*="sidebar"]')); return {total: doConteudo.length, montados: doConteudo.filter(x => x.parentElement.classList.contains('c2fc-select')).length, fora: doConteudo.filter(x => !x.parentElement.classList.contains('c2fc-select')).map(x => x.id || x.name)}; });
      conferir(modulo + ': todos os selects com o controle do painel', s.total > 0 && s.total === s.montados, s);
      const b = await page.evaluate(() => [...document.querySelectorAll('#c2f-admin-conteudo button, main button')].filter(x => !x.classList.contains('c2fc-aba') && !x.closest('.c2fc-select') && !x.closest('#c2f-admin-topbar, header') && x.id !== 'cron-form-cancelar' && x.id !== 'cron-form-salvar').map(x => ({id: x.id || x.textContent.trim().slice(0, 20), classe: /(^| )c2fc-botao( |$)/.test(x.className), dica: x.getAttribute('data-c2f-dica') || ''})));
      conferir(modulo + ': botões de ação com classe oficial e dica preenchida', b.length >= 4 && b.every(x => x.classe && x.dica.length > 3 && !/\[\[/.test(x.dica)), b.filter(x => !x.classe || x.dica.length <= 3 || /\[\[/.test(x.dica)));
      conferir(modulo + ': nenhum erro de script', page._erros.length === 0, page._erros);
      await foto(page, '16-' + modulo, true);
    }
    // No cron, o formulário escreve `select.value` por script: o controle precisa acompanhar.
    await abrir(page, 'admin-cron/');
    await page.click('#cron-btn-new'); await page.waitForTimeout(400);
    const c = await page.evaluate(() => { const s = document.getElementById('cron-form-frequencia'); const alvo = s.options[s.options.length - 1]; s.value = alvo.value; return {rotulo: s.closest('.c2fc-select').querySelector('.c2fc-select-gatilho').textContent.trim(), esperado: alvo.textContent.trim()}; });
    conferir('admin-cron: o controle acompanha select.value escrito por script', c.rotulo === c.esperado, c);
    await page.click('#cron-form-frequencia ~ .c2fc-select-gatilho'); await page.waitForTimeout(300);
    const painel = await page.evaluate(() => { const p = [...document.querySelectorAll('.c2fc-select-painel')].find(x => !x.classList.contains('c2fc-oculto')); if (!p) return null; const r = p.getBoundingClientRect(); const topo = document.elementFromPoint(r.left + 20, r.top + 20); return {visivel: r.height > 40, naFrente: !!topo && p.contains(topo)}; });
    conferir('admin-cron: lista do select abre por cima do modal', painel && painel.visivel && painel.naFrente, painel);
    await foto(page, '16-admin-cron-modal');
  });

  // ================================================================ SITE
  await grupo('17. coupons: máscara monetária', async () => {
    const rota = await primeiroLink(page, 'coupons/', 'coupons/edit/') || 'coupons/add/';
    conferir('HTTP 200', await abrir(page, rota) === 200, rota);
    await page.click('a[data-tab], [data-c2f-aba]').catch(() => {});
    const minimo = page.locator('#coupon-min_subtotal');
    await page.evaluate(() => { const e = document.getElementById('coupon-min_subtotal'); for (let n = e; n && n !== document.body; n = n.parentElement) { if (getComputedStyle(n).display === 'none') { n.classList.remove('hidden'); n.style.display = 'block'; } } });
    await minimo.fill(''); await minimo.pressSequentially('abc1234x'); await page.waitForTimeout(150);
    const m = await minimo.inputValue();
    conferir('Valor Mínimo da Compra aceita só número e formata como moeda', /12,34/.test(m) && !/[abcx]/.test(m), m);
    const estado = async () => page.evaluate(() => { const p = document.querySelector('[data-coupon-valor="percent"]'), d = document.querySelector('[data-coupon-valor="fixed"]'); return {tipo: document.querySelector('select[name="discount_type"]').value, percentual: {visivel: p.offsetParent !== null, nome: p.name, desligado: p.disabled, valor: p.value}, dinheiro: {visivel: d.offsetParent !== null, nome: d.name, desligado: d.disabled, valor: d.value}}; });
    await page.evaluate(() => { const s = document.querySelector('select[name="discount_type"]'); c2fControles.de(s).definir('fixed'); });
    await page.waitForTimeout(250);
    const valor = page.locator('[data-coupon-valor="fixed"]');
    await valor.fill(''); await valor.pressSequentially('q5000'); await page.waitForTimeout(150);
    const fixo = await estado();
    conferir('Valor (desconto fixo) com máscara monetária, e só ele é enviado', fixo.dinheiro.visivel && fixo.dinheiro.nome === 'discount_value' && /50,00/.test(fixo.dinheiro.valor) && !/q/.test(fixo.dinheiro.valor) && fixo.percentual.desligado && !fixo.percentual.visivel && fixo.percentual.nome === '', fixo);
    const enviado = await page.evaluate(() => { const f = document.querySelector('[data-coupon-valor="fixed"]').form; const d = new FormData(f); return {desconto: d.getAll('discount_value'), minimo: d.get('min_subtotal')}; });
    conferir('o formulário transporta decimais sem símbolo', enviado.desconto.length === 1 && enviado.desconto[0] === '50.00' && enviado.minimo === '12.34', enviado);
    await foto(page, '17-coupons-fixo');
    await page.evaluate(() => { const s = document.querySelector('select[name="discount_type"]'); c2fControles.de(s).definir('percent'); });
    await page.waitForTimeout(250);
    const pct = page.locator('[data-coupon-valor="percent"]');
    await pct.fill(''); await pct.pressSequentially('1x5,759'); await page.waitForTimeout(150);
    const percentual = await estado();
    conferir('Valor (percentual) aceita só número com até duas casas', percentual.percentual.visivel && percentual.percentual.nome === 'discount_value' && percentual.percentual.valor === '15,75' && percentual.dinheiro.desligado, percentual);
  });

  await grupo('18. analytics-manager e orders/buyer-form: selects do painel', async () => {
    for (const rota of ['analytics-manager/', 'analytics-manager/pipeline/', 'orders/buyer-form/']) {
      const status = await abrir(page, rota);
      if (status !== 200) { conferir(rota + ': HTTP 200', false, status); continue; }
      await page.waitForTimeout(500);
      const sem = await selectsSemControle(page);
      conferir(rota + ': selects visíveis com o controle do painel', sem.length === 0 && await selectsMontados(page) > 0, {sem, montados: await selectsMontados(page)});
      conferir(rota + ': nenhum erro de script', page._erros.length === 0, page._erros);
      await foto(page, '18-' + rota.replace(/\W+/g, '-'));
    }
  });

  await grupo('19. products-index: modelos compactos e copiar em linha', async () => {
    const rota = await primeiroLink(page, 'products-index/', 'products-index/editar/') || 'products-index/adicionar/';
    conferir('HTTP 200', await abrir(page, rota) === 200, rota);
    await page.click('a[data-tab="hep-editor"], [data-tab="hep-editor"]').catch(() => {});
    await page.waitForTimeout(500);
    await page.click('a[data-tab="modelos"], [data-tab="modelos"].item').catch(() => {});
    await page.waitForSelector('#modelos-cards .modelo-card', {timeout: 20000}).catch(() => {});
    await page.waitForTimeout(600);
    const g = await page.evaluate(() => { const c = document.getElementById('modelos-cards'); if (!c) return null; const cartoes = [...c.querySelectorAll('.modelo-card')].filter(x => x.offsetParent !== null).map(x => x.getBoundingClientRect()); return {n: cartoes.length, maior: Math.round(Math.max(0, ...cartoes.map(r => r.width)))}; });
    if (g && g.n) conferir('cartões de modelos compactos (até 240 px)', g.maior <= 241, g); else conferir('aba Modelos sem cartões neste alvo', true, g);
    await foto(page, '19-products-index-modelos');
    await page.click('a[data-tab="hep-widget"], [data-tab="hep-widget"]'); await page.waitForTimeout(400);
    const c = await copiarEmLinha(page);
    conferir('Copiar compacto ao lado do campo', c && c.visivel && c.compacto && c.mesmaLinha && c.altura <= 36, c);
    const m = await mapa(page);
    conferir('mapeamento em três colunas iguais', m && m.n === 3 && m.mesmaLinha && Math.max(...m.larguras) - Math.min(...m.larguras) <= 2, m);
    await foto(page, '19-products-index-widget');
  });

  await grupo('20. shipping-methods: conformidade', async () => {
    conferir('HTTP 200', await abrir(page, 'shipping-methods/') === 200);
    const s = await page.evaluate(() => { const principal = document.querySelector('main.mx-auto') || document.body; return {cartoes: [...principal.querySelectorAll('section')].map(x => getComputedStyle(x).borderRadius + '|' + getComputedStyle(x).backgroundColor), entradas: [...principal.querySelectorAll('input:not([type=hidden]):not([type=checkbox]), textarea')].map(x => x.classList.contains('c2fc-campo-entrada')), botoes: [...principal.querySelectorAll('button, a.c2fc-botao')].filter(x => !x.closest('.c2fc-select')).map(x => ({c: /(^| )c2fc-botao( |$)/.test(x.className), d: (x.getAttribute('data-c2f-dica') || '')})), selects: [document.querySelectorAll('main.mx-auto select').length, document.querySelectorAll('main.mx-auto .c2fc-select > select').length]}; });
    conferir('seções em cartões brancos arredondados', s.cartoes.length === 3 && s.cartoes.every(c => c === '12px|rgb(255, 255, 255)'), s.cartoes);
    conferir('entradas com c2fc-campo-entrada e select com o controle', s.entradas.length === 3 && s.entradas.every(Boolean) && s.selects[0] === 1 && s.selects[1] === 1, s);
    conferir('botões com classe oficial e dica resolvida', s.botoes.length >= 2 && s.botoes.every(b => b.c && b.d.length > 5 && !/\[\[/.test(b.d)), s.botoes);
    await foto(page, '20-shipping-methods', true);
  });

  await grupo('21. products/edit: dicas, botão de imagem e histórico', async () => {
    const rota = await primeiroLink(page, 'products/', 'products/edit/');
    conferir('HTTP 200', await abrir(page, rota) === 200, rota);
    const b = await page.evaluate(() => [...document.querySelectorAll('[data-id="status"], [data-id="excluir"]')].map(x => ({id: x.dataset.id, dica: x.getAttribute('data-c2f-dica'), rotulo: x.textContent.trim()})));
    conferir('Desativar/Ativar e Excluir com dica explicativa', b.length === 2 && b.every(x => x.dica && x.dica.length > x.rotulo.length), b);
    await page.hover('[data-id="status"]'); await page.waitForTimeout(250); await foto(page, '21-products-dica');
    conferir('configuração do seletor de imagem presente na página', await page.evaluate(() => !!(gestor.interface && gestor.interface.imagepick && gestor.interface.imagepick.modal && gestor.interface.imagepick.modal.url)));
    await page.locator('._gestor-widgetImage-btn-add').first().click(); await page.waitForTimeout(600);
    const quadro = page.frameLocator('.modal.iframePagina iframe');
    const abriu = await quadro.locator('#c2f-files-app').waitFor({timeout: 30000}).then(() => true).catch(() => false);
    const m = await page.evaluate(() => { const x = document.querySelector('.modal.iframePagina'); return {visivel: !!x && x.offsetParent !== null, src: (x.querySelector('iframe').getAttribute('src') || '').slice(0, 80)}; });
    conferir('o botão Adicionar de Imagem (URL) abre o gerenciador de arquivos no modal', abriu && m.visivel && /admin-arquivos/.test(m.src), m);
    await foto(page, '21-products-seletor');
  });

  await grupo('22. stripe-products: título antes da sincronização', async () => {
    conferir('HTTP 200', await abrir(page, 'stripe-products/') === 200);
    const o = await page.evaluate(() => { const hs = [...document.querySelectorAll('#c2f-admin-conteudo h1, main h1')].filter(h => h.offsetParent !== null); const p = document.querySelector('.stripe-products-sync-panel'); const l = document.getElementById('_gestor-interface-listar'); return {titulos: hs.map(h => h.textContent.trim()), y: hs[0] && Math.round(hs[0].getBoundingClientRect().top), painel: p && Math.round(p.getBoundingClientRect().top), lista: l && Math.round(l.getBoundingClientRect().top)}; });
    conferir('um título só, acima da caixa de sincronização, com a listagem depois', o.titulos.length === 1 && o.titulos[0].length > 3 && o.y < o.painel && o.painel < o.lista, o);
    await foto(page, '22-stripe-products');
  });

  await grupo('23. sales-reports: filtro de tipo e campos', async () => {
    conferir('HTTP 200', await abrir(page, 'sales-reports/?type=digital') === 200);
    const antes = await page.evaluate(() => { const s = document.querySelector('select[name="type"]'); return {valor: s.value, rotulo: s.closest('.c2fc-select') && s.closest('.c2fc-select').querySelector('.c2fc-select-gatilho').textContent.trim(), noRotulo: !!s.closest('label'), entradas: [...document.querySelectorAll('form[method="get"] input')].every(i => i.classList.contains('c2fc-campo-entrada')), botao: document.querySelector('form[method="get"] button[type="submit"]').className}; });
    conferir('tipo filtrado aparece no controle, que não fica dentro do rótulo', antes.valor === 'digital' && antes.rotulo && !antes.noRotulo, antes);
    conferir('entradas e botão no padrão do painel', antes.entradas && /c2fc-botao c2fc-botao-primario/.test(antes.botao), antes);
    await page.click('#sr-campo-type ~ .c2fc-select-gatilho'); await page.waitForTimeout(250);
    await page.locator('.c2fc-select:has(#sr-campo-type) .c2fc-select-opcao').first().click(); await page.waitForTimeout(300);
    const escolhido = await page.evaluate(() => { const s = document.querySelector('select[name="type"]'); return {valor: s.value, rotulo: s.closest('.c2fc-select').querySelector('.c2fc-select-gatilho').textContent.trim(), aberto: !s.closest('.c2fc-select').querySelector('.c2fc-select-painel').classList.contains('c2fc-oculto')}; });
    conferir('escolher "Todos" limpa o filtro no controle', escolhido.valor === '' && escolhido.rotulo.length > 2 && !escolhido.aberto, escolhido);
    await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.click('form[method="get"] button[type="submit"]')]);
    const depois = await page.evaluate(() => ({url: location.search, valor: document.querySelector('select[name="type"]').value}));
    conferir('o relatório é refeito sem o filtro de tipo', /[?&]type=(&|$)/.test(depois.url) && depois.valor === '', depois);
    await foto(page, '23-sales-reports');
  });

  await grupo('24. product-types/edit: selects, campos e dicas', async () => {
    const rota = await primeiroLink(page, 'product-types/', 'product-types/edit/') || 'product-types/add/';
    conferir('HTTP 200', await abrir(page, rota) === 200, rota);
    if (await page.locator('#product-type-fields [data-campo]').count() === 0) { await page.click('#product-type-add-field'); await page.waitForTimeout(400); }
    const t = await page.evaluate(() => { const l = document.querySelector('#product-type-fields [data-campo]'); const sel = l.querySelector('[data-f="type"]'); const cx = l.querySelector('[data-f="mandatory"]'); return {modelo: !!document.querySelector('.c2fc-select > #product-type-template'), linha: !!sel.closest('.c2fc-select'), entradas: [...l.querySelectorAll('input[type=text]')].every(i => i.classList.contains('c2fc-campo-entrada')), cursor: getComputedStyle(cx).cursor + '/' + getComputedStyle(cx.closest('label')).cursor, acoes: [...l.querySelectorAll('[data-acao]')].map(b => ({c: /c2fc-botao/.test(b.className), d: b.getAttribute('data-c2f-dica') || ''})), topo: [...document.querySelectorAll('#product-type-add-field, #product-type-import-fields')].map(b => b.getAttribute('data-c2f-dica') || '')}; });
    conferir('select do modelo e select de tipo da linha com o controle do painel', t.modelo && t.linha, t);
    conferir('entradas da linha com c2fc-campo-entrada e checkbox com cursor de clique', t.entradas && t.cursor === 'pointer/pointer', t);
    conferir('ações da linha e botões da seção com dica resolvida', t.acoes.length === 3 && t.acoes.every(a => a.c && a.d.length > 2) && t.topo.every(d => d.length > 5 && !/\[\[/.test(d)), t);
    await foto(page, '24-product-types', true);
  });

  await grupo('25. host-manager: barra em todas as telas, menu e ícone', async () => {
    const telas = ['', 'usuarios/', 'planos/', 'hestiacp/', 'logs/', 'updates/', 'distribuido/'];
    const barras = [];
    for (const tela of telas) {
      const status = await abrir(page, 'host-manager/' + tela);
      const b = await page.evaluate(() => { const n = document.querySelector('nav[data-hm-nav]'); if (!n) return null; const r = n.getBoundingClientRect(); const atual = n.querySelector('[aria-current="page"]'); return {y: Math.round(r.top + scrollY), visivel: r.height > 20, itens: [...n.querySelectorAll(':scope > a, :scope > button')].map(x => x.textContent.trim()), atual: atual && atual.textContent.trim(), destaque: atual && getComputedStyle(atual).backgroundColor}; });
      barras.push({tela, status, b});
    }
    conferir('as sete telas abrem com a barra do módulo no topo', barras.every(x => x.status === 200 && x.b && x.b.visivel && x.b.itens.length === 6 && x.b.y < 260), barras.filter(x => !(x.status === 200 && x.b && x.b.visivel)));
    conferir('mesmos itens em todas as telas e um item marcado como atual', new Set(barras.map(x => x.b && x.b.itens.join('|'))).size === 1 && barras.every(x => x.b && x.b.atual), barras.map(x => x.b && x.b.atual));
    await foto(page, '25-host-manager-interna');
    await abrir(page, 'host-manager/usuarios/');
    await page.click('#btn-host-actions > summary'); await page.waitForTimeout(200);
    const aberto = await page.evaluate(() => document.getElementById('btn-host-actions').open);
    await foto(page, '25-host-manager-menu');
    await page.mouse.click(400, 500); await page.waitForTimeout(250);
    const fechouFora = await page.evaluate(() => !document.getElementById('btn-host-actions').open);
    conferir('"Mais ações" abre e fecha ao clicar fora', aberto && fechouFora, {aberto, fechouFora});
    await page.click('#btn-host-actions > summary'); await page.waitForTimeout(150);
    await page.keyboard.press('Escape'); await page.waitForTimeout(150);
    conferir('"Mais ações" fecha com Esc', await page.evaluate(() => !document.getElementById('btn-host-actions').open));
    await abrir(page, 'host-manager/');
    await page.waitForTimeout(1500);
    const icone = await page.evaluate(() => { const s = document.querySelector('#sse-status-badge svg'); return s ? {contorno: getComputedStyle(s).outlineStyle, largura: getComputedStyle(s).outlineWidth, borda: getComputedStyle(s).borderTopWidth, classe: s.getAttribute('class')} : null; });
    conferir('ícone de "atualização por consulta" sem borda nem contorno', icone && (icone.contorno === 'none' || icone.largura === '0px') && icone.borda === '0px', icone);
    conferir('nenhum erro de script', page._erros.length === 0, page._erros);
    await foto(page, '25-host-manager', true);
  });

  // ================================================================ PENTE FINO (terceira passada)
  await grupo('27. pente fino: envios, planos, associados, variáveis e títulos', async () => {
    const envio = await primeiroLink(page, 'forms-submissions/', 'forms-submissions/view/');
    if (envio) { await abrir(page, envio); conferir('forms-submissions/view: select de status com o controle do painel', await page.evaluate(() => !!document.querySelector('.c2fc-select > #form-status-select')), envio); }
    else conferir('forms-submissions sem envio para abrir', true);

    const plano = await primeiroLink(page, 'subscriptions-plans/', 'subscriptions-plans/editar/');
    conferir('subscriptions-plans/editar abre', await abrir(page, plano) === 200, plano);
    await page.waitForTimeout(1200);
    const troca = await page.evaluate(async () => {
      const s = document.getElementById('template_id'); const outro = [...s.options].find(o => o.value && o.value !== s.value && !/-modificado$/.test(o.value)); if (!outro) return null;
      const ler = () => { const cm = document.querySelector('textarea[name="html"]'); const ed = cm && cm.nextElementSibling && cm.nextElementSibling.CodeMirror; return ed ? ed.getValue() : (cm || {}).value; };
      const antes = ler();
      let redesenhos = 0; const original = window.html_editor_refresh_preview; window.html_editor_refresh_preview = function () { redesenhos++; return original && original.apply(this, arguments); };
      window.c2fControles.de(s).definir(outro.value);
      await new Promise(r => setTimeout(r, 2500));
      const depois = ler();
      return {mudou: antes !== depois, redesenhos, tamanho: String(depois || '').length};
    });
    if (troca) conferir('trocar o modelo grava o HTML novo no editor e redesenha a visualização dele', troca.mudou && troca.redesenhos >= 1 && troca.tamanho > 20, troca);
    else conferir('plano com um modelo só: troca não exercitada', true);

    const associado = await primeiroLink(page, 'affiliates/', 'affiliates/edit/');
    conferir('affiliates/edit abre', await abrir(page, associado) === 200, associado);
    const taxa = page.locator('#affiliate-rate_subscriptions');
    await taxa.fill(''); await taxa.pressSequentially('ab12,759x'); await page.waitForTimeout(150);
    const a = await page.evaluate(() => ({valor: document.getElementById('affiliate-rate_subscriptions').value, selects: document.querySelectorAll('.c2fc-select > select').length, nativos: [...document.querySelectorAll('form.interfaceFormPadrao select')].filter(x => !x.closest('.c2fc-select')).length, entradas: [...document.querySelectorAll('form.interfaceFormPadrao input[type=text], form.interfaceFormPadrao input[type=email], form.interfaceFormPadrao textarea')].filter(i => i.offsetParent !== null).every(i => i.classList.contains('c2fc-campo-entrada')), enviado: new FormData(document.getElementById('affiliate-rate_subscriptions').form).get('rate_subscriptions')}));
    conferir('comissão aceita só percentual (12,75) e o formulário leva 12.75', a.valor === '12,75' && a.enviado === '12.75', a);
    conferir('selects com o controle e campos de texto no padrão', a.selects >= 3 && a.nativos === 0 && a.entradas, a);
    await taxa.fill(''); await taxa.pressSequentially('250'); await page.waitForTimeout(100);
    conferir('percentual não passa de 100', await taxa.inputValue() === '100', await taxa.inputValue());
    await foto(page, '27-affiliates-edit', true);
    const detalhes = associado.replace('affiliates/edit/', 'affiliates/details/');
    conferir('affiliates/details abre', await abrir(page, detalhes) === 200, detalhes);
    const ajuste = page.locator('input[name="adjust_amount"]');
    await ajuste.fill(''); await ajuste.pressSequentially('zz1500'); await page.waitForTimeout(150);
    const d = await page.evaluate(() => ({valor: document.querySelector('input[name="adjust_amount"]').value, tipo: !!document.querySelector('.c2fc-select > select[name="adjust_type"]'), noRotulo: !!document.querySelector('select[name="adjust_type"]').closest('label'), botoes: [...document.querySelectorAll('form button[type="submit"]')].filter(b => b.offsetParent !== null && !b.closest('header, aside, nav')).map(b => /(^| )c2fc-botao( |$)/.test(b.className))}));
    conferir('valor do ajuste aceita só dinheiro (R$ 15,00)', /15,00/.test(d.valor) && !/z/.test(d.valor), d);
    conferir('select do tipo com o controle, fora do rótulo, e botões no padrão', d.tipo && !d.noRotulo && d.botoes.length > 0 && d.botoes.every(Boolean), d);
    await foto(page, '27-affiliates-details', true);

    conferir('variables abre', await abrir(page, 'variables/?id=product-types') === 200);
    await page.waitForTimeout(600);
    await page.locator('.variavelBtnAdicionarAbaixo, .variavelBtnAdicionar').last().dispatchEvent('mouseup'); await page.waitForTimeout(700);
    const caixa = await page.evaluate(() => { const c = [...document.querySelectorAll('.card.adicionar')].find(x => x.offsetParent !== null); if (!c) return null; const s = c.querySelector('select'); const casca = s && s.closest('.c2fc-select'); if (casca) casca.setAttribute('data-roteiro-tipo', '1'); return {select: !!s, casca: !!casca, viva: !!(casca && casca.c2fcViva), opcoes: s ? s.options.length : 0, antes: s ? s.value : null}; });
    conferir('a caixa de adicionar variável aparece com o select do tipo montado', caixa && caixa.select && caixa.casca && caixa.viva && caixa.opcoes > 3, caixa);
    if (caixa && caixa.casca) {
      await page.click('[data-roteiro-tipo] .c2fc-select-gatilho'); await page.waitForTimeout(250);
      const aberto = await page.evaluate(() => { const p = document.querySelector('[data-roteiro-tipo] .c2fc-select-painel'); return p.classList.contains('c2fc-oculto') ? 0 : p.querySelectorAll('.c2fc-select-opcao').length; });
      await page.locator('[data-roteiro-tipo] .c2fc-select-opcao').nth(3).click(); await page.waitForTimeout(300);
      const depois = await page.evaluate(() => { const c = document.querySelector('[data-roteiro-tipo]'); return {valor: c.querySelector('select').value, rotulo: c.querySelector('.c2fc-select-gatilho').textContent.trim()}; });
      conferir('o select do tipo abre, escolhe e mostra a opção escolhida', aberto > 3 && depois.valor !== caixa.antes && depois.rotulo.length > 1, {aberto, caixa, depois});
      await foto(page, '27-variables-adicionar');
    }

    conferir('admin-arquivos abre', await abrir(page, 'admin-arquivos/') === 200);
    await page.waitForTimeout(800);
    const imagem = '#c2f-files-list .c2f-file[data-tipoarq="image"] .c2f-name';
    // Sem imagem na raiz, entra nas pastas (até três) à procura de uma.
    for (let i = 0; i < 3 && !(await page.locator(imagem).count()) && await page.locator('#c2f-files-list .c2f-folder-open').count(); i++) { await page.locator('#c2f-files-list .c2f-folder-open').first().click(); await page.waitForTimeout(1200); }
    const miniatura = page.locator(imagem).first();
    if (await miniatura.count()) {
      await miniatura.click(); await page.waitForTimeout(900);
      const g = await page.evaluate(() => { const m = document.getElementById('c2f-gallery-modal'); if (!m || !m.open) return null; const r = m.getBoundingClientRect(); const cor = e => { const x = getComputedStyle(e); return {fundo: x.backgroundColor, texto: x.color, icone: !!e.querySelector('svg'), raio: x.borderTopLeftRadius}; }; return {esq: Math.round(r.left), dir: Math.round(innerWidth - r.right), topo: Math.round(r.top), base: Math.round(innerHeight - r.bottom), fechar: cor(m.querySelector('.c2f-gallery-close')), anterior: cor(m.querySelector('.c2f-gallery-prev')), proximo: cor(m.querySelector('.c2f-gallery-next')), acoes: [...m.querySelectorAll('.c2f-gallery-actions > *')].map(cor)}; });
      const legivel = c => c.fundo !== c.texto && !(c.fundo === 'rgb(255, 255, 255)' && /rgb\(2(4|5)\d, 2(4|5)\d, 2(4|5)\d\)/.test(c.texto));
      conferir('galeria da imagem abre centralizada na tela', g && Math.abs(g.esq - g.dir) <= 2 && Math.abs(g.topo - g.base) <= 2, g);
      conferir('fechar e setas redondos, escuros e com ícone; ações com ícone e texto legível', g && g.fechar.raio !== '4px' && [g.anterior, g.proximo].every(c => c.icone && legivel(c) && c.fundo !== 'rgb(255, 255, 255)') && g.acoes.length >= 3 && g.acoes.every(c => c.icone && legivel(c)), g);
      await foto(page, '27-admin-arquivos-galeria');
      await page.keyboard.press('Escape');
    } else conferir('pasta sem imagem para abrir a galeria', true);

    // Página pública de documentação que tinha sido gravada com o conteúdo inflado: volta a acompanhar a vizinha.
    const publica = async rota => { await abrir(page, rota); return page.evaluate(() => ({html: document.documentElement.outerHTML.length, titulo: (document.querySelector('h1') || {}).textContent, lateral: document.documentElement.scrollWidth <= innerWidth + 1, navs: document.querySelectorAll('nav').length, links: document.querySelectorAll('nav a').length, h1: document.querySelectorAll('h1').length})); };
    const quebrada = await publica('docs/sdd/00-baseline-architecture/');
    const vizinha = await publica('docs/sdd/01-project-synchronization/');
    conferir('docs/sdd/00-baseline-architecture com a mesma estrutura da página vizinha', quebrada.lateral && quebrada.navs === vizinha.navs && Math.abs(quebrada.links - vizinha.links) <= 2 && quebrada.h1 === vizinha.h1 && quebrada.html < vizinha.html * 3, {quebrada, vizinha});
    await abrir(page, 'docs/sdd/00-baseline-architecture/'); await foto(page, '27-docs-baseline');

    const titulos = [];
    for (const tela of ['', 'usuarios/', 'planos/', 'hestiacp/', 'logs/', 'updates/', 'distribuido/']) {
      await abrir(page, 'host-manager/' + tela);
      titulos.push(await page.evaluate(t => { const h = document.querySelector('[id^="host-manager-admin"] h1'); const cab = h.closest('.flex.items-start.gap-3') || h.parentElement; return {tela: t, titulo: h.textContent.trim().slice(0, 30), icones: cab.querySelectorAll('svg, i[data-lucide]').length}; }, tela));
    }
    conferir('host-manager: no máximo um ícone no título em cada uma das sete telas', titulos.every(t => t.icones <= 1), titulos);
    await foto(page, '27-host-manager-titulo');
  });

  // ================================================================ 390 px
  await grupo('26. telas alteradas em 390 px sem rolagem lateral', async () => {
    const celular = await novo(390, 844);
    const rotas = ['admin-arquivos/', 'admin-cron/', 'admin-environment/', 'variables/', 'publisher-pages/', 'shipping-methods/', 'sales-reports/', 'stripe-products/', 'host-manager/', 'host-manager/planos/', 'coupons/add/', 'publisher-highlights/adicionar/', 'menus/adicionar/', 'pages-index/adicionar/'];
    const estouros = [];
    for (const rota of rotas) {
      const status = await abrir(celular, rota);
      if (status !== 200 || !(await semRolagemLateral(celular))) estouros.push({rota, status, largura: await celular.evaluate(() => document.documentElement.scrollWidth)});
    }
    conferir(rotas.length + ' telas sem rolagem horizontal em 390 px', estouros.length === 0, estouros);
    await abrir(celular, 'host-manager/usuarios/'); await foto(celular, '26-host-manager-390');
    await abrir(celular, 'publisher-highlights/adicionar/'); await foto(celular, '26-highlights-390', true);
    await celular.context().close();
  });

  await browser.close();
  const falhas = resultados.filter(r => !r.ok);
  fs.writeFileSync(path.join(saida, 'resultado.json'), JSON.stringify({base, quando: new Date().toISOString(), total: resultados.length, aprovadas: resultados.length - falhas.length, falhas, resultados}, null, 1));
  console.log(`\n${resultados.length - falhas.length} de ${resultados.length} conferências`);
  process.exit(falhas.length ? 1 : 0);
})();
