// REQ-259 — medição da lousa publicada na linha 3.1: como visitante, a página emite `c2f:analytics` na visita, quando
// um item entra na tela e no clique em link ou botão. Usa a página `/roteiro-req-252-grade/` (roteiro da REQ-252).
// Uso: C2F_BASE=https://v3.1-conn2flow.local C2F_PLAYWRIGHT=<pasta> node req259-browser.cjs
const {chromium} = require(process.env.C2F_PLAYWRIGHT || 'playwright');
const base = process.env.C2F_BASE || 'https://v3.1-conn2flow.local';
let falhas = 0, total = 0;
const conferir = (nome, ok, extra) => { total++; if (!ok) falhas++; console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok || extra === undefined ? '' : ' ' + JSON.stringify(extra).slice(0, 900))); };

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 700}});
  const page = await ctx.newPage();
  const erros = [], pedidos = [];
  page.on('pageerror', e => erros.push(e.message.slice(0, 160)));
  page.on('request', r => { const u = r.url(); if (!u.startsWith(base) && !u.startsWith('data:')) pedidos.push(r.resourceType() + ' ' + u.slice(0, 90)); });
  // Quem escuta é a página: aqui o roteiro faz o papel do módulo de análise.
  await page.addInitScript(() => { window.__eventos = []; document.addEventListener('c2f:analytics', e => window.__eventos.push(e.detail)); });
  // Linha de base: uma página do mesmo layout, sem lousa. O que o layout pede a terceiros e escreve na camada de dados
  // não é da lousa.
  await page.goto(base + '/plataforma/', {waitUntil: 'networkidle'});
  await page.waitForTimeout(800);
  const servidor = u => { try { return new URL(u.split(' ')[1]).hostname; } catch (e) { return u; } };
  const baseTerceiros = new Set(pedidos.map(servidor));
  const baseCamada = await page.evaluate(() => Array.isArray(window.dataLayer) ? window.dataLayer.length : 0);
  pedidos.length = 0;
  const r = await page.goto(base + '/roteiro-req-252-grade/', {waitUntil: 'networkidle'});
  await page.waitForTimeout(1500);
  const eventos = () => page.evaluate(() => window.__eventos);
  const marca = await page.evaluate(() => { const l = document.querySelector('[data-c2f-lousa]'); return l ? {lousa: l.getAttribute('data-lousa'), itens: [...l.children].map(i => i.getAttribute('data-item') + ':' + i.getAttribute('data-tipo'))} : null; });
  conferir('a página traz a lousa com identificador e a posição e o tipo de cada item', r.status() === 200 && !!marca && marca.lousa === 'roteiro-req-252-grade' && marca.itens.length === 7 && marca.itens[0] === '1:objeto-text' && marca.itens[6] === '7:objeto-button', marca);

  let e = await eventos();
  const visita = e.filter(x => x.event === 'lousa_view');
  conferir('visita: um evento, com a lousa, o modo e a quantidade de itens', visita.length === 1 && JSON.stringify(visita[0].data) === JSON.stringify({lousa: 'roteiro-req-252-grade', modo: 'grade', itens: 7}), visita);

  // Itens vistos: rola a página inteira e volta.
  await page.evaluate(async () => { for (let y = 0; y <= document.documentElement.scrollHeight; y += 250) { scrollTo(0, y); await new Promise(r => setTimeout(r, 120)); } scrollTo(0, 0); });
  await page.waitForTimeout(600);
  await page.evaluate(async () => { for (let y = 0; y <= document.documentElement.scrollHeight; y += 250) { scrollTo(0, y); await new Promise(r => setTimeout(r, 60)); } scrollTo(0, 0); });
  await page.waitForTimeout(400);
  e = await eventos();
  const vistos = e.filter(x => x.event === 'lousa_item_view').map(x => x.data);
  const posicoes = vistos.map(v => v.item).sort((a, b) => a - b);
  conferir('item visto: uma vez por item, mesmo rolando a página duas vezes', posicoes.length === new Set(posicoes).size && posicoes.length >= 6 && vistos.every(v => v.lousa === 'roteiro-req-252-grade' && v.tipo), {posicoes, total: vistos.length});
  conferir('o item com título do autor leva o título no evento', vistos.some(v => v.item === 2 && v.titulo === 'Título do autor'), vistos.filter(v => v.item === 2));

  // Clique no botão de chamada, sem sair da página.
  await page.evaluate(() => document.addEventListener('click', ev => { if (ev.target.closest('a')) ev.preventDefault(); }, true));
  await page.evaluate(() => document.querySelector('a.c2f-lousa-acao').scrollIntoView({block: 'center'}));
  await page.click('a.c2f-lousa-acao');
  await page.evaluate(() => document.querySelector('.c2f-lousa-texto').click());
  await page.waitForTimeout(300);
  e = await eventos();
  const cliques = e.filter(x => x.event === 'lousa_click').map(x => x.data);
  conferir('clique no botão: um evento, com a lousa, o item, o tipo, o texto e o destino', cliques.length === 1 && JSON.stringify(cliques[0]) === JSON.stringify({lousa: 'roteiro-req-252-grade', item: 6, tipo: 'objeto-button', titulo: '', texto: 'Conheça os planos', destino: '/plataforma/'}), cliques);
  conferir('todo evento segue o contrato do módulo de análise: nome e dados', e.length >= 8 && e.every(x => typeof x.event === 'string' && /^[a-z_]+$/.test(x.event) && x.data && typeof x.data === 'object'), e.length);

  // O core só avisa a página: além do que o layout do site já pede e da fonte que a lousa usa, nada vai para terceiros,
  // e a lousa não escreve na camada de dados.
  // Imagem, fonte e folha de estilo vêm do conteúdo dos widgets da lousa. A medição, se falasse com alguém, apareceria
  // como chamada de dados, script ou sinal a um servidor que a página sem lousa não usa.
  const novos = pedidos.filter(u => !baseTerceiros.has(servidor(u)) && !/^(image|font|stylesheet|media) /.test(u));
  const camada = await page.evaluate(() => (Array.isArray(window.dataLayer) ? window.dataLayer : []).map(x => { try { return JSON.stringify(x); } catch (e) { return ''; } }));
  conferir('a medição não faz chamada de dados, script nem sinal a terceiros, e não escreve na camada de dados', novos.length === 0 && camada.length === baseCamada && !camada.some(x => /lousa/i.test(x)), {novos, camada: camada.length, baseCamada});
  conferir('nenhum erro de script na página', erros.length === 0, erros);

  await browser.close();
  console.log('\n' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
