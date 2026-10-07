import {readFileSync} from 'node:fs';
import {beforeEach, describe, expect, it, vi} from 'vitest';

// REQ-258 — modelos de lousa prontos: lista no pop-up de layouts e criação de uma lousa do sistema por um modelo.
const source=readFileSync('gestor/modulos/dashboard/dashboard.js','utf8');
const start=source.indexOf('\tfunction initDashboardWidgets() {');
const init=source.slice(start,source.indexOf('\n\tinitDashboardWidgets();',start));
const component=readFileSync('gestor/modulos/dashboard/resources/pt-br/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html','utf8');
const block=(from,to)=>component.slice(component.indexOf(from),component.indexOf(to));
const menu=block('<details id="dashboard-options"','<!-- density-selector < -->')+'</div></details>';
const panel=block('<div id="dashboard-tab-widgets"','<!-- tab-content-widgets > -->').replace('class="dashboard-tab-panel space-y-6 hidden"','class="dashboard-tab-panel"');
const layouts=block('<div id="dashboard-widgets-layouts-modal"','<div id="dashboard-widget-config-modal"');
let calls, models, skipped, fail;
async function settle(){for(let i=0;i<14;i++)await Promise.resolve();}
function boot(access={pode_editar:true,modo:'grade',layout_perfil:[],salvos:[]}){
 const gestor={raiz:'/', dashboard_user_prefs:{widgets_layout:[],widgets:access}};
 const fetch=vi.fn(async (_url,options)=>{
  const p=Object.fromEntries(new URLSearchParams(options.body));calls.push(p);
  const a=p.ajaxOpcao;let data={};
  if(fail===a)return {ok:true,json:async()=>({status:'error',message:'x'})};
  if(a==='lousas-listar')data={lousas:[]};
  else if(a==='lousa-modelos')data={modelos:models};
  else if(a==='widgets-layouts')data={perfis:[],todos:{publicado:false,total:0},perfil_atual:'administradores'};
  else if(a==='lousa-de-modelo')data={id:'campanha',nome:p.nome||'Lousa - Marketing',modo:'grade',versao:1,total:3,fora:skipped};
  return {ok:true,json:async()=>({status:'Ok',data})};
 });
 new Function('document','gestor','fetch','getLocalStorage','setLocalStorage','dashboardSalvarPreferenciaBackend','sessionStorage','ResizeObserver',init+'\ninitDashboardWidgets();')(document,gestor,fetch,()=>null,()=>{},()=>{},sessionStorage,undefined);
}
const sent=action=>calls.filter(c=>c.ajaxOpcao===action);
const status=()=>document.getElementById('dashboard-widgets-layouts-status').textContent;
const select=()=>document.getElementById('dashboard-board-model');
async function open(){document.getElementById('dashboard-btn-layouts').click();await settle();}

beforeEach(()=>{
 calls=[];fail=null;skipped=0;sessionStorage.clear();
 models=[{id:'dashboard-boards-boas-vindas',nome:'Lousa - Boas-vindas',modo:'grade',total:5},{id:'dashboard-boards-marketing',nome:'Lousa - Marketing',modo:'grade',total:5}];
 document.body.innerHTML=menu+panel+layouts;
});

describe('Dashboard board templates (req-258)',()=>{
 it('lists the board templates next to the system boards, with the item count',async()=>{
  boot();await settle();await open();
  expect(sent('lousa-modelos')).toHaveLength(1);
  expect([...select().options].map(o=>[o.value,o.textContent])).toEqual([
   ['','@[[widgets-boards-model]]@'],
   ['dashboard-boards-boas-vindas','Lousa - Boas-vindas (5 @[[widgets-boards-items]]@)'],
   ['dashboard-boards-marketing','Lousa - Marketing (5 @[[widgets-boards-items]]@)'],
  ]);
  // Reabrir não duplica as opções.
  document.getElementById('dashboard-btn-layouts').click();await settle();
  expect(select().options.length).toBe(3);
 });

 it('asks for a template before creating',async()=>{
  boot();await settle();await open();
  document.getElementById('dashboard-board-from-model').click();await settle();
  expect([status(),sent('lousa-de-modelo')]).toEqual(['@[[widgets-boards-model-required]]@',[]]);
 });

 it('creates a system board from the chosen template, with the typed name, and reloads the list',async()=>{
  boot();await settle();await open();
  select().value='dashboard-boards-marketing';
  document.getElementById('dashboard-board-name').value='  Campanha  ';
  document.getElementById('dashboard-board-from-model').click();await settle();
  const call=sent('lousa-de-modelo')[0];
  expect([call.modelo,call.nome]).toEqual(['dashboard-boards-marketing','Campanha']);
  expect([document.getElementById('dashboard-board-name').value,select().value,status(),sent('lousas-listar').length]).toEqual(['','','@[[widgets-layouts-done]]@',2]);
 });

 it('says how many widgets were left out for lack of a record',async()=>{
  skipped=2;boot();await settle();await open();
  select().value='dashboard-boards-marketing';
  document.getElementById('dashboard-board-from-model').click();await settle();
  expect(status()).toBe('@[[widgets-boards-model-skipped]]@'.replace('#n#','2'));
  expect(sent('lousa-de-modelo')[0].nome).toBe('');
 });

 it('reports a failure and keeps the choice',async()=>{
  boot();await settle();await open();
  fail='lousa-de-modelo';
  select().value='dashboard-boards-boas-vindas';
  document.getElementById('dashboard-board-from-model').click();await settle();
  expect([status(),select().value]).toEqual([document.getElementById('dashboard-widgets-grid').getAttribute('data-label-error')||'','dashboard-boards-boas-vindas']);
 });

 it('does not reach the templates for a view-only user',async()=>{
  boot({pode_editar:false,layout_perfil:[],salvos:[]});await settle();
  document.getElementById('dashboard-btn-layouts').click();await settle();
  expect(sent('lousa-modelos')).toEqual([]);
 });
});
