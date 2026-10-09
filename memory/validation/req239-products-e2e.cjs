// Lab: produtos descartáveis, submissão real e remoção somente dos IDs criados aqui.
const {chromium}=require('../../node_modules/playwright');
const fs=require('node:fs'),path=require('node:path');
const base='https://conn2flow.local',prefix='req239-e2e-'+Date.now(),out=path.join(__dirname,'evidence-req239');
const records=[],checks=[],cleanup=[];
function check(name,ok,detail,soft=false){checks.push({name,ok:!!ok,detail});console.log((ok?'OK ':'FAIL ')+name);if(!ok&&!soft)throw Error(name+': '+JSON.stringify(detail));}
const jar=fs.readFileSync(path.resolve(__dirname,'../../temp/agent-cookies.txt'),'utf8').split(/\r?\n/).map(l=>l.replace(/^#HttpOnly_/,''))
 .filter(l=>l&&!l.startsWith('#')).map(l=>l.split('\t')).filter(p=>p.length>=7).map(p=>({domain:p[0].replace(/^\./,''),path:p[2],secure:p[3]==='TRUE',name:p[5],value:p[6]}));
(async()=>{
 const browser=await chromium.launch({headless:true,args:['--host-resolver-rules=MAP conn2flow.local 127.0.0.1']});
 const ctx=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:1440,height:1000}});await ctx.addCookies(jar);const page=await ctx.newPage();let code=1;
 async function submit(selector='form:has([name=name])'){
  await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator(selector).evaluate(el=>el.requestSubmit())]);
 }
 async function register(label){
  if(!page.url().includes('/products/edit/')){
   await page.goto(base+'/products/',{waitUntil:'networkidle'});
   const link=page.locator('main tr').filter({hasText:label}).locator('a[href*="edit/?"]').first();
   check('Created product found',await link.count()===1);
   await page.goto(new URL(await link.getAttribute('href'),base).href,{waitUntil:'networkidle'});
  }
  const id=new URL(page.url()).searchParams.get('id');
  check('Fixture ownership',id&&id.startsWith(prefix)&&await page.locator('[name=name]').inputValue()===label,id);
  const record={id,label,edit:page.url()};records.push(record);return record;
 }
 try{
  await page.goto(base+'/products/add/',{waitUntil:'networkidle'});
  const label=prefix+'-local';await page.locator('[name=name]').fill(label);
  await page.locator('[name=price]').fill('123456');await page.locator('[name=sale_price]').fill('100000');
  await page.locator('[name=type]').selectOption('physical',{force:true});
  for(const field of ['weight_grams','length_cm','width_cm','height_cm']){
   const input=page.locator('[name='+field+']');if(await input.count())await input.fill('10');
  }
  const shipping=page.locator('[name="shipping_method_ids[]"]');
  const methods=await shipping.locator('option').evaluateAll(es=>es.map(e=>e.value));
  check('Configured shipping methods exist',methods.length>0,methods);
  await shipping.selectOption(methods[0],{force:true});
  await page.locator('[name=sale_period]').selectOption('period',{force:true});
  const start='2030-01-01T10:00',end='2030-02-01T10:00';
  await page.locator('[name=sale_starts_at]').fill(start);await page.locator('[name=sale_ends_at]').fill(end);
  await page.evaluate(()=>EditorTexto.definirValor(document.querySelector('[name=description]'),'<p><strong>REQ-239</strong> descrição</p>'));
  await page.locator('[data-c2f-imagem-url]').fill('https://example.com/req239.png');
  await submit();const local=await register(label);
  await page.reload({waitUntil:'networkidle'});
  check('Masked price persisted',await page.locator('[name=price]').inputValue()==='R$\u00a01.234,56',await page.locator('[name=price]').inputValue());
  check('Quill HTML persisted',(await page.locator('[name=description]').inputValue()).includes('<strong>REQ-239</strong>'));
  check('Image URL persisted',await page.locator('[data-c2f-imagem-url]').inputValue()==='https://example.com/req239.png');
  check('Promotion dates persisted',await page.locator('[name=sale_starts_at]').inputValue()===start&&await page.locator('[name=sale_ends_at]').inputValue()===end);
  check('Shipping selection persisted',JSON.stringify(await shipping.evaluate(el=>[...el.selectedOptions].map(o=>o.value)))===JSON.stringify([methods[0]]));
  const publicUrl=await page.locator('a[target=_blank][href*="'+local.id+'"]').getAttribute('href').catch(()=>null);
  check('Public product link',!!publicUrl,publicUrl);
  await page.goto(base+'/products/variants/?id='+encodeURIComponent(local.id),{waitUntil:'networkidle'});
  await page.locator('[name="v[0][sku]"]').fill(prefix.toUpperCase());
  await page.locator('[name="v[0][label_pt]"]').fill('REQ-239');
  await page.locator('[name="v[0][price]"]').fill('25000');await page.locator('[name="v[0][sale_price]"]').fill('20000');
  await page.locator('[name="v[0][sale_period]"]').selectOption('period',{force:true});
  await page.locator('[name="v[0][sale_starts_at]"]').fill(start);await page.locator('[name="v[0][sale_ends_at]"]').fill(end);
  check('Variant action icons',await page.locator('[data-variant-remove] svg').count()===1&&await page.locator('svg[data-lucide=power-off]').count()===1);
  await submit('[data-variants-form]');await page.reload({waitUntil:'networkidle'});
  const variantSaved={price:await page.locator('[name="v[0][price]"]').inputValue(),start:await page.locator('[name="v[0][sale_starts_at]"]').inputValue(),sku:await page.locator('[name="v[0][sku]"]').inputValue()};
  check('Variant price and dates persisted',variantSaved.price==='R$\u00a0250,00'&&variantSaved.start===start,variantSaved);
  await page.locator('[data-variant-add]').click();await page.waitForTimeout(150);
  const dynamic=page.locator('[name="v[1][price]"]');await dynamic.fill('12345');
  check('Dynamic variant currency mask',await dynamic.inputValue()==='R$\u00a0123,45');
  await page.locator('[data-variant-row]').last().locator('[data-variant-remove]').click();
  await page.goto(base+'/products/page/?id='+encodeURIComponent(local.id),{waitUntil:'networkidle'});
  check('Editor template has no leaked comment',!(await page.locator('main').innerText()).includes('Vem na variante'));
  const tokens=await page.locator('main code').allTextContents();
  check('Product editor variable tokens intact',tokens.length>0&&tokens.every(token=>/^@\[\[product#[^\]]+\]\]@$/.test(token)),tokens,true);
  const modelsResponse=page.waitForResponse(r=>r.request().method()==='POST'&&(r.request().postData()||'').includes('html-editor-templates-load'),{timeout:35000});
  await page.locator('.menuContainerPagina [data-tab=modelos]').click();
  const loaded=await modelsResponse;const modelPayload=await loaded.json();
  check('Models AJAX succeeds',loaded.status()===200&&modelPayload.status==='Ok',{status:loaded.status(),payload:modelPayload.status},true);
  await page.waitForFunction(()=>getComputedStyle(document.getElementById('modelos-loading')).display==='none',{},{timeout:35000});
  const modelsState=await page.locator('#modelos-cards').evaluate(el=>({display:getComputedStyle(el).display,parents:[el.parentElement,el.parentElement.parentElement,el.closest('.containerPagina')].map(p=>({class:p.className,display:getComputedStyle(p).display}))}));
  const availableModels=modelPayload.data&&modelPayload.data.modelos;
  check('Models loading terminates',Array.isArray(availableModels)&&modelsState.display!=='none'&&(availableModels.length?await page.locator('#modelos-cards').isVisible():await page.locator('#modelos-empty').isVisible()),{...modelsState,modelCount:availableModels&&availableModels.length},true);
  await page.screenshot({path:path.join(out,'products-models.png')});
  await page.reload({waitUntil:'networkidle'});
  await page.route('**/products/',async route=>{
   if((route.request().postData()||'').includes('html-editor-templates-load'))await route.fulfill({status:500,contentType:'application/json',body:JSON.stringify({status:'error',message:'REQ-239: falha simulada de transporte'})});
   else await route.continue();
  });
  await page.locator('.menuContainerPagina [data-tab=modelos]').click();
  await page.waitForFunction(()=>getComputedStyle(document.getElementById('modelos-loading')).display==='none',{},{timeout:35000});
  check('Models loading terminates after HTTP failure',await page.locator('#modelos-loading .dimmer.active').count()===0&&await page.locator('#modelos-cards').evaluate(el=>getComputedStyle(el).display!=='none'));
  await page.unroute('**/products/');
  await page.goto(base+'/products/add/',{waitUntil:'networkidle'});
  const stripeLabel=prefix+'-stripe';await page.locator('[name=name]').fill(stripeLabel);
  await page.locator('[name=product_origin]').selectOption('stripe',{force:true});
  const option=page.locator('[name=stripe_product_ref] option[data-price]').first();
  const ref=await option.getAttribute('value'),expectedPrice=await option.getAttribute('data-price'),expectedCurrency=await option.getAttribute('data-currency');
  await page.locator('[name=stripe_product_ref]').selectOption(ref,{force:true});
  // Forjar apenas os valores de formulário prova a herança autoritativa do servidor no Lab.
  await page.evaluate(()=>{document.querySelector('[name=price]').value='0.01';document.querySelector('[name=currency]').disabled=false;document.querySelector('[name=currency]').value='EUR';});
  await submit();const stripe=await register(stripeLabel);await page.reload({waitUntil:'networkidle'});
  const price=await page.evaluate(()=>C2FCampoMoeda.decimal(document.querySelector('[name=price]').value));
  check('Backend inherits catalog price and currency',Number(price)===Number(expectedPrice)&&await page.locator('[name=currency]').inputValue()===expectedCurrency,{price,expectedPrice,expectedCurrency});
  code=checks.every(c=>c.ok)?0:1;
 }catch(e){console.error(e.message);}
 finally{
  for(const r of records.reverse()){
   await page.goto(r.edit,{waitUntil:'networkidle'});
   if(await page.locator('[name=name]').inputValue()!==r.label)throw Error('Cleanup ownership mismatch');
   const url=await page.evaluate(id=>interfaceUrlCsrf(gestor.raiz+'products/?opcao=excluir&id='+encodeURIComponent(id)),r.id);
   const response=await page.goto(new URL(url,base).href,{waitUntil:'networkidle'});
   await page.goto(base+'/products/',{waitUntil:'networkidle'});
   const removed=response.status()<400&&await page.locator('main a[href*="id='+r.id+'"]').count()===0;
   cleanup.push({id:r.id,removed});if(!removed)code=1;console.log('Cleanup',r.id,removed);
  }
  fs.mkdirSync(out,{recursive:true});fs.writeFileSync(path.join(out,'products-e2e.json'),JSON.stringify({prefix,checks,cleanup,exit:code},null,2));
  await browser.close();process.exitCode=code;
 }
})().catch(e=>{console.error(e);process.exitCode=1;});
