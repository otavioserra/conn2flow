import {readFileSync} from 'node:fs';
import {beforeEach, describe, expect, it, vi} from 'vitest';

// REQ-251 — lousas nomeadas: criar, abrir, gravar por cima, duplicar, versões e excluir, pelo pop-up de layouts.
const source=readFileSync('gestor/modulos/dashboard/dashboard.js','utf8');
const start=source.indexOf('\tfunction initDashboardWidgets() {');
const init=source.slice(start,source.indexOf('\n\tinitDashboardWidgets();',start));
const component=readFileSync('gestor/modulos/dashboard/resources/pt-br/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html','utf8');
const block=(from,to)=>component.slice(component.indexOf(from),component.indexOf(to));
const menu=block('<details id="dashboard-options"','<!-- density-selector < -->')+'</div></details>';
const panel=block('<div id="dashboard-tab-widgets"','<!-- tab-content-widgets > -->').replace('class="dashboard-tab-panel space-y-6 hidden"','class="dashboard-tab-panel"');
const layouts=block('<div id="dashboard-widgets-layouts-modal"','<div id="dashboard-widget-config-modal"');
let saved, calls, boards, versions, fail;
async function settle(){for(let i=0;i<12;i++)await Promise.resolve();}
function boot(layout,access={pode_editar:true,modo:'grade',layout_perfil:[],salvos:[]}){
 const gestor={raiz:'/', dashboard_user_prefs:{widgets_layout:layout,widgets:access}};
 const fetch=vi.fn(async (_url,options)=>{
  const p=Object.fromEntries(new URLSearchParams(options.body));calls.push(p);
  const a=p.ajaxOpcao;let data={};
  if(fail===a)return {ok:true,json:async()=>({status:'error',message:'x'})};
  if(a==='lousas-listar')data={lousas:boards};
  else if(a==='widgets-layouts')data={perfis:[],todos:{publicado:false,total:0},perfil_atual:'administradores'};
  else if(a==='lousa-obter')data={id:p.id,nome:'Vendas',modo:'lousa',versao:3,total:2,widgets:[{id:'menus',name:'Menus',registro_id:'main',instance_id:'b1',width:5,height_px:300,x:2,y:4},{id:'objeto',name:'Objeto',instance_id:'b2',width:3,height_px:120,x:8,y:0,object:{type:'text',text:'Oi'}}]};
  else if(a==='lousa-versoes')data={id:p.id,atual:3,versoes:versions};
  else if(a==='widget-render')data={html:'<p>ok</p>',css:''};
  else data={id:p.id||'nova',nome:p.nome||'x',modo:p.modo||'grade',versao:1,total:1};
  return {ok:true,json:async()=>({status:'Ok',data})};
 });
 new Function('document','gestor','fetch','getLocalStorage','setLocalStorage','dashboardSalvarPreferenciaBackend','sessionStorage','ResizeObserver',init+'\ninitDashboardWidgets();')(document,gestor,fetch,()=>null,()=>{},(key,value)=>{saved[key]=structuredClone(value);},sessionStorage,undefined);
}
const w=(id,extra)=>Object.assign({id:'menus',name:'Menus',registro_id:'main',instance_id:id,width:4,height:1,height_px:220},extra);
const sent=action=>calls.filter(c=>c.ajaxOpcao===action);
const status=()=>document.getElementById('dashboard-widgets-layouts-status').textContent;
const rows=()=>[...document.querySelectorAll('#dashboard-boards-list .dashboard-board')];
async function open(){document.getElementById('dashboard-btn-layouts').click();await settle();}

beforeEach(()=>{
 saved={};calls=[];fail=null;sessionStorage.clear();
 boards=[{id:'vendas',nome:'Vendas',modo:'lousa',versao:3,total:2,atualizada:'2026-10-07 10:00:00'},{id:'equipe',nome:'Equipe',modo:'grade',versao:1,total:5,atualizada:'2026-10-06 09:00:00'}];
 versions=[{versao:2,nome:'Vendas',modo:'lousa',total:4,data:'2026-10-06 18:00:00'},{versao:1,nome:'Vendas',modo:'grade',total:1,data:'2026-10-05 12:00:00'}];
 document.body.innerHTML=menu+panel+layouts;
});

