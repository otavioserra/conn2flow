import {readFileSync} from 'node:fs';
import {beforeEach, describe, expect, it, vi} from 'vitest';

// REQ-250 — fase 1 da lousa: objetos livres, esconder por largura, imagem de fundo e Google Fonts.
const source=readFileSync('gestor/modulos/dashboard/dashboard.js','utf8');
const start=source.indexOf('\tfunction initDashboardWidgets() {');
const init=source.slice(start,source.indexOf('\n\tinitDashboardWidgets();',start));
const component=readFileSync('gestor/modulos/dashboard/resources/pt-br/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html','utf8');
const block=(from,to)=>component.slice(component.indexOf(from),component.indexOf(to));
const menu=block('<details id="dashboard-options"','<!-- density-selector < -->')+'</div></details>';
const panel=block('<div id="dashboard-tab-widgets"','<!-- tab-content-widgets > -->').replace('class="dashboard-tab-panel space-y-6 hidden"','class="dashboard-tab-panel"');
const popups=block('<div id="dashboard-image-picker"','<div id="dashboard-widgets-layouts-modal"')+block('<div id="dashboard-widget-config-modal"','<div id="dashboard-widgets-modal"');
let saved, renders;
async function settle(){for(let i=0;i<10;i++)await Promise.resolve();}
function boot(layout,access={pode_editar:true,modo:'lousa',layout_perfil:[],salvos:[]},width=1300){
 const grid=document.getElementById('dashboard-widgets-grid');
 Object.defineProperty(grid,'clientWidth',{configurable:true,get:()=>width});
 grid.getBoundingClientRect=()=>({left:0,top:0,width,height:600});
 const gestor={raiz:'/', dashboard_user_prefs:{widgets_layout:layout,widgets:access}};
 const fetch=vi.fn(async()=>{renders++;return {ok:true,json:async()=>({status:'Ok',data:{html:'<p>ok</p>',css:''}})};});
 new Function('document','gestor','fetch','getLocalStorage','setLocalStorage','dashboardSalvarPreferenciaBackend','sessionStorage','ResizeObserver',init+'\ninitDashboardWidgets();')(document,gestor,fetch,()=>null,()=>{},(key,value)=>{saved[key]=structuredClone(value);},sessionStorage,undefined);
}
const grid=()=>document.getElementById('dashboard-widgets-grid');
const cards=()=>[...document.querySelectorAll('.dashboard-widget-card')];
const card=id=>cards().find(c=>c.dataset.widgetInstance===id);
const w=(id,extra)=>Object.assign({id:'menus',name:'Menus',registro_id:'main',instance_id:id,width:4,height:1,height_px:220},extra);
const obj=(id,object,extra)=>Object.assign({id:'objeto',name:'Objeto',instance_id:id,width:4,height:1,height_px:160,object},extra);
const option=name=>document.querySelector('[data-widget-option="'+name+'"]');
const field=name=>document.querySelector('[data-object-option="'+name+'"]');
const edit=()=>document.getElementById('dashboard-edit-mode').click();
const fontsLink=()=>document.getElementById('dashboard-google-fonts');
const lastSaved=id=>saved.dashboard_widgets_layout.find(x=>x.instance_id===id);

beforeEach(()=>{
 saved={};renders=0;sessionStorage.clear();
 // O teste não vai à rede buscar a folha do Google Fonts; só confere o endereço pedido.
 if(window.happyDOM&&window.happyDOM.settings){window.happyDOM.settings.disableCSSFileLoading=true;window.happyDOM.settings.disableIframePageLoading=true;}
 const old=fontsLink();if(old)old.remove();
 document.body.innerHTML=menu+panel+popups;
 grid().setAttribute('data-label-object','Objeto');grid().setAttribute('data-label-object-empty','Vazio');grid().setAttribute('data-label-not-image','Não é imagem');
});

