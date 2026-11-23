/**
 * REQ-305 / BATCH-314 — derivado de `conn2flow-site/memory/validation/ecommerce/req149/req149-checkout.cjs`
 * (REQ-149 / BATCH-143) para comprovar Pix e Cartão Parcelado pela Orders API v1 (`/v1/orders`) no Lab.
 * Diferenças: saída nesta pasta, Playwright do Core, e-mails `req305-*@testuser.com` e registro de
 * todas as respostas `payment-process` (status HTTP, code) para provar a ausência de 401.
 * Uso: CENARIOS=cartao,pix node req305-checkout.cjs
 *
 * Origem (REQ-149 / BATCH-143) — E2E do checkout multi-gateway no Lab (https://conn2flow.local/).
 *
 * Pedido novo do produto `mentoria-teste-parcelamento` por cenário (visitante → formulário do
 * comprador → pagamento) e conferência na tela:
 *   avista   — Stripe (1º) aberto direto; Mercado Pago e PayPal em botões abaixo; seletor visível.
 *   cartao   — Parcelado: só o Mercado Pago; compra com o cartão de teste aprovado (titular APRO).
 *   pix      — À Vista → botão Mercado Pago → Pix: QR Code e copia-e-cola na tela.
 *   paypal   — À Vista → botão PayPal: botões do SDK renderizados.
 * Uso: CENARIOS=avista,cartao,pix,paypal node req149-checkout.cjs  (saída: results-checkout.json)
 * Grava em `pedidos-checkout.json` o número de cada pedido para a conferência no banco.
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require(process.env.PLAYWRIGHT_PATH || path.resolve(__dirname, '../../../node_modules/playwright'));

const BASE = 'https://conn2flow.local/';
const PRODUTO = process.env.PRODUTO || 'mentoria-teste-parcelamento';
const OUT = process.env.EVIDENCE_DIR ? path.resolve(process.env.EVIDENCE_DIR) : __dirname;
fs.mkdirSync(OUT, { recursive: true });
const CENARIOS = (process.env.CENARIOS || 'cartao,pix').split(',');
const PARCELAS = Number(process.env.PARCELAS || '3');
const CARTAO_TESTE = process.env.CARTAO_TESTE === 'master' ? '5480832801033311' : '4235647728025682';
if (!Number.isInteger(PARCELAS) || PARCELAS < 1 || PARCELAS > 12) throw new Error('PARCELAS deve ser um inteiro de 1 a 12.');
const resultados = [];
const pedidos = {};
const registrar = (nome, ok, extra) => { resultados.push({ nome, ok: !!ok, ...(extra ? { extra } : {}) }); console.log((ok ? 'OK   ' : 'FALHA') + ' ' + nome + (extra ? ' — ' + JSON.stringify(extra) : '')); };

async function novoPedido(browser, rotulo, largura) {
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: largura || 1280, height: 1000 }, locale: 'pt-BR' });
    const page = await ctx.newPage();
    page.erros = [];
    page.on('pageerror', (e) => page.erros.push(String(e).slice(0, 200)));
    await instrumentar(page);
    await page.goto(BASE + 'checkout/?product=' + encodeURIComponent(PRODUTO), { waitUntil: 'networkidle' });
    const recusarCookies = page.getByRole('button', { name: 'Recusar opcionais' });
    if (await recusarCookies.isVisible()) await recusarCookies.click();
    await page.fill('[name="name"]', 'Teste REQ-305 ' + rotulo);
    await page.fill('[name="email"]', 'req305-' + rotulo + '-' + Date.now() + '@testuser.com');
    await Promise.all([
        page.waitForURL((u) => /order=/.test(String(u)), { timeout: 30000 }),
        page.evaluate(() => { const f = document.querySelector('[name="email"]').form; const b = f.querySelector('button[type=submit], input[type=submit]'); if (b) b.click(); else f.requestSubmit(); }),
    ]);
    await page.waitForLoadState('networkidle');
    pedidos[rotulo] = new URL(page.url()).searchParams.get('order');
    return { ctx, page };
}

const tela = (page) => page.evaluate(() => ({
    modalidades: !document.querySelector('[data-checkout-modalidades]').hidden,
    selecionada: (document.querySelector('[data-modalidade][aria-selected="true"]') || {}).dataset ? document.querySelector('[data-modalidade][aria-selected="true"]').dataset.modalidade : null,
    outros: Array.from(document.querySelectorAll('[data-checkout-outros] [data-gateway]')).map((b) => b.dataset.gateway),
    grade: Array.from(document.querySelectorAll('[data-checkout-grade] [data-gateway]')).map((b) => b.dataset.gateway),
    pagar: !document.querySelector('[data-checkout-pay]').hidden,
    nota: document.querySelector('[data-checkout-gateway-note]').textContent,
    legenda: document.querySelector('[data-checkout-legenda]').textContent,
    iframes: Array.from(document.querySelectorAll('#checkout-payment-element iframe')).map((f) => (f.name || f.title || 'iframe').slice(0, 40)).slice(0, 4),
    brick: !!document.querySelector('#checkout-payment-element [class*="mp-"], #checkout-payment-element form, #checkout-payment-element section'),
    pix: !document.querySelector('[data-checkout-pix]').hidden,
    mensagem: (document.querySelector('[data-checkout-message]:not(.hidden)') || {}).textContent || null,
    largura: document.documentElement.scrollWidth,
}));

/**
 * REQ-305: a resposta de `payment-process` é lida dentro da página (XMLHttpRequest instrumentado
 * por init script + exposeFunction), antes de o checkout navegar para a página de sucesso —
 * `response.json()` falhava após a navegação e `page.route` não interceptava o XHR do checkout.
 * Registra HTTP e corpo em `respostas-payment-process.json` (prova da ausência de 401).
 */
