const {chromium}=require('../../node_modules/playwright');
const fs=require('fs'),path=require('path');
const core=path.resolve(__dirname,'../..'),site=path.resolve(core,'../conn2flow-site');
const out=path.join(__dirname,'evidence-req238');fs.mkdirSync(out,{recursive:true});
const report={started_at:new Date().toISOString(),checks:[],pages:[]};
function check(name,ok,data){report.checks.push({name,ok:!!ok,data});console.log((ok?'OK ':'FAIL ')+name+(ok?'':' '+JSON.stringify(data)));}
const jar=fs.readFileSync(path.join(core,'temp/agent-cookies.txt'),'utf8').split(/\r?\n/).filter(l=>l.startsWith('#HttpOnly_') || l && !l.startsWith('#')).map(l=>{const p=l.replace(/^#HttpOnly_/,'').split('\t');return {domain:p[0].replace(/^\./,''),path:p[2],secure:p[3]==='TRUE',httpOnly:l.startsWith('#HttpOnly_'),name:p[5],value:p[6]};});
const groups=[[core,['dashboard','admin-ia','admin-modos-ia','admin-prompts-ia','forms','forms-search','forms-submissions','menus','galleries','admin-arquivos','admin-categorias','admin-atualizacoes','cookie-consent','usuarios','usuarios-perfis']],[site,['presentations','subscriptions','subscriptions-config','subscriptions-plans','social-connections','host-manager','3d-catalog']]];
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});
 const context=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:1366,height:900}});await context.addCookies(jar);
 const page=await context.newPage();let errors=[];page.on('pageerror',e=>errors.push(e.message));page.on('console',m=>{if(m.type()==='error')errors.push(m.text());});
 try{
  for(const [repo,modules] of groups)for(const id of modules){
   const meta=JSON.parse(fs.readFileSync(path.join(repo,'gestor/modulos',id,id+'.json'),'utf8'));
   const pages=(meta.resources['pt-br'].pages || []).filter(p=>p.layout==='layout-administrativo-tailwind');
   const main=pages.find(p=>p.path===id+'/') || pages.find(p=>p.root) || pages[0];
   const add=pages.find(p=>/adicionar\/$/.test(p.path));
   for(const item of [main,add].filter(Boolean)){
    errors=[];await page.setViewportSize({width:1366,height:900});const response=await page.goto('https://conn2flow.local/'+item.path,{waitUntil:'networkidle'});
    await page.waitForTimeout(200);
    const label=id+'/'+item.option;
    check(label+' authenticated HTTP 200',response.status()===200 && !page.url().includes('signin'),response.status());
    const metrics=await page.evaluate(()=>{
     const main=document.querySelector('[data-admin-main]');const title=main && main.querySelector('h1,h2,h3,h4');
     const name=document.querySelector('.c2fc-topbar-user-name');const link=document.querySelector('[data-menu-item]');
     const avatar=document.querySelector('.c2fc-topbar-user-btn > span:first-child');
     return {overflow:document.documentElement.scrollWidth-innerWidth,avatar:avatar&&getComputedStyle(avatar).width,title:title&&title.textContent.trim(),font:title&&getComputedStyle(title).fontSize,weight:link&&getComputedStyle(link).fontWeight,name:!!name && getComputedStyle(name).display!=='none',cards:main&&[...main.querySelectorAll('.bg-white')].filter(n=>getComputedStyle(n).backgroundColor==='rgb(255, 255, 255)').length,text:main&&main.innerText};
    });
    report.pages.push({module:id,path:item.path,viewport:1366,metrics:{...metrics,text:undefined}});
    check(label+' desktop geometry/profile/sidebar',metrics.overflow<=1 && metrics.name && metrics.avatar==='32px' && ['500','600'].includes(metrics.weight),{...metrics,text:undefined});
    check(label+' white surface/title',metrics.cards>0 && !!metrics.title,{cards:metrics.cards,title:metrics.title});
    const toggle=page.locator('[data-topbar-toggle="topbar-profile"]');await toggle.click();
    const profile=await page.locator('#topbar-profile').boundingBox();check(label+' profile width',profile&&profile.width>=288 && profile.width<=321,profile);await page.keyboard.press('Escape');
    await page.screenshot({path:path.join(out,label.replaceAll('/','-')+'-desktop.png')});
    await page.setViewportSize({width:390,height:844});await page.waitForTimeout(300);
    if(await page.locator('[data-admin-fechar]').isVisible())await page.locator('[data-admin-fechar]').click();
    await page.waitForTimeout(200);check(label+' 390px without overflow',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1 && getComputedStyle(document.querySelector('.c2fc-topbar-user-btn > span:first-child')).width==='32px'));
    check(label+' browser errors',errors.length===0,errors);await page.screenshot({path:path.join(out,label.replaceAll('/','-')+'-mobile.png')});
   }
  }
  await page.setViewportSize({width:1366,height:900});await page.goto('https://conn2flow.local/forms-submissions/',{waitUntil:'networkidle'});
  const view=page.locator('[data-admin-main] a[href*="view/?"]').first();
  if(await view.count()){
   await view.click();await page.waitForLoadState('networkidle');const tabs=page.locator('[data-c2f-abas] [data-c2f-aba]');
   check('Submission has three canonical tabs',await tabs.count()===3);
   for(let i=0;i<3;i++){await tabs.nth(i).click();check('Submission tab '+i+' selects one panel',await page.locator('[data-c2f-abas] [data-c2f-painel]:not([hidden])').count()===1);}
   await page.screenshot({path:path.join(out,'submissions-tabs.png')});
  }else check('Submission view fixture available',false);
  for(const id of groups[1][1]){const response=await page.goto('https://conn2flow.local/documentation/'+id+'/',{waitUntil:'networkidle'});check('Private guide '+id,response.status()===200 && await page.locator('[data-title="module-guide"] .c2fc-botao').count()>1,response.status());}
 }finally{
  report.finished_at=new Date().toISOString();report.passed=report.checks.filter(c=>c.ok).length;report.failed=report.checks.filter(c=>!c.ok).length;fs.writeFileSync(path.join(out,'modules.json'),JSON.stringify(report,null,2)+'\n');await browser.close();
 }
 process.exitCode=report.failed?1:0;
})().catch(e=>{console.error(e);process.exitCode=1;});
