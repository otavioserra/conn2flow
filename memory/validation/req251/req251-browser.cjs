// REQ-251 — lousas nomeadas na linha 3.1: criar, abrir, gravar por cima, versões, restaurar, duplicar e excluir,
// contra o banco da instalação. Com C2F_COOKIES_VER (usuário sem `widgets-administrar`) confere a recusa do servidor.
// Uso: C2F_BASE=https://v3.1-conn2flow.local C2F_PLAYWRIGHT=<pasta> C2F_COOKIES=<cookies do administrador> [C2F_COOKIES_VER=<cookies>] node req251-browser.cjs
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
const conferir = (nome, ok, extra) => { total++; if (!ok) falhas++; console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok || extra === undefined ? '' : ' ' + JSON.stringify(extra).slice(0, 700))); };
const NOME = 'Roteiro REQ-251 ' + Date.now();

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 900}});
  await ctx.addCookies(ler(process.env.C2F_COOKIES));
  const page = await ctx.newPage();
  const erros = [];
  page.on('pageerror', e => erros.push(e.message.slice(0, 160)));
  const foto = nome => page.screenshot({path: path.join(saida, nome + '.jpg'), type: 'jpeg', quality: 60});
  const AJAX = async ([a, d]) => { const p = new URLSearchParams(Object.assign({opcao: 'inicio', ajax: 'sim', ajaxOpcao: a}, d)); const r = await fetch(gestor.raiz + 'dashboard/', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p}); try { return await r.json(); } catch (e) { return {status: 'http-' + r.status}; } };
  const ajax = (acao, dados) => page.evaluate(AJAX, [acao, dados || {}]);
  const abrir = async () => { await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'}); await page.waitForTimeout(1200); if (await page.evaluate(() => document.getElementById('dashboard-tab-widgets').classList.contains('hidden'))) await page.click('#dashboard-tab-btn-widgets'); await page.waitForTimeout(2500); };
  const popup = async () => { await page.evaluate(() => { document.getElementById('dashboard-options').open = true; }); await page.click('#dashboard-btn-layouts'); await page.waitForTimeout(1200); await page.evaluate(() => { document.getElementById('dashboard-options').open = false; }); };
  const linha = nome => page.evaluate(n => { const r = [...document.querySelectorAll('#dashboard-boards-list .dashboard-board')].find(x => x.querySelector('.dashboard-layout-name').textContent === n); return r ? {id: r.getAttribute('data-board'), detalhe: r.querySelector('.dashboard-layout-detail').textContent} : null; }, nome);
  const clicar = async (id, acao) => { await page.click('#dashboard-boards-list .dashboard-board[data-board="' + id + '"] [data-board-' + acao + ']'); await page.waitForTimeout(1500); };
  const criadas = [];

  await abrir();
  const inicial = await page.evaluate(() => ({layout: JSON.stringify(gestor.dashboard_user_prefs.widgets_layout), modo: gestor.dashboard_user_prefs.widgets.modo}));
  const itens = JSON.parse(inicial.layout);
  try {
    conferir('administrador com widgets no layout', itens.length >= 2, itens.length);
    await popup();
    const secao = await page.evaluate(() => ({campo: !!document.getElementById('dashboard-board-name'), lista: !!document.getElementById('dashboard-boards-list'), titulo: (document.querySelector('#dashboard-widgets-layouts-modal h4') || {}).textContent}));
    conferir('pop-up de layouts tem a seção de lousas do sistema', secao.campo && secao.lista && /\S/.test(secao.titulo || ''), secao);

    // ----- Criar
    await page.click('#dashboard-board-create'); await page.waitForTimeout(400);
    conferir('criar sem nome pede o nome e não cria', !(await linha('')) && /\S/.test(await page.evaluate(() => document.getElementById('dashboard-widgets-layouts-status').textContent)));
    await page.fill('#dashboard-board-name', NOME); await page.click('#dashboard-board-create'); await page.waitForTimeout(1800);
    const criada = await linha(NOME);
    conferir('lousa criada com o layout atual aparece na lista', criada && /^roteiro-req-251-\d+$/.test(criada.id) && criada.detalhe.includes(' ' + itens.length + ' '), criada);
    if (!criada) throw new Error('sem lousa para continuar');
    criadas.push(criada.id);
    const v1 = (await ajax('lousa-obter', {id: criada.id})).data;
    conferir('a lousa guarda os itens e o modo do layout', v1.versao === 1 && v1.widgets.length === itens.length && v1.widgets.map(w => w.id).join() === itens.map(w => w.id).join() && v1.modo === (inicial.modo === 'lousa' ? 'lousa' : 'grade'), {versao: v1.versao, total: v1.widgets.length, modo: v1.modo});
    await foto('1-lousa-criada');

    // ----- Gravar por cima: muda o layout (tira o último item) e grava
    await ajax('salvar-preferencias', {chave: 'dashboard_widgets_layout', valor: JSON.stringify(itens.slice(0, -1))});
    await abrir(); await popup();
    await clicar(criada.id, 'update');
    const v2 = (await ajax('lousa-obter', {id: criada.id})).data;
    conferir('gravar por cima troca o conteúdo e sobe a versão', v2.versao === 2 && v2.widgets.length === itens.length - 1, {versao: v2.versao, total: v2.widgets.length});

    // ----- Versões e restaurar
    await clicar(criada.id, 'versions');
    const versoes = await page.evaluate(id => [...document.querySelectorAll('.dashboard-board[data-board="' + id + '"] .dashboard-board-versions .dashboard-layout-row')].map(r => r.querySelector('[data-board-restore]').getAttribute('data-board-version') + ':' + r.querySelector('.dashboard-layout-detail').textContent), criada.id);
    conferir('a versão anterior fica guardada com a quantidade de itens que tinha', versoes.length === 1 && versoes[0].startsWith('1:') && versoes[0].includes(' ' + itens.length + ' '), versoes);
    await foto('2-versoes');
    await page.click('.dashboard-board[data-board="' + criada.id + '"] [data-board-restore][data-board-version="1"]'); await page.waitForTimeout(1800);
    const v3 = (await ajax('lousa-obter', {id: criada.id})).data;
    const historico = (await ajax('lousa-versoes', {id: criada.id})).data;
    conferir('restaurar devolve o conteúdo da versão 1 e guarda a que estava', v3.versao === 3 && v3.widgets.length === itens.length && historico.versoes.map(v => v.versao + ':' + v.total).join() === '2:' + (itens.length - 1) + ',1:' + itens.length, {versao: v3.versao, total: v3.widgets.length, historico: historico.versoes.map(v => [v.versao, v.total])});

    // ----- Abrir no próprio layout (hoje com um item a menos)
    await clicar(criada.id, 'open'); await page.waitForTimeout(2500);
    const aberto = await page.evaluate(() => ({cards: document.querySelectorAll('.dashboard-widget-card').length}));
    await abrir();
    const gravado = await page.evaluate(() => gestor.dashboard_user_prefs.widgets_layout.length);
    conferir('abrir a lousa traz os itens dela para o próprio layout e grava', aberto.cards === itens.length && gravado === itens.length, {aberto, gravado});

    // ----- Duplicar e excluir
    await popup();
    await clicar(criada.id, 'duplicate');
    const copia = await page.evaluate(n => { const r = [...document.querySelectorAll('#dashboard-boards-list .dashboard-board')].find(x => x.querySelector('.dashboard-layout-name').textContent.startsWith(n + ' ')); return r ? {id: r.getAttribute('data-board'), nome: r.querySelector('.dashboard-layout-name').textContent} : null; }, NOME);
    conferir('duplicar cria outra lousa, com nome de cópia e identificador próprio', copia && copia.id !== criada.id && /\((cópia|copy)\)$/.test(copia.nome), copia);
    if (copia) {
      criadas.push(copia.id);
      const c = (await ajax('lousa-obter', {id: copia.id})).data;
      conferir('a cópia nasce na versão 1 com o mesmo conteúdo', c.versao === 1 && c.widgets.length === v3.widgets.length, {versao: c.versao, total: c.widgets.length});
      await clicar(copia.id, 'delete');
      conferir('excluir tira a cópia da lista e ela deixa de abrir', !(await page.evaluate(id => !!document.querySelector('.dashboard-board[data-board="' + id + '"]'), copia.id)) && (await ajax('lousa-obter', {id: copia.id})).status === 'error');
    }
    conferir('identificador que não existe ou fora do formato é recusado', (await ajax('lousa-obter', {id: "x' OR '1'='1"})).status === 'error' && (await ajax('lousa-restaurar', {id: criada.id, versao: '999'})).status === 'error');

    // ----- 390 px
    await page.setViewportSize({width: 390, height: 800}); await page.waitForTimeout(500);
    const estreito = await page.evaluate(() => { const r = document.getElementById('dashboard-widgets-layouts-modal').firstElementChild.getBoundingClientRect(); return {esq: Math.round(r.left), dir: Math.round(r.right), janela: innerWidth, lateral: document.documentElement.scrollWidth <= innerWidth + 1}; });
    conferir('em 390 px o pop-up com as lousas cabe sem rolagem lateral', estreito.esq >= 0 && estreito.dir <= estreito.janela && estreito.lateral, estreito);
    await foto('3-lousas-390');
    await page.setViewportSize({width: 1366, height: 900});

    // ----- Quem não administra widgets
    if (process.env.C2F_COOKIES_VER) {
      const outro = await browser.newContext({ignoreHTTPSErrors: true});
      await outro.addCookies(ler(process.env.C2F_COOKIES_VER));
      const p2 = await outro.newPage();
      await p2.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
      const pode = await p2.evaluate(() => gestor.dashboard_user_prefs.widgets.pode_editar);
      const r = {};
      for (const [acao, dados] of [['lousas-listar', {}], ['lousa-salvar', {nome: 'invasor', layout: '[]'}], ['lousa-obter', {id: criada.id}], ['lousa-duplicar', {id: criada.id}], ['lousa-excluir', {id: criada.id}], ['lousa-versoes', {id: criada.id}], ['lousa-restaurar', {id: criada.id, versao: '1'}]]) r[acao] = (await p2.evaluate(AJAX, [acao, dados])).status;
      conferir('usuário sem a operação de administrar tem as sete ações recusadas', pode === false && Object.values(r).every(s => s === 'error'), {pode, r});
      conferir('e a lousa continua intacta', (await ajax('lousa-obter', {id: criada.id})).data.versao === 3);
      await outro.close();
    } else conferir('sem cookie de usuário não administrador: recusa não exercitada', true);
    conferir('nenhum erro de script na página', erros.length === 0, erros);
  } finally {
    await page.setViewportSize({width: 1366, height: 900});
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
    for (const id of criadas) await ajax('lousa-excluir', {id});
    await ajax('salvar-preferencias', {chave: 'dashboard_widgets_layout', valor: inicial.layout});
    await ajax('salvar-preferencias', {chave: 'dashboard_widgets_modo', valor: inicial.modo || 'grade'});
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
    const depois = await page.evaluate(() => JSON.stringify(gestor.dashboard_user_prefs.widgets_layout));
    const sobrou = ((await ajax('lousas-listar')).data.lousas || []).filter(l => l.nome.startsWith(NOME));
    conferir('layout do usuário devolvido e lousas do roteiro excluídas', depois === inicial.layout && sobrou.length === 0, {sobrou: sobrou.length});
  }
  await browser.close();
  console.log('\n' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
