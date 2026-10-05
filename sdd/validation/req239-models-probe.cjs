const {chromium}=require('../../node_modules/playwright'),fs=require('node:fs'),path=require('node:path');
const base='https://conn2flow.local';
const jar=fs.readFileSync(path.resolve(__dirname,'../../temp/agent-cookies.txt'),'utf8').split(/\r?\n/).map(l=>l.replace(/^#HttpOnly_/,''))
 .filter(l=>l&&!l.startsWith('#')).map(l=>l.split('\t')).filter(p=>p.length>=7).map(p=>({domain:p[0].replace(/^\./,''),path:p[2],secure:p[3]==='TRUE',name:p[5],value:p[6]}));
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});
 try{
 const ctx=await browser.newContext({ignoreHTTPSErrors:true});await ctx.addCookies(jar);const page=await ctx.newPage();
 await page.goto(base+'/products/',{waitUntil:'networkidle'});
 const href=await page.locator('main a[href*="products/edit/?"]').first().getAttribute('href');const id=new URL(href,base).searchParams.get('id');
 await page.goto(base+'/products/page/?id='+encodeURIComponent(id),{waitUntil:'networkidle'});
 const response=page.waitForResponse(r=>r.request().method()==='POST'&&(r.request().postData()||'').includes('html-editor-templates-load'));
 await page.locator('.menuContainerPagina [data-tab=modelos]').click();const result=await response;
 const data=await result.json();console.log(JSON.stringify({status:result.status(),payloadStatus:data.status,keys:Object.keys(data),info:data.info}));
 if(result.status()!==200||data.status!=='Ok')process.exitCode=1;
 }finally{await browser.close();}
})().catch(e=>{console.error(e.message);process.exitCode=1;});