describe('Dashboard named boards (req-251)',()=>{
 it('lists the system boards with mode, items and version, and every action per board',async()=>{
  boot([w('a')]);await settle();await open();
  expect(sent('lousas-listar')).toHaveLength(1);
  expect(rows().map(r=>[r.getAttribute('data-board'),r.querySelector('.dashboard-layout-name').textContent,r.querySelector('.dashboard-layout-detail').textContent])).toEqual([
   ['vendas','Vendas','@[[widgets-boards-mode-board]]@ · 2 @[[widgets-boards-items]]@ · @[[widgets-boards-version]]@ 3'],
   ['equipe','Equipe','@[[widgets-boards-mode-grid]]@ · 5 @[[widgets-boards-items]]@ · @[[widgets-boards-version]]@ 1'],
  ]);
  expect([...rows()[0].querySelectorAll('button')].map(b=>[...b.attributes].find(a=>a.name.startsWith('data-board-')).name)).toEqual(['data-board-open','data-board-update','data-board-duplicate','data-board-versions','data-board-delete']);
 });

 it('says so when there is no board',async()=>{
  boards=[];boot([w('a')]);await settle();await open();
  expect(document.querySelector('#dashboard-boards-list .dashboard-layout-empty').textContent).toBe('@[[widgets-boards-none]]@');
 });

 it('creates a board from the layout on screen, with its mode, and asks for a name first',async()=>{
  boot([w('a',{width:6,x:1,y:2})],{pode_editar:true,modo:'lousa',layout_perfil:[],salvos:[]});await settle();await open();
  document.getElementById('dashboard-board-create').click();
  expect([status(),sent('lousa-salvar')]).toEqual(['@[[widgets-layouts-name-required]]@',[]]);
  document.getElementById('dashboard-board-name').value='  Campanha de outubro  ';
  document.getElementById('dashboard-board-create').click();await settle();
  const call=sent('lousa-salvar')[0];
  expect([call.nome,call.modo,call.id]).toEqual(['Campanha de outubro','lousa',undefined]);
  expect(JSON.parse(call.layout).map(x=>[x.id,x.width,x.x,x.y])).toEqual([['menus',6,1,2]]);
  expect([document.getElementById('dashboard-board-name').value,status(),sent('lousas-listar').length]).toEqual(['','@[[widgets-layouts-done]]@',2]);
 });

 it('opens a board into the own layout with mode, positions and objects, under new instances',async()=>{
  boot([w('a')]);await settle();await open();
  document.querySelector('[data-board-open="vendas"]').click();await settle();
  expect(sent('lousa-obter')[0].id).toBe('vendas');
  const list=saved.dashboard_widgets_layout;
  expect(list.map(x=>[x.id,x.width,x.x,x.y])).toEqual([['menus',5,2,4],['objeto',3,8,0]]);
  expect(list[1].object.text).toBe('Oi');
  expect(list.some(x=>['b1','b2','a'].includes(x.instance_id))).toBe(false);
  expect([saved.dashboard_widgets_modo,document.getElementById('dashboard-widgets-grid').classList.contains('is-board')]).toEqual(['lousa',true]);
 });

 it('overwrites, duplicates and deletes by id and reloads the list each time',async()=>{
  boot([w('a',{width:8})]);await settle();await open();
  document.querySelector('[data-board-update="equipe"]').click();await settle();
  const update=sent('lousa-salvar')[0];
  expect([update.id,update.nome,update.modo,JSON.parse(update.layout)[0].width]).toEqual(['equipe',undefined,'grade',8]);
  document.querySelector('[data-board-duplicate="vendas"]').click();await settle();
  document.querySelector('[data-board-delete="equipe"]').click();await settle();
  expect([sent('lousa-duplicar')[0].id,sent('lousa-excluir')[0].id,sent('lousas-listar').length]).toEqual(['vendas','equipe',4]);
 });

 it('shows the versions of a board on demand and restores one',async()=>{
  boot([w('a')]);await settle();await open();
  const box=rows()[0].querySelector('.dashboard-board-versions');
  expect(box.hidden).toBe(true);
  document.querySelector('[data-board-versions="vendas"]').click();await settle();
  expect(box.hidden).toBe(false);
  expect([...box.querySelectorAll('.dashboard-layout-name')].map(e=>e.textContent)).toEqual(['@[[widgets-boards-version]]@ 2','@[[widgets-boards-version]]@ 1']);
  document.querySelector('[data-board-versions="vendas"]').click();
  expect(box.hidden).toBe(true);
  document.querySelector('[data-board-versions="vendas"]').click();await settle();
  box.querySelector('[data-board-restore][data-board-version="1"]').click();await settle();
  expect([sent('lousa-restaurar')[0].id,sent('lousa-restaurar')[0].versao]).toEqual(['vendas','1']);
  versions=[];document.querySelector('[data-board-versions="equipe"]').click();await settle();
  expect(rows()[1].querySelector('.dashboard-layout-empty').textContent).toBe('@[[widgets-boards-no-versions]]@');
 });

 it('reports a failure and changes nothing',async()=>{
  boot([w('a')]);await settle();await open();
  fail='lousa-obter';
  document.querySelector('[data-board-open="vendas"]').click();await settle();
  expect(saved.dashboard_widgets_layout).toBeUndefined();
  expect(status()).toBe(document.getElementById('dashboard-widgets-grid').getAttribute('data-label-error')||'');
 });

 it('does not reach the boards for a view-only user',async()=>{
  boot([],{pode_editar:false,layout_perfil:[w('p')],salvos:[]});await settle();
  document.getElementById('dashboard-btn-layouts').click();await settle();
  expect(sent('lousas-listar')).toEqual([]);
  expect(document.getElementById('dashboard-widgets-layouts-modal').classList.contains('hidden')).toBe(true);
 });
});
