// REQ-247 — área de widgets do Dashboard no Lab, em quatro fases (C2F_FASE):
//   admin   usuário com `widgets-administrar`: ferramentas do card, menu, layouts salvos e publicação por perfil
//   sem     usuário sem nenhuma das duas operações: a área não existe e o AJAX recusa
//   ver     usuário só com `widgets-visualizar`: vê o layout do perfil, sem controles, e o AJAX recusa gravar
//   limpar  volta ao que o administrador tinha antes (preferências e layouts publicados pelo roteiro)
// Uso: C2F_FASE=admin C2F_PLAYWRIGHT=<pasta> C2F_COOKIES=<cookies do usuário da fase> C2F_PERFIL=<perfil do visualizador> node req247-browser.cjs
const fs = require('node:fs'), os = require('node:os'), path = require('node:path');
const {chromium} = require(process.env.C2F_PLAYWRIGHT || 'playwright');
const base = process.env.C2F_BASE || 'https://conn2flow.local';
const fase = process.env.C2F_FASE || 'admin';
const perfil = process.env.C2F_PERFIL || 'cloud-nano';
const guarda = path.join(os.tmpdir(), 'req247-estado-inicial.json');
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
  const abrir = async () => { const r = await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'}); await page.waitForTimeout(1500); return r.status(); };
  const aba = async () => { await page.click('#dashboard-tab-btn-widgets'); await page.waitForTimeout(3000); };
  const ajax = (acao, dados) => page.evaluate(async ([a, d]) => { const p = new URLSearchParams(Object.assign({opcao: 'inicio', ajax: 'sim', ajaxOpcao: a}, d)); const r = await fetch(gestor.raiz + 'dashboard/', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p}); try { return await r.json(); } catch (e) { return {status: 'http-' + r.status}; } }, [acao, dados || {}]);
  const menu = aberto => page.evaluate(a => { document.getElementById('dashboard-options').open = a; }, aberto);
  const item = async id => { await menu(true); await page.click('#' + id); await page.waitForTimeout(400); await menu(false); };
  const edicao = async ligar => { if (await page.evaluate(() => document.getElementById('dashboard-widgets-grid').classList.contains('is-editing')) !== ligar) await item('dashboard-edit-mode'); };
  const cards = () => page.evaluate(() => [...document.querySelectorAll('.dashboard-widget-card')].map(c => ({id: c.dataset.widgetId, instancia: c.dataset.widgetInstance, colunas: Number(c.getAttribute('data-widget-cols')), cabecalho: getComputedStyle(c.querySelector('.dashboard-widget-card-header')).display, quadro: !!c.querySelector('iframe'), largura: Math.round(c.getBoundingClientRect().width)})));
  const recusado = r => r && r.status === 'error';

  conferir('dashboard abre', await abrir() === 200);

  if (fase === 'admin') {
    const inicial = await page.evaluate(() => ({layout: gestor.dashboard_user_prefs.widgets_layout, fonte: gestor.dashboard_user_prefs.widgets.fonte, salvos: gestor.dashboard_user_prefs.widgets.salvos, pode: gestor.dashboard_user_prefs.widgets.pode_editar}));
    if (!fs.existsSync(guarda)) fs.writeFileSync(guarda, JSON.stringify(inicial));
    conferir('administrador recebe a permissão de edição', inicial.pode === true);
    await aba();
    if (inicial.fonte === 'perfil') await item('dashboard-widgets-source');
    await edicao(true);
    const antes = await cards();
    conferir('há widgets para exercitar', antes.length >= 2, antes.length);

    // ----- Botões do card
    const botoes = await page.evaluate(() => { const c = document.querySelector('.dashboard-widget-card[data-widget-id="menus"]') || document.querySelector('.dashboard-widget-card'); return {id: c.dataset.widgetId, classes: [...c.querySelectorAll('.dashboard-widget-card-header > button, .dashboard-widget-card-header > a')].filter(b => getComputedStyle(b).display !== 'none').map(b => b.className.match(/dashboard-widget-[a-z]+-(btn|handle)/)[0]), link: (c.querySelector('.dashboard-widget-edit-btn') || {}).href, icones: c.querySelectorAll('.dashboard-widget-card-header svg').length}; });
    conferir('cabeçalho do card: arrastar, trocar, configurar, duplicar, editar registro e remover', botoes.classes.join() === 'dashboard-widget-drag-handle,dashboard-widget-switch-btn,dashboard-widget-config-btn,dashboard-widget-duplicate-btn,dashboard-widget-edit-btn,dashboard-widget-remove-btn' && botoes.icones >= 6, botoes);
    const destino = await ctx.request.get(botoes.link || base);
    conferir('o atalho abre a tela de edição do registro', /\/editar\/\?id=/.test(botoes.link || '') && destino.status() === 200 && /name="id"|interfaceFormPadrao|_gestor-interface-edit/.test(await destino.text()), {link: botoes.link, status: destino.status()});
    await foto('admin-1-card');

    // ----- Duplicar e desfazer
    const alvo = antes[0];
    await page.click('.dashboard-widget-card[data-widget-instance="' + alvo.instancia + '"] .dashboard-widget-duplicate-btn'); await page.waitForTimeout(2500);
    const dup = await cards();
    conferir('duplicar cria o card logo depois, com o mesmo widget e a mesma largura', dup.length === antes.length + 1 && dup[1].id === alvo.id && dup[1].colunas === alvo.colunas && dup[1].instancia !== alvo.instancia, dup.slice(0, 2));
    await page.click('.dashboard-widget-card[data-widget-instance="' + dup[1].instancia + '"] .dashboard-widget-remove-btn'); await page.waitForTimeout(2000);
    conferir('remover a cópia volta à quantidade de antes', (await cards()).length === antes.length);

    // ----- Largura coluna a coluna
    const puxar = async colunas => {
      const h = page.locator('.dashboard-widget-card[data-widget-instance="' + alvo.instancia + '"] .dashboard-widget-resize-handle');
      await h.scrollIntoViewIfNeeded();
      const b = await h.boundingBox(), passo = (await page.evaluate(() => document.getElementById('dashboard-widgets-grid').getBoundingClientRect().width)) / 12;
      await page.mouse.move(b.x + b.width / 2, b.y + b.height / 2); await page.mouse.down();
      await page.mouse.move(b.x + b.width / 2 + passo * colunas, b.y + b.height / 2, {steps: 6}); await page.mouse.up(); await page.waitForTimeout(500);
      return (await cards()).find(c => c.instancia === alvo.instancia).colunas;
    };
    const menos = await puxar(alvo.colunas > 3 ? -1 : 1);
    conferir('redimensionar anda de uma em uma coluna (larguras fora de 4, 6, 8 e 12)', Math.abs(menos - alvo.colunas) === 1, {de: alvo.colunas, para: menos});
    const minimo = await puxar(-12);
    conferir('largura mínima de 2 colunas', minimo === 2, minimo);
    await foto('admin-2-largura-minima');
    const volta = await puxar(alvo.colunas - 2);
    conferir('volta à largura original', volta === alvo.colunas, {volta, original: alvo.colunas});

    // ----- Menu: todos os cabeçalhos e tela cheia
    await item('dashboard-btn-toggle-headers'); await edicao(false);
    const semCab = await cards();
    conferir('esconder todos os cabeçalhos de uma vez', semCab.every(c => c.cabecalho === 'none'), semCab.map(c => c.cabecalho));
    await foto('admin-3-sem-cabecalhos');
    await item('dashboard-btn-toggle-headers');
    conferir('mostrar todos de novo', (await cards()).every(c => c.cabecalho !== 'none'));
    await menu(true); await page.click('#dashboard-btn-widgets-fullscreen'); await page.waitForTimeout(700);
    const cheia = await page.evaluate(() => document.fullscreenElement ? document.fullscreenElement.id : null);
    conferir('tela cheia da área de widgets', cheia === 'dashboard-tab-widgets', cheia);
    await page.evaluate(() => document.fullscreenElement && document.exitFullscreen()); await page.waitForTimeout(400); await menu(false);

    // ----- Layouts salvos
    await item('dashboard-btn-layouts'); await page.waitForTimeout(900);
    const pop = await page.evaluate(() => { const m = document.getElementById('dashboard-widgets-layouts-modal'), r = m.firstElementChild.getBoundingClientRect(); return {visivel: !m.classList.contains('hidden'), esq: Math.round(r.left), dir: Math.round(innerWidth - r.right), perfis: [...m.querySelectorAll('.dashboard-layout-check')].map(c => c.value)}; });
    conferir('pop-up de layouts abre centralizado com todos os perfis', pop.visivel && Math.abs(pop.esq - pop.dir) <= 2 && pop.perfis[0] === '*' && pop.perfis.includes(perfil) && pop.perfis.includes('administradores'), pop);
    await page.fill('#dashboard-widgets-layout-name', 'Roteiro 247'); await page.click('#dashboard-widgets-layout-save'); await page.waitForTimeout(700);
    conferir('layout atual salvo com nome', await page.evaluate(() => [...document.querySelectorAll('#dashboard-widgets-saved-list .dashboard-layout-name')].some(e => e.textContent === 'Roteiro 247')));

    // ----- Publicar para o perfil do visualizador e para o do administrador
    await page.evaluate(p => document.querySelectorAll('.dashboard-layout-check').forEach(c => { c.checked = c.value === p || c.value === 'administradores'; }), perfil);
    await page.click('#dashboard-widgets-layout-publish'); await page.waitForTimeout(1500);
    const publicados = await page.evaluate(() => [...document.querySelectorAll('#dashboard-widgets-profile-list .dashboard-layout-row')].filter(r => r.querySelector('[data-layout-unpublish]')).map(r => r.querySelector('.dashboard-layout-check').value));
    conferir('layout publicado nos dois perfis marcados', publicados.includes(perfil) && publicados.includes('administradores'), publicados);
    await foto('admin-4-layouts');
    await page.setViewportSize({width: 390, height: 800}); await page.waitForTimeout(400);
    const estreito = await page.evaluate(() => { const r = document.getElementById('dashboard-widgets-layouts-modal').firstElementChild.getBoundingClientRect(); return {esq: Math.round(r.left), dir: Math.round(r.right), janela: innerWidth, lateral: document.documentElement.scrollWidth <= innerWidth + 1}; });
    conferir('em 390 px o pop-up de layouts cabe sem rolagem lateral', estreito.esq >= 0 && estreito.dir <= estreito.janela && estreito.lateral, estreito);
    await foto('admin-5-layouts-390');
    await page.setViewportSize({width: 1366, height: 900});
    await page.click('.dashboard-widgets-layouts-close'); await page.waitForTimeout(300);

    // ----- Próprio x padrão do perfil
    await item('dashboard-widgets-source'); await page.waitForTimeout(2500);
    const padrao = await page.evaluate(() => ({aviso: !document.getElementById('dashboard-widgets-source-notice').classList.contains('hidden'), editar: document.getElementById('dashboard-edit-mode').disabled, adicionar: document.getElementById('dashboard-btn-add-widget').disabled, editando: document.getElementById('dashboard-widgets-grid').classList.contains('is-editing'), marcado: document.getElementById('dashboard-widgets-source').getAttribute('aria-checked')}));
    conferir('usar o padrão do perfil: aviso à mostra e edição travada', padrao.aviso && padrao.editar && padrao.adicionar && !padrao.editando && padrao.marcado === 'true' && (await cards()).length === antes.length, padrao);
    await foto('admin-6-padrao-do-perfil');
    await abrir(); await aba();
    const recarregado = await page.evaluate(() => ({fonte: gestor.dashboard_user_prefs.widgets.fonte, salvos: gestor.dashboard_user_prefs.widgets.salvos.map(l => l.name), perfil: gestor.dashboard_user_prefs.widgets.layout_perfil.length, origem: gestor.dashboard_user_prefs.widgets.perfil_origem}));
    conferir('fonte, layout salvo e padrão do perfil voltam do servidor', recarregado.fonte === 'perfil' && recarregado.salvos.includes('Roteiro 247') && recarregado.perfil === antes.length && recarregado.origem === 'administradores', recarregado);
    await item('dashboard-widgets-source'); await page.waitForTimeout(2000);
    conferir('voltar ao próprio layout libera a edição', await page.evaluate(() => !document.getElementById('dashboard-edit-mode').disabled && document.getElementById('dashboard-widgets-source-notice').classList.contains('hidden')));
    await item('dashboard-btn-layouts'); await page.waitForTimeout(700);
    await page.click('#dashboard-widgets-saved-list .dashboard-layout-row:has-text("Roteiro 247") [data-layout-delete]'); await page.waitForTimeout(600);
    conferir('layout salvo excluído', await page.evaluate(() => ![...document.querySelectorAll('#dashboard-widgets-saved-list .dashboard-layout-name')].some(e => e.textContent === 'Roteiro 247')));
    await page.click('.dashboard-widgets-layouts-close');
  }

  if (fase === 'sem' || fase === 'ver') {
    const acesso = await page.evaluate(() => gestor.dashboard_user_prefs.widgets);
    const tela = await page.evaluate(() => { const tem = id => !!document.getElementById(id); return {aba: tem('dashboard-tab-btn-widgets'), grade: tem('dashboard-widgets-grid'), editar: tem('dashboard-edit-mode'), adicionar: tem('dashboard-btn-add-widget'), layouts: tem('dashboard-btn-layouts'), fonte: tem('dashboard-widgets-source'), catalogo: tem('dashboard-widgets-modal'), config: tem('dashboard-widget-config-modal'), popLayouts: tem('dashboard-widgets-layouts-modal'), cheia: tem('dashboard-btn-widgets-fullscreen'), modulos: tem('dashboard-tab-btn-modulos')}; });
    const semControles = !tela.editar && !tela.adicionar && !tela.layouts && !tela.fonte && !tela.catalogo && !tela.config && !tela.popLayouts;
    conferir('nenhum controle de edição chega ao navegador', acesso.pode_editar === false && semControles && tela.modulos, tela);
    const layout = JSON.stringify([{id: 'menus', registro_id: 'docs-sidebar', instance_id: 'invasor', width: 4, height: 1}]);
    const tentativas = {gravar: await ajax('salvar-preferencias', {chave: 'dashboard_widgets_layout', valor: layout}), fonte: await ajax('salvar-preferencias', {chave: 'dashboard_widgets_fonte', valor: 'proprio'}), publicar: await ajax('widgets-layout-publicar', {perfis: JSON.stringify(['*']), layout}), remover: await ajax('widgets-layout-remover', {perfil}), listar: await ajax('widgets-layouts'), catalogo: await ajax('widgets-catalogo'), registros: await ajax('widgets-registros', {widget_id: 'menus'})};
    conferir('o servidor recusa gravar, publicar, remover, listar perfis e abrir o catálogo', Object.values(tentativas).every(recusado), tentativas);
    conferir('densidade continua gravável por qualquer usuário', (await ajax('salvar-preferencias', {chave: 'dashboard_densidade', valor: 'm'})).status === 'Ok');
    const render = await ajax('widget-render', {widget_id: 'menus', registro_id: 'docs-sidebar', instance_id: 'x'});
    if (fase === 'sem') {
      conferir('sem as operações a aba de widgets não existe', !tela.aba && !tela.grade && !tela.cheia, tela);
      conferir('sem as operações o servidor não renderiza widget', recusado(render), render);
      await foto('sem-operacao');
    } else {
      conferir('quem só visualiza tem a aba de widgets e a tela cheia', tela.aba && tela.grade && tela.cheia, tela);
      conferir('o servidor renderiza o widget, sem atalho de edição', render.status === 'Ok' && render.data.edit_url === '', {status: render.status, edit: render.data && render.data.edit_url});
      await aba();
      const visto = await cards();
      conferir('vê o layout publicado para o perfil, com os widgets carregados', visto.length === acesso.layout_perfil.length && visto.length >= 2 && acesso.perfil_origem === perfil && visto.every(c => c.quadro), {visto: visto.length, perfil: acesso.layout_perfil.length, origem: acesso.perfil_origem});
      const controles = await page.evaluate(() => [...document.querySelectorAll('.dashboard-widget-card-header button, .dashboard-widget-card-header a, .dashboard-widget-resize-handle')].filter(b => getComputedStyle(b).display !== 'none').length);
      conferir('nenhum botão de controle aparece nos cards', controles === 0, controles);
      conferir('página sem rolagem lateral', await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
      await foto('visualizador');
    }
  }

  if (fase === 'limpar') {
    const inicial = JSON.parse(fs.readFileSync(guarda, 'utf8'));
    for (const p of [perfil, 'administradores']) await ajax('widgets-layout-remover', {perfil: p});
    await ajax('salvar-preferencias', {chave: 'dashboard_widgets_layout', valor: JSON.stringify(inicial.layout)});
    await ajax('salvar-preferencias', {chave: 'dashboard_widgets_fonte', valor: inicial.fonte || 'proprio'});
    await ajax('salvar-preferencias', {chave: 'dashboard_widgets_salvos', valor: JSON.stringify(inicial.salvos || [])});
    await abrir();
    const fim = await page.evaluate(() => ({layout: gestor.dashboard_user_prefs.widgets_layout, fonte: gestor.dashboard_user_prefs.widgets.fonte, salvos: gestor.dashboard_user_prefs.widgets.salvos.length, perfil: gestor.dashboard_user_prefs.widgets.layout_perfil.length}));
    conferir('preferências do administrador de volta ao que eram', JSON.stringify(fim.layout) === JSON.stringify(inicial.layout) && fim.fonte === (inicial.fonte || 'proprio') && fim.salvos === (inicial.salvos || []).length, {fim: fim.layout.length, inicial: inicial.layout.length});
    const lista = (await ajax('widgets-layouts')).data;
    conferir('layouts publicados pelo roteiro removidos', fim.perfil === 0 && !lista.perfis.some(p => (p.id === perfil || p.id === 'administradores') && p.publicado), lista.perfis.filter(p => p.publicado));
    fs.unlinkSync(guarda);
  }

  conferir('nenhum erro de script na página', erros.length === 0, erros);
  await browser.close();
  console.log('\n[' + fase + '] ' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
