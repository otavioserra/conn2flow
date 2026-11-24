/**
 * REQ-304 / BATCH-313 — validação no Lab (conn2flow-site-local, https://conn2flow.local/).
 *
 * Repetível: `php cli/c2f.php auth:cookie --project=conn2flow-site-local` e depois
 * `node memory/validation/req304-browser.cjs`. Cookies e tokens nunca entram na evidência.
 *
 * Envia e-mails REAIS pelo SMTP do Lab; com MAIL_REDIRECT_TO ativo, todos chegam só à caixa de teste.
 */
const fs = require('node:fs');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

const origin = 'https://conn2flow.local/';
const destinoTeste = 'otavio@conn2flow.com';
const destinoOriginal = 'test_user_validacao@testuser.com';
const out = 'memory/validation/evidence-req304';
fs.mkdirSync(out, { recursive: true });

const results = [];
const errors = [];
function record(name, detail = {}) { results.push({ name, ok: true, ...detail }); }

function lerCookies() {
    return fs.readFileSync('temp/agent-cookies.txt', 'utf8').split(/\r?\n/)
        .filter(l => l && (!l.startsWith('#') || l.startsWith('#HttpOnly_')))
        .map(l => {
            const [dominio, , path, secure, expiry, name, value] = l.replace(/^#HttpOnly_/, '').split('\t');
            return { domain: dominio, path, name, value, expires: Number(expiry), secure: secure === 'TRUE', httpOnly: true };
        })
        .filter(c => c.name && c.value);
}

async function main() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1280, height: 900 } });
    await context.addCookies(lerCookies());
    const page = await context.newPage();
    page.on('pageerror', e => errors.push(e.message));
    page.on('console', m => { if (m.type() === 'error') errors.push(m.text().replace(/(token|csrf)[^&\s]*=[^&\s]+/gi, '$1=[oculto]')); });
    page.on('dialog', d => d.accept());

    async function goto(rota) {
        const r = await page.goto(new URL(rota, origin).href, { waitUntil: 'networkidle' });
        assert.equal(r.status(), 200, rota);
        assert(!page.url().includes('signin'), `${rota}: sessão não autenticada`);
    }

    async function inspecionar(nome) {
        const m = await page.evaluate(() => ({
            overflow: document.documentElement.scrollWidth - innerWidth,
            cru: /\[\[|\]\]@|#[a-z_]+_[a-z_]+#|#mail-redirect-[a-z-]+#/.test(document.querySelector('main, body').innerText),
            largura: innerWidth,
        }));
        assert(m.overflow <= 0, `${nome}: overflow ${m.overflow}`);
        assert(!m.cru, `${nome}: marcador não resolvido`);
        const shot = `${out}/${nome}.png`;
        await page.screenshot({ path: shot, fullPage: true });
        record(nome, { largura: m.largura, overflow: m.overflow, screenshot: shot });
    }

    try {
        // ===== admin-environment: campo MAIL_REDIRECT_TO e estado real
        await goto('admin-environment/');
        await page.locator('[data-tab="email"], [data-tab="emails"], button:has-text("Email"), button:has-text("E-mail")').first().click().catch(() => {});
        const campo = page.locator('#mail_redirect_to');
        assert.equal(await campo.inputValue(), destinoTeste);
        const estadoEnv = (await page.locator('#mail-redirect-box').innerText()).replace(/\s+/g, ' ');
        assert(/ATIVO|ON/.test(estadoEnv), 'admin-environment: estado ativo');
        assert(estadoEnv.includes('staging'), 'admin-environment: APP_ENV exibido');
        record('admin-environment-redirect', { valor: destinoTeste, estado: estadoEnv.slice(estadoEnv.indexOf('Redirecionamento ATIVO'), estadoEnv.indexOf('Redirecionamento ATIVO') + 60) });
        await page.locator('#mail-redirect-box').scrollIntoViewIfNeeded();
        await page.screenshot({ path: `${out}/admin-environment-email.png` });

        // ===== admin-comunicacao: painel, banner, cards
        await goto('admin-comunicacao/');
        await page.waitForSelector('#com-tabela-corpo tr');
        const banner = page.locator('#com-banner');
        assert(await banner.isVisible(), 'banner do modo teste visível');
        assert((await banner.innerText()).includes(destinoTeste), 'banner cita a caixa de teste');
        record('banner-modo-teste', { texto: (await banner.innerText()).trim() });
        assert.equal(await page.locator('#com-tabela-aviso').isVisible(), false, 'tabela existe (aviso de migration oculto)');
        await inspecionar('admin-comunicacao-desktop');

        // ===== Configurações (mesma fonte do admin-environment)
        await page.locator('.com-aba[data-aba="config"]').click();
        assert.equal(await page.locator('#com-cfg-redirect').inputValue(), destinoTeste);
        assert.equal(await page.locator('#com-cfg-modo').inputValue(), 'hibrido');
        assert.equal(await page.locator('#com-cfg-rate').inputValue(), '30');
        assert.equal(await page.locator('#com-cfg-tentativas').inputValue(), '4');
        assert.equal(await page.locator('#com-cfg-retencao').inputValue(), '90');
        assert.equal((await page.locator('#com-cfg-ambiente').innerText()).trim(), 'staging');
        record('configuracoes-carregadas', { modo: 'hibrido', rate: 30, tentativas: 4, retencao: 90, ambiente: 'staging' });
        await page.screenshot({ path: `${out}/admin-comunicacao-config.png`, fullPage: true });

        // ===== E-mail de teste para o endereço do sandbox do Mercado Pago
        await page.locator('#com-teste-destino').fill(destinoOriginal);
        const resposta = page.waitForResponse(r => r.request().method() === 'POST' && (r.request().postData() || '').includes('ajaxOpcao=testar'));
        await page.locator('#com-btn-testar').click();
        const json = await (await resposta).json();
        assert.equal(json.status, 'Ok', `teste: ${json.message}`);
        assert.equal(json.data.resultado.status, 'enviado', 'teste enviado pelo SMTP real do Lab');
        const idTeste = json.data.resultado.id;
        record('email-teste-enviado', { id: idTeste, status: json.data.resultado.status, mensagem: json.message });

        // ===== Histórico: primeira linha é a do teste, redirecionada
        await page.locator('.com-aba[data-aba="fila"]').click();
        await page.waitForFunction(id => !!document.querySelector(`#com-tabela-corpo button[data-acao="ver"][data-id="${id}"]`), idTeste);
        const linha = (await page.locator('#com-tabela-corpo tr').first().innerText()).replace(/\s+/g, ' ');
        assert(linha.includes(destinoTeste), 'destinatário efetivo = caixa de teste');
        assert(linha.includes(destinoOriginal), 'destinatário original visível');
        record('historico-linha', { linha });

        // ===== Modal: metadados + preview isolado; fecha com Escape
        await page.locator(`#com-tabela-corpo button[data-acao="ver"][data-id="${idTeste}"]`).click();
        await page.locator('#com-modal').waitFor({ state: 'visible' });
        const det = await page.evaluate(() => {
            const f = document.getElementById('com-det-preview');
            return {
                destinatario: document.getElementById('com-det-destinatario').textContent,
                original: document.getElementById('com-det-original').textContent,
                sandbox: f.getAttribute('sandbox'),
                srcdocBytes: (f.srcdoc || '').length,
                iframeAltura: Math.round(f.getBoundingClientRect().height),
            };
        });
        assert.equal(det.destinatario, destinoTeste);
        assert.equal(det.original, destinoOriginal);
        assert.equal(det.sandbox, '', 'iframe sandbox sem permissões');
        assert(det.srcdocBytes > 200, 'preview com o HTML final do layout de e-mail');
        await page.screenshot({ path: `${out}/admin-comunicacao-modal.png` });
        await page.keyboard.press('Escape');
        await page.locator('#com-modal').waitFor({ state: 'hidden' });
        record('modal-preview', det);

        // ===== Reenvio manual: nova linha, original intacta
        const reenvio = page.waitForResponse(r => r.request().method() === 'POST' && (r.request().postData() || '').includes('ajaxOpcao=reenviar'));
        await page.locator(`#com-tabela-corpo button[data-acao="reenviar"][data-id="${idTeste}"]`).click();
        const jr = await (await reenvio).json();
        assert.equal(jr.status, 'Ok', `reenvio: ${jr.message}`);
        assert.notEqual(jr.data.resultado.id, idTeste);
        assert.equal(jr.data.resultado.status, 'enviado');
        record('reenvio', { idOriginal: idTeste, idNovo: jr.data.resultado.id, status: jr.data.resultado.status });

        // ===== Filtro por status e busca
        await page.locator('#com-filtro-busca').fill('testuser.com');
        await page.waitForTimeout(800);
        await page.waitForLoadState('networkidle');
        const linhasBusca = await page.locator('#com-tabela-corpo button[data-acao="ver"]').count();
        assert(linhasBusca >= 2, 'busca encontra as duas mensagens do teste');
        record('busca-destinatario-original', { linhas: linhasBusca });

        // ===== Mobile
        await page.setViewportSize({ width: 390, height: 844 });
        await page.reload({ waitUntil: 'networkidle' });
        await page.waitForSelector('#com-tabela-corpo tr');
        await inspecionar('admin-comunicacao-mobile');

        assert.deepEqual(errors, [], 'sem erros de console');
        record('console-limpo', { erros: 0 });
    } finally {
        await browser.close();
        fs.writeFileSync(`${out}/resultado.json`, JSON.stringify({ origin, results, errors }, null, 2));
    }
}

main().then(() => { console.log(JSON.stringify(results, null, 2)); }).catch(e => {
    console.error('FALHA:', e.message);
    console.error(JSON.stringify({ results, errors }, null, 2));
    process.exit(1);
});