const processos = [];
const esperas = new WeakMap();
async function instrumentar(page) {
    await page.exposeFunction('__req305Processo', (http, texto, parcelasBrick, parcelasEnviadas) => {
        let corpo;
        try { corpo = JSON.parse(texto); } catch (e) { corpo = { bruto: String(texto).slice(0, 300) }; }
        processos.push({ http, status: corpo.status, code: corpo.code || null, payment_status: corpo.payment_status || null, pix: !!corpo.pix, status_detail: corpo.status_detail || null, parcelasBrick, parcelasEnviadas });
        const espera = esperas.get(page);
        if (espera) { esperas.delete(page); espera(corpo); }
    });
    await page.addInitScript(() => {
        const enviar = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.send = function (corpo) {
            if (typeof corpo === 'string' && corpo.indexOf('payment-process') !== -1) {
                const dados = new URLSearchParams(corpo);
                const pagamento = JSON.parse(dados.get('payment') || '{}');
                const parcelas = pagamento.installments ?? null;
                this.addEventListener('load', () => {
                    window.__req305Processo(this.status, this.responseText, parcelas, parcelas);
                });
            }
            return enviar.apply(this, arguments);
        };
    });
}
function capturarProcesso(page) {
    return new Promise((resolve, reject) => {
        const limite = setTimeout(() => reject(new Error('payment-process sem resposta em 90 s')), 90000);
        esperas.set(page, (corpo) => { clearTimeout(limite); resolve(corpo); });
    });
}

async function esperarBrick(page) {
    await page.locator('#checkout-payment-element').getByText(/Cartão de crédito|Credit card/).first().waitFor({ timeout: 45000 });
    await page.waitForTimeout(1000);
}

