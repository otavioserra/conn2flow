import {readFileSync} from 'node:fs';
import {beforeEach, describe, expect, it, vi} from 'vitest';

// REQ-249 — widgets do Dashboard: navegação sempre para fora do quadro e aba de widgets em primeiro.
const source=readFileSync('gestor/modulos/dashboard/dashboard.js','utf8');
const start=source.indexOf('\tfunction initDashboardWidgets() {');
const init=source.slice(start,source.indexOf('\n\tinitDashboardWidgets();',start));
const component=readFileSync('gestor/modulos/dashboard/resources/pt-br/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html','utf8');
const block=(from,to)=>component.slice(component.indexOf(from),component.indexOf(to));
const menu=block('<details id="dashboard-options"','<!-- density-selector < -->')+'</div></details>';
const tabs='<nav><button type="button" id="dashboard-tab-btn-modulos">Módulos</button><button type="button" id="dashboard-tab-btn-widgets">Widgets</button></nav>';
const panel=block('<div id="dashboard-tab-widgets"','<!-- tab-content-widgets > -->').replace('class="dashboard-tab-panel space-y-6 hidden"','class="dashboard-tab-panel"');
const layouts=block('<div id="dashboard-widgets-layouts-modal"','<div id="dashboard-widget-config-modal"');
let saved, calls;
async function settle(){for(let i=0;i<10;i++)await Promise.resolve();}
function boot(layout,access){
 const gestor={raiz:'/', dashboard_user_prefs:{widgets_layout:layout,widgets:access}};
 const fetch=vi.fn(async (_url,options)=>{
  const params=Object.fromEntries(new URLSearchParams(options.body));calls.push(params);
  const data=params.ajaxOpcao==='widgets-layouts'?{perfis:[{id:'administradores',nome:'Administradores',publicado:false,total:0}],todos:{publicado:false,total:0},perfil_atual:'administradores'}:{html:'<p>ok</p>',css:''};
  return {ok:true,json:async()=>({status:'Ok',data})};
 });
 new Function('document','gestor','fetch','getLocalStorage','setLocalStorage','dashboardSalvarPreferenciaBackend','sessionStorage','ResizeObserver',init+'\ninitDashboardWidgets();')(document,gestor,fetch,()=>null,()=>{},(key,value)=>{saved[key]=structuredClone(value);},sessionStorage,undefined);
}
const w=(id,extra)=>Object.assign({id:'menus',name:'Menus',registro_id:'main',instance_id:id,width:4,height:1},extra);
const order=()=>[...document.querySelectorAll('nav button')].map(b=>b.id.replace('dashboard-tab-btn-',''));

// O script que roda dentro do documento isolado, tirado do `srcdoc` de um widget montado.
function frameScript(){
 const srcdoc=document.querySelector('iframe').srcdoc,at=srcdoc.lastIndexOf('<script>window.addEventListener("load"');
 return srcdoc.slice(at+'<script>'.length,srcdoc.indexOf('</script>',at));
}
function runInFrame(html){
 const code=frameScript();
 const doc=document.implementation.createHTMLDocument('widget');
 doc.body.innerHTML=html;
 const win={top:{location:{href:''}},addEventListener(){},dispatchEvent(){},navigation:{handlers:[],addEventListener(type,fn){this.handlers.push(fn);}}};
 new Function('window','document','navigation',code)(win,doc,win.navigation);
 return {doc,win,navigate(detail){const e=Object.assign({cancelable:true,hashChange:false,downloadRequest:null,navigationType:'push',prevented:false,preventDefault(){this.prevented=true;}},detail);win.navigation.handlers.forEach(fn=>fn(e));return e;}};
}

beforeEach(()=>{
 saved={};calls=[];sessionStorage.clear();
 const documents=new WeakMap();
 vi.spyOn(HTMLIFrameElement.prototype,'srcdoc','set').mockImplementation(function(value){documents.set(this,value);});
 vi.spyOn(HTMLIFrameElement.prototype,'srcdoc','get').mockImplementation(function(){return documents.get(this)||'';});
 document.body.innerHTML=menu+tabs+panel+layouts;
});