describe('Dashboard board objects (req-250)',()=>{
 it('draws each object type from plain attributes, without asking the server',async()=>{
  boot([
   obj('t',{type:'text',text:'<b>Olá</b>\nmundo',font:'Poppins',size:40,weight:400,align:'left',color:'#112233'}),
   obj('s',{type:'shape',shape:'circle',fill:'#ff0000'}),
   obj('i',{type:'image',src:'/files/2026/foto.webp',fit:'contain',alt:'Foto'}),
   obj('k',{type:'icon',icon:'rocket',color:'#00aa00'}),
   obj('b',{type:'button',text:'Assine',href:'/planos/',fill:'#0000ff',color:'#ffffff',newTab:true}),
  ]);await settle();
  expect(renders).toBe(0);
  expect(cards().every(c=>c.classList.contains('is-object'))).toBe(true);
  const text=card('t').querySelector('.dashboard-object-text');
  // O que o usuário escreve é texto, não HTML.
  expect([text.textContent,text.querySelector('b'),text.style.fontSize,String(text.style.fontWeight),text.style.textAlign]).toEqual(['<b>Olá</b>\nmundo',null,'40px','400','left']);
  expect(text.style.fontFamily.replace(/['"]/g,'')).toBe('Poppins, sans-serif');
  const shape=card('s').querySelector('.dashboard-object-figure');
  expect([shape.getAttribute('data-shape'),shape.style.backgroundColor]).toEqual(['circle','#ff0000']);
  const image=card('i').querySelector('img');
  expect([image.getAttribute('src'),image.alt,image.style.objectFit]).toEqual(['/files/2026/foto.webp','Foto','contain']);
  expect(card('k').querySelector('.dashboard-object [data-lucide]').getAttribute('data-lucide')).toBe('rocket');
  const action=card('b').querySelector('a.dashboard-object-action');
  expect([action.textContent,action.getAttribute('href'),action.target,action.rel]).toEqual(['Assine','/planos/','_blank','noopener']);
 });

 it('discards unsafe images, links, colors and icons',async()=>{
  boot([
   obj('i',{type:'image',src:'https://evil.example/x.png'}),
   obj('j',{type:'image',src:'/files/../../etc/passwd.png'}),
   obj('k',{type:'image',src:'/files/a.png") , url("//evil'}),
   obj('b',{type:'button',text:'x',href:'javascript:alert(1)'}),
   obj('c',{type:'button',text:'x',href:'//evil.example/'}),
   obj('d',{type:'icon',icon:'"><script>',color:'red'}),
   obj('e',{type:'unknown',text:'cai em texto',size:9999,font:'Comic Sans'}),
  ]);await settle();
  for(const id of ['i','j','k'])expect(card(id).querySelector('img')).toBeNull();
  for(const id of ['b','c'])expect(card(id).querySelector('a').hasAttribute('href')).toBe(false);
  expect(card('d').querySelector('.dashboard-object [data-lucide]').getAttribute('data-lucide')).toBe('star');
  const text=card('e').querySelector('.dashboard-object-text');
  expect([text.textContent,text.style.fontSize,text.style.fontFamily]).toEqual(['cai em texto','28px','']);
  expect(document.querySelector('script')).toBeNull();
 });

 it('adds an object from the menu, opens its settings and applies type and attributes without rendering again',async()=>{
  boot([w('a',{x:0,y:0})]);await settle();
  document.getElementById('dashboard-btn-add-object').click();await settle();
  const created=saved.dashboard_widgets_layout[1];
  expect([created.id,created.object.type,created.options.header,created.options.frame,created.registro_id]).toEqual(['objeto','text',false,false,'']);
  const modal=document.getElementById('dashboard-widget-config-modal'),section=modal.querySelector('[data-object-section]');
  expect([modal.classList.contains('hidden'),section.hidden,grid().classList.contains('is-editing')]).toEqual([false,false,true]);
  const visible=()=>[...modal.querySelectorAll('[data-object-for]')].filter(r=>!r.hidden).map(r=>(r.querySelector('[data-object-option]')||{getAttribute(){return '';}}).getAttribute('data-object-option'));
  expect(visible()).toEqual(['text','font','size','weight','align','color']);
  field('type').value='button';field('type').dispatchEvent(new Event('change'));
  expect(visible()).toEqual(['text','font','size','weight','color','fill','href','newTab']);
  field('text').value='Comprar';field('href').value='https://conn2flow.com/planos';field('fill').value='#0284c7';field('color').value='#ffffff';field('font').value='Oswald';field('size').value='22';field('newTab').checked=true;
  const before=renders;
  modal.querySelector('.dashboard-widget-config-save').click();
  const object=lastSaved(created.instance_id).object;
  expect([object.type,object.text,object.href,object.fill,object.font,object.size,object.newTab]).toEqual(['button','Comprar','https://conn2flow.com/planos','#0284c7','Oswald',22,true]);
  const action=card(created.instance_id).querySelector('a.dashboard-object-action');
  expect([action.textContent,action.getAttribute('href')]).toEqual(['Comprar','https://conn2flow.com/planos']);
  expect(renders).toBe(before);
  expect(fontsLink().getAttribute('href')).toBe('https://fonts.googleapis.com/css2?family=Oswald:wght@400;700&display=swap');
 });

 it('hides the object section for a regular widget and keeps objects when duplicating',async()=>{
  boot([w('a',{x:0,y:0}),obj('o',{type:'shape',shape:'line',fill:'#123456'},{x:4,y:0})]);await settle();edit();
  card('a').querySelector('.dashboard-widget-config-btn').click();
  expect(document.querySelector('[data-object-section]').hidden).toBe(true);
  document.querySelector('.dashboard-widget-config-close').click();
  card('o').querySelector('.dashboard-widget-duplicate-btn').click();await settle();
  const copies=saved.dashboard_widgets_layout.filter(x=>x.id==='objeto');
  expect(copies.map(x=>[x.object.type,x.object.shape,x.object.fill])).toEqual([['shape','line','#123456'],['shape','line','#123456']]);
  expect(copies[0].instance_id).not.toBe(copies[1].instance_id);
 });
});

describe('Dashboard widget appearance (req-250)',()=>{
 it('applies background image with opacity and fit, and the title font, to a regular widget',async()=>{
  boot([w('a',{options:{bgImage:'/files/fundo.jpg',bgOpacity:35,bgFit:'repeat',titleFont:'Playfair Display'}}),w('b',{options:{bgImage:'https://evil.example/x.jpg',bgOpacity:'abc',bgFit:'explode',titleFont:'Papyrus'}})]);await settle();
  const a=card('a'),b=card('b');
  expect([a.classList.contains('has-bg-image'),a.style.getPropertyValue('--widget-bg-image'),a.style.getPropertyValue('--widget-bg-opacity'),a.style.getPropertyValue('--widget-bg-size'),a.style.getPropertyValue('--widget-bg-repeat')]).toEqual([true,'url("/files/fundo.jpg")','0.35','auto','repeat']);
  expect(a.querySelector('.dashboard-widget-title').style.fontFamily).toBe('"Playfair Display", sans-serif');
  expect([b.classList.contains('has-bg-image'),b.style.getPropertyValue('--widget-bg-image'),b.querySelector('.dashboard-widget-title').style.fontFamily]).toEqual([false,'','']);
  expect(fontsLink().getAttribute('href')).toBe('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap');
 });

 it('saves the new options from the settings popup and drops the font sheet when no font is left',async()=>{
  boot([w('a',{options:{titleFont:'Bebas Neue'}})]);await settle();edit();
  // Bebas Neue só tem um peso: pedir 700 invalidaria a folha.
  expect(fontsLink().getAttribute('href')).toBe('https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap');
  card('a').querySelector('.dashboard-widget-config-btn').click();
  expect([option('titleFont').value,option('hide').value,option('bgOpacity').value,option('bgFit').value]).toEqual(['Bebas Neue','','100','cover']);
  option('titleFont').value='';option('hide').value='md';option('bgImage').value='/files/a.png';option('bgOpacity').value='60';option('bgFit').value='contain';
  document.querySelector('.dashboard-widget-config-save').click();
  expect(lastSaved('a').options).toMatchObject({titleFont:'',hide:'md',bgImage:'/files/a.png',bgOpacity:60,bgFit:'contain'});
  expect(fontsLink()).toBeNull();
  expect(card('a').getAttribute('data-widget-hide')).toBe('md');
 });

 it('hides an item below its width threshold outside edit mode and frees its place on the board',async()=>{
  const layout=[w('a',{x:0,y:0,options:{hide:'lg'}}),w('b',{x:0,y:12}),w('c',{x:4,y:12,options:{hide:'sm'}})];
  const place=c=>c.style.gridRow.replace(/\s+/g,' ');
  window.innerWidth=1100;
  boot(layout);await settle();
  // 1100 px: `lg` (abaixo de 1280) some; `sm` (abaixo de 640) fica. `b` sobe para a primeira linha.
  expect(cards().map(c=>c.classList.contains('is-hidden-now'))).toEqual([true,false,false]);
  expect(place(card('b'))).toBe('1 / span 12');
  edit();
  expect(cards().map(c=>c.classList.contains('is-hidden-now'))).toEqual([false,false,false]);
  expect(place(card('b'))).toBe('13 / span 12');
  edit();
  expect(card('a').classList.contains('is-hidden-now')).toBe(true);
  expect(saved.dashboard_widgets_layout).toBeUndefined();
  window.innerWidth=1400;
  document.body.innerHTML=menu+panel+popups;boot(layout);await settle();
  expect(cards().map(c=>c.classList.contains('is-hidden-now'))).toEqual([false,false,false]);
  window.innerWidth=1024;
 });

 it('accepts an image only from the file manager of the panel itself',async()=>{
  boot([w('a')]);await settle();edit();
  card('a').querySelector('.dashboard-widget-config-btn').click();
  const picker=document.getElementById('dashboard-image-picker');
  const by=attr=>[...document.querySelectorAll('['+attr+']')].find(b=>b.getAttribute(attr).includes('bgImage'));
  by('data-pick-image').click();
  expect([picker.classList.contains('hidden'),picker.querySelector('iframe').getAttribute('src')]).toEqual([false,'/admin-arquivos/?paginaIframe=sim']);
  const send=(file,origin=location.origin)=>window.dispatchEvent(new MessageEvent('message',{origin,data:JSON.stringify({moduloId:'admin-arquivos',data:encodeURI(JSON.stringify(file))})}));
  send({caminho:'files/2026/fundo.png',imgSrc:'/files/2026/mini/fundo.png',tipo:'image/png'},'https://evil.example');
  expect(option('bgImage').value).toBe('');
  send({caminho:'files/2026/contrato.pdf',tipo:'application/pdf'});
  expect([option('bgImage').value,picker.querySelector('[data-picker-status]').textContent]).toEqual(['','Não é imagem']);
  send({caminho:'files/2026/fundo.png',imgSrc:'/files/2026/mini/fundo.png',tipo:'image/png'});
  expect([option('bgImage').value,picker.classList.contains('hidden'),picker.querySelector('iframe').hasAttribute('src')]).toEqual(['/files/2026/fundo.png',true,false]);
  by('data-clear-image').click();
  expect(option('bgImage').value).toBe('');
 });

 it('keeps the font list of the popup equal to the list accepted by the code',()=>{
  const list=JSON.parse(init.match(/var FONTS = (\[[^\]]+\]);/)[1].replace(/'/g,'"'));
  for(const select of ['[data-widget-option="titleFont"]','[data-object-option="font"]']){
   expect([...document.querySelector(select).options].map(o=>o.value)).toEqual(['',...list]);
  }
 });
});