(async () => {
    const browser = await chromium.launch();

    if (CENARIOS.includes('avista')) {
        for (const largura of [1280, 390]) {
            const { ctx, page } = await novoPedido(browser, 'avista' + largura, largura);
            await page.waitForFunction(() => !document.querySelector('[data-checkout-pay]').hidden, null, { timeout: 45000 }).catch(() => null);
            const t = await tela(page);
            registrar(largura + 'px à vista: Stripe aberto, seletor e Mercado Pago/PayPal abaixo', t.modalidades && t.selecionada === 'cash' && t.pagar && t.outros.join() === 'mercado-pago-sandbox,paypal-sandbox' && /Stripe/.test(t.nota), t);
            registrar(largura + 'px à vista: sem rolagem horizontal e sem erro de JS', t.largura <= largura && page.erros.length === 0, { largura: t.largura, erros: page.erros });
            await page.screenshot({ path: path.join(OUT, 'checkout-' + largura + '-avista-stripe.png'), fullPage: true });
            await ctx.close();
        }
    }

    if (CENARIOS.includes('cartao')) {
        const { ctx, page } = await novoPedido(browser, 'cartao');
        await page.waitForFunction(() => !document.querySelector('[data-checkout-pay]').hidden, null, { timeout: 45000 }).catch(() => null);
        await page.click('[data-modalidade="installments"]');
        await esperarBrick(page);
        const t = await tela(page);
        registrar('parcelado: só Mercado Pago, sem botões alternativos, Stripe desmontado', t.selecionada === 'installments' && t.outros.length === 0 && !t.pagar && /Mercado Pago/.test(t.nota), t);
        await page.screenshot({ path: path.join(OUT, 'checkout-1280-parcelado-mp.png'), fullPage: true });

        // Cartão de teste do Mercado Pago, titular APRO e CPF de teste.
        const brick = page.locator('#checkout-payment-element');
        await brick.getByText(/Cartão de crédito/).first().click();
        await page.waitForTimeout(2500);
        const quadro = (nome) => page.frames().find((f) => f.name() === nome);
        await quadro('cardNumber').locator('input:visible').first().fill(CARTAO_TESTE);
        await quadro('expirationDate').locator('input:visible').first().fill('11/30');
        await quadro('securityCode').locator('input:visible').first().fill('123');
        await brick.getByPlaceholder(/Maria Santos Pereira|Name/).first().fill('APRO');
        await brick.getByPlaceholder(/999\.999\.999-99/).first().fill('12345678909');
        await page.waitForTimeout(3000);
        // REQ-305: parcelamento real (PARCELAS, padrão 3) — o Brick sugere 1x por padrão.
        const parcelas = String(PARCELAS);
        const selects = await brick.locator('select').evaluateAll((els) => els.map((s) => ({ name: s.name, id: s.id, opcoes: Array.from(s.options).map((o) => o.value + '|' + o.textContent.trim()).slice(0, 14) })));
        console.log('selects do Brick: ' + JSON.stringify(selects));
        const alvo = selects.findIndex((s) => s.opcoes.some((o) => o.startsWith(parcelas + '|' + parcelas + 'x')));
        if (alvo >= 0) await brick.locator('select').nth(alvo).selectOption(parcelas);
        else if (PARCELAS > 1) {
            const gatilho = brick.getByText(/1x de|Parcelas|Installments/).first();
            await gatilho.click({ timeout: 5000 });
            await brick.getByText(new RegExp('^\\s*' + parcelas + 'x')).first().click({ timeout: 5000 });
        }
        const processo = capturarProcesso(page);
        await brick.getByRole('button', { name: /Pagar|Pay/ }).first().click();
        const resposta = await processo;
        const ultimo = processos.at(-1);
        registrar('parcelado: inclui o número solicitado de parcelas', Number(ultimo.parcelasEnviadas) === PARCELAS, {
            solicitado: PARCELAS, brick: ultimo.parcelasBrick, enviado: ultimo.parcelasEnviadas,
        });
        registrar('parcelado: pagamento criado no servidor e aprovado', resposta.status === 'Ok' && resposta.payment_status === 'paid', { status: resposta.status, payment_status: resposta.payment_status, code: resposta.code, message: resposta.message });
        await page.waitForURL(/checkout\/success\//, { timeout: 30000 }).catch(() => null);
        registrar('parcelado: segue para a página de sucesso', /checkout\/success\//.test(page.url()));
        await page.screenshot({ path: path.join(OUT, 'checkout-1280-sucesso-mp.png'), fullPage: true });
        await ctx.close();
    }

    if (CENARIOS.includes('pix')) {
        const { ctx, page } = await novoPedido(browser, 'pix');
        await page.waitForFunction(() => document.querySelectorAll('[data-checkout-outros] [data-gateway]').length > 0, null, { timeout: 45000 });
        await page.click('[data-checkout-outros] [data-gateway="mercado-pago-sandbox"]');
        await esperarBrick(page);
        const t = await tela(page);
        registrar('à vista → Mercado Pago: Brick com Pix e Stripe/PayPal abaixo', t.outros.join() === 'stripe-sandbox,paypal-sandbox' && !t.pagar, t);
        const brick = page.locator('#checkout-payment-element');
        await brick.getByText(/^Pix$/).first().click();
        await page.waitForTimeout(1500);
        const processo = capturarProcesso(page);
        await brick.getByRole('button', { name: /Pagar|Pay/ }).first().click();
        const resposta = await processo;
        await page.waitForTimeout(1500);
        const depois = await tela(page);
        const codigo = await page.locator('[data-checkout-pix-code]').inputValue();
        registrar('pix: QR Code e copia-e-cola na tela, pedido aguardando', resposta.status === 'Ok' && !!resposta.pix && depois.pix && codigo.startsWith('000201'), { payment_status: resposta.payment_status, codigo: codigo.slice(0, 12) + '…', mensagem: resposta.message });
        await page.screenshot({ path: path.join(OUT, 'checkout-1280-pix.png'), fullPage: true });
        await page.setViewportSize({ width: 390, height: 900 });
        await page.screenshot({ path: path.join(OUT, 'checkout-390-pix.png'), fullPage: true });
        await ctx.close();
    }

    if (CENARIOS.includes('paypal')) {
        const { ctx, page } = await novoPedido(browser, 'paypal');
        await page.waitForFunction(() => document.querySelectorAll('[data-checkout-outros] [data-gateway]').length > 0, null, { timeout: 45000 });
        await page.click('[data-checkout-outros] [data-gateway="paypal-sandbox"]');
        await page.waitForSelector('#checkout-payment-element iframe', { timeout: 45000 }).catch(() => null);
        await page.waitForTimeout(2000);
        const t = await tela(page);
        registrar('à vista → PayPal: botões do SDK e Stripe/Mercado Pago abaixo', t.iframes.length > 0 && t.outros.join() === 'stripe-sandbox,mercado-pago-sandbox' && /PayPal/.test(t.nota), t);
        await page.screenshot({ path: path.join(OUT, 'checkout-1280-paypal.png'), fullPage: true });
        await ctx.close();
    }

    await browser.close();
    fs.writeFileSync(path.join(OUT, 'results-checkout.json'), JSON.stringify(resultados, null, 2) + '\n');
    fs.writeFileSync(path.join(OUT, 'pedidos-checkout.json'), JSON.stringify(pedidos, null, 2) + '\n');
    fs.writeFileSync(path.join(OUT, 'respostas-payment-process.json'), JSON.stringify(processos, null, 2) + '\n');
    process.exit(resultados.every((r) => r.ok) ? 0 : 1);
})().catch((e) => { console.error(e); process.exit(2); });
