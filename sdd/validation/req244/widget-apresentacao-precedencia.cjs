// Widget de apresentação no Dashboard x a mesma apresentação como página pública, slide a slide.
// Navega pela seta, como o operador, e compara o estilo computado de cada elemento na mesma largura.
// Uso: C2F_PLAYWRIGHT=<pasta do playwright> C2F_COOKIES=<cookies do auth:cookie> node widget-apresentacao-precedencia.cjs
const fs = require('node:fs'), path = require('node:path');
const {chromium} = require(process.env.C2F_PLAYWRIGHT || 'playwright');
const base = process.env.C2F_BASE || 'https://conn2flow.local';
const rota = process.env.C2F_APRESENTACAO || 'apresentacoes/conn2flow-widget/';
const saida = path.join(__dirname, 'evidencias-precedencia');
fs.mkdirSync(saida, {recursive: true});
const jar = fs.readFileSync(process.env.C2F_COOKIES, 'utf8').split(/\r?\n/).filter(l => l.startsWith('#HttpOnly_') || (l && !l.startsWith('#'))).map(l => {
  const p = l.replace(/^#HttpOnly_/, '').split('\t');
  return {domain: p[0].replace(/^\./, ''), path: p[2], secure: p[3] === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), name: p[5], value: p[6]};
});
const PROPS = ['display', 'position', 'fontSize', 'fontWeight', 'color', 'backgroundColor', 'backgroundImage', 'paddingTop', 'paddingLeft', 'marginTop', 'marginBottom', 'borderTopLeftRadius', 'borderTopWidth', 'gridTemplateColumns', 'flexDirection', 'gap', 'textAlign', 'lineHeight', 'letterSpacing', 'textTransform', 'width', 'height', 'maxWidth', 'alignItems', 'justifyContent', 'top', 'left'];
const ler = props => {
  const d = document.querySelector('[data-c2f-deck]'), s = d.querySelector('[data-slide].is-active');
  return {indice: [...d.querySelectorAll('[data-slide]')].indexOf(s), itens: [s, ...s.querySelectorAll('*')].map(e => { const c = getComputedStyle(e), o = {}; props.forEach(p => o[p] = c[p]); return {cls: e.getAttribute('class') || '', o}; })};
};
let falhas = 0, total = 0;
const conferir = (nome, ok, extra) => { total++; if (!ok) falhas++; console.log((ok ? '  ok    ' : '  FALHA ') + nome + (ok || extra === undefined ? '' : ' ' + JSON.stringify(extra).slice(0, 700))); };

