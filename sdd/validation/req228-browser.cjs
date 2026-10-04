const {chromium} = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const net = require('node:net');
const ROOT = path.resolve(__dirname, '../..');
const URL_BASE = process.env.REQ228_URL || 'https://c2f-teste.local:8443/';
const OUT = path.join(__dirname, 'req228-evidence');
fs.mkdirSync(OUT,{recursive:true});
const report={checks:[],errors:[],expectedConsoleErrors:[],measurements:[]};
function check(name,ok,data){ report.checks.push({name,ok:!!ok,data}); console.log((ok?'OK ':'FAIL ')+name+(ok?'':': '+JSON.stringify(data))); }
function jar(){ return fs.readFileSync(path.join(ROOT,'temp/agent-cookies.txt'),'utf8').split(/\r?\n/)
 .filter(l=>l && (!l.startsWith('#') || l.startsWith('#HttpOnly_'))).map(l=>({httpOnly:l.startsWith('#HttpOnly_'),p:l.replace(/^#HttpOnly_/,'').split('\t')}))
 .filter(x=>x.p.length>=7).map(({httpOnly,p})=>({name:p[5],value:p[6],domain:p[0].replace(/^\./,''),path:p[2],secure:p[3]==='TRUE',httpOnly})); }
async function proxy(){
 const server=http.createServer((req,res)=>{res.writeHead(502);res.end();});
 server.on('connect',(req,client,head)=>{
  const [host,port]=req.url.split(':');
  const socket=net.connect(Number(port)||443, host==='c2f-teste.local'?'127.0.0.1':host,()=>{
   client.write('HTTP/1.1 200 Connection Established\r\n\r\n'); if(head.length)socket.write(head);socket.pipe(client);client.pipe(socket);
  });
  const close=()=>{socket.destroy();client.destroy();};socket.on('error',close);client.on('error',close);
 });
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));return server;
}
(async()=>{
 const server=await proxy();
 const browser=await chromium.launch({headless:true,proxy:{server:'http://127.0.0.1:'+server.address().port}});
 let ctx, testPage, baseline, touched=[];
 try{
  ctx=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:1366,height:900}});await ctx.addCookies(jar());
  const page=await ctx.newPage();testPage=page;page.on('pageerror',e=>report.errors.push(e.message));
  page.on('console',msg=>{
   if(msg.type()!=='error')return;
   if(/Failed to load resource.*status of (403|503)/.test(msg.text()))report.expectedConsoleErrors.push(msg.text());
   else report.errors.push('Console: '+msg.text());
  });
  const response=await page.goto(URL_BASE+'modulos-grupos/',{waitUntil:'networkidle'});
  check('authenticated Tailwind screen',response.status()===200 && await page.locator('[data-admin-topbar]').count()===1,{url:page.url(),status:response.status()});
  if(!await page.locator('[data-admin-topbar]').count()){
   console.log((await page.locator('body').innerText()).slice(0,800));return;
  }
  await page.waitForFunction(()=>document.querySelector('[data-admin-topbar]').dataset.topbarReady==='1');
  const initial=await page.locator('[data-admin-topbar]').getAttribute('data-topbar-state');
  const state=JSON.parse(initial);
  baseline=state.favoritos.map(x=>x.id);
  report.initialFavorites=state.favoritos.map(x=>x.id);
  check('SQL migration available',state.disponivel);
  check('catalog includes current screen',!!state.atual,{current:state.atual,count:state.catalogo.length});
  const profile=page.locator('[data-topbar-toggle="topbar-profile"]');
  await profile.focus();await page.keyboard.press('ArrowDown');
  check('ArrowDown opens and focuses profile link',await page.locator('#topbar-profile').isVisible() && await page.locator('#topbar-profile a').first().evaluate(n=>n===document.activeElement));
  await page.screenshot({path:path.join(OUT,'desktop-profile.png')});
  await page.keyboard.press('Escape');check('Escape closes and returns focus',!await page.locator('#topbar-profile').isVisible() && await profile.evaluate(n=>n===document.activeElement));
  await profile.click();await page.locator('[data-admin-main]').click({position:{x:10,y:10}});check('click outside closes',!await page.locator('#topbar-profile').isVisible());
  await profile.click();await page.keyboard.press('Tab');check('Tab reaches profile link',await page.locator('#topbar-profile a').first().evaluate(n=>n===document.activeElement));
  await page.keyboard.press('Escape');
  const more=page.locator('[data-topbar-toggle="topbar-more"]');
  await more.click();await page.locator('[data-topbar-customize]').click();
  check('customization dialog and focus',await page.locator('#topbar-customize').isVisible() && await page.locator('#topbar-search').evaluate(n=>n===document.activeElement));
  await page.locator('#topbar-search').fill('zzzz-nonexistent');check('search no results',await page.locator('[data-topbar-no-results]').isVisible());
  await page.locator('#topbar-search').fill('');
  await page.keyboard.press('Escape');
  await page.locator('#topbar-customize').waitFor({state:'hidden'});
  await page.waitForFunction(()=>document.activeElement===document.querySelector('[data-topbar-toggle="topbar-more"]'));
  check('dialog Escape returns focus',await more.evaluate(n=>n===document.activeElement));
  await page.route('**/modulos-grupos/',async route=>{
   if(route.request().method()==='POST'&&(route.request().postData()||'').includes('admin-topbar-'))await route.fulfill({status:503,contentType:'application/json',body:'{"status":"Erro"}'});
   else await route.continue();
  });
  await more.click();await page.locator('[data-topbar-current]').click();
  await page.locator('[data-topbar-error]').waitFor({state:'visible'});
  check('save failure is visible and preserves favorite state',await page.locator('[data-topbar-current]').getAttribute('aria-pressed')===String(state.favoritos.some(x=>x.id===state.atual)));
  await page.unroute('**/modulos-grupos/');await page.keyboard.press('Escape');
  async function toggleFavorite(id,selected){
   await more.click();await page.locator('[data-topbar-customize]').click();
   const choice=page.locator('[data-topbar-choices] input[value="'+id+'"]');
   if(await choice.isChecked()!==selected){
    const req=page.waitForResponse(r=>r.request().method()==='POST' && (r.request().postData()||'').includes('admin-topbar-'));
    await choice.click();const res=await req;
    check('favorite '+id+' '+selected+' saves with CSRF',res.status()===200 && (await res.json()).status==='Ok');
    await page.waitForFunction(()=>!document.querySelector('[data-topbar-choices] input').disabled);
   }
   await page.keyboard.press('Escape');
   await page.locator('#topbar-customize').waitFor({state:'hidden'});
   await page.waitForFunction(()=>document.activeElement===document.querySelector('[data-topbar-toggle="topbar-more"]'));
  }
  const chosen=state.catalogo.slice(0,8).map(x=>x.id);
  touched=[...new Set([...chosen,state.atual])].filter(Boolean);
  for(const id of chosen)await toggleFavorite(id,true);
  await page.reload({waitUntil:'networkidle'});
  check('favorites restored after reload',await page.locator('[data-topbar-shortcuts] a').count()>=chosen.length);
  const ctx2=await browser.newContext({ignoreHTTPSErrors:true});await ctx2.addCookies(jar());const second=await ctx2.newPage();
  await second.goto(URL_BASE+'modulos-grupos/',{waitUntil:'networkidle'});
  check('favorites restored in a second browser context',JSON.parse(await second.locator('[data-admin-topbar]').getAttribute('data-topbar-state')).favoritos.length>=chosen.length);
  await ctx2.close();
  const shortcut=page.locator('[data-topbar-shortcuts] a').first();
  const shortcutUrl=await shortcut.getAttribute('href');
  await shortcut.click();await page.waitForLoadState('networkidle');
  check('favorite link opens its screen',new URL(page.url()).pathname===new URL(shortcutUrl,URL_BASE).pathname);
  await page.goto(URL_BASE+'modulos-grupos/',{waitUntil:'networkidle'});
  for(const width of [1366,1024,768,390,320]){
   await page.setViewportSize({width,height:900});
   if(width<1024 && await page.locator('[data-admin-sidebar]').evaluate(n=>n.getBoundingClientRect().left>=0)){
    await page.locator('[data-admin-fechar]').click();
    await page.waitForFunction(()=>document.querySelector('[data-admin-sidebar]').getBoundingClientRect().right<=1);
   }
   const metrics=await page.evaluate(()=>{
    const bar=document.querySelector('[data-admin-topbar]');const toolbar=document.getElementById('c2f-site-toolbar');
    return {width:innerWidth,overflow:document.documentElement.scrollWidth-innerWidth,bar:bar.getBoundingClientRect().toJSON(),toolbar:toolbar?toolbar.getBoundingClientRect().toJSON():null,
     visible:[...document.querySelectorAll('[data-topbar-shortcuts] a')].filter(n=>getComputedStyle(n).display!=='none').length,
     background:getComputedStyle(bar).backgroundColor};
   }); report.measurements.push(metrics);
   check('no horizontal overflow at '+width,metrics.overflow<=0,metrics);
   check('responsive favorites at '+width,metrics.visible===(width>=1280?6:width>=768?4:0),metrics.visible);
   if(metrics.toolbar)check('editbar does not overlap at '+width,metrics.bar.top>=metrics.toolbar.bottom,metrics);
   await more.click();
   const rect=await page.locator('#topbar-more').boundingBox();
   check('overflow dropdown fits at '+width,rect.x>=0 && rect.x+rect.width<=width,rect);
   await page.screenshot({path:path.join(OUT,'favorites-'+width+'.png')});await page.keyboard.press('Escape');
   await profile.click();const pr=await page.locator('#topbar-profile').boundingBox();check('profile dropdown fits at '+width,pr.x>=0 && pr.x+pr.width<=width,pr);
   await page.screenshot({path:path.join(OUT,'profile-'+width+'.png')});await page.keyboard.press('Escape');
  }
  await page.setViewportSize({width:1366,height:900});
  await more.click();
  const currentSaved=page.waitForResponse(r=>r.request().method()==='POST' && (r.request().postData()||'').includes('admin-topbar-'));
  await page.locator('[data-topbar-current]').click();await currentSaved;
  await page.waitForTimeout(800);await page.reload({waitUntil:'networkidle'});
  check('current screen toggle persists',JSON.parse(await page.locator('[data-admin-topbar]').getAttribute('data-topbar-state')).favoritos.some(x=>x.id===state.atual)!==state.favoritos.some(x=>x.id===state.atual));
  await profile.click();await page.locator('#topbar-language').selectOption('en');await page.waitForURL(/\/en\//);await page.waitForLoadState('networkidle');
  await profile.click();check('English translated profile and language',await page.locator('#topbar-profile').innerText().then(t=>t.includes('My Profile')&&t.includes('Interface Language')));
  await page.locator('#topbar-profile a[href*="perfil-usuario/#seguranca"]').click();await page.waitForLoadState('networkidle');
  check('security link opens security tab',await page.locator('#perfil-painel-seguranca').isVisible());
  // Restore only preferences added by this test, leaving account state as found.
  await page.goto(URL_BASE+'modulos-grupos/',{waitUntil:'networkidle'});
  const originalIds=state.favoritos.map(x=>x.id);
  for(const id of [...new Set([...chosen,state.atual])].filter(Boolean))await toggleFavorite(id,originalIds.includes(id));
  const noToken=await page.evaluate(async()=>{
   const res=await fetch(location.pathname,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'ajax=sim&ajaxOpcao=admin-topbar-adicionar&paginaId=invalid'});
   return res.status;
  });check('CSRF missing token refused',noToken===403,noToken);
  // The same session with its profile cookie exercises the real editbar, without DOM injection.
  const cookies=jar();const auth=cookies.find(c=>c.name.startsWith('_C2FCID'));
  if(auth){await ctx.addCookies([{name:auth.name.replace('_C2FCID','_C2FCP'),value:'admin',domain:auth.domain,path:'/',secure:true,httpOnly:false}]);}
  await page.reload({waitUntil:'networkidle'});
  check('editbar actually present',await page.locator('#c2f-site-toolbar').count()===1);
  for(const width of [1366,390]){
   await page.setViewportSize({width,height:900});await page.waitForTimeout(250);
   const metrics=await page.evaluate(()=>({bar:document.querySelector('[data-admin-topbar]').getBoundingClientRect().toJSON(),toolbar:document.querySelector('#c2f-site-toolbar')?.getBoundingClientRect().toJSON(),overflow:document.documentElement.scrollWidth-innerWidth}));
   check('editbar coexistence '+width,metrics.toolbar&&metrics.bar.top>=metrics.toolbar.bottom&&metrics.overflow<=0,metrics);
   await page.screenshot({path:path.join(OUT,'editbar-'+width+'.png')});
  }
  await page.setViewportSize({width:1366,height:900});
  await profile.click();await page.locator('#topbar-profile a[href$="signout/"]').click();await page.waitForLoadState('networkidle');
  check('signout ends the session',await page.locator('[data-admin-topbar]').count()===0);
  check('no page errors',report.errors.length===0,report.errors);
 }catch(error){report.errors.push(String(error));console.error(error.message);process.exitCode=1;}
 finally{
  // On a failed run, restore precisely the account preferences touched by this scenario.
  if(process.exitCode && testPage && baseline){
   try{
    await testPage.goto(URL_BASE+'modulos-grupos/',{waitUntil:'networkidle'});
    await testPage.evaluate(async({baseline,touched})=>{
     for(const id of touched)await fetch(location.pathname,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':window.gestor.csrfToken},body:new URLSearchParams({ajax:'sim',opcao:window.gestor.moduloOpcao,ajaxOpcao:baseline.includes(id)?'admin-topbar-adicionar':'admin-topbar-remover',paginaId:id})});
    },{baseline,touched});
   }catch(e){report.errors.push('Preference restoration failed: '+e.message);}
  }
  fs.writeFileSync(path.join(OUT,'results.json'),JSON.stringify(report,null,2)+'\n');
  await browser.close();server.close();
  if(report.checks.some(x=>!x.ok)||report.errors.length)process.exitCode=1;
 }
})();
