// req-275: gravação real pelo formulário do admin-paginas no Lab (conn2flow-site-local, local=true).
// Desliga a visibilidade, gera link novo e religa, conferindo o banco a cada passo. Termina visível.
const fs = require('node:fs');
const assert = require('node:assert/strict');
const { execSync } = require('node:child_process');
const { chromium } = require('playwright');

const URL = 'https://conn2flow.local/admin-paginas/editar/?id=comece-a-construir';
const banco = () => execSync(`ssh lab "sudo mariadb -N admin_conn2flow -e \\"SELECT visivel, hash_privado FROM paginas WHERE id='comece-a-construir' AND language='pt-br'\\""`)
  .toString().trim().split('\t');

function cookies() {
  return fs.readFileSync('temp/agent-cookies.txt', 'utf8').split(/\r?\n/)
    .filter(line => line && (!line.startsWith('#') || line.startsWith('#HttpOnly_')))
    .map(line => {
      const httpOnly = line.startsWith('#HttpOnly_');
      const [domain, , path, secure, expiry, name, value] = line.replace(/^#HttpOnly_/, '').split('\t');
      return { domain, path, name, value, secure: secure === 'TRUE', httpOnly, expires: Number(expiry) || -1 };
    });
}

async function salvar(page) {
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.evaluate(() => { const f = document.querySelector('input[name="visivel"]').form; f.requestSubmit ? f.requestSubmit() : f.submit(); }),
  ]);
}

async function main() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  await context.addCookies(cookies());
  const page = await context.newPage();
  const passos = [];
  try {
    const [, hashInicial] = banco();

    await page.goto(URL, { waitUntil: 'networkidle' });
    await page.locator('input[name="visivel"]').uncheck({ force: true });
    await salvar(page);
    let [visivel, hash] = banco();
    assert.equal(visivel, '0', 'desligar visível');
    assert.equal(hash, hashInicial, 'hash mantido ao só esconder');
    assert.equal(await page.isChecked('input[name="visivel"]'), false, 'tela recarregada mostra o estado gravado');
    passos.push({ passo: 'desligar visível', visivel, hashMantido: true });

    page.once('dialog', d => d.accept());
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.click('#page-visibility-regenerate')]);
    [visivel, hash] = banco();
    assert.notEqual(hash, hashInicial, 'gerar novo link troca o hash');
    assert.match(await page.inputValue('#page-visibility-link'), new RegExp(hash + '$'), 'tela mostra o link novo');
    passos.push({ passo: 'gerar novo link', hashTrocado: true, visivel });

    await page.locator('input[name="visivel"]').check({ force: true });
    await salvar(page);
    [visivel] = banco();
    assert.equal(visivel, '1', 'religar visível');
    passos.push({ passo: 'religar visível', visivel });
  } finally {
    await browser.close();
  }
  fs.writeFileSync('memory/validation/req275/save-results.json', JSON.stringify(passos, null, 2) + '\n');
  console.log('req-275 gravação: ' + passos.length + ' passos OK');
}

main().catch(e => { console.error(e); process.exit(1); });
