// Local Lab only. Uses an agent cookie jar; restores Dashboard preferences in finally.
const {chromium}=require('../../../node_modules/playwright');
const fs=require('node:fs'),path=require('node:path');
const root=path.resolve(__dirname,'../../..'),out=path.join(__dirname,'evidencias');
fs.mkdirSync(out,{recursive:true});
const base='https://conn2flow.local',results=[];
function check(name,ok,details){results.push({name,ok:!!ok,details});console.log((ok?'OK ':'FAIL ')+name+(ok?'':' '+JSON.stringify(details)));}
const cookies=fs.readFileSync(path.join(root,'temp/agent-cookies.txt'),'utf8').split(/\r?\n/).filter(l=>l.startsWith('#HttpOnly_')||(l&&!l.startsWith('#'))).map(l=>{const p=l.replace(/^#HttpOnly_/,'').split('\t');return {domain:p[0].replace(/^\./,''),path:p[2],secure:p[3]==='TRUE',httpOnly:l.startsWith('#HttpOnly_'),name:p[5],value:p[6]};});
const measure=()=>{
 const deck=document.querySelector('[data-c2f-deck]'),slide=deck?.querySelector('[data-slide].is-active'),next=deck?.querySelector('[data-c2f-deck-next]');
 const style=el=>{if(!el)return null;const s=getComputedStyle(el);return {color:s.color,background:s.backgroundColor,font:s.fontFamily,weight:s.fontWeight,display:s.display};};
 const details=[...(deck?.querySelectorAll('h1,h2,[class*="bg-c2f"],[class*="bg-linear"]')||[])].map(el=>{const s=getComputedStyle(el);return {classes:el.className,color:s.color,background:s.backgroundColor,gradient:s.backgroundImage,font:s.fontFamily,size:s.fontSize,weight:s.fontWeight};});
 const rules=[];const visit=(list,source)=>{for(const rule of list){if(rule.selectorText&&/md.*text-(base|8xl)|\.text-(sm|5xl)(\W|$)/.test(rule.selectorText))rules.push({source,selector:rule.selectorText,css:rule.cssText});if(rule.cssRules)visit(rule.cssRules,source+' '+(rule.conditionText||rule.name||''));}};for(const sheet of document.styleSheets){try{visit(sheet.cssRules,sheet.ownerNode.outerHTML.slice(0,100));}catch(e){}}
 return {deck:style(deck),slide:style(slide),next:style(next),details,rules,blue:getComputedStyle(document.documentElement).getPropertyValue('--color-c2f-blue').trim(),width:deck?.clientWidth,height:deck?.clientHeight,overflow:document.documentElement.scrollWidth-innerWidth,count:deck?.querySelector('[data-c2f-deck-current]')?.textContent,icons:document.querySelectorAll('svg.lucide').length};
};
(async()=>{
 const browser=await chromium.launch({...(process.env.C2F_BROWSER_EXECUTABLE?{executablePath:process.env.C2F_BROWSER_EXECUTABLE}:{channel:'chrome'}),headless:true,args:['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});
 const context=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:1366,height:900}});await context.addCookies(cookies);
 const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));page.on('console',m=>{if(m.type()==='error')errors.push(m.text());});
 const ajax=async(action,extra)=>page.evaluate(async({action,extra})=>{const p=new URLSearchParams({ajax:'sim',opcao:'inicio',ajaxOpcao:action});Object.entries(extra||{}).forEach(([k,v])=>p.set(k,typeof v==='object'?JSON.stringify(v):v));const r=await fetch(gestor.raiz+'dashboard/',{method:'POST',body:p});return r.json();},{action,extra});
 let original,tab;
 try{
  const response=await page.goto(base+'/pt-br/dashboard/',{waitUntil:'networkidle'});check('Dashboard HTTP 200',response.status()===200);
  ({original,tab}=await page.evaluate(()=>({original:gestor.dashboard_user_prefs.widgets_layout,tab:gestor.dashboard_user_prefs.aba_ativa})));
  const catalog=await ajax('widgets-catalogo');fs.writeFileSync(path.join(out,'catalogo.json'),JSON.stringify(catalog,null,2));
  const layout=[{id:'presentations',registro_id:'conn2flow',instance_id:'req244-pilot',name:'Conn2Flow',width:12,height:2,height_px:600,params:{grupo_slug:'conn2flow'}}];
  for(const id of ['forms-search','pages-index','menus','galleries','cookie-consent']){
   const records=await ajax('widgets-registros',{widget_id:id,pagina:1});
   if(records.data?.items?.[0]){const r=records.data.items[0];layout.push({id,registro_id:r.id,instance_id:'req244-'+id,name:id,width:6,height:2,height_px:460,params:{grupo_slug:r.id}});}
  }
  check('Preferências de teste salvas',(await ajax('salvar-preferencias',{chave:'dashboard_widgets_layout',valor:layout})).status==='Ok');
  await ajax('salvar-preferencias',{chave:'dashboard_aba_ativa',valor:'dashboard-tab-widgets'});
  await page.reload({waitUntil:'networkidle'});
  await page.locator('[data-tab-target="dashboard-tab-widgets"]').click();
  const frameElement=page.locator('[data-widget-instance="req244-pilot"] iframe');await frameElement.waitFor();
  const frame=await (await frameElement.elementHandle()).contentFrame();
  await frame.waitForSelector('[data-c2f-deck].is-ready');await frame.waitForTimeout(1500);
  const actual=await frame.evaluate(measure);check('Deck tem fundo e tokens de tema',actual.deck?.background==='rgb(25, 42, 70)'&&!!actual.blue,actual);
  const refContext=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:actual.width,height:actual.height}});await refContext.addCookies(cookies);const ref=await refContext.newPage();await ref.goto(base+'/pt-br/apresentacoes/conn2flow-widget/',{waitUntil:'networkidle'});await ref.waitForSelector('[data-c2f-deck].is-ready');
  const expected=await ref.evaluate(measure);check('Paridade de fundo, cor, fonte e controles',JSON.stringify(actual.deck)===JSON.stringify(expected.deck)&&JSON.stringify(actual.next)===JSON.stringify(expected.next),{actual,expected});
  check('Paridade de títulos, gradientes e destaques',actual.details.length>0&&JSON.stringify(actual.details)===JSON.stringify(expected.details),{actual:actual.details,expected:expected.details});
  await ref.screenshot({path:path.join(out,'referencia.png')});
  await frameElement.screenshot({path:path.join(out,'dashboard-piloto.png')});
  await refContext.close();await page.bringToFront();

  const clickFrameButton=async selector=>{await frameElement.scrollIntoViewIfNeeded();const outer=await frameElement.boundingBox();const inner=await frame.locator(selector).first().evaluate(el=>{const r=el.getBoundingClientRect();return {x:r.x+r.width/2,y:r.y+r.height/2};});await page.mouse.click(outer.x+inner.x,outer.y+inner.y);};
  await clickFrameButton('[data-c2f-deck-next]');await frame.waitForTimeout(700);check('Navegação avança o slide',(await frame.evaluate(measure)).count!==actual.count);
  await clickFrameButton('[data-c2f-deck-dot="2"]');await frame.waitForTimeout(700);check('Pontos selecionam o terceiro slide',(await frame.evaluate(measure)).count==='3');
  await clickFrameButton('[data-c2f-deck-prev]');await frame.waitForTimeout(700);check('Seta anterior retorna ao segundo slide',(await frame.evaluate(measure)).count==='2');
  check('Tela cheia permitida',await frame.evaluate(()=>document.fullscreenEnabled));
  await clickFrameButton('[data-c2f-deck-fullscreen]');await frame.waitForTimeout(300);
  check('Botão ativa tela cheia',await frame.evaluate(()=>!!document.fullscreenElement));
  await clickFrameButton('[data-c2f-deck-fullscreen]');await frame.waitForFunction(()=>!document.fullscreenElement);await frame.waitForTimeout(300);
  check('Botão encerra tela cheia',await frame.evaluate(()=>!document.fullscreenElement));
  for(const width of [1366,390]){
   await page.setViewportSize({width,height:900});await page.waitForTimeout(600);
   const m=await frame.evaluate(measure);const geometry=await frameElement.evaluate(el=>{const body=el.parentElement,b=body.getBoundingClientRect(),f=el.getBoundingClientRect();return {body:b.height,frame:f.height,width:f.width,padding:getComputedStyle(body).padding};});
   check('Iframe preenche corpo em '+width,Math.abs(geometry.body-geometry.frame)<2&&geometry.padding==='0px',geometry);
   check('Sem overflow horizontal em '+width,m.overflow<=1&&await page.evaluate(()=>document.documentElement.scrollWidth-innerWidth)<=1,m);
   if(width===390){
    const mobileContext=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:m.width,height:m.height}});await mobileContext.addCookies(cookies);const mobile=await mobileContext.newPage();await mobile.goto(base+'/pt-br/apresentacoes/conn2flow-widget/',{waitUntil:'networkidle'});await mobile.waitForSelector('[data-c2f-deck].is-ready');
    const expectedMobile=await mobile.evaluate(measure);check('Paridade de estilos em 390 px',JSON.stringify(m.details)===JSON.stringify(expectedMobile.details)&&JSON.stringify(m.deck)===JSON.stringify(expectedMobile.deck),{actual:m,expected:expectedMobile});await mobile.screenshot({path:path.join(out,'referencia-mobile.png')});await mobileContext.close();await page.bringToFront();
   }
   await page.screenshot({path:path.join(out,'dashboard-'+width+'.png'),fullPage:true});
  }
  await page.setViewportSize({width:1366,height:900});await page.locator('#dashboard-options summary').click();await page.locator('#dashboard-edit-mode').click();
  const handle=page.locator('[data-widget-instance="req244-pilot"] .dashboard-widget-resize-handle');await handle.scrollIntoViewIfNeeded();const r=await handle.boundingBox();
  await page.mouse.move(r.x+r.width/2,r.y+r.height/2);await page.mouse.down();await page.mouse.move(r.x-200,r.y+80,{steps:10});await page.mouse.up();await page.waitForTimeout(700);
  check('Resize acompanha nova altura',(await frame.evaluate(measure)).height!==actual.height);
  for(const w of layout.slice(1)){
   const iframe=page.locator('[data-widget-instance="'+w.instance_id+'"] iframe');check(w.id+' renderizado',await iframe.count()===1);
   if(await iframe.count()){await iframe.scrollIntoViewIfNeeded();await page.waitForTimeout(300);await iframe.screenshot({path:path.join(out,w.id+'.png')});const f=await (await iframe.elementHandle()).contentFrame();check(w.id+' tem HTML e compilador',await f.evaluate(()=>document.body.textContent.trim().length>0&&!!document.querySelector('script[src*="index.global.js"]')));}
  }
  check('Sem exceções JavaScript',errors.length===0,errors);
 }catch(e){check('Roteiro concluído',false,e.stack);}
 finally{
  try{
   if(original!==undefined){const layout=await ajax('salvar-preferencias',{chave:'dashboard_widgets_layout',valor:original});const activeTab=await ajax('salvar-preferencias',{chave:'dashboard_aba_ativa',valor:tab});check('Preferências originais restauradas',layout.status==='Ok'&&activeTab.status==='Ok');}
  }catch(e){check('Preferências originais restauradas',false,e.message);}
  finally{fs.writeFileSync(path.join(out,'resultado.json'),JSON.stringify(results,null,2));await browser.close();}
 }
 process.exitCode=results.every(r=>r.ok)?0:1;
})();
