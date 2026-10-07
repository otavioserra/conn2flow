// REQ-258 — modelos de lousa prontos na linha 3.1: o pop-up de layouts lista os modelos e cria uma lousa do sistema
// a partir de um deles, ligando os widgets aos registros que a instalação tem. No fim exclui o que criou e devolve
// o layout do administrador.
// Uso: C2F_BASE=https://v3.1-conn2flow.local C2F_PLAYWRIGHT=<pasta> C2F_COOKIES=<cookies do administrador> [C2F_COOKIES_VER=<cookies de usuário comum>] node req258-browser.cjs
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
const conferir = (nome, ok, extra) => { total++; if (!ok) falhas++; console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok || extra === undefined ? '' : ' ' + JSON.stringify(extra).slice(0, 800))); };
const NOME = 'Roteiro REQ-258 ' + Date.now();
const MODELOS = ['dashboard-boards-boas-vindas', 'dashboard-boards-marketing', 'dashboard-boards-vitrine'];

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 900}});
  await ctx.addCookies(ler(process.env.C2F_COOKIES));
  const page = await ctx.newPage();
  const erros = [];
  page.on('pageerror', e => erros.push(e.message.slice(0, 160)));
  const AJAX = async ([a, d]) => { const p = new URLSearchParams(Object.assign({opcao: 'inicio', ajax: 'sim', ajaxOpcao: a}, d)); const r = await fetch(gestor.raiz + 'dashboard/', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p}); try { return await r.json(); } catch (e) { return {status: 'http-' + r.status}; } };
  const ajax = (acao, dados) => page.evaluate(AJAX, [acao, dados || {}]);
  const abrir = async () => { await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'}); await page.waitForTimeout(1200); if (await page.evaluate(() => document.getElementById('dashboard-tab-widgets').classList.contains('hidden'))) await page.click('#dashboard-tab-btn-widgets'); await page.waitForTimeout(2000); };
  const popup = async () => { await page.evaluate(() => { document.getElementById('dashboard-options').open = true; }); await page.click('#dashboard-btn-layouts'); await page.waitForTimeout(1500); await page.evaluate(() => { document.getElementById('dashboard-options').open = false; }); };
  const criadas = [];

  await abrir();
  const inicial = await page.evaluate(() => ({layout: JSON.stringify(gestor.dashboard_user_prefs.widgets_layout), modo: gestor.dashboard_user_prefs.widgets.modo}));
  try {
    await popup();
    const opcoes = await page.evaluate(() => [...document.getElementById('dashboard-board-model').options].map(o => [o.value, o.textContent]));
    conferir('o pop-up de layouts lista os três modelos de lousa, com a quantidade de itens', MODELOS.every(m => opcoes.some(o => o[0] === m && /\(\d+ /.test(o[1]))) && opcoes[0][0] === '', opcoes);
    await page.screenshot({path: path.join(saida, '1-modelos-no-popup.jpg'), type: 'jpeg', quality: 60});

    await page.click('#dashboard-board-from-model'); await page.waitForTimeout(400);
    conferir('sem escolher modelo, pede o modelo e não cria', /\S/.test(await page.evaluate(() => document.getElementById('dashboard-widgets-layouts-status').textContent)) && !((await ajax('lousas-listar')).data.lousas || []).some(l => l.nome === NOME));

    // O que a instalação tem de registro, por tipo usado no modelo.
    const esperado = {};
    for (const tipo of ['menus', 'pages-index']) esperado[tipo] = (((await ajax('widgets-registros', {widget_id: tipo})).data || {}).items || []).length > 0;

    await page.selectOption('#dashboard-board-model', 'dashboard-boards-boas-vindas');
    await page.fill('#dashboard-board-name', NOME);
    await page.click('#dashboard-board-from-model'); await page.waitForTimeout(2200);
    const aviso = await page.evaluate(() => document.getElementById('dashboard-widgets-layouts-status').textContent);
    const linha = await page.evaluate(n => { const r = [...document.querySelectorAll('#dashboard-boards-list .dashboard-board')].find(x => x.querySelector('.dashboard-layout-name').textContent === n); return r ? {id: r.getAttribute('data-board'), detalhe: r.querySelector('.dashboard-layout-detail').textContent} : null; }, NOME);
    conferir('a lousa criada pelo modelo aparece na lista de lousas do sistema', !!linha && /^roteiro-req-258-\d+$/.test(linha.id), {linha, aviso});
    if (!linha) throw new Error('lousa não foi criada');
    criadas.push(linha.id);
    const lousa = (await ajax('lousa-obter', {id: linha.id})).data;
    const objetos = lousa.widgets.filter(w => w.id === 'objeto'), widgets = lousa.widgets.filter(w => w.id !== 'objeto');
    const deviam = Object.values(esperado).filter(Boolean).length, fora = 2 - deviam;
    conferir('objetos do modelo entram sempre: dois textos e um botão, com os textos do idioma', objetos.length === 3 && objetos.map(o => o.object.type).join() === 'text,text,button' && objetos[0].object.text === 'Bem-vindo' && objetos[2].object.href === '/contato/', objetos.map(o => [o.object.type, o.object.text]));
    conferir('widgets entram ligados a um registro da instalação; tipo sem registro fica de fora', widgets.length === deviam && widgets.every(w => w.registro_id !== '' && esperado[w.id]), {esperado, widgets: widgets.map(w => [w.id, w.registro_id])});
    conferir('o aviso diz quantos widgets ficaram de fora (ou só confirma quando entraram todos)', fora ? aviso.includes(String(fora)) : /\S/.test(aviso), {fora, aviso});
    conferir('modo e versão da lousa nova', lousa.modo === 'grade' && lousa.versao === 1, {modo: lousa.modo, versao: lousa.versao});

    // Abrir a lousa criada no próprio layout: os itens aparecem na tela.
    await page.click('#dashboard-boards-list .dashboard-board[data-board="' + linha.id + '"] [data-board-open]'); await page.waitForTimeout(3000);
    const tela = await page.evaluate(() => ({cards: document.querySelectorAll('.dashboard-widget-card').length, textos: [...document.querySelectorAll('.dashboard-object-text')].map(e => e.textContent), botao: (document.querySelector('.dashboard-object-action') || {}).textContent || null}));
    conferir('abrir a lousa mostra os itens do modelo na área de widgets', tela.cards === lousa.widgets.length && tela.textos[0] === 'Bem-vindo' && tela.botao === 'Fale com a gente', tela);
    await page.evaluate(() => { const m = document.getElementById('dashboard-widgets-layouts-modal'); if (m) m.classList.add('hidden'); });
    await page.screenshot({path: path.join(saida, '2-lousa-do-modelo.jpg'), type: 'jpeg', quality: 60, fullPage: true});

    // Sem nome, a lousa recebe o nome do modelo; modelo inexistente é recusado.
    const semNome = await ajax('lousa-de-modelo', {modelo: 'dashboard-boards-vitrine'});
    if (semNome.status === 'Ok') criadas.push(semNome.data.id);
    conferir('sem nome, a lousa recebe o nome do modelo', semNome.status === 'Ok' && semNome.data.nome === 'Lousa - Vitrine de conteúdo', semNome);
    const recusas = [await ajax('lousa-de-modelo', {modelo: 'nao-existe'}), await ajax('lousa-de-modelo', {modelo: "x' OR '1'='1"}), await ajax('lousa-de-modelo', {modelo: 'dashboard-pages-simples'})];
    conferir('modelo inexistente, fora do formato ou de outro alvo é recusado', recusas.every(r => r.status === 'error'), recusas.map(r => r.status));

    if (process.env.C2F_COOKIES_VER) {
      const outro = await browser.newContext({ignoreHTTPSErrors: true});
      await outro.addCookies(ler(process.env.C2F_COOKIES_VER));
      const p2 = await outro.newPage();
      await p2.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
      const r = [(await p2.evaluate(AJAX, ['lousa-modelos', {}])).status, (await p2.evaluate(AJAX, ['lousa-de-modelo', {modelo: 'dashboard-boards-boas-vindas'}])).status];
      conferir('usuário sem a operação de administrar não lista modelos nem cria lousa', r.every(s => s === 'error'), r);
      await outro.close();
    } else conferir('sem cookie de usuário comum: recusa não exercitada', true);
    conferir('nenhum erro de script na página', erros.length === 0, erros);
  } finally {
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
    for (const id of criadas) await ajax('lousa-excluir', {id});
    await ajax('salvar-preferencias', {chave: 'dashboard_widgets_layout', valor: inicial.layout});
    await ajax('salvar-preferencias', {chave: 'dashboard_widgets_modo', valor: inicial.modo || 'grade'});
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
    const depois = await page.evaluate(() => JSON.stringify(gestor.dashboard_user_prefs.widgets_layout));
    const sobrou = ((await ajax('lousas-listar')).data.lousas || []).filter(l => criadas.includes(l.id));
    conferir('layout do administrador devolvido e lousas do roteiro excluídas', depois === inicial.layout && sobrou.length === 0, {sobrou: sobrou.length});
  }
  await browser.close();
  console.log('\n' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
