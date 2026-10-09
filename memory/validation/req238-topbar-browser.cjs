const {chromium}=require('../../node_modules/playwright'),fs=require('fs'),path=require('path');
const out=path.join(__dirname,'evidence-req238'),checks=[];
function check(name,ok){checks.push({name,ok:!!ok});console.log((ok?'OK ':'FAIL ')+name);}
(async()=>{const b=await chromium.launch({args:['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});const c=await b.newContext({ignoreHTTPSErrors:true,viewport:{width:1366,height:900}});await c.addCookies(fs.readFileSync('temp/agent-cookies.txt','utf8').split(/\r?\n/).filter(l=>l.startsWith('#HttpOnly_')||l&&!l.startsWith('#')).map(l=>{const p=l.replace(/^#HttpOnly_/,'').split('\t');return {domain:p[0],path:p[2],name:p[5],value:p[6],secure:p[3]==='TRUE'};}));const page=await c.newPage();const added=[];
try{
 await page.goto('https://conn2flow.local/dashboard/',{waitUntil:'networkidle'});
 await page.locator('[data-topbar-toggle="topbar-more"]').click();await page.locator('[data-topbar-customize]').click();
 const choices=page.locator('[data-topbar-choices] input:not(:checked)');
 for(let i=0;i<2 && await choices.count();i++){const choice=choices.first(),id=await choice.getAttribute('value');await choice.check();await page.waitForFunction(()=>![...document.querySelectorAll('[data-topbar-choices] input')].some(n=>n.disabled));added.push(id);}
 await page.locator('[data-topbar-dialog-close]').click();
 check('Shortcuts use icon, hidden accessible label and tooltip',await page.locator('[data-topbar-shortcuts] a').evaluateAll(items=>items.length>=2 && items.every(n=>{const s=n.querySelector('span');return n.querySelector('svg') && n.dataset.c2fDica && s && getComputedStyle(s).width==='1px';})));
 const shortcut=page.locator('[data-topbar-shortcuts] a:visible').first();await shortcut.hover();await page.waitForTimeout(400);
 await page.screenshot({path:path.join(out,'topbar-shortcuts.png')});
 for(const width of [1366,390]){await page.setViewportSize({width,height:844});await page.locator('[data-topbar-toggle="topbar-profile"]').click();const box=await page.locator('#topbar-profile').boundingBox();check('Profile aligned inside '+width+'px viewport',box && box.width===320 && box.x>=0 && box.x+box.width<=width && box.y>=(await page.locator('[data-topbar-toggle="topbar-profile"]').boundingBox()).y+32);check('Username remains visible at '+width+'px',await page.locator('.c2fc-topbar-user-name').isVisible());await page.screenshot({path:path.join(out,'profile-'+width+'.png')});await page.keyboard.press('Escape');}
 await page.setViewportSize({width:1366,height:900});await page.goto('https://conn2flow.local/dashboard/',{waitUntil:'networkidle'});
 for(const id of ['social-connections','host-manager','3d-catalog']){const links=page.locator(`[data-module-id="${id}"] a[href*="documentation/"]`);check('Private dashboard links '+id,await links.count()>=2 && (await links.evaluateAll(nodes=>nodes.map(n=>n.getAttribute('href')))).every(h=>h.includes('/documentation/'+id+'/')));}
}finally{
 await page.evaluate(async ids=>{for(const id of ids){const r=await fetch(location.pathname,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':gestor.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || ''},body:new URLSearchParams({ajax:'sim',opcao:gestor.moduloOpcao || '',ajaxOpcao:'admin-topbar-remover',paginaId:id})});const result=await r.json();if(result.status!=='Ok')throw Error('Shortcut restoration failed');}},added);
 fs.writeFileSync(path.join(out,'topbar.json'),JSON.stringify({checks,passed:checks.filter(x=>x.ok).length,failed:checks.filter(x=>!x.ok).length},null,2)+'\n');await b.close();
}process.exitCode=checks.some(x=>!x.ok)?1:0;})().catch(e=>{console.error(e);process.exitCode=1;});
