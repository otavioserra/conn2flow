const {chromium} = require('../../node_modules/playwright');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const base = process.argv[2] || 'https://conn2flow.local';
const label = process.argv[3] || 'v30';
const output = path.join(__dirname, 'req267-evidence', label);
fs.mkdirSync(output, {recursive: true});
const assets = JSON.parse(fs.readFileSync(path.join(__dirname, 'req267-assets.json')));
const report = {url: base, checks: [], cards: [], errors: []};
const check = (name, ok, data) => {
  report.checks.push({name, ok: !!ok, data});
  console.log(`${ok ? 'PASS' : 'FAIL'} ${name}`);
};
const cookies = fs.readFileSync(process.argv[4] || path.join(__dirname, '../../temp/agent-cookies.txt'), 'utf8')
  .split(/\r?\n/).filter(s => s && (!s.startsWith('#') || s.startsWith('#HttpOnly_')))
  .map(s => { const httpOnly = s.startsWith('#HttpOnly_'); const p = s.replace(/^#HttpOnly_/, '').split('\t');
    return p.length < 7 ? null : {domain:p[0],path:p[2],secure:p[3]==='TRUE',httpOnly,name:p[5],value:p[6]}; })
  .filter(Boolean);
(async () => {
  const browser = await chromium.launch({headless:true});
  const context = await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:1440,height:1000}});
  await context.addCookies(cookies);
  const page = await context.newPage();
  page.on('pageerror', e => report.errors.push(e.message));
  page.on('console', e => {if(e.type()==='error') report.errors.push(e.text());});
  try {
    for (const asset of assets) {
      const response = await context.request.get(base + '/modulos/covers/' + asset.id + '.webp');
      const payload = await response.body();
      check('HTTP and SHA256 ' + asset.id, response.status()===200 && crypto.createHash('sha256').update(payload).digest('hex')===asset.sha256,
        {status:response.status(),bytes:payload.length});
    }
    const response = await page.goto(base + '/dashboard/', {waitUntil:'networkidle'});
    check('Authenticated Dashboard', response.status()===200 && !/signin|login/.test(new URL(page.url()).pathname));
    const tab = page.locator('#dashboard-tab-btn-modulos');
    if (await tab.count()) await tab.click();
    await page.waitForTimeout(500);
    for (const asset of assets) {
      const card = page.locator(`.dashboard-module-card[data-module-id="${asset.id}"]`);
      if (!await card.count()) {
        report.cards.push({id:asset.id,present:false,reason:'Module not listed for this profile/version'});
        continue;
      }
      await card.scrollIntoViewIfNeeded();
      const im = card.locator('.dashboard-module-cover');
      if (await im.count()) await im.evaluate(el => el.decode());
      const data = await card.evaluate(el => { const im=el.querySelector('.dashboard-module-cover');
        return {hasCover:el.classList.contains('has-cover'),loaded:!!im && im.complete && im.naturalWidth===1024,
          src:im?.getAttribute('src'),naturalWidth:im?.naturalWidth,naturalHeight:im?.naturalHeight}; });
      report.cards.push({id:asset.id,present:true,...data});
      check('Dashboard card ' + asset.id, data.hasCover && data.loaded && data.src.includes(asset.id+'.webp'), data);
      await card.screenshot({path:path.join(output,asset.id+'.png')});
    }
    check('New module cards present', ['ai-agents','ai-assistant','ai-images','ai-admin','ai-fields','dashboard-pages'].every(id=>report.cards.some(c=>c.id===id && c.present)),report.cards);
    for (const width of [1440,390]) {
      await page.setViewportSize({width,height:1000});
      await page.waitForTimeout(350);
      if(width===390) {
        const close = page.locator('[data-admin-fechar]').first();
        if(await close.isVisible()) await close.click();
        await page.waitForTimeout(350);
        const card=page.locator('.dashboard-module-card[data-module-id="ai-agents"]');
        await card.scrollIntoViewIfNeeded();
        await card.screenshot({path:path.join(output,'ai-agents-390.png')});
      }
      check('No horizontal overflow '+width, await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));
      await page.evaluate(()=>scrollTo(0,0));
      await page.screenshot({path:path.join(output,`dashboard-${width}.png`)});
    }
    check('No browser errors', report.errors.length===0,report.errors);
  } catch(e) { check('Scenario completes',false,e.stack); }
  finally {
    await browser.close();
    report.summary = `${report.checks.filter(c=>c.ok).length}/${report.checks.length}`;
    fs.writeFileSync(path.join(output,'report.json'),JSON.stringify(report,null,2)+'\n');
    console.log(report.summary);
    process.exitCode=report.checks.every(c=>c.ok)?0:1;
  }
})();
