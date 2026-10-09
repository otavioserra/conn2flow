const {chromium}=require('../../node_modules/playwright');
const fs=require('fs'),path=require('path');
const output=path.join(__dirname,'evidence-req236'+(process.env.C2F_WIDGET_TYPE?'-'+process.env.C2F_WIDGET_TYPE:''));fs.mkdirSync(output,{recursive:true});
const report={started_at:new Date().toISOString(),checks:[]};
const check=(name,ok,data)=>{report.checks.push({name,ok:!!ok,data});console.log(`${ok?'OK':'FAIL'} ${name}${ok?'':' '+JSON.stringify(data)}`);};
const cookies=fs.readFileSync(path.join(__dirname,'../../temp/agent-cookies.txt'),'utf8').split(/\r?\n/).map(l=>l.replace(/^#HttpOnly_/,'' )).filter(l=>l&&!l.startsWith('#')).map(l=>l.split('\t')).filter(p=>p.length>=7).map(p=>({domain:p[0].replace(/^\./,''),path:p[2],secure:p[3]==='TRUE',name:p[5],value:p[6]}));
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});
 const ctx=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:1366,height:900}});await ctx.addCookies(cookies);
 const page=await ctx.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
 let original;
 async function ajax(action,data){return page.evaluate(async ({action,data})=>{const body=new URLSearchParams({opcao:'inicio',ajax:'sim',ajaxOpcao:action,...data});return (await fetch(gestor.raiz+'dashboard/',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})).json();},{action,data});}
 async function menu(){await page.locator('#dashboard-options').evaluate(el=>el.open=true);}
 try {
  const response=await page.goto('https://conn2flow.local/dashboard/',{waitUntil:'networkidle'});
  check('Dashboard authenticated HTTP 200',response.status()===200&&!/signin/.test(page.url()),response.status());
  original=await page.evaluate(()=>structuredClone(gestor.dashboard_user_prefs));
  await page.locator('#dashboard-tab-btn-modulos').click();
  check('Title precedes tabs',await page.evaluate(()=>document.querySelector('.dashboard-cards-title').getBoundingClientRect().bottom<=document.querySelector('.dashboard-tabs-container').getBoundingClientRect().top));
  await menu();await page.locator('[data-density="m"]').click();
  const cover=await page.locator('.dashboard-module-card.has-cover').first().evaluate(el=>({images:el.querySelectorAll('.dashboard-card-header img').length,svg:el.querySelectorAll('.dashboard-card-header svg image').length,height:el.querySelector('.dashboard-card-header').getBoundingClientRect().height,fit:getComputedStyle(el.querySelector('.dashboard-card-header img')).objectFit}));
  check('Single cover M 176px',cover.images===1&&cover.svg===0&&Math.abs(cover.height-176)<1&&cover.fit==='cover',cover);
  await menu();await page.locator('[data-density="g"]').click();
  check('Cover G 232px',Math.abs(await page.locator('.has-cover .dashboard-card-header').first().evaluate(el=>el.getBoundingClientRect().height)-232)<1);
  await page.locator('.dashboard-card-drag-handle').first().hover();
  const tip=await page.locator('.dashboard-card-drag-handle').first().evaluate(el=>{const s=getComputedStyle(el,'::after');return {left:s.left,transform:s.transform,z:s.zIndex,pos:el.getAttribute('data-c2f-dica-pos'),overflow:getComputedStyle(el.closest('.dashboard-module-card')).overflow};});
  check('Tooltip aligned left and unclipped',tip.left==='0px'&&tip.transform==='none'&&tip.z==='100030'&&tip.pos==='bottom left'&&tip.overflow!=='hidden',tip);
  await page.locator('#dashboard-tab-btn-widgets').click();
  await page.evaluate(()=>sessionStorage.removeItem('dashboard_widgets_editing'));
  await menu();if(await page.locator('#dashboard-edit-mode').getAttribute('aria-pressed')==='true')await page.locator('#dashboard-edit-mode').click();
  const catalog=await ajax('widgets-catalogo',{});let type,record;
  for(const candidate of catalog.data||[]){if(process.env.C2F_WIDGET_TYPE&&candidate.id!==process.env.C2F_WIDGET_TYPE)continue;const records=await ajax('widgets-registros',{widget_id:candidate.id});if(records.data?.items?.length){type=candidate;record=records.data.items[0];break;}}
  if(!type)throw Error('No active widget record available in Lab');
  async function add(){await menu();await page.locator('#dashboard-btn-add-widget').click();await page.locator('#dashboard-widgets-modal-list button').filter({hasText:type.name}).first().click();await page.locator('#dashboard-widgets-modal-list button').filter({hasText:record.nome||record.id}).first().click();await page.waitForTimeout(700);}
  await add();await add();
  const card=page.locator('.dashboard-widget-card').last();
  check('View mode hides widget controls',await card.locator('.dashboard-widget-switch-btn').evaluate(el=>getComputedStyle(el).display==='none'));
  await menu();await page.locator('#dashboard-edit-mode').click();
  check('Edit mode shows four corner handles',await card.locator('.dashboard-widget-resize-handle').count()===4&&await card.locator('.dashboard-widget-switch-btn').isVisible());
  const handle=card.locator('[data-corner="se"]');await handle.scrollIntoViewIfNeeded();
  const box=await handle.boundingBox(),gridWidth=await page.locator('#dashboard-widgets-grid').evaluate(el=>el.getBoundingClientRect().width);
  await page.mouse.move(box.x+box.width/2,box.y+box.height/2);await page.mouse.down();await page.mouse.move(box.x+gridWidth/6,box.y+250,{steps:10});await page.mouse.up();await page.waitForTimeout(500);
  check('Resize snaps and persists',await card.getAttribute('data-widget-cols')==='6'&&await card.getAttribute('data-widget-height')==='2');
  await page.reload({waitUntil:'networkidle'});
  const saved=await page.evaluate(()=>gestor.dashboard_user_prefs.widgets_layout);
  const instances=saved.filter(w=>w.id===type.id&&w.registro_id===record.id);
  check('Type, record and independent instances survive reload',instances.length>=2&&new Set(instances.map(w=>w.instance_id)).size===instances.length&&instances.every(w=>w.params.grupo_slug===record.id),instances.map(w=>({id:w.id,registro_id:w.registro_id,instance_id:w.instance_id})));
  check('Resize survives reload',instances.at(-1).width===6&&instances.at(-1).height===2);
  check('Widget actually renders in isolated document',await page.locator('.dashboard-widget-frame').last().evaluate(el=>el.contentDocument?.body.children.length>0&&el.contentDocument.body.textContent.trim().length>0));
  await page.screenshot({path:path.join(output,'dashboard-desktop.png')});
  await page.setViewportSize({width:390,height:844});await page.waitForTimeout(300);
  if(await page.locator('[data-admin-fechar]').isVisible())await page.locator('[data-admin-fechar]').click();
  await page.waitForTimeout(350);await page.evaluate(()=>scrollTo(0,0));
  check('390px has no horizontal overflow',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),await page.evaluate(()=>[document.documentElement.scrollWidth,innerWidth]));
  await menu();await page.screenshot({path:path.join(output,'dashboard-mobile-options.png')});
  check('No Fomantic assets',await page.evaluate(()=>!Array.from(document.querySelectorAll('link[href],script[src]')).some(el=>/semantic(?:\.min)?\.(css|js)/.test(el.href||el.src))));
  check('No JavaScript errors',errors.length===0,errors);
 } catch(error){check('Scenario completes',false,error.stack);} finally {
  if(original){for(const [key,field] of [['dashboard_widgets_layout','widgets_layout'],['dashboard_densidade','densidade'],['dashboard_aba_ativa','aba_ativa']]){const restored=await ajax('salvar-preferencias',{chave:key,valor:JSON.stringify(original[field]??(field==='widgets_layout'?[]:field==='densidade'?'m':'dashboard-tab-modulos'))}).catch(()=>null);check('Restore '+key,restored?.status==='Ok');}}
  await browser.close();report.summary=`${report.checks.filter(c=>c.ok).length}/${report.checks.length}`;fs.writeFileSync(path.join(output,'dashboard.json'),JSON.stringify(report,null,2));console.log(report.summary);process.exit(report.checks.every(c=>c.ok)?0:1);
 }
})();
