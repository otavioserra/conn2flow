const {chromium} = require('../../node_modules/playwright');
const fs = require('fs'), path = require('path');
const output = path.join(__dirname, 'evidence-req237');
fs.mkdirSync(output, {recursive: true});
const report = {started_at: new Date().toISOString(), checks: []};
function check(name, ok, data) {
    report.checks.push({name, ok: !!ok, data});
    console.log(`${ok ? 'OK' : 'FAIL'} ${name}${ok ? '' : ' ' + JSON.stringify(data)}`);
}
const cookies = fs.readFileSync(path.join(__dirname, '../../temp/agent-cookies.txt'), 'utf8')
    .split(/\r?\n/).map(line => line.replace(/^#HttpOnly_/, ''))
    .filter(line => line && !line.startsWith('#')).map(line => line.split('\t'))
    .filter(parts => parts.length >= 7).map(parts => ({domain: parts[0].replace(/^\./, ''),
        path: parts[2], secure: parts[3] === 'TRUE', name: parts[5], value: parts[6]}));
(async () => {
    const browser = await chromium.launch({headless: true,
        args: ['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});
    const context = await browser.newContext({ignoreHTTPSErrors: true,
        viewport: {width: 1366, height: 900}});
    await context.addCookies(cookies);
    const page = await context.newPage(), errors = [];
    page.on('pageerror', error => errors.push(error.message));
    let original;
    const menu = () => page.locator('#dashboard-options').evaluate(element => element.open = true);
    const order = () => page.locator('.dashboard-module-card').evaluateAll(cards => cards.map(card => card.dataset.moduleId));
    try {
        const response = await page.goto('https://conn2flow.local/dashboard/', {waitUntil: 'networkidle'});
        check('Authenticated dashboard', response.status() === 200 && !/signin/.test(page.url()));
        original = await page.evaluate(() => structuredClone(gestor.dashboard_user_prefs));
        await page.locator('#dashboard-tab-btn-modulos').click();
        for (const [density, height] of [['p', 36], ['m', 211.2], ['g', 280]]) {
            await menu();
            await page.locator(`[data-density="${density}"]`).click();
            const actual = await page.locator('.dashboard-card-header').first().evaluate(element => element.getBoundingClientRect().height);
            check(`Density ${density} height`, Math.abs(actual - height) < 1, actual);
        }
        await menu(); await page.locator('[data-density="m"]').click();
        const covers = await page.locator('.has-cover .dashboard-module-cover').evaluateAll(images =>
            images.map(image => ({loaded: image.complete && image.naturalWidth > 0,
                fit: getComputedStyle(image).objectFit, position: getComputedStyle(image).objectPosition,
                transform: getComputedStyle(image).transform,
                filled: Math.abs(image.getBoundingClientRect().height - image.closest('.dashboard-card-header').getBoundingClientRect().height - 20) < 1})));
        check('Covers load, align to top and rise 20px', covers.length > 0 && covers.every(cover =>
            cover.loaded && cover.fit === 'cover' && cover.position === '50% -20px' && cover.filled), covers);
        const aliases = await page.locator('.dashboard-module-card').evaluateAll(cards => cards.map(card => {
            const link = card.querySelector('.dashboard-card-header-link');
            const access = card.querySelector('.dashboard-card-actions-left a');
            const handle = card.querySelector('.dashboard-card-drag-handle');
            return !!link && link.href === access.href && !!link.getAttribute('aria-label') &&
                !!link.querySelector('.dashboard-card-cover-wrapper') && !!link.querySelector('.dashboard-card-svg') &&
                handle.closest('a') === null && +getComputedStyle(handle).zIndex > +getComputedStyle(link).zIndex;
        }));
        check('All headers alias Access and isolate handles', aliases.length > 0 && aliases.every(Boolean));
        check('Module titles and accessible names resolve', await page.locator('.dashboard-module-card').evaluateAll(cards =>
            cards.every(card => !card.textContent.includes('#modulo-') &&
                !card.querySelector('.dashboard-card-header-link').getAttribute('aria-label').includes('#modulo-'))));
        const card = page.locator('.dashboard-module-card.has-cover').first();
        const target = await card.locator('.dashboard-card-header-link').getAttribute('href');
        await card.locator('.dashboard-module-cover').click();
        await page.waitForLoadState('networkidle');
        check('Cover click navigates to module', new URL(page.url()).pathname === new URL(target, 'https://conn2flow.local').pathname, page.url());
        await page.goto('https://conn2flow.local/dashboard/', {waitUntil: 'networkidle'});
        await page.locator('#dashboard-tab-btn-modulos').click();
        const fallback = page.locator('.dashboard-module-card').first();
        const fallbackTarget = await fallback.locator('.dashboard-card-header-link').getAttribute('href');
        await fallback.evaluate(element => element.classList.remove('has-cover'));
        await fallback.locator('.dashboard-card-svg').click();
        await page.waitForLoadState('networkidle');
        check('Fallback icon click navigates to module', new URL(page.url()).pathname === new URL(fallbackTarget, 'https://conn2flow.local').pathname);
        await page.goto('https://conn2flow.local/dashboard/', {waitUntil: 'networkidle'});
        await page.locator('#dashboard-tab-btn-modulos').click();
        const before = await order(), dashboardUrl = page.url();
        const handle = page.locator('.dashboard-card-drag-handle').first();
        await handle.scrollIntoViewIfNeeded();
        const source = await handle.boundingBox();
        const destination = await page.locator('.dashboard-module-card').nth(1).boundingBox();
        await page.mouse.move(source.x + source.width / 2, source.y + source.height / 2);
        await page.mouse.down();
        await page.mouse.move(destination.x + destination.width * .8, destination.y + destination.height / 2, {steps: 16});
        await page.mouse.up(); await page.waitForTimeout(800);
        const reordered = await order();
        check('Drag changes order without navigation', JSON.stringify(before) !== JSON.stringify(reordered) && page.url() === dashboardUrl);
        await page.reload({waitUntil: 'networkidle'});
        check('Reordered modules survive reload', JSON.stringify(await order()) === JSON.stringify(reordered));
        await page.screenshot({path: path.join(output, 'dashboard-desktop.png')});
        await page.setViewportSize({width: 390, height: 844});
        if (await page.locator('[data-admin-fechar]').isVisible()) await page.locator('[data-admin-fechar]').click();
        await page.waitForTimeout(350);
        check('390px without horizontal overflow', await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
        await page.screenshot({path: path.join(output, 'dashboard-mobile.png')});
        check('No Fomantic assets', await page.evaluate(() => !Array.from(document.querySelectorAll('link[href],script[src]'))
            .some(element => /(?:semantic|fomantic)(?:\.min)?\.(?:css|js)/.test(element.href || element.src))));
        check('No browser errors', errors.length === 0, errors);
    } catch (error) { check('Scenario completes', false, error.stack); }
    finally {
        if (original) {
            for (const [key, field, empty] of [['dashboard_cards_order', 'cards_order', []],
                ['dashboard_densidade', 'densidade', 'm'], ['dashboard_aba_ativa', 'aba_ativa', 'dashboard-tab-modulos']]) {
                const restored = await page.evaluate(async ({key, value}) => {
                    const body = new URLSearchParams({opcao: 'inicio', ajax: 'sim', ajaxOpcao: 'salvar-preferencias', chave: key, valor: JSON.stringify(value)});
                    return (await fetch(gestor.raiz + 'dashboard/', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body})).json();
                }, {key, value: original[field] ?? empty}).catch(() => null);
                check('Restore ' + key, restored?.status === 'Ok');
            }
        }
        await browser.close();
        report.summary = `${report.checks.filter(item => item.ok).length}/${report.checks.length}`;
        fs.writeFileSync(path.join(output, 'dashboard.json'), JSON.stringify(report, null, 2));
        console.log(report.summary); process.exit(report.checks.every(item => item.ok) ? 0 : 1);
    }
})();
