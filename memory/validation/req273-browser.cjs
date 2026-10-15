// Somente conn2flow-site-local. Não salva alterações no formulário existente.
const fs = require('node:fs');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

async function main() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1280, height: 900 } });
  const cookies = fs.readFileSync('temp/agent-cookies.txt', 'utf8').split(/\r?\n/)
    .filter(line => line && (!line.startsWith('#') || line.startsWith('#HttpOnly_')))
    .map(line => {
      const httpOnly = line.startsWith('#HttpOnly_');
      const [domain, , path, secure, expiry, name, value] = line.replace(/^#HttpOnly_/, '').split('\t');
      return { domain, path, name, value, secure: secure === 'TRUE', httpOnly, expires: Number(expiry) || -1 };
    });
  await context.addCookies(cookies);
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const results = [];
  try {
    for (const route of ['editar/?id=form-pro-acceleration', 'adicionar/', 'clonar/?id=form-pro-acceleration']) {
      const response = await page.goto('https://conn2flow.local/forms/' + route, { waitUntil: 'networkidle' });
      assert.equal(response.status(), 200);
      const option = await page.evaluate(() => {
        const node = document.querySelector('#field-row-template')?.content.querySelector('option[value="domain"]');
        return node && node.textContent.trim();
      });
      assert.equal(option, 'Domínio', route);
      results.push({ route, status: response.status(), option });
    }
    await page.goto('https://conn2flow.local/forms/editar/?id=form-pro-acceleration', { waitUntil: 'networkidle' });
    await page.locator('a[data-tab="forms-fields"]').click();
    const rows = page.locator('#forms-fields-table tbody tr');
    assert(await rows.count() > 0);
    // Exercita o seletor real e a serialização no campo oculto, sem gravar no banco.
    let domainRow;
    for (const row of await rows.all()) {
      if (await row.locator('.forms-field-name').inputValue() === 'domain') domainRow = row;
    }
    assert(domainRow, 'Campo domain existente');
    await domainRow.locator('.forms-field-type').selectOption('domain', { force: true });
    await page.waitForFunction(() => {
      const el = document.querySelector('input[name="fields_schema"]');
      return el && JSON.parse(el.value).fields.some(field => field.name === 'domain' && field.type === 'domain');
    });
    results.push({ check: 'domain selection serialized to fields_schema', ok: true });
    const previewDomain = page.frameLocator('#iframe-forms-preview').locator('input[name="domain"][autocapitalize="none"]');
    await previewDomain.waitFor({ state: 'attached' });
    for (const [attr, expected] of Object.entries({ type: 'text', spellcheck: 'false', autocomplete: 'off' })) {
      assert.equal(await previewDomain.getAttribute(attr), expected);
    }
    results.push({ check: 'widget preview renders domain attributes', ok: true });
    const screenshots = [];
    fs.mkdirSync('temp/req273-browser', { recursive: true });
    for (const width of [1280, 390]) {
      await page.setViewportSize({ width, height: 900 });
      if (width === 390 && await page.locator('[data-admin-fechar]').isVisible()) {
        await page.locator('[data-admin-fechar]').click();
        await page.waitForFunction(() => document.querySelector('[data-admin-sidebar]').getBoundingClientRect().right <= 0);
      }
      await page.waitForFunction(() => document.documentElement.scrollWidth <= innerWidth);
      const metrics = await page.evaluate(() => ({ width: innerWidth, overflow: document.documentElement.scrollWidth - innerWidth }));
      const path = `temp/req273-browser/forms-${width}.png`;
      await page.screenshot({ path, fullPage: true });
      screenshots.push(path);
      results.push(metrics);
    }
    // Recarregar descarta a alteração de teste e comprova que o editor continua acessível.
    await page.reload({ waitUntil: 'networkidle' });
    assert.equal(errors.length, 0, JSON.stringify(errors));
    const report = { project: 'conn2flow-site-local', date: new Date().toISOString(), results, errors, screenshots, savedChanges: false };
    fs.writeFileSync('memory/validation/req273-browser-results.json', JSON.stringify(report, null, 2) + '\n');
    console.log(JSON.stringify(report, null, 2));
  } finally { await browser.close(); }
}
main().catch(error => { console.error(error.message); process.exitCode = 1; });
