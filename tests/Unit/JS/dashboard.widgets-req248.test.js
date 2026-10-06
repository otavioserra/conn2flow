import {readFileSync} from 'node:fs';
import {beforeEach, describe, expect, it, vi} from 'vitest';

// REQ-248 — dois modos na área de widgets: grade (padrão) e lousa (colunas pela largura, posição livre, arrasto),
// troca pelo menu, widgets por linha na grade, janela cheia e menu de opções que fica aberto.
const source=readFileSync('gestor/modulos/dashboard/dashboard.js','utf8');
const start=source.indexOf('\tfunction initDashboardWidgets() {');
const init=source.slice(start,source.indexOf('\n\tinitDashboardWidgets();',start));
const component=readFileSync('gestor/modulos/dashboard/resources/pt-br/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html','utf8');
const block=(from,to)=>component.slice(component.indexOf(from),component.indexOf(to));
const menu=block('<details id="dashboard-options"','<!-- density-selector < -->')+'</div></details>';
const panel=block('<div id="dashboard-tab-widgets"','<!-- tab-content-widgets > -->').replace('class="dashboard-tab-panel space-y-6 hidden"','class="dashboard-tab-panel"');
let saved, calls;
async function settle(){for(let i=0;i<10;i++)await Promise.resolve();}
// Largura da lousa em pixels: 1300 dá 12 colunas de 110 px de passo (90 de célula + 20 de distância).
function boot(layout,width=1300,access={pode_editar:true,modo:'lousa',layout_perfil:[],salvos:[]}){
 const grid=document.getElementById('dashboard-widgets-grid');
 Object.defineProperty(grid,'clientWidth',{configurable:true,get:()=>grid.boardWidth});
 grid.boardWidth=width;
 grid.getBoundingClientRect=()=>({left:0,top:0,width:grid.boardWidth,height:600});
 const gestor={raiz:'/', dashboard_user_prefs:{widgets_layout:layout,widgets:access}};
 const fetch=vi.fn(async()=>({ok:true,json:async()=>({status:'Ok',data:{html:'<p>ok</p>',css:''}})}));
 new Function('document','gestor','fetch','getLocalStorage','setLocalStorage','dashboardSalvarPreferenciaBackend','sessionStorage','ResizeObserver',init+'\ninitDashboardWidgets();')(document,gestor,fetch,()=>null,()=>{},(key,value)=>{saved[key]=structuredClone(value);},sessionStorage,undefined);
 calls=fetch.mock.calls;
}
const grid=()=>document.getElementById('dashboard-widgets-grid');
const cards=()=>[...document.querySelectorAll('.dashboard-widget-card')];
const place=c=>[c.style.gridColumn.replace(/\s+/g,' '),c.style.gridRow.replace(/\s+/g,' ')];
const places=()=>Object.fromEntries(cards().map(c=>[c.dataset.widgetInstance,place(c)]));
const w=(id,extra)=>Object.assign({id:'menus',name:'Menus',registro_id:'main',instance_id:id,width:4,height:1,height_px:220},extra);
const edit=()=>document.getElementById('dashboard-edit-mode').click();
function pointer(el,type,x,y){el.dispatchEvent(new PointerEvent(type,{bubbles:true,pointerId:7,clientX:x,clientY:y}));}
function handleOf(card){const h=card.querySelector('.dashboard-widget-drag-handle');h.setPointerCapture=()=>{};h.hasPointerCapture=()=>false;h.releasePointerCapture=()=>{};return h;}

beforeEach(()=>{
 saved={};sessionStorage.clear();document.documentElement.className='';
 document.body.innerHTML=menu+panel;
});

