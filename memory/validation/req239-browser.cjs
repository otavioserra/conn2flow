const {chromium} = require('../../node_modules/playwright');
const fs = require('node:fs'), path = require('node:path');
const root = path.resolve(__dirname, '../..'), out = path.join(__dirname, 'evidence-req239');
fs.mkdirSync(out, {recursive:true});
const base = 'https://conn2flow.local', report = {checks:[], pages:[]};
function check(name, ok, detail) { report.checks.push({name,ok:!!ok,detail}); console.log((ok?'OK ':'FAIL ')+name+(!ok?' '+JSON.stringify(detail):'')); }
const jar = fs.readFileSync(path.join(root, 'temp/agent-cookies.txt'),'utf8').split(/\r?\n/).filter(l => l.startsWith('#HttpOnly_') || l && !l.startsWith('#')).map(l => {
 const p=l.replace(/^#HttpOnly_/,'').split('\t'); return {domain:p[0].replace(/^\./,''),path:p[2],secure:p[3]==='TRUE',httpOnly:l.startsWith('#HttpOnly_'),name:p[5],value:p[6]};
});
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});
 const ctx=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:1366,height:900}}); await ctx.addCookies(jar);
 const page=await ctx.newPage(); let errors=[]; page.on('pageerror', e=>errors.push(e.message));
 page.on('console',message=>{if(message.type()==='error')errors.push('console: '+message.text());});
 try {
  const routes=['dashboard/','admin-paginas/adicionar/','publisher/adicionar/','publisher-highlights/adicionar/','publisher-index/adicionar/','publisher-pages/adicionar/','publisher-pages/','variables/','admin-ia/adicionar/','admin-prompts-ia/adicionar/','admin-arquivos/','cookie-consent/adicionar/','products/add/','stripe-products/adicionar/'];
  for (const route of routes) {
   errors=[]; await page.setViewportSize({width:1366,height:900}); const response=await page.goto(base+'/'+route,{waitUntil:'networkidle'});
   check(route+' authenticated',response.status()===200&&!page.url().includes('signin'),{status:response.status(),url:page.url()});
   check(route+' no JS errors',errors.length===0,errors);
   const metrics=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth-innerWidth,unresolved:[...document.querySelectorAll('[data-admin-main] input,[data-admin-main] select')].filter(n=>n.getClientRects().length&&/^#[a-z_-]+#$/.test(n.value)).map(n=>n.name)}));
   check(route+' resolved fields',metrics.unresolved.length===0,metrics.unresolved);
   await page.screenshot({path:path.join(out,route.replaceAll('/','-')+'desktop.png')});
   await page.setViewportSize({width:390,height:844}); await page.waitForTimeout(150);
   await page.locator('[data-admin-fechar]').click(); await page.waitForTimeout(250);
   const overflow=await page.evaluate(()=>document.documentElement.scrollWidth-innerWidth); check(route+' mobile geometry',overflow<=1,overflow);
   await page.screenshot({path:path.join(out,route.replaceAll('/','-')+'mobile.png')});
   report.pages.push({route,metrics,overflow});
   await page.setViewportSize({width:1366,height:900});
   await page.waitForTimeout(250);
   if(route==='dashboard/') {
    const original=await page.locator('.dashboard-density-btn.active').getAttribute('data-density');
    const originalTab=await page.locator('.dashboard-tab-trigger.active').getAttribute('id');
    await page.locator('#dashboard-tab-btn-modulos').click();
    await page.locator('#dashboard-options summary').click();
    try {
     await page.locator('[data-density=p]').click();
     const small=await page.locator('.dashboard-module-card').first().evaluate(el=>({icon:!!el.querySelector('.dashboard-card-svg svg')&&el.querySelector('.dashboard-card-svg svg').getBoundingClientRect().width>0,name:el.querySelector('.dashboard-card-title').textContent.trim(),header:el.querySelector('.dashboard-card-header').getBoundingClientRect().width}));
     check('Dashboard P icon and legible name',small.icon&&small.name.length>0&&small.header<=40,small);
     check('Dashboard P drag handle does not obscure icon',await page.locator('.dashboard-module-card .dashboard-card-drag-handle').first().evaluate(el=>getComputedStyle(el).display==='none'));
     await page.screenshot({path:path.join(out,'dashboard-density-p.png')});
     await page.locator('[data-density=g]').click();
     const pos=await page.locator('.dashboard-module-cover').first().evaluate(el=>getComputedStyle(el).objectPosition);
     check('Dashboard G cover position',pos==='50% 15%',pos);
     await page.screenshot({path:path.join(out,'dashboard-density-g.png')});
    }finally{await page.locator('[data-density="'+original+'"]').click();await page.locator('#'+originalTab).click();await page.waitForTimeout(300);}
   }
   if(route==='admin-paginas/adicionar/') {
    await page.evaluate(()=>html_editor_set_html('<section data-id="req239-a" data-title="Primeira"><p>A</p></section><section data-id="req239-b" data-title="Segunda"><p>B</p></section>'));
    await page.locator('.menuContainerPagina [data-tab=assistente-ia]').click();
    await page.locator('.page-modification-target-select').selectOption('sessao',{force:true});
    const sections=await page.locator('.page-modification-section-select option').evaluateAll(es=>es.map(e=>e.value));
    check('Editor loads sections for active target',JSON.stringify(sections)===JSON.stringify(['req239-a','req239-b']),sections);
    await page.locator('.menuContainerPagina [data-tab=visualizacao-pagina]').click();
    await page.locator('.editorHtmlVisual').click();
    await page.locator('.previsualizar .html-editor-add-btn').click();
    check('Visual editor add panel opens above iframe',await page.locator('.html-editor-add-panel').isVisible()&&await page.locator('.html-editor-add-panel').evaluate(el=>getComputedStyle(el).zIndex==='100050'));
    await page.screenshot({path:path.join(out,'admin-paginas-add-panel.png')});
    await page.keyboard.press('Escape');
   }
   if(route==='publisher-pages/') {
    const radios=page.locator('input[type=radio][name=tipo]');
    check('Publisher page type native radio',await radios.count()===3&&await page.locator('.c2fc-chave input[type=radio][name=tipo]').count()===0);
    await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),radios.locator('..').filter({has:page.locator('[value=sistema]')}).click()]);
    check('Publisher type filter changes route',new URL(page.url()).searchParams.get('tipo')==='sistema');
   }
   if(route==='publisher/adicionar/') {
    check('Publisher linked model preview stays on one line',await page.locator('.publisher-link-preview').first().evaluate(el=>getComputedStyle(el).display==='inline-flex'&&getComputedStyle(el).whiteSpace==='nowrap'));
    await page.locator('#add-field-btn').click();
    const field=page.locator('.field-row').filter({visible:true}).first();
    check('Publisher field uses compact horizontal controls',await field.locator('.fields').first().evaluate(el=>getComputedStyle(el).display==='flex')&&await field.locator('.remove-field-btn').evaluate(el=>el.getBoundingClientRect().width===28));
    await page.screenshot({path:path.join(out,'publisher-field-desktop.png')});
   }
   if(route==='admin-ia/adicionar/') {
    await page.goto(base+'/admin-ia/listar/',{waitUntil:'networkidle'});
    const edit=page.locator('main a[href*="admin-ia/editar/"]').first();
    check('AI connection edit fixture available',await edit.count()===1);
    if(await edit.count()){
     await page.goto(new URL(await edit.getAttribute('href'),base).href,{waitUntil:'networkidle'});
     const boxes=await page.locator('#historico-testes, #form-global-models').evaluateAll(es=>es.map(el=>({background:getComputedStyle(el.closest('section')).backgroundColor,border:getComputedStyle(el.closest('section')).borderTopWidth})));
     check('AI history and global models use solid white cards',boxes.length===2&&boxes.every(b=>b.background==='rgb(255, 255, 255)'&&b.border==='1px'),boxes);
     await page.screenshot({path:path.join(out,'admin-ia-edit-desktop.png')});
    }
   }
   if(route==='variables/') check('Module picker replaces placeholder',await page.locator('select[name=modulo]').count()===1);
   if(route==='cookie-consent/adicionar/') {
    const tabs=page.locator('.menuOpcoesWidget');
    check('Cookie tabs use attached canonical layout',await tabs.evaluate(el=>el.classList.contains('c2fc-abas-lista')&&el.classList.contains('c2fc-anexa')&&getComputedStyle(el).display==='flex'&&getComputedStyle(el).marginBottom==='0px'));
    await tabs.locator('[data-tab=cc-categories]').click();
    check('Cookie category tab opens canonical panel',await page.locator('.c2fc-painel-aba[data-tab=cc-categories]').isVisible()&&await page.locator('.c2fc-painel-aba[data-tab=cc-general]').isHidden());
    await tabs.locator('[data-tab=cc-general]').click();
   }
   if(route==='products/add/' || route==='stripe-products/adicionar/') {
    check(route+' Quill',await page.locator('.ql-container').count()===1&&await page.locator('.ql-toolbar').count()===1);
    const input=page.locator(route==='products/add/'?'[name=price]':'[name=amount]');
    await input.fill('123456'); check(route+' currency mask',(await input.inputValue()).replace(/\s/g,' ')==='R$ 1.234,56',await input.inputValue());
    check(route+' Image URL and folder',await page.locator('[data-c2f-imagem-url]').count()===1&&await page.locator('._gestor-widgetImage-btn-add svg[data-lucide="folder-open"]').count()===1);
    const imageGeometry=await page.locator('._gestor-widgetImage-cont').evaluate(el=>({preview:el.querySelector('.fileImageParent').getBoundingClientRect().width,details:el.querySelector('[data-c2f-imagem-url]').getBoundingClientRect().width}));
    check(route+' Image preview preserves room for controls',imageGeometry.preview<=161&&imageGeometry.details>=150,imageGeometry);
    await page.locator('[data-c2f-imagem-url]').fill(base+'/images/imagem-padrao.png');
    check(route+' Image URL synchronizes',await page.locator('._gestor-widgetImage-file-caminho').inputValue()===base+'/images/imagem-padrao.png');
    await page.locator('._gestor-widgetImage-btn-add').click(); await page.waitForTimeout(400);
    check(route+' File modal modern route',(await page.locator('.iframePagina iframe').getAttribute('src')).includes('admin-arquivos/'));
    await page.keyboard.press('Escape');
   }
   if(route==='products/add/') {
    await page.locator('[name=type]').selectOption('physical',{force:true});
    check('Physical shipping selector',await page.locator('#product-shipping-section').isVisible()&&await page.locator('[name="shipping_method_ids[]"]').count()===1);
    await page.locator('[name=product_origin]').selectOption('stripe',{force:true});
    const options=await page.locator('[name=stripe_product_ref] option[data-price]').count();
    check('Stripe catalog available',options>0,options);
    if(options){const ref=await page.locator('[name=stripe_product_ref] option[data-price]').first().getAttribute('value');await page.locator('[name=stripe_product_ref]').selectOption(ref,{force:true});}
    check('Stripe inherited price locks fields',await page.locator('[name=price]').getAttribute('readonly')!==null&&await page.locator('[name=currency]').isDisabled()&&await page.locator('[name=sale_price]').isHidden());
    await page.locator('[name=product_origin]').selectOption('local',{force:true});
    check('Local origin unlocks fields',await page.locator('[name=price]').getAttribute('readonly')===null&&await page.locator('[name=currency]').isEnabled());
   }
  }
  await page.goto(base+'/admin-paginas/?tipo=sistema',{waitUntil:'networkidle'});
  const historyLinks=await page.locator('main a[href*="editar/"]').evaluateAll(es=>es.slice(0,8).map(e=>e.href));
  await page.goto(base+'/admin-paginas/?tipo=pagina',{waitUntil:'networkidle'});
  historyLinks.push(...await page.locator('main a[href*="editar/"]').evaluateAll(es=>es.slice(0,8).map(e=>e.href)));
  let historyFound=false;
  for(const href of historyLinks){
   await page.goto(href,{waitUntil:'networkidle'});
   const row=page.locator('[data-c2f-historico] .list > .item').first();
   if(!await row.count())continue;
   historyFound=true;
   const history=await row.evaluate(el=>({display:getComputedStyle(el).display,contentDisplay:getComputedStyle(el.querySelector('.content')).display,wrap:getComputedStyle(el.querySelector('.content')).flexWrap,icon:!!el.querySelector('svg[data-lucide=info]')}));
   check('Page history uses horizontal rows and Lucide info',history.display==='flex'&&history.contentDisplay==='flex'&&history.wrap==='nowrap'&&history.icon,history);
   await page.screenshot({path:path.join(out,'admin-paginas-history-desktop.png')});break;
  }
  if(!historyFound){report.skipped=[{name:'Page history visual row',reason:'Nenhuma ocorrência de histórico nas páginas existentes consultadas; estrutura PHP/CSS revisada.',candidates:historyLinks.length}];console.log('SKIP Page history visual row: no existing history records');}
 } catch(e) { check('Browser scenario completed',false,e.message); throw e; }
 finally { report.finished_at=new Date().toISOString(); report.failed=report.checks.filter(c=>!c.ok).length; fs.writeFileSync(path.join(out,'browser.json'),JSON.stringify(report,null,2)); await browser.close(); }
 process.exitCode=report.failed?1:0;
})().catch(e=>{console.error(e);process.exitCode=1;});
