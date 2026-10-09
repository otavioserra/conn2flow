// REQ-248 — área de widgets com dois modos (grade e lousa), widgets por linha, janela cheia e opções flutuantes.
// Usa o administrador do Lab: guarda layout e modo da carga e os devolve no fim, mesmo se parar no meio.
// Uso: C2F_PLAYWRIGHT=<pasta do playwright> C2F_COOKIES=<cookies do auth:cookie> node req248-browser.cjs
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
  const erros = [];
  page.on('pageerror', e => erros.push(e.message.slice(0, 160)));
  const foto = nome => page.screenshot({path: path.join(saida, nome + '.jpg'), type: 'jpeg', quality: 60});
  const abrir = async () => { await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'}); await page.waitForTimeout(1500); if (await page.evaluate(() => document.getElementById('dashboard-tab-widgets').classList.contains('hidden'))) await page.click('#dashboard-tab-btn-widgets'); await page.waitForTimeout(2500); };
  const gravar = (chave, valor) => page.evaluate(async ([c, v]) => { const p = new URLSearchParams({opcao: 'inicio', ajax: 'sim', ajaxOpcao: 'salvar-preferencias', chave: c, valor: v}); return (await (await fetch(gestor.raiz + 'dashboard/', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p})).json()).status; }, [chave, valor]);
  const menu = aberto => page.evaluate(a => { document.getElementById('dashboard-options').open = a; }, aberto);
  const item = async seletor => { await menu(true); await page.click(seletor); await page.waitForTimeout(500); };
  const edicao = async ligar => { if (await page.evaluate(() => document.getElementById('dashboard-widgets-grid').classList.contains('is-editing')) !== ligar) await item('#dashboard-edit-mode'); };
  const lousa = () => page.evaluate(() => { const g = document.getElementById('dashboard-widgets-grid'); const cs = [...g.querySelectorAll('.dashboard-widget-card')].map(c => { const r = c.getBoundingClientRect(); return {i: c.dataset.widgetInstance, x: c.getAttribute('data-board-x'), y: c.getAttribute('data-board-y'), cols: Number(c.getAttribute('data-widget-cols')), l: Math.round(r.left), t: Math.round(r.top + scrollY), r: Math.round(r.right), b: Math.round(r.bottom + scrollY), quadro: !!c.querySelector('iframe')}; });
    let sobre = 0; for (let a = 0; a < cs.length; a++) for (let b = a + 1; b < cs.length; b++) if (cs[a].l < cs[b].r - 1 && cs[b].l < cs[a].r - 1 && cs[a].t < cs[b].b - 1 && cs[b].t < cs[a].b - 1) sobre++;
    const linha = Math.min(...cs.map(c => c.t));
    return {modo: g.classList.contains('is-board') ? 'lousa' : 'grade', colunas: Number(g.getAttribute('data-board-cols')) || 0, largura: Math.round(g.getBoundingClientRect().width), cards: cs, sobrepostos: sobre, naPrimeiraLinha: cs.filter(c => Math.abs(c.t - linha) < 3).length, lateral: document.documentElement.scrollWidth <= innerWidth + 1}; });
  const arrastar = async (instancia, colunas, pixelsY) => {
    const h = page.locator('.dashboard-widget-card[data-widget-instance="' + instancia + '"] .dashboard-widget-drag-handle');
    await h.scrollIntoViewIfNeeded();
    const b = await h.boundingBox(), e = await lousa(), passo = (e.largura + 20) / e.colunas;
    await page.mouse.move(b.x + b.width / 2, b.y + b.height / 2); await page.mouse.down();
    await page.mouse.move(b.x + b.width / 2 + passo * colunas, b.y + b.height / 2 + pixelsY, {steps: 8});
    const sombra = await page.evaluate(() => !!document.querySelector('.dashboard-board-ghost'));
    await page.mouse.up(); await page.waitForTimeout(600);
    return sombra;
  };

  await abrir();
  const inicial = await page.evaluate(() => ({layout: JSON.stringify(gestor.dashboard_user_prefs.widgets_layout), modo: gestor.dashboard_user_prefs.widgets.modo, fonte: gestor.dashboard_user_prefs.widgets.fonte}));
  const devolver = async () => { await gravar('dashboard_widgets_layout', inicial.layout); await gravar('dashboard_widgets_modo', inicial.modo || 'grade'); };
  try {
    // Começa sem posições guardadas na lousa, para o arranjo automático ser o conferido.
    await gravar('dashboard_widgets_layout', JSON.stringify(JSON.parse(inicial.layout).map(w => { const c = Object.assign({}, w); delete c.x; delete c.y; return c; })));
    await abrir();
    conferir('administrador no próprio layout, com widgets', inicial.fonte !== 'perfil' && JSON.parse(inicial.layout).length >= 3, {fonte: inicial.fonte, total: JSON.parse(inicial.layout).length});

    // ----- Opções flutuantes
    const botao = await page.evaluate(() => { const d = document.getElementById('dashboard-options'), r = d.querySelector('summary').getBoundingClientRect(); return {posicao: getComputedStyle(d).position, direita: Math.round(innerWidth - r.right), base: Math.round(innerHeight - r.bottom)}; });
    conferir('botão de opções flutuante no canto inferior direito', botao.posicao === 'fixed' && botao.direita >= 8 && botao.direita <= 32 && botao.base >= 8 && botao.base <= 32, botao);
    await page.click('#dashboard-options > summary'); await page.waitForTimeout(300);
    await page.mouse.click(700, 300); await page.waitForTimeout(300);
    const aberto = await page.evaluate(() => { const d = document.getElementById('dashboard-options'), r = d.querySelector('.dashboard-options-menu').getBoundingClientRect(); return {aberto: d.open, topo: Math.round(r.top), base: Math.round(r.bottom), janela: innerHeight, direita: Math.round(r.right), larguraJanela: innerWidth}; });
    conferir('o menu fica aberto depois de clicar fora e cabe na tela', aberto.aberto && aberto.topo >= 0 && aberto.base <= aberto.janela && aberto.direita <= aberto.larguraJanela, aberto);
    await foto('1-opcoes-flutuantes');

    // ----- Grade (modo de antes) e widgets por linha
    if (inicial.modo === 'lousa') await item('#dashboard-widgets-mode');
    await page.waitForTimeout(1500);
    const grade = await lousa();
    conferir('modo grade: cards posicionados pela folha, sem sobreposição', grade.modo === 'grade' && grade.colunas === 0 && grade.sobrepostos === 0 && grade.cards.every(c => c.x === null), {modo: grade.modo, sobrepostos: grade.sobrepostos});
    await item('[data-widgets-per-row="3"]'); await page.waitForTimeout(500);
    const tres = await lousa();
    conferir('widgets por linha: 3 deixa três na primeira linha', tres.cards.every(c => c.cols === 4) && tres.naPrimeiraLinha === 3 && tres.sobrepostos === 0, {cols: tres.cards.map(c => c.cols), primeira: tres.naPrimeiraLinha});
    await foto('2-grade-tres-por-linha');
    await item('[data-widgets-per-row="4"]'); await page.waitForTimeout(500);
    const quatro = await lousa();
    conferir('widgets por linha: 4 deixa quatro na primeira linha', quatro.cards.every(c => c.cols === 3) && quatro.naPrimeiraLinha === Math.min(4, quatro.cards.length), {primeira: quatro.naPrimeiraLinha});
    await item('[data-widgets-per-row="2"]'); await page.waitForTimeout(400);
    const grade2 = await lousa();

    // ----- Lousa
    await item('#dashboard-widgets-mode'); await page.waitForTimeout(2500);
    await menu(false);
    const l1 = await lousa();
    conferir('modo lousa: colunas pela largura e nenhum card sobreposto', l1.modo === 'lousa' && l1.colunas >= 6 && l1.sobrepostos === 0 && l1.cards.every(c => c.x !== null && c.quadro), {colunas: l1.colunas, largura: l1.largura, sobrepostos: l1.sobrepostos});
    const lado = [...l1.cards].sort((a, b) => a.t - b.t || a.l - b.l);
    const larguras = grade2.cards.map(c => c.r - c.l);
    conferir('a troca mantém a proporção: quem era metade da grade continua perto de metade da lousa', l1.cards.every(c => Math.abs((c.r - c.l) / l1.largura - 0.5) < 0.08), {larguras: l1.cards.map(c => c.r - c.l), lousa: l1.largura, grade: larguras});
    conferir('mesma distância de 20 px entre vizinhos na horizontal e na vertical', lado.length > 2 && Math.abs(lado[1].l - lado[0].r - 20) <= 1 && Math.abs(lado[1].t - lado[0].t) <= 1 && lado.some(c => lado.some(d => d !== c && d.l === c.l && Math.abs(d.t - c.b - 20) <= 1)), lado.slice(0, 3));
    await foto('3-lousa');
    await edicao(true); await menu(false);
    // Duas colunas à direita e 15 linhas (300 px) para baixo: fica na coluna escolhida, com vão à esquerda.
    const alvo = lado[0];
    const sombra = await arrastar(alvo.i, 2, 300);
    const l2 = await lousa(), movido = l2.cards.find(c => c.i === alvo.i);
    conferir('arrastar mostra a sombra e solta o widget na coluna escolhida, com vão e sem faixa vazia no topo', sombra && Number(movido.x) === Number(alvo.x) + 2 && l2.sobrepostos === 0 && Math.min(...l2.cards.map(c => Number(c.y))) === 0, {de: [alvo.x, alvo.y], para: [movido.x, movido.y], linhas: l2.cards.map(c => c.y)});
    await foto('4-lousa-depois-de-arrastar');
    // Soltar em cima de outro: o que estava ali desce.
    const cima = l2.cards.filter(c => c.i !== alvo.i).sort((a, b) => a.t - b.t || a.l - b.l)[0];
    const h = page.locator('.dashboard-widget-card[data-widget-instance="' + alvo.i + '"] .dashboard-widget-drag-handle');
    await h.scrollIntoViewIfNeeded();
    const hb = await h.boundingBox(), alvoAgora = (await lousa()).cards.find(c => c.i === alvo.i);
    await page.mouse.move(hb.x + hb.width / 2, hb.y + hb.height / 2); await page.mouse.down();
    await page.mouse.move(hb.x + hb.width / 2 + (cima.l - alvoAgora.l), hb.y + hb.height / 2 - (alvoAgora.t - cima.t), {steps: 12}); await page.mouse.up(); await page.waitForTimeout(700);
    const l3 = await lousa(), a3 = l3.cards.find(c => c.i === alvo.i), c3 = l3.cards.find(c => c.i === cima.i);
    conferir('soltar sobre outro widget empurra o de baixo, sem sobrepor', l3.sobrepostos === 0 && a3.x === cima.x && Number(a3.y) <= Number(cima.y) && Number(c3.y) > Number(a3.y), {solto: [a3.x, a3.y], empurrado: [c3.x, c3.y], antes: [cima.x, cima.y]});
    await edicao(false); await menu(false);

    await abrir();
    const l4 = await lousa();
    conferir('modo e posições voltam do servidor depois de recarregar', l4.modo === 'lousa' && JSON.stringify(l4.cards.map(c => [c.i, c.x, c.y]).sort()) === JSON.stringify(l3.cards.map(c => [c.i, c.x, c.y]).sort()) && await page.evaluate(() => gestor.dashboard_user_prefs.widgets.modo) === 'lousa', {antes: l3.cards.map(c => [c.x, c.y]), depois: l4.cards.map(c => [c.x, c.y])});

    // ----- Janela cheia
    await item('#dashboard-btn-widgets-window'); await page.waitForTimeout(700);
    const cheia = await page.evaluate(() => { const r = document.getElementById('dashboard-tab-widgets').getBoundingClientRect(); return {esq: Math.round(r.left), topo: Math.round(r.top), largura: Math.round(r.width), altura: Math.round(r.height), janela: [innerWidth, innerHeight], marcado: document.getElementById('dashboard-btn-widgets-window').getAttribute('aria-checked'), opcoes: getComputedStyle(document.getElementById('dashboard-options')).zIndex}; });
    const l5 = await lousa();
    conferir('janela cheia cobre a área do navegador e a lousa ganha colunas', cheia.esq === 0 && cheia.topo === 0 && cheia.largura === cheia.janela[0] && cheia.altura === cheia.janela[1] && cheia.marcado === 'true' && l5.colunas > l4.colunas && l5.sobrepostos === 0, {cheia, antes: l4.colunas, depois: l5.colunas});
    await menu(false); await page.waitForTimeout(600);
    const visto = await page.evaluate(() => { const c = [...document.querySelectorAll('.dashboard-widget-card')].map(x => x.getBoundingClientRect()); return {naTela: c.filter(r => r.top < innerHeight && r.bottom > 0).length, topos: c.map(r => Math.round(r.top)), rolagem: document.getElementById('dashboard-tab-widgets').scrollTop}; });
    conferir('na janela cheia os widgets aparecem na área visível', visto.naTela >= 1, visto);
    await foto('5-janela-cheia');
    await page.keyboard.press('Escape'); await page.waitForTimeout(500);
    conferir('Esc sai da janela cheia', await page.evaluate(() => !document.getElementById('dashboard-tab-widgets').classList.contains('is-window')) && (await lousa()).colunas === l4.colunas);

    // ----- Lousa estreita: joga para baixo
    await page.setViewportSize({width: 900, height: 900}); await page.waitForTimeout(900);
    const media = await lousa();
    conferir('lousa mais estreita: menos colunas, nada sobreposto, sem rolagem lateral', media.colunas < l4.colunas && media.colunas >= 2 && media.sobrepostos === 0 && media.lateral, {colunas: media.colunas, sobrepostos: media.sobrepostos});
    await page.setViewportSize({width: 390, height: 800}); await page.waitForTimeout(900);
    const celular = await lousa();
    conferir('em 390 px: uma coluna, widgets empilhados, sem rolagem lateral', celular.colunas === 1 && celular.sobrepostos === 0 && celular.lateral && celular.naPrimeiraLinha === 1, {colunas: celular.colunas, sobrepostos: celular.sobrepostos, lateral: celular.lateral});
    await foto('6-lousa-390');
    await page.setViewportSize({width: 1366, height: 900}); await page.waitForTimeout(700);
    const volta = await lousa();
    conferir('voltar à largura cheia devolve o arranjo guardado', JSON.stringify(volta.cards.map(c => [c.i, c.x, c.y]).sort()) === JSON.stringify(l4.cards.map(c => [c.i, c.x, c.y]).sort()));

    // ----- De volta à grade
    await item('#dashboard-widgets-mode'); await page.waitForTimeout(2500); await menu(false);
    const fim = await lousa();
    conferir('trocar de volta para a grade tira as posições e não sobrepõe', fim.modo === 'grade' && fim.sobrepostos === 0 && fim.cards.every(c => c.x === null) && fim.cards.every(c => c.quadro));
    conferir('nenhum erro de script na página', erros.length === 0, erros);

    // ----- Link de dentro do widget abre na página de fora (por último: sai do Dashboard)
    let quadro = null, destino = null;
    for (const f of page.frames()) { if (f === page.mainFrame()) continue; try { destino = await f.evaluate(() => { const a = [...document.querySelectorAll('a[href]')].find(x => { const h = x.getAttribute('href'); return h && h.charAt(0) !== '#' && !/^javascript:/i.test(h) && x.offsetParent !== null; }); if (!a) return null; a.setAttribute('data-roteiro-link', '1'); return {href: a.href, alvo: getComputedStyle(a).display !== 'none', base: (document.querySelector('base') || {}).target}; }); } catch (e) { destino = null; } if (destino) { quadro = f; break; } }
    conferir('há um widget com link para exercitar', !!quadro, destino);
    if (quadro) {
      const sandbox = await (await quadro.frameElement()).getAttribute('sandbox');
      await (await quadro.frameElement()).scrollIntoViewIfNeeded();
      await Promise.all([page.waitForNavigation({timeout: 20000}).catch(() => null), quadro.click('[data-roteiro-link]')]);
      await page.waitForTimeout(800);
      const fora = new URL(page.url()), esperado = new URL(destino.href);
      conferir('clicar no link do widget leva a página de fora ao destino, sem abrir dentro do iframe', destino.base === '_top' && /allow-top-navigation-by-user-activation/.test(sandbox) && !/allow-same-origin/.test(sandbox) && fora.pathname === esperado.pathname && fora.pathname !== '/dashboard/', {de: destino.href, para: page.url(), sandbox});
    }
  } finally {
    await page.setViewportSize({width: 1366, height: 900});
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
    await devolver();
    await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
    const depois = await page.evaluate(() => ({layout: JSON.stringify(gestor.dashboard_user_prefs.widgets_layout), modo: gestor.dashboard_user_prefs.widgets.modo}));
    conferir('layout e modo do administrador devolvidos ao estado da carga', depois.layout === inicial.layout && depois.modo === (inicial.modo || 'grade'), {modo: depois.modo});
  }
  await browser.close();
  console.log('\n' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
