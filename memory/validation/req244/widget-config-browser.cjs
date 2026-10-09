// Configurações por widget no Dashboard: botão no cabeçalho do card, pop-up, aplicação e persistência.
// Mexe na preferência do usuário da sessão: guarda as opções que o widget tinha e as devolve no fim.
// Uso: C2F_PLAYWRIGHT=<pasta do playwright> C2F_COOKIES=<cookies do auth:cookie> node widget-config-browser.cjs
const fs = require('node:fs'), path = require('node:path');
const {chromium} = require(process.env.C2F_PLAYWRIGHT || 'playwright');
const base = process.env.C2F_BASE || 'https://conn2flow.local';
const saida = path.join(__dirname, 'evidencias-config');
fs.mkdirSync(saida, {recursive: true});
const jar = fs.readFileSync(process.env.C2F_COOKIES, 'utf8').split(/\r?\n/).filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
  const p = l.replace(/^#HttpOnly_/, '').split('\t');
  return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
});
let falhas = 0, total = 0;
const conferir = (nome, ok, extra) => { total++; if (!ok) falhas++; console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok || extra === undefined ? '' : ' ' + JSON.stringify(extra).slice(0, 600))); };

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 900}});
  await ctx.addCookies(jar);
  const page = await ctx.newPage();
  const erros = [];
  page.on('pageerror', e => erros.push(e.message.slice(0, 160)));
  const foto = nome => page.screenshot({path: path.join(saida, nome + '.jpg'), type: 'jpeg', quality: 60});
  const abrir = async () => { await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'}); await page.waitForTimeout(3500); };
  const edicao = async ligar => {
    const atual = await page.evaluate(() => document.getElementById('dashboard-widgets-grid').classList.contains('is-editing'));
    if (atual === ligar) return;
    await page.evaluate(() => { document.getElementById('dashboard-options').open = true; });
    await page.click('#dashboard-edit-mode'); await page.waitForTimeout(300);
    await page.evaluate(() => { document.getElementById('dashboard-options').open = false; });
  };
  const CARD = '.dashboard-widget-card[data-roteiro-config]';
  const estado = () => page.evaluate(sel => {
    const c = document.querySelector(sel), h = c.querySelector('.dashboard-widget-card-header'), b = c.querySelector('.dashboard-widget-card-body'), f = c.querySelector('iframe'), s = getComputedStyle(c);
    return {cabecalho: getComputedStyle(h).display, titulo: c.querySelector('.dashboard-widget-title').textContent, fundo: s.backgroundColor, borda: s.borderTopColor, sombra: s.boxShadow, tom: c.getAttribute('data-widget-tone'), recuo: getComputedStyle(b).paddingTop,
      card: Math.round(c.getBoundingClientRect().height), quadro: f ? Math.round(f.getBoundingClientRect().height) : 0, mesmoQuadro: !!(f && f.roteiroMarca), botao: getComputedStyle(c.querySelector('.dashboard-widget-config-btn')).display};
  }, CARD);

  // Grava um conjunto exato de opções pelo próprio pop-up (cliques reais nas chaves que diferem).
  const definir = async o => {
    await edicao(true);
    await page.locator(CARD + ' .dashboard-widget-config-btn').scrollIntoViewIfNeeded();
    await page.click(CARD + ' .dashboard-widget-config-btn'); await page.waitForTimeout(300);
    for (const [nome, valor] of [['header', o.header], ['frame', o.frame], ['background-custom', !!o.background]]) {
      if (await page.evaluate(n => document.querySelector('[data-widget-option="' + n + '"]').checked, nome) !== valor) await page.click('label:has([data-widget-option="' + nome + '"])');
    }
    if (o.background) await page.fill('[data-widget-option="background"]', o.background);
    await page.fill('[data-widget-option="title"]', o.title);
    await page.evaluate(v => { for (const n of ['padding', 'refresh']) { const s = document.querySelector('[data-widget-option="' + n + '"]'); s.value = String(v[n]); s.dispatchEvent(new Event('change', {bubbles: true})); } }, o);
    await page.click('.dashboard-widget-config-save'); await page.waitForTimeout(900);
    await edicao(false);
  };
  const PADRAO = {header: true, frame: true, title: '', background: '', padding: 'none', refresh: 0};

  await abrir();
  await edicao(false);
  const alvo = await page.evaluate(() => { const c = document.querySelector('.dashboard-widget-card[data-widget-id="presentations"]') || document.querySelector('.dashboard-widget-card'); if (!c) return null; c.setAttribute('data-roteiro-config', '1'); c.querySelector('iframe').roteiroMarca = true; return c.dataset.widgetInstance; });
  conferir('o Dashboard tem um widget para configurar', !!alvo);
  if (!alvo) { await browser.close(); process.exit(1); }
  const marcar = () => page.evaluate(i => { const c = [...document.querySelectorAll('.dashboard-widget-card')].find(x => x.dataset.widgetInstance === i); c.setAttribute('data-roteiro-config', '1'); }, alvo);
  const original = Object.assign({}, PADRAO, await page.evaluate(i => (gestor.dashboard_user_prefs.widgets_layout.find(w => w.instance_id === i) || {}).options || {}, alvo));
  if (JSON.stringify(original) !== JSON.stringify(PADRAO)) { await definir(PADRAO); await page.evaluate(sel => { document.querySelector(sel + ' iframe').roteiroMarca = true; }, CARD); }
  // Se o roteiro parar no meio, o layout inteiro do usuário volta ao que era na carga.
  const layoutInicial = await page.evaluate(() => JSON.stringify(gestor.dashboard_user_prefs.widgets_layout));
  try {
  await page.locator(CARD).scrollIntoViewIfNeeded();
  const antes = await estado();
  conferir('fora do modo de edição o botão de configurações não aparece', antes.botao === 'none' && antes.cabecalho !== 'none', antes);

  await edicao(true);
  const botoes = await page.evaluate(sel => [...document.querySelectorAll(sel + ' .dashboard-widget-card-header button')].filter(b => getComputedStyle(b).display !== 'none').map(b => b.className.match(/dashboard-widget-[a-z]+-(btn|handle)/)[0]), CARD);
  conferir('no modo de edição o botão fica ao lado do de trocar', botoes.join().indexOf('dashboard-widget-drag-handle,dashboard-widget-switch-btn,dashboard-widget-config-btn,') === 0 && botoes[botoes.length - 1] === 'dashboard-widget-remove-btn', botoes);
  await page.click(CARD + ' .dashboard-widget-config-btn'); await page.waitForTimeout(400);
  const pop = await page.evaluate(() => { const m = document.getElementById('dashboard-widget-config-modal'), caixa = m.firstElementChild.getBoundingClientRect(); const o = n => m.querySelector('[data-widget-option="' + n + '"]');
    return {visivel: !m.classList.contains('hidden'), esq: Math.round(caixa.left), dir: Math.round(innerWidth - caixa.right), nome: m.querySelector('[data-widget-config-name]').textContent, cabecalho: o('header').checked, moldura: o('frame').checked, corTravada: o('background').disabled,
      chaves: m.querySelectorAll('.dashboard-menu-item.dashboard-widget-config-switch .dashboard-edit-track').length, trilhoLigado: getComputedStyle(o('header').nextElementSibling).backgroundColor, icones: m.querySelectorAll('svg').length, selects: m.querySelectorAll('select').length}; });
  conferir('o pop-up abre centralizado, com o nome do widget e os valores padrão', pop.visivel && Math.abs(pop.esq - pop.dir) <= 2 && pop.nome.length > 1 && pop.cabecalho && pop.moldura && pop.corTravada, pop);
  conferir('chaves no estilo do menu de opções (linha, ícone e trilho azul quando ligada)', pop.chaves >= 3 && pop.trilhoLigado === 'rgb(2, 132, 199)' && pop.icones >= 5 && pop.selects >= 2, pop);
  await foto('1-popup');

  await page.click('label:has([data-widget-option="header"])');
  await page.click('label:has([data-widget-option="background-custom"])');
  await page.fill('[data-widget-option="background"]', '#0f172a');
  await page.fill('[data-widget-option="title"]', 'Roteiro');
  await page.evaluate(() => { const s = document.querySelector('[data-widget-option="padding"]'); s.value = 'small'; s.dispatchEvent(new Event('change', {bubbles: true})); });
  await page.waitForTimeout(400);
  await foto('2-popup-preenchido');
  await page.click('.dashboard-widget-config-save'); await page.waitForTimeout(900);
  const editando = await estado();
  conferir('aplicar fecha o pop-up e não recarrega o iframe', await page.evaluate(() => document.getElementById('dashboard-widget-config-modal').classList.contains('hidden')) && editando.mesmoQuadro, editando);
  conferir('cor de fundo, título e margem aplicados; cabeçalho escuro legível', editando.fundo === 'rgb(15, 23, 42)' && editando.titulo === 'Roteiro' && editando.recuo === '8px' && editando.tom === 'dark', editando);
  conferir('com o modo de edição ligado o cabeçalho continua aparecendo', editando.cabecalho !== 'none', editando);
  await foto('3-editando-sem-cabecalho');

  await edicao(false); await page.waitForTimeout(300);
  const limpo = await estado();
  conferir('fora do modo de edição o cabeçalho some e o widget ocupa o card', limpo.cabecalho === 'none' && limpo.quadro >= limpo.card - 20 && limpo.quadro > editando.quadro, {limpo, editando: editando.quadro});
  await page.locator(CARD).scrollIntoViewIfNeeded(); await foto('4-sem-cabecalho');

  await abrir(); await marcar();
  const recarregado = await estado();
  const gravado = await page.evaluate(i => (gestor.dashboard_user_prefs.widgets_layout.find(w => w.instance_id === i) || {}).options, alvo);
  conferir('a configuração volta do servidor depois de recarregar', recarregado.cabecalho === 'none' && recarregado.fundo === 'rgb(15, 23, 42)' && recarregado.titulo === 'Roteiro' && gravado && gravado.header === false && gravado.background === '#0f172a' && gravado.padding === 'small', {recarregado, gravado});

  // Sem moldura: borda e sombra somem fora da edição.
  await edicao(true);
  await page.click(CARD + ' .dashboard-widget-config-btn'); await page.waitForTimeout(300);
  conferir('o pop-up reabre com o que foi gravado', await page.evaluate(() => { const o = n => document.querySelector('[data-widget-option="' + n + '"]'); return !o('header').checked && o('background').value === '#0f172a' && o('title').value === 'Roteiro' && o('padding').value === 'small'; }));
  await page.click('label:has([data-widget-option="frame"])');
  await page.click('.dashboard-widget-config-save'); await page.waitForTimeout(600);
  await edicao(false);
  const semMoldura = await estado();
  conferir('sem moldura: borda transparente e sem sombra', /rgba\(0, 0, 0, 0\)|transparent/.test(semMoldura.borda) && semMoldura.sombra === 'none', semMoldura);
  await page.locator(CARD).scrollIntoViewIfNeeded(); await foto('5-sem-moldura');

  // 390 px: o pop-up cabe na tela.
  await page.setViewportSize({width: 390, height: 800}); await page.waitForTimeout(400);
  await edicao(true);
  // A janela foi estreitada depois da carga e o menu lateral ficou aberto por cima: o clique vai direto no botão.
  await page.locator(CARD + ' .dashboard-widget-config-btn').dispatchEvent('click'); await page.waitForTimeout(400);
  const estreito = await page.evaluate(() => { const r = document.getElementById('dashboard-widget-config-modal').firstElementChild.getBoundingClientRect(); return {esq: Math.round(r.left), dir: Math.round(r.right), janela: innerWidth, lateral: document.documentElement.scrollWidth <= innerWidth + 1}; });
  conferir('em 390 px o pop-up cabe sem rolagem lateral', estreito.esq >= 0 && estreito.dir <= estreito.janela && estreito.lateral, estreito);
  await foto('6-popup-390');

  // Restaurar padrão pelo botão do pop-up.
  await page.click('.dashboard-widget-config-reset'); await page.click('.dashboard-widget-config-save'); await page.waitForTimeout(900);
  await page.setViewportSize({width: 1366, height: 900});
  await edicao(false);
  await abrir(); await marcar();
  const fim = await estado();
  const opcoes = await page.evaluate(i => (gestor.dashboard_user_prefs.widgets_layout.find(w => w.instance_id === i) || {}).options, alvo);
  conferir('restaurar padrão devolve cabeçalho, moldura, fundo e título', fim.cabecalho !== 'none' && fim.fundo === antes.fundo && fim.titulo === antes.titulo && fim.sombra === antes.sombra && opcoes.header === true && opcoes.background === '' && opcoes.title === '', {fim, antes, opcoes});
  // Devolve ao widget o que ele tinha antes do roteiro.
  if (JSON.stringify(original) !== JSON.stringify(PADRAO)) {
    await definir(original); await abrir();
    const devolvido = await page.evaluate(i => (gestor.dashboard_user_prefs.widgets_layout.find(w => w.instance_id === i) || {}).options, alvo);
    conferir('opções que o widget tinha antes do roteiro devolvidas', JSON.stringify(devolvido) === JSON.stringify(original), {devolvido, original});
  }
  conferir('nenhum erro de script na página', erros.length === 0, erros);
  } catch (e) {
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
    await page.evaluate(async valor => { const p = new URLSearchParams({opcao: 'inicio', ajax: 'sim', ajaxOpcao: 'salvar-preferencias', chave: 'dashboard_widgets_layout', valor}); await fetch(gestor.raiz + 'dashboard/', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p}); }, layoutInicial);
    console.log('  roteiro interrompido: layout do usuário devolvido ao estado da carga');
    throw e;
  }
  await browser.close();
  console.log('\n' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
