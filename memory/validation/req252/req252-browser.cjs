// REQ-252 — widget "Lousa" numa página pública da linha 3.1, sem iframe: grade, lousa, objetos, opções e larguras.
// Preparo (uma vez, no banco da instalação de teste): duas páginas com o marcador do widget apontando para as
// lousas `roteiro-req-252-grade` e `roteiro-req-252-lousa` (comandos em `preparar-paginas.sql`).
// O roteiro cria ou regrava essas duas lousas pelo Dashboard e confere as páginas como visitante, sem sessão.
// As lousas e as páginas ficam na instalação de teste para conferência humana.
// Uso: C2F_BASE=https://v3.1-conn2flow.local C2F_PLAYWRIGHT=<pasta> C2F_COOKIES=<cookies do administrador> node req252-browser.cjs
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
const LOUSAS = {grade: 'roteiro-req-252-grade', lousa: 'roteiro-req-252-lousa'};

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});

  // ----- Como administrador: catálogo e as duas lousas
  const admin = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 900}});
  await admin.addCookies(ler(process.env.C2F_COOKIES));
  const painel = await admin.newPage();
  await painel.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
  const ajax = (acao, dados) => painel.evaluate(async ([a, d]) => { const p = new URLSearchParams(Object.assign({opcao: 'inicio', ajax: 'sim', ajaxOpcao: a}, d)); const r = await fetch(gestor.raiz + 'dashboard/', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: p}); try { return await r.json(); } catch (e) { return {status: 'http-' + r.status}; } }, [acao, dados || {}]);

  const catalogo = (await ajax('widgets-catalogo')).data || [];
  conferir('o widget "Lousa" está no cadastro de widgets', catalogo.some(w => w.id === 'dashboard' && w.tabela === 'dashboard_boards'), catalogo.map(w => w.id));

  // Dois widgets de verdade, tirados do layout do administrador, mais os objetos.
  const proprios = (await painel.evaluate(() => gestor.dashboard_user_prefs.widgets_layout)).filter(w => w.id !== 'objeto' && w.id !== 'dashboard' && w.registro_id).slice(0, 2);
  conferir('administrador tem dois widgets para compor a lousa', proprios.length === 2, proprios.map(w => w.id));
  const w = (n, extra) => Object.assign({id: proprios[n].id, name: proprios[n].name, registro_id: proprios[n].registro_id}, extra);
  const o = (objeto, extra) => Object.assign({id: 'objeto', name: 'Objeto', object: objeto, options: {header: false, frame: false}}, extra);
  const itens = [
    o({type: 'text', text: 'Lousa publicada <script>window.__invadiu = 1</script>', font: 'Oswald', size: 44, color: '#0f172a'}, {width: 12, height_px: 120, x: 0, y: 0}),
    w(0, {width: 6, height_px: 320, x: 0, y: 7, options: {title: 'Título do autor', background: '#0f172a', padding: 'medium'}}),
    w(1, {width: 6, height_px: 320, x: 6, y: 7, options: {hide: 'md', header: false}}),
    o({type: 'shape', shape: 'circle', fill: '#f97316'}, {width: 2, height_px: 160, x: 0, y: 24}),
    o({type: 'icon', icon: 'rocket', color: '#0284c7'}, {width: 2, height_px: 160, x: 2, y: 24}),
    o({type: 'button', text: 'Conheça os planos', href: '/plataforma/', fill: '#0284c7', color: '#ffffff'}, {width: 4, height_px: 160, x: 4, y: 24}),
    o({type: 'button', text: 'Destino recusado', href: 'javascript:alert(1)'}, {width: 4, height_px: 160, x: 8, y: 24}),
  ];
  const existentes = ((await ajax('lousas-listar')).data || {}).lousas || [];
  for (const [modo, id] of Object.entries(LOUSAS)) {
    const dados = {modo, layout: JSON.stringify(itens)};
    const r = existentes.some(l => l.id === id) ? await ajax('lousa-salvar', Object.assign({id}, dados)) : await ajax('lousa-salvar', Object.assign({nome: 'Roteiro REQ-252 ' + modo}, dados));
    conferir('lousa de teste em modo ' + modo + ' gravada com o identificador esperado', r.status === 'Ok' && r.data.id === id && r.data.total === itens.length, r);
  }
  const registros = (await ajax('widgets-registros', {widget_id: 'dashboard'})).data || {};
  conferir('as lousas aparecem como registros do widget para o editor de páginas', Object.values(LOUSAS).every(id => (registros.items || []).some(i => i.id === id)), (registros.items || []).map(i => i.id));
  await admin.close();

  // ----- Como visitante, sem sessão
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 900}});
  const page = await ctx.newPage();
  const erros = [];
  page.on('pageerror', e => erros.push(e.message.slice(0, 160)));
  const foto = nome => page.screenshot({path: path.join(saida, nome + '.jpg'), type: 'jpeg', quality: 60, fullPage: true});
  const medir = () => page.evaluate(() => {
    const lousa = document.querySelector('[data-c2f-lousa]');
    if (!lousa) return null;
    const caixa = e => { const r = e.getBoundingClientRect(); return {x: Math.round(r.left), y: Math.round(r.top + scrollY), w: Math.round(r.width), h: Math.round(r.height)}; };
    const itens = [...lousa.children].map(e => ({visivel: getComputedStyle(e).display !== 'none', caixa: caixa(e), classe: e.className, fundo: getComputedStyle(e).backgroundColor, borda: getComputedStyle(e).borderTopColor,
      titulo: (e.querySelector(':scope > .c2f-lousa-titulo') || {}).textContent || '', texto: (e.querySelector('.c2f-lousa-texto') || {}).textContent || '', coluna: e.style.gridColumn, linha: e.style.gridRow}));
    return {modo: lousa.getAttribute('data-mode'), arrumada: lousa.classList.contains('is-arranged'), colunas: lousa.getAttribute('data-cols'), largura: lousa.clientWidth, opacidade: getComputedStyle(lousa).opacity,
      colunasGrade: getComputedStyle(lousa).gridTemplateColumns.split(' ').length, iframes: lousa.querySelectorAll('iframe').length, scripts: lousa.querySelectorAll('script').length, itens,
      invadiu: typeof window.__invadiu, svg: !!lousa.querySelector('.c2f-lousa-icone svg'), figura: getComputedStyle(lousa.querySelector('.c2f-lousa-figura')).borderRadius,
      botoes: [...lousa.querySelectorAll('.c2f-lousa-acao')].map(b => [b.tagName, b.getAttribute('href'), b.textContent]), fonte: getComputedStyle(lousa.querySelector('.c2f-lousa-texto')).fontFamily,
      folhaFontes: [...document.querySelectorAll('link[rel="stylesheet"]')].map(l => l.href).filter(h => h.includes('fonts.googleapis.com/css2')), lateral: document.documentElement.scrollWidth <= innerWidth + 1,
      // Conteúdo de posição fixa tem de se ancorar na caixa do item, não na janela (no Dashboard quem segura é o iframe).
      // O que passar do tamanho da caixa é recortado por ela; por isso a medida é do canto de início.
      vazou: [...lousa.querySelectorAll('.c2f-lousa-item')].filter(e => getComputedStyle(e).display !== 'none').map((e, n) => { const c = e.getBoundingClientRect(); return [...e.querySelectorAll('.c2f-lousa-corpo *')].filter(x => { const s = getComputedStyle(x); if (s.display === 'none' || s.visibility === 'hidden') return false; const r = x.getBoundingClientRect(); return r.width > 0 && r.height > 0 && s.position === 'fixed' && (r.left < c.left - 1 || r.top < c.top - 1 || r.left > c.right || r.top > c.bottom); }).map(x => n + ':' + x.tagName + '.' + String(x.className).slice(0, 40)); }).flat(),
      widgetCheio: [...lousa.querySelectorAll('.c2f-lousa-item:not(.is-objeto) > .c2f-lousa-corpo')].map(c => c.children.length > 0 && c.textContent.trim().length > 0)};
  });
  const sobrepoe = itens => { const v = itens.filter(i => i.visivel).map(i => i.caixa); for (let a = 0; a < v.length; a++) for (let b = a + 1; b < v.length; b++) if (v[a].x < v[b].x + v[b].w && v[b].x < v[a].x + v[a].w && v[a].y < v[b].y + v[b].h && v[b].y < v[a].y + v[a].h) return [a, b]; return null; };

  // Grade
  const rGrade = await page.goto(base + '/' + LOUSAS.grade + '/', {waitUntil: 'networkidle'});
  await page.waitForTimeout(1200);
  let m = await medir();
  conferir('página da grade responde ao visitante e traz a lousa', rGrade.status() === 200 && !!m && m.modo === 'grade', m && m.modo);
  if (!m) throw new Error('lousa não renderizou na página');
  conferir('sete itens, sem iframe e sem script dentro da lousa', m.itens.length === 7 && m.iframes === 0 && m.scripts === 0, {itens: m.itens.length, iframes: m.iframes, scripts: m.scripts});
  conferir('os dois widgets vieram renderizados pela própria página', m.widgetCheio.length === 2 && m.widgetCheio.every(Boolean), m.widgetCheio);
  conferir('conteúdo de posição fixa de um widget fica dentro da caixa dele', m.vazou.length === 0, m.vazou);
  conferir('texto do autor aparece como texto e nada foi executado', m.itens[0].texto === 'Lousa publicada <script>window.__invadiu = 1</script>' && m.invadiu === 'undefined', {texto: m.itens[0].texto, invadiu: m.invadiu});
  conferir('grade de 12 colunas: metade e metade na mesma linha, texto na largura toda', m.colunasGrade === 12 && m.itens[1].caixa.y === m.itens[2].caixa.y && Math.abs(m.itens[1].caixa.w - m.itens[2].caixa.w) <= 2 && m.itens[0].caixa.w >= m.largura - 2, {colunas: m.colunasGrade, a: m.itens[1].caixa, b: m.itens[2].caixa, texto: m.itens[0].caixa.w, largura: m.largura});
  conferir('altura que o autor deu a cada item', m.itens[0].caixa.h === 120 && m.itens[1].caixa.h === 320 && m.itens[3].caixa.h === 160, m.itens.map(i => i.caixa.h));
  conferir('título só no item em que o autor escreveu um; fundo escuro aplicado', m.itens[1].titulo === 'Título do autor' && m.itens.filter(i => i.titulo).length === 1 && m.itens[1].fundo === 'rgb(15, 23, 42)', {titulos: m.itens.map(i => i.titulo), fundo: m.itens[1].fundo});
  conferir('objeto sem moldura fica sem caixa', m.itens[0].classe.includes('is-frameless') && m.itens[0].fundo === 'rgba(0, 0, 0, 0)', {classe: m.itens[0].classe, fundo: m.itens[0].fundo});
  conferir('forma, ícone desenhado e botões (o destino recusado vira rótulo sem link)', m.figura === '50%' && m.svg && JSON.stringify(m.botoes) === JSON.stringify([['A', '/plataforma/', 'Conheça os planos'], ['SPAN', null, 'Destino recusado']]), {figura: m.figura, svg: m.svg, botoes: m.botoes});
  conferir('fonte do Google carregada por uma folha só', m.folhaFontes.length === 1 && /family=Oswald/.test(m.folhaFontes[0]) && /Oswald/.test(m.fonte), {folha: m.folhaFontes, fonte: m.fonte});
  await foto('1-grade-1366');
  await page.setViewportSize({width: 900, height: 900}); await page.waitForTimeout(500);
  m = await medir();
  conferir('em 900 px a grade tem 6 colunas e o item marcado para sumir abaixo de 1024 px some', m.colunasGrade === 6 && m.itens[2].visivel === false && m.itens[1].visivel === true, {colunas: m.colunasGrade, visiveis: m.itens.map(i => i.visivel)});
  await page.setViewportSize({width: 390, height: 800}); await page.waitForTimeout(500);
  m = await medir();
  conferir('em 390 px uma coluna, sem rolagem lateral', m.colunasGrade === 1 && m.lateral && !sobrepoe(m.itens) && m.itens.filter(i => i.visivel).every(i => i.caixa.w <= 390), {colunas: m.colunasGrade, lateral: m.lateral});
  await foto('2-grade-390');

  // Lousa
  await page.setViewportSize({width: 1366, height: 900});
  const rLousa = await page.goto(base + '/' + LOUSAS.lousa + '/', {waitUntil: 'networkidle'});
  await page.waitForTimeout(1200);
  m = await medir();
  const esperadas = l => l < 640 ? 1 : Math.max(2, Math.min(24, Math.floor((l + 20) / 110)));
  conferir('página da lousa: arranjo aplicado e visível, sem iframe', rLousa.status() === 200 && m.modo === 'lousa' && m.arrumada && m.opacidade === '1' && m.iframes === 0, {modo: m.modo, arrumada: m.arrumada, opacidade: m.opacidade});
  conferir('na lousa também nada escapa da caixa', m.vazou.length === 0, m.vazou);
  conferir('colunas pela largura do contêiner, como no Dashboard', Number(m.colunas) === esperadas(m.largura) && m.colunasGrade === Number(m.colunas), {colunas: m.colunas, largura: m.largura, esperadas: esperadas(m.largura)});
  conferir('cada item com coluna e linha próprias, sem sobreposição', m.itens.every(i => /^\d+ \/ span \d+$/.test(i.coluna) && /^\d+ \/ span \d+$/.test(i.linha)) && !sobrepoe(m.itens), {sobrepoe: sobrepoe(m.itens), itens: m.itens.map(i => [i.coluna, i.linha])});
  conferir('posição guardada respeitada: texto no topo, círculo e ícone lado a lado abaixo dos widgets', m.itens[0].linha.startsWith('1 /') && m.itens[3].caixa.y === m.itens[4].caixa.y && m.itens[3].caixa.x < m.itens[4].caixa.x && m.itens[3].caixa.y > m.itens[1].caixa.y, m.itens.map(i => i.caixa));
  await foto('3-lousa-1366');
  const antes = m.itens.map(i => i.coluna + '|' + i.linha).join();
  await page.setViewportSize({width: 900, height: 900}); await page.waitForTimeout(600);
  m = await medir();
  conferir('ao estreitar para 900 px a lousa se rearruma com menos colunas e o item escondido sai', Number(m.colunas) === esperadas(m.largura) && m.itens.map(i => i.coluna + '|' + i.linha).join() !== antes && m.itens[2].visivel === false && !sobrepoe(m.itens), {colunas: m.colunas, largura: m.largura});
  await page.setViewportSize({width: 390, height: 800}); await page.waitForTimeout(600);
  m = await medir();
  const ys = m.itens.filter(i => i.visivel).map(i => i.caixa.y);
  conferir('em 390 px a lousa vira uma coluna, na ordem das posições, sem rolagem lateral', m.colunas === '1' && m.lateral && !sobrepoe(m.itens) && ys.every((y, n) => n === 0 || y > ys[n - 1]), {colunas: m.colunas, ys, lateral: m.lateral});
  await foto('4-lousa-390');
  conferir('nenhum erro de script nas páginas', erros.length === 0, erros);

  await browser.close();
  console.log('\n' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
