// req-275: linha "Visibilidade" nos formulários do admin-paginas e do publisher-pages (conn2flow-site-local).
// Só navega e interage; não salva nenhum formulário. Uso: node memory/validation/req275-browser.cjs
const fs = require('node:fs');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

const BASE = 'https://conn2flow.local/';
const ROTAS = [
  'admin-paginas/adicionar/',
  'admin-paginas/editar/?id=comece-a-construir',
  'publisher-pages/adicionar/',
  'publisher-pages/editar/?id=docs-guides-documentation',
];
const SAIDA = 'memory/validation/req275';

function cookies() {
  return fs.readFileSync('temp/agent-cookies.txt', 'utf8').split(/\r?\n/)
    .filter(line => line && (!line.startsWith('#') || line.startsWith('#HttpOnly_')))
    .map(line => {
      const httpOnly = line.startsWith('#HttpOnly_');
      const [domain, , path, secure, expiry, name, value] = line.replace(/^#HttpOnly_/, '').split('\t');
      return { domain, path, name, value, secure: secure === 'TRUE', httpOnly, expires: Number(expiry) || -1 };
    });
}

async function main() {
  fs.mkdirSync(SAIDA, { recursive: true });
  const browser = await chromium.launch({ headless: true });
  const resultados = [];
  try {
    for (const largura of [1280, 390]) {
      const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: largura, height: 900 } });
      await context.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: BASE });
      await context.addCookies(cookies());
      const page = await context.newPage();
      const erros = [];
      page.on('pageerror', e => erros.push(e.message));
      page.on('console', m => { if (m.type() === 'error') erros.push(m.text()); });

      for (const rota of ROTAS) {
        erros.length = 0;
        const resp = await page.goto(BASE + rota, { waitUntil: 'networkidle' });
        const bloco = page.locator('.page-visibility');
        await bloco.scrollIntoViewIfNeeded();
        const medida = await page.evaluate(() => {
          const el = document.querySelector('.page-visibility');
          const chave = document.querySelector('input[name="visivel"]');
          const link = document.querySelector('#page-visibility-link');
          const r = el.getBoundingClientRect();
          return {
            larguraBloco: Math.round(r.width),
            altura: Math.round(r.height),
            colunas: getComputedStyle(el).gridTemplateColumns,
            rolagemHorizontal: document.documentElement.scrollWidth - window.innerWidth,
            visivelMarcado: chave.checked,
            semPermissaoMarcado: document.querySelector('input[name="sem_permissao"]')?.checked ?? null,
            link: link.value,
            botaoCopiarVisivel: getComputedStyle(document.querySelector('#page-visibility-copy')).display !== 'none',
            marcadorCru: /#visibility-|#page-visibility#/.test(document.body.innerHTML),
            antesDoAgendamento: (() => {
              const ag = [...document.querySelectorAll('h4')].find(h => /Agendamento|Scheduling/i.test(h.textContent));
              return ag ? !!(el.compareDocumentPosition(ag) & Node.DOCUMENT_POSITION_FOLLOWING) : null;
            })(),
          };
        });
        const nome = rota.replace(/[^a-z0-9]+/gi, '-').replace(/-+$/, '') + '-' + largura;
        await bloco.screenshot({ path: `${SAIDA}/${nome}.png` });

        assert.equal(resp.status(), 200, rota);
        assert.equal(medida.marcadorCru, false, rota);
        assert.ok(medida.rolagemHorizontal <= 0, `${rota} rola na horizontal (${medida.rolagemHorizontal})`);
        assert.equal(medida.visivelMarcado, true, rota);
        assert.notEqual(medida.antesDoAgendamento, false, rota);
        if (rota.includes('adicionar')) {
          assert.equal(medida.semPermissaoMarcado, true, `${rota}: acesso público marcado por padrão`);
          assert.equal(medida.link, '', rota);
          assert.equal(medida.botaoCopiarVisivel, false, `${rota}: sem link, sem copiar`);
        } else {
          assert.match(medida.link, /\?acesso=[a-f0-9]{32}$/, rota);
          assert.equal(medida.botaoCopiarVisivel, true, rota);
        }
        assert.deepEqual(erros, [], `${rota}: erros de console`);
        resultados.push({ rota, largura, status: resp.status(), ...medida, link: medida.link ? medida.link.replace(/[a-f0-9]{32}$/, '<hash>') : '' });
      }

      if (largura === 1280) {
        // Copiar: o link vai para a área de transferência e o botão avisa.
        await page.goto(BASE + 'admin-paginas/editar/?id=comece-a-construir', { waitUntil: 'networkidle' });
        const link = await page.inputValue('#page-visibility-link');
        await page.click('#page-visibility-copy');
        await page.waitForFunction(() => /copiado|copied/i.test(document.querySelector('#page-visibility-copy').textContent));
        assert.equal(await page.evaluate(() => navigator.clipboard.readText()), link);
        // Gerar novo link cancelado: nada muda e nada é enviado.
        page.once('dialog', d => d.dismiss());
        await page.click('#page-visibility-regenerate');
        assert.equal(await page.inputValue('input[name="regenerar_hash_privado"]'), '');
        assert.equal(page.url(), BASE + 'admin-paginas/editar/?id=comece-a-construir');
        resultados.push({ interacao: 'copiar e cancelar gerar novo link', ok: true });
      }
      await context.close();
    }
  } finally {
    await browser.close();
  }
  fs.writeFileSync(`${SAIDA}/browser-results.json`, JSON.stringify(resultados, null, 2) + '\n');
  console.log(`req-275: ${resultados.length} verificações OK`);
}

main().catch(e => { console.error(e); process.exit(1); });