(async () => {
  const browser = await chromium.launch({headless: true, args: ['--host-resolver-rules=MAP ' + new URL(base).hostname + ' 127.0.0.1']});
  const ctx = await browser.newContext({ignoreHTTPSErrors: true, viewport: {width: 1366, height: 900}});
  await ctx.addCookies(jar);
  const page = await ctx.newPage();
  await page.goto(base + '/dashboard/', {waitUntil: 'networkidle'});
  await page.waitForTimeout(5000);
  const quadros = [];
  for (const f of page.frames()) { if (f === page.mainFrame()) continue; try { quadros.push({f, deck: await f.evaluate(() => !!document.querySelector('[data-c2f-deck]'))}); } catch (e) { } }
  const alvo = quadros.find(q => q.deck);
  conferir('o Dashboard tem o widget de apresentação', !!alvo);
  if (!alvo) { await browser.close(); process.exit(1); }
  const quadro = alvo.f, el = await quadro.frameElement();
  await el.scrollIntoViewIfNeeded();

  // A folha completa do compilador é a última das utilities: nenhuma folha parcial pré-compilada depois dela.
  const ordem = await quadro.evaluate(() => { const folhas = [...document.head.querySelectorAll('style')]; const gerada = folhas.findIndex(s => !s.attributes.length && /@layer/.test(s.textContent) && s.textContent.length > 5000); return {gerada, depois: folhas.slice(gerada + 1).filter(s => s.getAttribute('data-tailwind-role')).map(s => s.getAttribute('data-tailwind-role')), parciais: folhas.filter(s => /precompiled/.test(s.getAttribute('data-tailwind-role') || '')).length}; });
  conferir('folha gerada pelo compilador existe e nenhuma pré-compilada vem depois dela', ordem.gerada >= 0 && ordem.depois.length === 0, ordem);

  const tam = await quadro.evaluate(() => [innerWidth, innerHeight]);
  const pub = await ctx.newPage();
  await pub.setViewportSize({width: tam[0], height: tam[1]});
  await pub.goto(base + '/' + rota, {waitUntil: 'networkidle'});
  await pub.waitForTimeout(1200);
  await pub.evaluate(() => document.querySelectorAll('[data-c2f-cookie-consent], .c2f-cookie-consent, [id*="cookie-consent"]').forEach(b => b.style.display = 'none'));
  const parciaisPub = await pub.evaluate(() => [...document.querySelectorAll('style[data-tailwind-role]')].map(s => s.getAttribute('data-tailwind-role')));
  conferir('página pública sem folha parcial de template depois da folha da página', parciaisPub.indexOf('resource-precompiled') === -1, parciaisPub);

  const slides = await quadro.evaluate(() => document.querySelectorAll('[data-c2f-deck] [data-slide]').length);
  conferir('a apresentação tem mais de um slide', slides > 1, slides);
  for (let i = 0; i < slides; i++) {
    if (i > 0) {
      // Clique real na seta, nos dois documentos.
      await quadro.locator('[data-c2f-deck-next]').first().click();
      await pub.locator('[data-c2f-deck-next]').first().click();
    }
    await page.waitForTimeout(1300);
    const a = await pub.evaluate(ler, PROPS), b = await quadro.evaluate(ler, PROPS);
    const difs = [];
    for (let k = 0; k < Math.min(a.itens.length, b.itens.length); k++) {
      const d = PROPS.filter(p => a.itens[k].o[p] !== b.itens[k].o[p]);
      if (d.length) difs.push({cls: a.itens[k].cls.slice(0, 80), dif: d.slice(0, 4).map(p => p + ': ' + a.itens[k].o[p] + ' => ' + b.itens[k].o[p])});
    }
    // Responsiva `md:grid-cols-*` aplicada de fato (a largura do iframe passa de 768 px).
    const grades = b.itens.filter(x => /(^| )md:grid-cols-/.test(x.cls)).map(x => ({cls: (x.cls.match(/md:grid-cols-\S+/) || [''])[0], colunas: x.o.gridTemplateColumns.split(' ').length}));
    const linhas = b.itens.filter(x => /(^| )md:flex-row( |$)/.test(x.cls) && /flex/.test(x.o.display)).map(x => x.o.flexDirection);
    conferir('slide ' + (i + 1) + ': widget igual à página (' + b.itens.length + ' elementos)', a.indice === i && b.indice === i && a.itens.length === b.itens.length && difs.length === 0, {indices: [a.indice, b.indice], difs: difs.slice(0, 4)});
    if (tam[0] >= 768 && (grades.length || linhas.length)) conferir('slide ' + (i + 1) + ': responsivas md: vencem a utility simples', grades.every(g => g.colunas > 1) && linhas.every(d => d === 'row'), {grades, linhas});
    await el.screenshot({path: path.join(saida, 'slide-' + (i + 1) + '-widget.jpg'), type: 'jpeg', quality: 60});
    await pub.screenshot({path: path.join(saida, 'slide-' + (i + 1) + '-pagina.jpg'), type: 'jpeg', quality: 60});
  }

  // Os outros widgets continuam inteiros: documento sem rolagem lateral e com conteúdo.
  for (const q of quadros.filter(x => !x.deck)) {
    const o = await q.f.evaluate(() => ({lateral: document.documentElement.scrollWidth <= innerWidth + 1, texto: document.body.innerText.trim().length, nos: document.body.querySelectorAll('*').length}));
    const card = await (await q.f.frameElement()).evaluate(f => f.closest('.dashboard-widget-card').dataset.widgetId);
    conferir('widget ' + card + ' sem rolagem lateral e com conteúdo', o.lateral && o.nos > 3, o);
  }
  await browser.close();
  console.log('\n' + (total - falhas) + '/' + total + ' conferências');
  process.exit(falhas ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
