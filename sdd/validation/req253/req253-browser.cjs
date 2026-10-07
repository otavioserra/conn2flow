// REQ-253 — módulo "Páginas de Lousa" na linha 3.1: criar pelo formulário, ver a página como visitante, trocar
// modelo e controles, recusar endereço repetido, desativar e excluir, e o acesso de quem não tem o módulo.
// Usa a lousa `roteiro-req-252-grade`, criada pelo roteiro da REQ-252. No fim exclui as páginas que criou.
// Uso: C2F_BASE=https://v3.1-conn2flow.local C2F_PLAYWRIGHT=<pasta> C2F_COOKIES=<cookies do administrador> [C2F_COOKIES_VER=<cookies de usuário comum>] node req253-browser.cjs
const fs = require('node:fs'), path = require('node:path');
const {chromium} = require(process.env.C2F_PLAYWRIGHT || 'playwright');
const base = process.env.C2F_BASE || 'https://v3.1-conn2flow.local';
const saida = path.join(__dirname, 'evidencias');
fs.mkdirSync(saida, {recursive: true});
const ler = arquivo => fs.readFileSync(arquivo, 'utf8').split(/\r?\n/).filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
  const p = l.replace(/^#HttpOnly_/, '').split('\t');
  return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
});
let falhas = 0, total = 0;
const conferir = (nome, ok, extra) => { total++; if (!ok) falhas++; console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok || extra === undefined ? '' : ' ' + JSON.stringify(extra).slice(0, 900))); };
const SELO = Date.now();
const NOME = 'Roteiro REQ-253 ' + SELO, CAMINHO = 'roteiro-req-253-' + SELO + '/', NOVO = 'roteiros/req-253-' + SELO + '/';
const LOUSA = 'roteiro-req-252-grade';

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const admin = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 900}});
  await admin.addCookies(ler(process.env.C2F_COOKIES));
  const page = await admin.newPage();
  const erros = [];
  page.on('pageerror', e => erros.push(e.message.slice(0, 160)));
  const visita = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 900}});
  const publico = await visita.newPage();
  const foto = (p, nome, cheia) => p.screenshot({path: path.join(saida, nome + '.jpg'), type: 'jpeg', quality: 60, fullPage: !!cheia});
  const enviar = async () => { await Promise.all([page.waitForNavigation({waitUntil: 'networkidle'}), page.evaluate(() => document.querySelector('input[name="nome"]').form.requestSubmit())]); await page.waitForTimeout(600); };
  const marcar = (nome, valor) => page.evaluate(([n, v]) => { const e = document.querySelector('input[name="' + n + '"]'); if (e.checked !== v) { e.checked = v; e.dispatchEvent(new Event('change', {bubbles: true})); } }, [nome, valor]);
  const escolher = (nome, valor) => page.evaluate(([n, v]) => { const e = document.querySelector('select[name="' + n + '"]'); e.value = v; e.dispatchEvent(new Event('change', {bubbles: true})); return e.value; }, [nome, valor]);
  const ver = async caminho => {
    const r = await publico.goto(base + '/' + caminho, {waitUntil: 'networkidle'});
    await publico.waitForTimeout(900);
    return Object.assign({status: r.status()}, await publico.evaluate(() => {
      const lousa = document.querySelector('[data-c2f-lousa]');
      const h1 = document.querySelector('.c2f-lousa-pagina-titulo, .c2f-lousa-pagina-larga-titulo');
      return {lousa: !!lousa, modo: lousa && lousa.getAttribute('data-mode'), itens: lousa ? lousa.children.length : 0, objetos: lousa ? lousa.querySelectorAll('.is-objeto').length : 0,
        titulos: lousa ? lousa.querySelectorAll('.c2f-lousa-titulo').length : 0, comMoldura: lousa ? lousa.querySelectorAll('.c2f-lousa-item:not(.is-frameless)').length : 0,
        comFundo: lousa ? [...lousa.children].filter(e => e.style.backgroundColor).length : 0, h1: h1 ? h1.textContent : null, secao: (document.querySelector('.c2f-lousa-pagina, .c2f-lousa-pagina-larga') || {}).className || null,
        larguraSecao: (() => { const s = document.querySelector('.c2f-lousa-pagina, .c2f-lousa-pagina-larga'); return s ? Math.round(s.getBoundingClientRect().width) : 0; })(), janela: innerWidth,
        marcadores: document.body.innerHTML.includes('[[lousa#') || /<!--\s*lousa-titulo/.test(document.body.innerHTML)};
    }));
  };
  // Mudar status e excluir são ações por GET protegidas por token: o endereço com o token vem da tela de edição.
  const acao = async (id, tipo) => {
    await page.goto(base + '/dashboard-pages/editar/?id=' + encodeURIComponent(id), {waitUntil: 'networkidle'});
    const url = await page.evaluate(t => { const e = t === 'excluir' ? document.querySelector('button.excluir[data-href]') : document.querySelector('a[href*="opcao=status"]'); return e ? (e.getAttribute('data-href') || e.getAttribute('href')) : null; }, tipo);
    if (!url) return false;
    await page.goto(new URL(url, base).href, {waitUntil: 'networkidle'});
    return true;
  };
  const criadas = [];
  const idDaUrl = () => new URL(page.url()).searchParams.get('id');

  try {
    // ----- Listagem e formulário
    const rLista = await page.goto(base + '/dashboard-pages/', {waitUntil: 'networkidle'});
    conferir('listagem do módulo abre para o administrador', rLista.status() === 200 && await page.evaluate(() => !!document.getElementById('_gestor-interface-listar')));
    conferir('módulo aparece no menu do painel', await page.evaluate(() => [...document.querySelectorAll('a[href]')].some(a => /\/dashboard-pages\/$/.test(a.getAttribute('href')))));
    await page.goto(base + '/dashboard-pages/adicionar/', {waitUntil: 'networkidle'});
    const opcoes = await page.evaluate(() => Object.fromEntries(['board_id', 'template_id', 'layout_id', 'mode'].map(n => [n, [...document.querySelector('select[name="' + n + '"]').options].map(o => o.value).filter(Boolean)])));
    conferir('formulário lista lousas, os dois modelos, layouts de site e os três arranjos', opcoes.board_id.includes(LOUSA) && ['dashboard-pages-simples', 'dashboard-pages-largura-total'].every(m => opcoes.template_id.includes(m))
      && opcoes.layout_id.includes('layout-conn2flow-site') && !opcoes.layout_id.some(l => l.startsWith('layout-administrativo')) && opcoes.mode.join() === 'auto,grade,lousa', opcoes);
    const padrao = await page.evaluate(() => Object.fromEntries(['sem_permissao', 'show_title', 'show_item_titles', 'show_frames', 'show_backgrounds', 'show_objects'].map(n => [n, document.querySelector('input[name="' + n + '"]').checked])));
    conferir('página nova nasce pública e com todos os controles ligados', Object.values(padrao).every(Boolean), padrao);
    await page.fill('input[name="nome"]', NOME);
    conferir('o endereço acompanha o nome', await page.inputValue('input[name="caminho"]') === CAMINHO, await page.inputValue('input[name="caminho"]'));
    await escolher('board_id', LOUSA); await escolher('template_id', 'dashboard-pages-simples'); await escolher('layout_id', 'layout-conn2flow-site');
    await foto(page, '1-formulario', true);
    await page.setViewportSize({width: 390, height: 800}); await page.waitForTimeout(300);
    conferir('em 390 px o formulário não rola de lado', await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    await page.setViewportSize({width: 1366, height: 900});

    // ----- Criar
    await enviar();
    const id = idDaUrl();
    conferir('salvar cria a página e abre a edição dela', /\/dashboard-pages\/editar\//.test(page.url()) && !!id, page.url());
    if (!id) throw new Error('página não foi criada: ' + page.url());
    criadas.push(id);
    let v = await ver(CAMINHO);
    conferir('visitante abre a página no endereço informado, com título e lousa', v.status === 200 && v.lousa && v.h1 === NOME && v.secao === 'c2f-lousa-pagina' && !v.marcadores, v);
    conferir('tudo ligado: itens, objetos, título do autor, molduras e fundo', v.itens === 7 && v.objetos === 5 && v.titulos === 1 && v.comMoldura === 2 && v.comFundo === 1 && v.modo === 'grade', v);
    conferir('modelo simples: conteúdo centrado em até 1280 px', v.larguraSecao <= 1280 && v.larguraSecao < v.janela, {secao: v.larguraSecao, janela: v.janela});
    await foto(publico, '2-pagina-modelo-simples', true);

    // ----- Edição: o formulário volta com o que foi gravado
    const gravado = await page.evaluate(() => ({nome: document.querySelector('input[name="nome"]').value, caminho: document.querySelector('input[name="caminho"]').value, lousa: document.querySelector('select[name="board_id"]').value,
      modelo: document.querySelector('select[name="template_id"]').value, layout: document.querySelector('select[name="layout_id"]').value, publica: document.querySelector('input[name="sem_permissao"]').checked,
      abrir: (document.querySelector('[data-dashboard-pages-abrir]') || {}).href || ''}));
    conferir('a edição mostra o que foi gravado e o endereço da página', gravado.nome === NOME && gravado.caminho === CAMINHO && gravado.lousa === LOUSA && gravado.modelo === 'dashboard-pages-simples' && gravado.layout === 'layout-conn2flow-site'
      && gravado.publica && gravado.abrir.endsWith('/' + CAMINHO), gravado);

    // ----- Trocar modelo, controles e endereço
    for (const c of ['show_title', 'show_item_titles', 'show_frames', 'show_backgrounds', 'show_objects']) await marcar(c, false);
    await escolher('mode', 'lousa'); await escolher('template_id', 'dashboard-pages-largura-total');
    await page.fill('input[name="caminho"]', 'Roteiros/REQ 253 ' + SELO);
    await enviar();
    conferir('salvar a edição volta para a mesma página', idDaUrl() === id && await page.inputValue('input[name="caminho"]') === NOVO, [page.url(), await page.inputValue('input[name="caminho"]')]);
    v = await ver(NOVO);
    conferir('endereço novo no ar, com o modelo de largura total', v.status === 200 && v.lousa && v.secao === 'c2f-lousa-pagina-larga' && v.larguraSecao >= v.janela - 20, v);
    conferir('controles desligados: sem título de página, sem títulos de item, sem molduras, sem fundos, sem objetos', v.h1 === null && v.titulos === 0 && v.comMoldura === 0 && v.comFundo === 0 && v.objetos === 0 && v.itens === 2, v);
    conferir('arranjo forçado para lousa', v.modo === 'lousa', v.modo);
    await foto(publico, '3-pagina-controles-desligados', true);
    const antigo = await ver(CAMINHO);
    conferir('o endereço antigo deixa de mostrar a página', !antigo.lousa, antigo);
    const controles = await page.evaluate(() => Object.fromEntries(['show_title', 'show_item_titles', 'show_frames', 'show_backgrounds', 'show_objects'].map(n => [n, document.querySelector('input[name="' + n + '"]').checked]).concat([['mode', document.querySelector('select[name="mode"]').value]])));
    conferir('a edição lembra os controles desligados', Object.entries(controles).every(([k, val]) => k === 'mode' ? val === 'lousa' : val === false), controles);

    // ----- Título próprio, escapado
    await marcar('show_title', true);
    await page.fill('input[name="title"]', 'Oferta <b>de</b> outubro');
    await enviar();
    v = await ver(NOVO);
    conferir('título próprio aparece como texto', v.h1 === 'Oferta <b>de</b> outubro', v.h1);

    // ----- REQ-254: editor HTML
    const editor = () => page.evaluate(() => ({campo: !!document.querySelector('textarea[name="html"]'), api: typeof window.html_editor_get_html === 'function', html: typeof window.html_editor_get_html === 'function' ? window.html_editor_get_html() : null}));
    let e = await editor();
    conferir('a edição tem o editor HTML, aberto com o HTML guardado da página (modelo de largura total)', e.campo && e.api && /c2f-lousa-pagina-larga/.test(e.html || '') && (e.html || '').includes('[[lousa#widget]]'), e);
    await escolher('template_id', 'dashboard-pages-simples'); await page.waitForTimeout(1500);
    e = await editor();
    conferir('trocar o modelo carrega o HTML dele no editor', /class="c2f-lousa-pagina"/.test(e.html || '') && !/c2f-lousa-pagina-larga/.test(e.html || ''), (e.html || '').slice(0, 200));
    const PROPRIO = '<section class="c2f-lousa-pagina">\n<!-- lousa-titulo < --><h1 class="c2f-lousa-pagina-titulo">[[lousa#titulo]]</h1><!-- lousa-titulo > -->\n<p class="roteiro-254">Escrito no editor ' + SELO + '</p>\n[[lousa#widget]]\n</section>';
    await page.evaluate(([h, c]) => { window.html_editor_set_html(h); window.html_editor_set_css(c); }, [PROPRIO, '.c2f-lousa-pagina{max-width:1280px;margin:0 auto;padding:32px 16px}\n.roteiro-254{color:rgb(200, 0, 0);font-weight:700}']);
    await page.waitForTimeout(500);
    await enviar();
    e = await editor();
    conferir('o HTML escrito no editor volta ao editor depois de salvar', (e.html || '').includes('Escrito no editor ' + SELO) && (e.html || '').includes('[[lousa#widget]]'), (e.html || '').slice(0, 300));
    v = await ver(NOVO);
    const escrito = await publico.evaluate(() => { const p = document.querySelector('.roteiro-254'); return p ? {texto: p.textContent, cor: getComputedStyle(p).color} : null; });
    conferir('o que foi escrito no editor aparece na página, com o estilo do editor', !!escrito && escrito.texto === 'Escrito no editor ' + SELO && escrito.cor === 'rgb(200, 0, 0)', escrito);
    conferir('os controles continuam valendo sobre o HTML do editor', v.lousa && v.h1 === 'Oferta <b>de</b> outubro' && v.titulos === 0 && v.objetos === 0 && v.modo === 'lousa' && !v.marcadores, v);
    await foto(publico, '5-pagina-html-do-editor', true);

    // ----- REQ-254: clonar
    const linkClonar = await page.evaluate(() => { const a = [...document.querySelectorAll('a[href]')].find(x => /dashboard-pages\/clonar\/\?/.test(x.getAttribute('href'))); return a ? a.getAttribute('href') : null; });
    conferir('a edição tem o botão de clonar', !!linkClonar, linkClonar);
    await page.goto(new URL(linkClonar || '/dashboard-pages/clonar/?id=' + encodeURIComponent(id), base).href, {waitUntil: 'networkidle'});
    const clone = await page.evaluate(() => ({nome: document.querySelector('input[name="nome"]').value, caminho: document.querySelector('input[name="caminho"]').value, lousa: document.querySelector('select[name="board_id"]').value,
      modelo: document.querySelector('select[name="template_id"]').value, layout: document.querySelector('select[name="layout_id"]').value, modo: document.querySelector('select[name="mode"]').value,
      objetos: document.querySelector('input[name="show_objects"]').checked, titulo: document.querySelector('input[name="title"]').value, html: typeof window.html_editor_get_html === 'function' ? window.html_editor_get_html() : ''}));
    conferir('clonar abre com os dados da origem, sem nome nem endereço', clone.nome === '' && clone.caminho === '' && clone.lousa === LOUSA && clone.modelo === 'dashboard-pages-simples' && clone.layout === 'layout-conn2flow-site'
      && clone.modo === 'lousa' && clone.objetos === false && clone.titulo === 'Oferta <b>de</b> outubro' && clone.html.includes('Escrito no editor ' + SELO), Object.assign({}, clone, {html: clone.html.slice(0, 120)}));
    await foto(page, '6-clonar', true);
    await page.fill('input[name="nome"]', NOME + ' clone');
    const caminhoClone = await page.inputValue('input[name="caminho"]');
    await enviar();
    const idClone = idDaUrl();
    conferir('salvar o clone cria outra página e abre a edição dela', /\/dashboard-pages\/editar\//.test(page.url()) && !!idClone && idClone !== id, page.url());
    if (idClone && idClone !== id) criadas.push(idClone);
    const vClone = await ver(caminhoClone);
    const escritoClone = await publico.evaluate(() => (document.querySelector('.roteiro-254') || {}).textContent || null);
    conferir('o clone está no ar no endereço dele, com o HTML e os controles da origem', vClone.status === 200 && vClone.lousa && vClone.modo === 'lousa' && vClone.objetos === 0 && escritoClone === 'Escrito no editor ' + SELO, {vClone, escritoClone});
    v = await ver(NOVO);
    conferir('a página de origem continua igual', v.lousa && v.h1 === 'Oferta <b>de</b> outubro' && v.modo === 'lousa', v);

    // ----- REQ-255: aba Modelos do editor e os dois modelos com estrutura em volta da lousa
    await page.goto(base + '/dashboard-pages/editar/?id=' + encodeURIComponent(id), {waitUntil: 'networkidle'});
    await page.click('a[data-tab="modelos"]'); await page.waitForTimeout(2500);
    const abaModelos = await page.evaluate(() => (document.querySelector('div[data-tab="modelos"]') || {innerText: ''}).innerText);
    conferir('a aba Modelos do editor lista os quatro modelos do módulo', ['Simples', 'Largura total', 'Campanha', 'Painel com navegação'].every(n => abaModelos.includes('Página de Lousa - ' + n)), abaModelos.replace(/\s+/g, ' ').slice(0, 400));
    await foto(page, '7-aba-modelos', true);
    await page.click('a[data-tab="visualizacao-pagina"]');
    await marcar('show_title', true);
    for (const [modelo, seletor] of [['dashboard-pages-campanha', '.c2f-lousa-campanha'], ['dashboard-pages-painel', '.c2f-lousa-painel']]) {
      await escolher('template_id', modelo);
      await enviar();
      await ver(NOVO);
      const m = await publico.evaluate(s => { const r = document.querySelector(s); if (!r) return null; const l = r.querySelector('[data-c2f-lousa]');
        return {lousa: !!l, itens: l ? l.children.length : 0, titulo: (r.querySelector('h1, .c2f-lousa-painel-nome') || {}).textContent || null, links: [...r.querySelectorAll('a.c2f-lousa-campanha-botao, .c2f-lousa-painel-menu a')].map(a => a.getAttribute('href')),
          selo: (r.querySelector('.c2f-lousa-campanha-selo') || {}).textContent || null, rodape: !!r.querySelector('.c2f-lousa-campanha-chamada, .c2f-lousa-painel-rodape'), cru: r.innerHTML.includes('[[') || r.innerHTML.includes('@[['), lateral: document.documentElement.scrollWidth <= innerWidth + 1}; }, seletor);
      conferir('modelo ' + modelo + ' no ar: estrutura dele, título, lousa no lugar e links resolvidos', !!m && m.lousa && m.itens >= 1 && m.titulo === 'Oferta <b>de</b> outubro' && m.rodape && !m.cru && m.lateral && m.links.length >= 1 && m.links.every(h => /^(#|\/)/.test(h)) && m.links.some(h => h.endsWith('contato/')), m);
      await foto(publico, modelo === 'dashboard-pages-campanha' ? '8-modelo-campanha' : '9-modelo-painel', true);
    }
    await publico.setViewportSize({width: 390, height: 800}); await publico.waitForTimeout(400);
    conferir('modelo com navegação em 390 px sem rolagem lateral', await publico.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    await publico.setViewportSize({width: 1366, height: 900});

    // ----- Recusas
    await page.goto(base + '/dashboard-pages/adicionar/', {waitUntil: 'networkidle'});
    await page.fill('input[name="nome"]', NOME + ' repetida'); await page.fill('input[name="caminho"]', NOVO);
    await escolher('board_id', LOUSA); await escolher('template_id', 'dashboard-pages-simples'); await escolher('layout_id', 'layout-conn2flow-site');
    await enviar();
    conferir('endereço repetido é recusado e nada é criado', /\/dashboard-pages\/adicionar\//.test(page.url()) && !idDaUrl(), page.url());
    await page.goto(base + '/dashboard-pages/adicionar/', {waitUntil: 'networkidle'});
    await page.fill('input[name="nome"]', NOME + ' sem lousa'); await page.fill('input[name="caminho"]', 'roteiro-req-253-sem-lousa-' + SELO);
    await page.evaluate(() => { const s = document.querySelector('select[name="board_id"]'); const o = document.createElement('option'); o.value = 'lousa-que-nao-existe'; o.textContent = 'x'; s.appendChild(o); s.value = o.value; });
    await escolher('template_id', 'dashboard-pages-simples'); await escolher('layout_id', 'layout-conn2flow-site');
    await enviar();
    conferir('lousa que não existe é recusada pelo servidor', /\/dashboard-pages\/adicionar\//.test(page.url()) && !(await ver('roteiro-req-253-sem-lousa-' + SELO + '/')).lousa, page.url());

    // ----- Listagem, desativar e excluir
    await page.goto(base + '/dashboard-pages/', {waitUntil: 'networkidle'}); await page.waitForTimeout(1200);
    const naLista = await page.evaluate(n => document.body.innerText.includes(n), NOME);
    conferir('a página aparece na listagem do módulo', naLista);
    await foto(page, '4-listagem');
    await page.goto(base + '/dashboard-pages/?opcao=status&status=I&id=' + encodeURIComponent(id), {waitUntil: 'networkidle'});
    await page.goto(base + '/dashboard-pages/?opcao=excluir&id=' + encodeURIComponent(id), {waitUntil: 'networkidle'});
    v = await ver(NOVO);
    conferir('desativar ou excluir sem o token da sessão não tem efeito', v.lousa && v.status === 200, v);
    conferir('botão de status da edição leva à ação', await acao(id, 'status'));
    v = await ver(NOVO);
    conferir('página desativada sai do ar', !v.lousa, v);
    await acao(id, 'status');
    v = await ver(NOVO);
    conferir('reativada, volta', v.lousa && v.status === 200, v);

    // ----- Quem não tem o módulo
    if (process.env.C2F_COOKIES_VER) {
      const outro = await browser.newContext({ignoreHTTPSErrors: true});
      await outro.addCookies(ler(process.env.C2F_COOKIES_VER));
      const p2 = await outro.newPage();
      const r = {};
      for (const u of ['dashboard-pages/', 'dashboard-pages/adicionar/', 'dashboard-pages/editar/?id=' + encodeURIComponent(id)]) { await p2.goto(base + '/' + u, {waitUntil: 'networkidle'}); r[u] = await p2.evaluate(() => !!document.querySelector('input[name="nome"], #_gestor-interface-listar')); }
      conferir('usuário sem acesso ao módulo não vê listagem nem formulário', Object.values(r).every(x => x === false), r);
      await outro.close();
    } else conferir('sem cookie de usuário comum: acesso não exercitado', true);
    conferir('nenhum erro de script no painel', erros.length === 0, erros);
  } finally {
    for (const id of criadas) await acao(id, 'excluir').catch(() => {});
    if (criadas.length) {
      const fim = await ver(NOVO);
      await page.goto(base + '/dashboard-pages/', {waitUntil: 'networkidle'}); await page.waitForTimeout(1000);
      conferir('excluída, a página sai do ar e da listagem', !fim.lousa && !(await page.evaluate(n => document.body.innerText.includes(n), NOME)), fim);
    }
  }
  await browser.close();
  console.log('\n' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
