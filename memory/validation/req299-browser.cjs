/** node memory/validation/req299-lab.cjs prepare; node memory/validation/req299-browser.cjs */
const { chromium } = require('@playwright/test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { remote, root, out } = require('./req299-lab.cjs');
const BASE = 'https://snapphoton.local/';
const fixture = JSON.parse(fs.readFileSync(path.join(out, 'prepare.json'), 'utf8'));
const checks = [];
function auth(id) {
  const file = path.join(out, 'cookie-' + id + '.txt');
  const r = spawnSync('php', ['cli/c2f.php', 'auth:cookie', '--project=snapphoton-local', '--user=' + id, '--out=' + file], { cwd: root, encoding: 'utf8', timeout: 60000 });
  if (r.status !== 0) throw new Error('Auth fixture failed, ID ' + id);
  return fs.readFileSync(file, 'utf8').split(/\r?\n/).filter(l => l.trim() && (!l.startsWith('#') || l.startsWith('#HttpOnly_'))).map(l => {
    const [domain, , cookiePath, secure, expires, name, value] = l.replace(/^#HttpOnly_/, '').split('\t');
    return { name, value, domain: domain.replace(/^\./, ''), path: cookiePath, secure: secure === 'TRUE', httpOnly: l.startsWith('#HttpOnly_'), expires: Number(expires) || -1 };
  });
}
async function check(name, fn) { await fn(); checks.push({ name, ok: true }); console.log('OK ' + name); }
async function save(page, name) { await page.screenshot({ path: path.join(out, name + '.png'), fullPage: true }); }
async function submit(page, button) {
  const response = page.waitForResponse(r => r.url().includes('/equipe/') && r.request().method() === 'POST');
  const loaded = page.waitForEvent('load', { timeout: 15000 });
  await page.locator(button).click();
  const r = await response;
  assert.equal(r.status(), 200);
  await loaded;
  await page.waitForTimeout(350);
}
async function main() {
  const browser = await chromium.launch({ headless: true });
  const errors = [];
  let ctx;
  try {
    ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1280, height: 900 } });
    await ctx.addCookies(auth(fixture.titular));
    const page = await ctx.newPage();
    page.on('pageerror', e => errors.push(e.message));
    const r = await page.goto(BASE + 'equipe/');
    assert.equal(r.status(), 200);
    await page.locator('#btn-equipe-novo').waitFor();
    for (const width of [1280, 390]) {
      await page.setViewportSize({ width, height: 900 });
      if (width === 390) {
        const close = page.locator('[data-admin-sidebar] [data-admin-fechar]');
        if (await close.count()) await close.click();
      }
      for (const tab of ['membros', 'perfis']) {
        await check(`${width}px aba ${tab}: indicador, transição e sem scroll vertical`, async () => {
          await page.locator(`[data-equipe-aba="${tab}"]`).click();
          await page.waitForTimeout(200);
          const metrics = await page.locator(`[data-equipe-aba="${tab}"]`).evaluate(n => {
            const s = getComputedStyle(n), p = n.parentElement;
            return { selected: n.getAttribute('aria-selected'), border: s.borderBottomWidth, color: s.borderBottomColor,
              transition: s.transitionProperty, overflow: getComputedStyle(p).overflowY, height: p.clientHeight, scroll: p.scrollHeight,
              pageOverflow: document.documentElement.scrollWidth - innerWidth };
          });
          assert.equal(metrics.selected, 'true');
          assert.equal(metrics.border, '2px');
          assert.notEqual(metrics.color, 'rgba(0, 0, 0, 0)');
          assert.ok(!metrics.color.includes('/ 0)'));
          assert.notEqual(metrics.transition, 'all');
          assert.ok(['hidden', 'clip'].includes(metrics.overflow));
          assert.ok(metrics.scroll <= metrics.height + 1);
          assert.ok(metrics.pageOverflow <= 0);
          await page.locator(`[data-equipe-painel="${tab}"]`).waitFor({ state: 'visible' });
          await page.locator(`[data-equipe-painel="${tab === 'membros' ? 'perfis' : 'membros'}"]`).waitFor({ state: 'hidden' });
          checks.push({ name: `${width}-${tab}-metrics`, metrics });
          await save(page, `${width}-${tab}`);
        });
      }
    }
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.locator('[data-equipe-aba="membros"]').click();
    await check('criar membro pela interface', async () => {
      await page.locator('#btn-equipe-novo').click();
      await page.locator('#equipe-nome').fill('REQ299 E2E Membro');
      await page.locator('#equipe-email').fill('req299-e2e-membro@teste.local');
      await page.locator('#equipe-senha').fill('Req299-Primeira-Senha');
      await page.locator('#equipe-perfil').selectOption(String(fixture.perfil));
      await submit(page, '#btn-equipe-criar');
      await page.locator('.btn-equipe-editar').waitFor();
      assert.equal(remote('member', fixture).length, 1);
    });
    const membro = Number(remote('member', fixture)[0].id_usuarios);
    const memberCtx = await browser.newContext({ ignoreHTTPSErrors: true });
    await memberCtx.addCookies(auth(membro));
    const memberPage = await memberCtx.newPage();
    await check('sessão de membro ativa acessa agenda', async () => {
      await memberPage.goto(BASE + 'agenda/');
      assert.ok(memberPage.url().endsWith('/agenda/'), memberPage.url());
    });
    await check('editar nome, e-mail/login e perfil pela interface', async () => {
      await page.locator('.btn-equipe-editar').click();
      await page.locator('#equipe-senha').waitFor({ state: 'hidden' });
      assert.equal(await page.locator('#equipe-nome').inputValue(), 'REQ299 E2E Membro');
      assert.equal(await page.locator('#equipe-email').inputValue(), 'req299-e2e-membro@teste.local');
      await page.locator('#equipe-nome').fill('REQ299 E2E Atualizado');
      await page.locator('#equipe-email').fill('req299-e2e-editado@teste.local');
      await page.locator('#equipe-perfil').selectOption(String(fixture.perfil2));
      await save(page, 'editar-membro');
      await submit(page, '#btn-equipe-criar');
      const row = remote('member', fixture)[0];
      assert.equal(row.nome, 'REQ299 E2E Atualizado');
      assert.equal(row.email, 'req299-e2e-editado@teste.local');
      assert.equal(row.usuario, row.email);
      assert.equal(Number(row.id_usuarios_perfis), fixture.perfil2);
      await memberPage.goto(BASE + 'agenda/');
      assert.ok(memberPage.url().includes('/signin'), memberPage.url());
    });
    await check('redefinir senha pela interface e conferir hash', async () => {
      await page.locator('.btn-equipe-senha').click();
      await page.locator('#equipe-nome').waitFor({ state: 'hidden' });
      await page.locator('#equipe-email').waitFor({ state: 'hidden' });
      await page.locator('#equipe-senha').fill("Req299-Nova-Senha-'Segura");
      await save(page, 'redefinir-senha');
      await submit(page, '#btn-equipe-criar');
      assert.equal(remote('verify-password', { ...fixture, membro, senha: "Req299-Nova-Senha-'Segura" }).valid, true);
    });
    await check('GET de exclusão recusado sem alterar membro', async () => {
      const r = await ctx.request.get(BASE + `equipe/?ajax=sim&ajaxOpcao=membro-excluir&id=${membro}`);
      assert.equal(r.status(), 405);
      assert.equal(remote('member', fixture).length, 1);
    });
    await check('POST sem CSRF recusado sem alterar membro', async () => {
      const r = await ctx.request.post(BASE + 'equipe/', { form: { ajax: 'sim', ajaxOpcao: 'membro-excluir', id: membro } });
      assert.equal(r.status(), 403);
      assert.equal(remote('member', fixture).length, 1);
    });
    for (const status of ['cancelled', 'suspended', 'expired']) {
      await memberCtx.clearCookies();
      await memberCtx.addCookies(auth(membro));
      await check('sessão aberta bloqueada pela assinatura ' + status, async () => {
        remote('subscription', { ...fixture, status });
        await memberPage.goto(BASE + 'agenda/');
        assert.ok(memberPage.url().includes('/signin'), memberPage.url());
        const html = await memberPage.content();
        assert.ok(html.includes('Peça ao titular para regularizar'), 'orientação ausente');
        await save(memberPage, 'bloqueio-' + status);
        remote('subscription', { ...fixture, status: 'payment-confirmed' });
      });
    }
    await memberCtx.clearCookies();
    await memberCtx.addCookies(auth(membro));
    await check('reativação devolve acesso sem alterar membro', async () => {
      await memberPage.goto(BASE + 'agenda/');
      assert.ok(memberPage.url().endsWith('/agenda/'), memberPage.url());
    });
    await check('exclusão confirmada remove membro e libera assento', async () => {
      await page.reload();
      const before = await page.locator('#equipe-assentos').innerText();
      await page.locator('.btn-equipe-excluir').click();
      const dialog = page.locator('.c2fc-dialogo');
      await dialog.waitFor();
      await page.keyboard.press('Escape');
      await dialog.waitFor({ state: 'hidden' });
      assert.equal(remote('member', fixture).length, 1);
      await page.locator('.btn-equipe-excluir').click();
      await dialog.waitFor();
      await save(page, 'excluir-confirmacao');
      const response = page.waitForResponse(r => r.url().includes('/equipe/') && r.request().method() === 'POST');
      const loaded = page.waitForEvent('load', { timeout: 15000 });
      await dialog.locator('button').last().click();
      assert.equal((await response).status(), 200);
      await loaded;
      await page.waitForTimeout(500);
      await page.reload();
      assert.equal(remote('member', fixture).length, 0);
      const after = await page.locator('#equipe-assentos').innerText();
      assert.notEqual(after, before);
      await memberPage.goto(BASE + 'agenda/');
      assert.ok(memberPage.url().includes('/signin'));
    });
    await check('nenhum erro JavaScript no módulo equipe', async () => assert.deepEqual(errors, []));
    await memberCtx.close();
  } finally {
    if (ctx) await ctx.close();
    await browser.close();
    const cleanup = remote('cleanup');
    assert.equal(cleanup.remaining, 0);
    checks.push({ name: 'fixtures removidas', ok: true });
    fs.writeFileSync(path.join(out, 'browser.json'), JSON.stringify(checks, null, 2));
  }
}
main().catch(e => { console.error(e.stack); process.exitCode = 1; });
