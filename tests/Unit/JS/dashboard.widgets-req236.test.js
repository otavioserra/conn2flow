import {readFileSync} from 'node:fs';
import {beforeEach, describe, expect, it, vi} from 'vitest';

const source=readFileSync(process.env.C2F_DASHBOARD_SOURCE || 'gestor/modulos/dashboard/dashboard.js','utf8');
const start=source.indexOf('\tfunction initDashboardWidgets() {');
const end=source.indexOf('\n\tinitDashboardWidgets();',start);
const init=source.slice(start,end);
let saved, calls, sortable;
async function settle(){for(let i=0;i<8;i++)await Promise.resolve();}
function boot(layout=[], failRender=false){
 const gestor={raiz:'/', dashboard_user_prefs:{widgets_layout:layout}};
 const fetch=vi.fn(async (_url,options)=>{
  const params=new URLSearchParams(options.body);calls.push(Object.fromEntries(params));
  const action=params.get('ajaxOpcao');
  const data=action==='widgets-catalogo'?[{id:'menus',name:'Menus'}]:action==='widgets-registros'?{items:[{id:'main-menu',nome:'Main'}],tem_mais:false}:{html:'<p>Rendered</p>',css:''};
  return {ok:!(failRender && action==='widget-render'),json:async()=>({status:'Ok',data})};
 });
 function Sortable(){sortable=this;this.disabled=null;this.option=(_key,value)=>{this.disabled=value;};this.destroy=()=>{};}
 new Function('document','gestor','fetch','getLocalStorage','setLocalStorage','dashboardSalvarPreferenciaBackend','Sortable','sessionStorage',init+'\ninitDashboardWidgets();')(document,gestor,fetch,()=>null,()=>{},(_key,value)=>{saved=structuredClone(value);},Sortable,sessionStorage);
}
beforeEach(()=>{
 saved=null;calls=[];sessionStorage.clear();
 document.body.innerHTML='<details id="dashboard-options"></details><button id="dashboard-edit-mode"></button><button id="dashboard-btn-add-widget"></button><button id="dashboard-btn-reset-widgets"></button><div id="dashboard-widgets-empty"></div><div id="dashboard-widgets-grid" data-label-type="Type" data-label-record="Record" data-label-loading="Loading" data-label-error="Error"></div><div id="dashboard-widgets-modal" class="hidden"><button class="dashboard-widgets-modal-close"></button><div id="dashboard-widgets-modal-list"></div></div>';
});
describe('Dashboard widget instances (req-236)',()=>{
 it('starts in view mode and enables sorting only after edit toggle',()=>{
  boot([{id:'menus',registro_id:'main-menu'}]);
  expect(sortable.disabled).toBe(true);
  expect(document.getElementById('dashboard-widgets-grid').classList.contains('is-editing')).toBe(false);
  document.getElementById('dashboard-edit-mode').click();
  expect(sortable.disabled).toBe(false);
  expect(sessionStorage.getItem('dashboard_widgets_editing')).toBe('true');
 });
 it('requires type then record and persists both before rendering',async()=>{
  boot();document.getElementById('dashboard-btn-add-widget').click();await settle();
  document.querySelector('#dashboard-widgets-modal-list button').click();await settle();
  expect(saved).toBeNull();
  document.querySelectorAll('#dashboard-widgets-modal-list button')[1].click();await settle();
  expect(saved[0]).toMatchObject({id:'menus',registro_id:'main-menu',params:{grupo_slug:'main-menu'},width:4,height:1});
  expect(calls.find(c=>c.ajaxOpcao==='widget-render')).toMatchObject({widget_id:'menus',registro_id:'main-menu',ajax:'sim',opcao:'inicio'});
 });
 it('preserves instance params on reload and removes just one matching type',async()=>{
  boot([{id:'menus',registro_id:'one',instance_id:'first',params:{grupo_slug:'one'}},{id:'menus',registro_id:'two',instance_id:'second',params:{grupo_slug:'two'}}]);await settle();
  expect(calls.filter(c=>c.ajaxOpcao==='widget-render').map(c=>c.registro_id)).toEqual(['one','two']);
  document.querySelector('.dashboard-widget-remove-btn').click();expect(saved).toBeNull();
  document.getElementById('dashboard-edit-mode').click();document.querySelector('.dashboard-widget-remove-btn').click();
  expect(saved).toHaveLength(1);expect(saved[0]).toMatchObject({instance_id:'second',registro_id:'two',params:{grupo_slug:'two'}});
 });
 it('reports render failure without claiming a working widget',async()=>{
  boot([{id:'menus',registro_id:'one'}],true);await settle();
  expect(document.querySelector('.dashboard-widget-card-body').textContent).toBe('Error');
 });
 it('preserves the legacy eight-column width on reload',()=>{
  boot([{id:'menus',width:'col-span-8'}]);
  expect(document.querySelector('.dashboard-widget-card').getAttribute('data-widget-cols')).toBe('8');
 });
});
