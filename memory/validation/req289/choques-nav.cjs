// REQ-286 r2 (diagnóstico): a aba de choques navega sem recarregar? Lab apenas.
const { chromium } = require('playwright');
const fs = require('fs');
(async () => {
  const jar = fs.readFileSync('temp/agent-cookies.txt', 'utf8');
  const cookies = jar.split(/\r?\n/).filter(l => l && !l.startsWith('#') || l.startsWith('#HttpOnly_')).map(l => {
    const http = l.startsWith('#HttpOnly_'); const p = (http ? l.slice(10) : l).split('\t');
    return p.length >= 7 ? { name: p[5], value: p[6], domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: http } : null;
  }).filter(Boolean);
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ ignoreHTTPSErrors: true });
  await ctx.addCookies(cookies);
  const page = await ctx.newPage();
  const erros = []; page.on('pageerror', e => erros.push(String(e))); page.on('console', m => { if (m.type() === 'error') erros.push(m.text()); });
  const navegacoes = []; page.on('framenavigated', f => { if (f === page.mainFrame()) navegacoes.push(f.url()); });
  await page.goto('https://conn2flow.local/admin-atualizacoes/', { waitUntil: 'networkidle' });
  const out = { url: page.url() };
  out.scripts = await page.$$eval('script[src]', s => s.map(x => x.src).filter(u => /atualizacoes/.test(u)));
  out.painel = await page.$('[data-c2f-painel="choques"]') !== null;
  out.links = await page.$$eval('[data-c2f-painel="choques"] [data-choques-filtro] a, [data-c2f-painel="choques"] [data-choques-paginacao] a', a => a.map(x => x.getAttribute('href')));
  await page.evaluate(() => { window.__marca = 'sem-reload'; });
  out.abas = await page.$$eval('[data-c2f-aba]', b => b.map(x => ({ aba: x.getAttribute('data-c2f-aba'), visivel: !!(x.offsetWidth || x.offsetHeight), sel: x.getAttribute('aria-selected') })));
  out.paineis = await page.$$eval('[data-c2f-painel]', p => p.map(x => ({ painel: x.getAttribute('data-c2f-painel'), hidden: x.hidden, display: getComputedStyle(x).display })));
  const botao = await page.$('[data-c2f-aba="choques"]');
  if (!botao) { console.log(JSON.stringify(out, null, 1)); await browser.close(); return; }
  await botao.click();
  await page.waitForTimeout(800);
  out.apos_clicar_aba = await page.$eval('[data-c2f-painel="choques"]', x => ({ hidden: x.hidden, display: getComputedStyle(x).display, html: x.innerHTML.slice(0, 300) }));
  const todos = await page.$('[data-c2f-painel="choques"] [data-choques-filtro] a[href="?choques=todos"]');
  out.todos_existe = !!todos;
  if (todos) {
    await todos.click();
    await page.waitForTimeout(2500);
    out.apos_todos = { url: page.url(), marca: await page.evaluate(() => window.__marca || null), aba_visivel: await page.$eval('[data-c2f-painel="choques"]', p => !p.hidden), linhas: await page.$$eval('[data-c2f-painel="choques"] tbody tr', r => r.length), paginas: await page.$$eval('[data-choques-paginacao] a', a => a.map(x => x.textContent.trim())) };
    const pg2 = await page.$('[data-choques-paginacao] a[href*="choques_pagina=2"]');
    console.log(JSON.stringify(out, null, 1));
    if (pg2 && out.apos_todos.aba_visivel) { await pg2.click(); await page.waitForTimeout(2500); out.apos_pagina2 = { url: page.url(), marca: await page.evaluate(() => window.__marca || null), aba_visivel: await page.$eval('[data-c2f-painel="choques"]', p => !p.hidden) }; }
  }
  out.navegacoes = navegacoes; out.erros = erros.slice(0, 8);
  console.log(JSON.stringify(out, null, 1));
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