describe('Dashboard widget navigation (req-249)',()=>{
 it('tells widget scripts they run inside the Dashboard and keeps the document isolated',async()=>{
  boot([w('a')]);await settle();
  const frame=document.querySelector('iframe');
  // Sem `allow-same-origin`: o documento não lê cookie nem a página do painel.
  expect(frame.getAttribute('sandbox')).toBe('allow-scripts allow-forms allow-top-navigation-by-user-activation');
  expect(frame.srcdoc).toContain('"dashboardWidget":true');
  expect(frame.srcdoc).toMatch(/<base href="[^"]+" target="_top">/);
 });

 it('sends every link to the outer page, whatever target it declares, and keeps anchors inside',async()=>{
  boot([w('a')]);await settle();
  const {doc}=runInFrame('<a id="self" href="/plataforma/" target="_self">a</a><a id="blank" href="https://x.example/" target="_blank"><span id="inner">b</span></a><a id="none" href="/c/">c</a><a id="js" href="javascript:void(0)" target="_self">d</a><a id="hash" href="#alvo">e</a><p id="alvo">alvo</p>');
  const click=id=>{const e=new MouseEvent('click',{bubbles:true,cancelable:true});doc.getElementById(id).dispatchEvent(e);return e;};
  click('self');click('inner');click('none');click('js');
  expect(['self','blank','none','js'].map(id=>doc.getElementById(id).getAttribute('target'))).toEqual(['_top','_top','_top','_self']);
  const target=doc.getElementById('alvo');target.scrollIntoView=vi.fn();
  expect(click('hash').defaultPrevented).toBe(true);
  expect(target.scrollIntoView).toHaveBeenCalledTimes(1);
  expect(doc.getElementById('hash').getAttribute('target')).toBeNull();
 });

 it('sends form submissions to the outer page',async()=>{
  boot([w('a')]);await settle();
  const {doc}=runInFrame('<form id="f" action="/busca/" target="_self"><button type="submit">ok</button></form>');
  doc.getElementById('f').dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));
  expect(doc.getElementById('f').getAttribute('target')).toBe('_top');
 });

 it('redirects a navigation started by script to the outer page and ignores reload, hash and about:',async()=>{
  boot([w('a')]);await settle();
  const {win,navigate}=runInFrame('<p>x</p>');
  const go=navigate({destination:{url:'https://conn2flow.local/store/produto/'}});
  expect([go.prevented,win.top.location.href]).toEqual([true,'https://conn2flow.local/store/produto/']);
  win.top.location.href='';
  for (const detail of [{navigationType:'reload',destination:{url:'https://conn2flow.local/a/'}},{hashChange:true,destination:{url:'https://conn2flow.local/a/#x'}},{cancelable:false,destination:{url:'https://conn2flow.local/a/'}},{destination:{url:'about:srcdoc'}},{downloadRequest:'arquivo',destination:{url:'https://conn2flow.local/a.zip'}}]) {
   expect(navigate(detail).prevented).toBe(false);
  }
  expect(win.top.location.href).toBe('');
 });
});

describe('Dashboard widgets tab first (req-249)',()=>{
 it('moves the widgets tab before modules from the menu, opens it and remembers',async()=>{
  boot([w('a')],{pode_editar:true,layout_perfil:[],salvos:[]});await settle();
  const toggle=document.getElementById('dashboard-widgets-first'),opened=[];
  document.querySelectorAll('nav button').forEach(b=>b.addEventListener('click',()=>opened.push(b.id)));
  expect([order(),toggle.getAttribute('aria-checked')]).toEqual([['modulos','widgets'],'false']);
  toggle.click();
  expect([order(),toggle.getAttribute('aria-checked'),saved.dashboard_widgets_primeiro,opened]).toEqual([['widgets','modulos'],'true','1',['dashboard-tab-btn-widgets']]);
  toggle.click();
  expect([order(),saved.dashboard_widgets_primeiro,opened[1]]).toEqual([['modulos','widgets'],'0','dashboard-tab-btn-modulos']);
 });

 it('starts with the stored order and follows the profile layout for a view-only user',async()=>{
  boot([w('a')],{pode_editar:true,primeiro:true,layout_perfil:[],salvos:[]});await settle();
  expect(order()).toEqual(['widgets','modulos']);
  document.body.innerHTML=menu+tabs+panel+layouts;
  boot([],{pode_editar:false,primeiro:false,primeiro_perfil:true,layout_perfil:[w('p')],salvos:[]});await settle();
  const toggle=document.getElementById('dashboard-widgets-first');
  expect([order(),toggle.disabled]).toEqual([['widgets','modulos'],true]);
  toggle.click();
  expect([order(),saved]).toEqual([['widgets','modulos'],{}]);
 });

 it('follows the profile order while an editor views the profile default',async()=>{
  boot([w('a')],{pode_editar:true,primeiro:false,primeiro_perfil:true,layout_perfil:[w('p')],salvos:[]});await settle();
  expect(order()).toEqual(['modulos','widgets']);
  document.getElementById('dashboard-widgets-source').click();await settle();
  expect(order()).toEqual(['widgets','modulos']);
  document.getElementById('dashboard-btn-copy-profile').click();await settle();
  expect([order(),saved.dashboard_widgets_primeiro]).toEqual([['widgets','modulos'],'1']);
 });

 it('keeps the order in a named layout and sends it when publishing to profiles',async()=>{
  boot([w('a')],{pode_editar:true,primeiro:true,layout_perfil:[],salvos:[{id:'g',name:'Antigo',mode:'grid',first:false,widgets:[w('z')]}]});await settle();
  document.getElementById('dashboard-btn-layouts').click();await settle();
  document.getElementById('dashboard-widgets-layout-name').value='Novo';
  document.getElementById('dashboard-widgets-layout-save').click();
  expect(saved.dashboard_widgets_salvos.map(l=>[l.name,l.first])).toEqual([['Novo',true],['Antigo',false]]);
  document.querySelectorAll('.dashboard-layout-check').forEach(c=>{c.checked=true;});
  document.getElementById('dashboard-widgets-layout-publish').click();await settle();
  expect(calls.find(c=>c.ajaxOpcao==='widgets-layout-publicar').primeiro).toBe('1');
  document.querySelector('[data-layout-apply="g"]').click();await settle();
  expect([order(),saved.dashboard_widgets_primeiro]).toEqual([['modulos','widgets'],'0']);
 });
});