describe('Dashboard widgets board (req-248)',()=>{
 it('fills the free cells in order when widgets have no stored position',async()=>{
  boot([w('a'),w('b'),w('c'),w('d',{width:6,height_px:120})]);await settle();
  expect(grid().getAttribute('data-board-cols')).toBe('12');
  // 220 px de altura ocupam 11 linhas de 20 px mais uma de distância.
  expect(places()).toEqual({a:['1 / span 4','1 / span 12'],b:['5 / span 4','1 / span 12'],c:['9 / span 4','1 / span 12'],d:['1 / span 6','13 / span 7']});
  expect(saved).toEqual({});
 });

 it('keeps stored positions with their gaps and pushes down what collides',async()=>{
  boot([w('a',{x:0,y:0}),w('b',{x:8,y:5}),w('c',{x:2,y:3}),w('free')]);await settle();
  expect(places()).toEqual({a:['1 / span 4','1 / span 12'],b:['9 / span 4','6 / span 12'],c:['3 / span 4','13 / span 12'],free:['5 / span 4','1 / span 12']});
 });

 it('has more columns on a wider board and pushes down on a narrower one, without saving',async()=>{
  const layout=[w('a',{x:0,y:0}),w('b',{x:4,y:0}),w('c',{x:8,y:0})];
  boot(layout,2000);await settle();
  expect(grid().getAttribute('data-board-cols')).toBe('18');
  expect(places().c).toEqual(['9 / span 4','1 / span 12']);
  document.body.innerHTML=menu+panel;boot(layout,750);await settle();
  // 7 colunas: `c` encosta na borda (coluna 4), bate em `b` e desce.
  expect(grid().getAttribute('data-board-cols')).toBe('7');
  expect(places()).toEqual({a:['1 / span 4','1 / span 12'],b:['4 / span 4','13 / span 12'],c:['4 / span 4','25 / span 12']});
  document.body.innerHTML=menu+panel;boot(layout,390);await settle();
  expect(grid().getAttribute('data-board-cols')).toBe('1');
  expect(places()).toEqual({a:['1 / span 1','1 / span 12'],b:['1 / span 1','13 / span 12'],c:['1 / span 1','25 / span 12']});
  expect(saved).toEqual({});
 });

 it('drops a dragged widget on any cell, leaves gaps, saves positions and does not reload frames',async()=>{
  boot([w('a',{x:0,y:0}),w('b',{x:4,y:0})]);await settle();edit();
  const [a,b]=cards(),frame=a.querySelector('iframe'),handle=handleOf(a);
  a.getBoundingClientRect=()=>({left:0,top:0,width:420,height:220});
  pointer(handle,'pointerdown',10,10);
  const ghost=grid().querySelector('.dashboard-board-ghost');
  expect(ghost).not.toBeNull();
  expect(grid().classList.contains('is-interacting')).toBe(true);
  // 7 colunas para a direita (7 × 110 px) e 5 linhas para baixo (5 × 20 px).
  pointer(handle,'pointermove',780,110);
  expect([ghost.style.gridColumn.replace(/\s+/g,' '),ghost.style.gridRow.replace(/\s+/g,' ')]).toEqual(['8 / span 4','6 / span 12']);
  expect(a.style.transform).toBe('translate(770px,100px)');
  pointer(handle,'pointerup',780,110);
  expect(grid().querySelector('.dashboard-board-ghost')).toBeNull();
  expect([a.style.transform,a.classList.contains('is-dragging'),grid().classList.contains('is-interacting')]).toEqual(['',false,false]);
  // `a` foi solto sobre `b`, que desce; como nada ficou acima, o conjunto sobe até a primeira linha.
  expect(places()).toEqual({a:['8 / span 4','1 / span 12'],b:['5 / span 4','13 / span 12']});
  expect(saved.dashboard_widgets_layout.map(x=>[x.instance_id,x.x,x.y])).toEqual([['a',7,0],['b',4,12]]);
  expect(a.querySelector('iframe')).toBe(frame);
  expect(b.isConnected).toBe(true);
 });

 it('ignores the drag handle outside edit mode and a click without movement',async()=>{
  boot([w('a',{x:0,y:0})]);await settle();
  const handle=handleOf(cards()[0]);
  pointer(handle,'pointerdown',10,10);pointer(handle,'pointermove',500,200);pointer(handle,'pointerup',500,200);
  expect(grid().querySelector('.dashboard-board-ghost')).toBeNull();
  edit();pointer(handle,'pointerdown',10,10);pointer(handle,'pointerup',10,10);
  expect(places().a).toEqual(['1 / span 4','1 / span 12']);
  expect(saved.dashboard_widgets_layout).toBeUndefined();
 });

 it('resizes in whole cells up to the board width',async()=>{
  boot([w('a',{x:0,y:0}),w('b',{x:4,y:0})]);await settle();edit();
  const a=cards()[0],handle=a.querySelector('.dashboard-widget-resize-handle');
  handle.setPointerCapture=()=>{};handle.hasPointerCapture=()=>false;handle.releasePointerCapture=()=>{};
  a.getBoundingClientRect=()=>({left:0,top:0,width:420,height:220});
  pointer(handle,'pointerdown',420,220);pointer(handle,'pointermove',640,220);
  expect(a.getAttribute('data-widget-cols')).toBe('6');
  pointer(handle,'pointerup',640,220);
  // `a` cresceu sobre `b`, que desce.
  expect(places()).toEqual({a:['1 / span 6','1 / span 12'],b:['5 / span 4','13 / span 12']});
  expect(saved.dashboard_widgets_layout.map(x=>[x.width,x.x,x.y])).toEqual([[6,0,0],[4,4,12]]);
  pointer(handle,'pointerdown',640,220);pointer(handle,'pointermove',9000,220);pointer(handle,'pointerup',9000,220);
  expect(saved.dashboard_widgets_layout[0].width).toBe(12);
  pointer(handle,'pointerdown',1300,220);pointer(handle,'pointermove',-9000,220);pointer(handle,'pointerup',-9000,220);
  expect(saved.dashboard_widgets_layout[0].width).toBe(2);
 });

 it('places a duplicate in the first free cell and stores every position',async()=>{
  boot([w('a',{x:0,y:0}),w('b',{x:8,y:0})]);await settle();edit();
  cards()[0].querySelector('.dashboard-widget-duplicate-btn').click();await settle();
  const list=saved.dashboard_widgets_layout;
  expect(list.map(x=>[x.x,x.y])).toEqual([[0,0],[4,0],[8,0]]);
  expect(list[1].instance_id).not.toBe('a');
 });

 it('keeps gaps between widgets but never an empty band above all of them',async()=>{
  boot([w('a',{x:0,y:30}),w('b',{x:6,y:60})]);await settle();
  expect(places()).toEqual({a:['1 / span 4','1 / span 12'],b:['7 / span 4','31 / span 12']});
  expect(saved).toEqual({});
 });

 it('discards stored positions outside the board limits',async()=>{
  boot([w('a',{x:-3,y:0}),w('b',{x:2.5,y:1}),w('c',{x:'7',y:'9'}),w('d',{x:99,y:0})]);await settle();
  expect(places().c).toEqual(['8 / span 4','10 / span 12']);
  expect(places().a).toEqual(['1 / span 4','1 / span 12']);
  // `b` não cabe ao lado de `a`: `c` ocupa a coluna 8 a partir da linha 10.
  expect(places().b).toEqual(['1 / span 4','13 / span 12']);
  expect(places().d[1]).toBe('22 / span 12');
 });

 it('turns the widgets panel into a full window and leaves it with Escape',async()=>{
  boot([w('a')]);await settle();
  const button=document.getElementById('dashboard-btn-widgets-window'),panelEl=document.getElementById('dashboard-tab-widgets');
  expect(button.getAttribute('role')).toBe('switch');
  button.click();
  expect([panelEl.classList.contains('is-window'),document.documentElement.classList.contains('dashboard-window-open'),button.getAttribute('aria-checked')]).toEqual([true,true,'true']);
  document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape'}));
  expect([panelEl.classList.contains('is-window'),document.documentElement.classList.contains('dashboard-window-open'),button.getAttribute('aria-checked')]).toEqual([false,false,'false']);
  button.click();button.click();
  expect(panelEl.classList.contains('is-window')).toBe(false);
 });

 it('keeps the options menu open on outside click and on Escape',async()=>{
  boot([w('a')]);await settle();
  const options=document.getElementById('dashboard-options');
  options.open=true;
  document.body.click();grid().click();
  expect(options.open).toBe(true);
  document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape'}));
  expect(options.open).toBe(true);
 });

 it('gives a view-only user the board of the profile, with positions and no drag',async()=>{
  boot([],1300,{pode_editar:false,modo_perfil:'lousa',layout_perfil:[w('p1',{x:6,y:2,width:5})],salvos:[]});await settle();
  expect(places()).toEqual({p1:['7 / span 5','1 / span 12']});
  edit();const handle=handleOf(cards()[0]);
  pointer(handle,'pointerdown',10,10);pointer(handle,'pointermove',500,200);pointer(handle,'pointerup',500,200);
  expect(places()).toEqual({p1:['7 / span 5','1 / span 12']});
  expect(saved).toEqual({});
 });
 it('starts in grid mode, where the style sheet places the cards and nothing is positioned by cell',async()=>{
  boot([w('a',{x:7,y:5}),w('b',{width:20})],1300,{pode_editar:true,modo:'grade',layout_perfil:[],salvos:[]});await settle();
  expect(grid().classList.contains('is-board')).toBe(false);
  expect(grid().hasAttribute('data-board-cols')).toBe(false);
  expect(cards().map(c=>[c.style.gridColumn,c.style.gridRow,c.getAttribute('data-widget-cols')])).toEqual([['','','4'],['','','12']]);
  edit();const handle=handleOf(cards()[0]);
  pointer(handle,'pointerdown',10,10);pointer(handle,'pointermove',500,200);pointer(handle,'pointerup',500,200);
  expect(grid().querySelector('.dashboard-board-ghost')).toBeNull();
  expect(saved.dashboard_widgets_layout).toBeUndefined();
 });

 it('sets how many widgets fit per row in grid mode, and not in board mode',async()=>{
  boot([w('a',{width:6}),w('b',{width:12}),w('c',{width:2})],1300,{pode_editar:true,modo:'grade',layout_perfil:[],salvos:[]});await settle();
  const pick=n=>document.querySelector('[data-widgets-per-row="'+n+'"]');
  expect([...document.querySelectorAll('[data-widgets-per-row]')].map(b=>b.textContent)).toEqual(['1','2','3','4','6']);
  pick(3).click();
  expect(cards().map(c=>c.getAttribute('data-widget-cols'))).toEqual(['4','4','4']);
  expect(saved.dashboard_widgets_layout.map(x=>x.width)).toEqual([4,4,4]);
  pick(6).click();
  expect(saved.dashboard_widgets_layout.map(x=>x.width)).toEqual([2,2,2]);
  pick(1).click();
  expect(saved.dashboard_widgets_layout.map(x=>x.width)).toEqual([12,12,12]);
  document.getElementById('dashboard-widgets-mode').click();await settle();
  expect(pick(2).disabled).toBe(true);
  pick(2).click();
  expect(saved.dashboard_widgets_layout.map(x=>x.width)).toEqual([12,12,12]);
 });

 it('switches between grid and board from the menu and remembers the choice',async()=>{
  boot([w('a'),w('b'),w('c')],1300,{pode_editar:true,modo:'grade',layout_perfil:[],salvos:[]});await settle();
  const toggle=document.getElementById('dashboard-widgets-mode');
  expect(toggle.getAttribute('aria-checked')).toBe('false');
  toggle.click();await settle();
  expect([toggle.getAttribute('aria-checked'),grid().classList.contains('is-board'),saved.dashboard_widgets_modo]).toEqual(['true',true,'lousa']);
  expect(saved.dashboard_widgets_layout.map(x=>[x.instance_id,x.x,x.y])).toEqual([['a',0,0],['b',4,0],['c',8,0]]);
  // Na lousa, `a` vai para baixo de tudo; de volta à grade, a ordem é a de leitura da tela.
  edit();const a=cards().find(c=>c.dataset.widgetInstance==='a'),handle=handleOf(a);
  a.getBoundingClientRect=()=>({left:0,top:0,width:420,height:220});
  pointer(handle,'pointerdown',10,10);pointer(handle,'pointermove',10,410);pointer(handle,'pointerup',10,410);
  expect(saved.dashboard_widgets_layout.find(x=>x.instance_id==='a').y).toBe(20);
  toggle.click();await settle();
  expect([toggle.getAttribute('aria-checked'),grid().classList.contains('is-board'),saved.dashboard_widgets_modo]).toEqual(['false',false,'grade']);
  expect(cards().map(c=>c.dataset.widgetInstance)).toEqual(['b','c','a']);
  expect(saved.dashboard_widgets_layout.map(x=>x.instance_id)).toEqual(['b','c','a']);
  expect(cards().every(c=>c.style.gridColumn==='')).toBe(true);
 });

 it('keeps the proportion of each widget when the width unit changes with the mode',async()=>{
  // Lousa de 2000 px: 18 células. Metade da grade (6 de 12) vira 9 de 18; na volta, 9 de 18 vira 6 de 12.
  boot([w('a',{width:6}),w('b',{width:12}),w('c',{width:2})],2000,{pode_editar:true,modo:'grade',layout_perfil:[],salvos:[]});await settle();
  const toggle=document.getElementById('dashboard-widgets-mode');
  toggle.click();await settle();
  expect(saved.dashboard_widgets_layout.map(x=>x.width)).toEqual([9,18,3]);
  // Na lousa `c` coube ao lado de `a` e `b` desceu: essa passa a ser a ordem de leitura na grade.
  toggle.click();await settle();
  expect(saved.dashboard_widgets_layout.map(x=>[x.instance_id,x.width])).toEqual([['a',6],['c',2],['b',12]]);
 });

 it('does not let a view-only user change the mode and follows the mode of the published layout',async()=>{
  boot([],1300,{pode_editar:false,modo:'lousa',modo_perfil:'grade',layout_perfil:[w('p1',{x:6,y:2})],salvos:[]});await settle();
  const toggle=document.getElementById('dashboard-widgets-mode');
  expect(grid().classList.contains('is-board')).toBe(false);
  toggle.click();
  expect([grid().classList.contains('is-board'),toggle.disabled]).toEqual([false,true]);
  expect(saved).toEqual({});
 });

 it('saves the mode with a named layout and sends it when publishing to profiles',async()=>{
  document.body.innerHTML=menu+panel+block('<div id="dashboard-widgets-layouts-modal"','<div id="dashboard-widget-config-modal"');
  boot([w('a',{x:3,y:1})],1300,{pode_editar:true,modo:'lousa',layout_perfil:[],salvos:[{id:'g',name:'Grade',mode:'grid',widgets:[w('z')]}]});await settle();
  document.getElementById('dashboard-btn-layouts').click();await settle();
  document.getElementById('dashboard-widgets-layout-name').value='Lousa';
  document.getElementById('dashboard-widgets-layout-save').click();
  expect(saved.dashboard_widgets_salvos.map(l=>[l.name,l.mode])).toEqual([['Lousa','board'],['Grade','grid']]);
  document.querySelectorAll('.dashboard-layout-check').forEach(c=>{c.checked=true;});
  document.getElementById('dashboard-widgets-layout-publish').click();await settle();
  const sent=calls.map(c=>Object.fromEntries(new URLSearchParams(c[1].body))).find(c=>c.ajaxOpcao==='widgets-layout-publicar');
  expect(sent.modo).toBe('lousa');
  expect(JSON.parse(sent.layout).map(x=>[x.x,x.y])).toEqual([[3,1]]);
  document.querySelector('[data-layout-apply="g"]').click();await settle();
  expect([saved.dashboard_widgets_modo,grid().classList.contains('is-board')]).toEqual(['grade',false]);
 });
});
