const {chromium}=require('../../node_modules/playwright'),fs=require('fs'),path=require('path');
const out=path.join(__dirname,'evidence-req238');fs.mkdirSync(out,{recursive:true});
const report={checks:[]},check=(name,ok,data)=>{report.checks.push({name,ok:!!ok,data});console.log((ok?'OK ':'FAIL ')+name+(ok?'':' '+JSON.stringify(data)));};
const cookies=fs.readFileSync(path.join(__dirname,'../../temp/agent-cookies.txt'),'utf8').split(/\r?\n/).filter(l=>l.startsWith('#HttpOnly_') || l && !l.startsWith('#')).map(l=>{const p=l.replace(/^#HttpOnly_/,'').split('\t');return {domain:p[0],path:p[2],secure:p[3]==='TRUE',name:p[5],value:p[6]};});
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});const context=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:1366,height:1000}});await context.addCookies(cookies);
 const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));let original;
 try{
  await page.goto('https://conn2flow.local/dashboard/',{waitUntil:'networkidle'});original=await page.evaluate(()=>structuredClone(gestor.dashboard_user_prefs));
  await page.locator('#dashboard-tab-btn-widgets').click();await page.locator('#dashboard-options summary').click();
  const edit=page.locator('#dashboard-edit-mode');if(await edit.getAttribute('aria-checked')!=='true')await edit.click();
  check('Semantic edit switch',await edit.getAttribute('role')==='switch' && await edit.getAttribute('aria-checked')==='true');
  await page.locator('#dashboard-btn-add-widget').click();const modal=page.locator('#dashboard-widgets-modal');
  await modal.locator('[data-widget-choice]').first().waitFor();
  const search=page.locator('#dashboard-widget-search');await search.fill('zz-no-widget');check('Catalog search hides unmatched types',await modal.locator('[data-widget-choice]:visible').count()===0);
  await search.fill('');const type=modal.locator('[data-widget-choice="menus"]');await (await type.count()?type:modal.locator('[data-widget-choice]').first()).click();
  await modal.locator('[data-widget-choice]').first().waitFor();await modal.locator('[data-widget-choice]').first().click();await modal.waitFor({state:'hidden'});
  const card=page.locator('.dashboard-widget-card').last();await card.locator('iframe').waitFor();await page.waitForTimeout(500);
  check('One bottom-right handle',await card.locator('.dashboard-widget-resize-handle').count()===1 && await card.locator('.dashboard-widget-resize-handle').getAttribute('data-corner')==='se');
  const frame=card.frameLocator('iframe');check('Isolated widget has content',await frame.locator('body').evaluate(n=>n.innerHTML.trim().length>0));
  check('Widget compiled styles are carried into the iframe',await frame.locator('head').evaluate(n=>!!n.querySelector('[data-c2f-css-role="compiled"], [data-tailwind-role]')));
  check('Widget scripts cannot access the parent document',await frame.locator('body').evaluate(()=>{try{return !parent.document;}catch(e){return e.name==='SecurityError';}}));
  await card.scrollIntoViewIfNeeded();const corner=await card.locator('.dashboard-widget-resize-handle').boundingBox();const before=await card.boundingBox();
  await page.mouse.move(corner.x+corner.width/2,corner.y+corner.height/2);await page.mouse.down();await page.mouse.move(corner.x+corner.width/2,corner.y+corner.height/2+160,{steps:12});
  check('Blueprint is visible during resize',await page.locator('#dashboard-widgets-grid').evaluate(n=>n.classList.contains('is-interacting') && +getComputedStyle(n,'::before').opacity>0));
  await page.screenshot({path:path.join(out,'widget-resize.png')});await page.mouse.up();await page.waitForTimeout(350);
  const after=await card.boundingBox();check('Height grows gradually in 60px steps',after.height>before.height && (after.height-180)%60===0,{before:before.height,after:after.height});
  check('Blueprint clears after resize',await page.locator('#dashboard-widgets-grid').evaluate(n=>!n.classList.contains('is-interacting')));
  const instance=await card.getAttribute('data-widget-instance');await page.reload({waitUntil:'networkidle'});const restored=page.locator(`[data-widget-instance="${instance}"]`);check('Height persists across reload',Math.abs((await restored.boundingBox()).height-after.height)<1);
  await restored.locator('.dashboard-widget-switch-btn').click();await modal.locator('[data-widget-choice][aria-pressed="true"]').waitFor();check('Current record preselected',await modal.locator('[aria-pressed="true"]').count()===1);
  await page.locator('#dashboard-widget-reset-selection').click();await modal.locator('[data-widget-choice]').first().waitFor();check('Reset clears current visual selection',await modal.locator('[data-widget-choice][aria-pressed="true"]').count()===0);
  await page.keyboard.press('Escape');await page.screenshot({path:path.join(out,'widgets-desktop.png')});
  await page.setViewportSize({width:390,height:844});if(await page.locator('[data-admin-fechar]').isVisible())await page.locator('[data-admin-fechar]').click();await page.waitForTimeout(300);
  check('Widgets mobile without horizontal overflow',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));await page.screenshot({path:path.join(out,'widgets-mobile.png')});
  check('Widgets have no page errors',errors.length===0,errors);
 }finally{
  if(original){await page.evaluate(async prefs=>{for(const [key,value] of [['dashboard_widgets_layout',prefs.widgets_layout],['dashboard_tab_ativa',prefs.aba_ativa]]){const response=await fetch(gestor.raiz+'dashboard/',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({ajax:'sim',opcao:'inicio',ajaxOpcao:'salvar-preferencias',chave:key,valor:JSON.stringify(value || (key.includes('layout')?[]:'modulos'))})});if(!response.ok)throw Error('Preference restoration failed');}sessionStorage.removeItem('dashboard_widgets_editing');},original);}
  report.passed=report.checks.filter(c=>c.ok).length;report.failed=report.checks.filter(c=>!c.ok).length;fs.writeFileSync(path.join(out,'widgets.json'),JSON.stringify(report,null,2)+'\n');await browser.close();
 }
 process.exitCode=report.failed?1:0;
})().catch(e=>{console.error(e);process.exitCode=1;});
