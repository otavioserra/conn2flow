import {readFileSync} from 'node:fs';
import {beforeEach, describe, expect, it, vi} from 'vitest';

// REQ-247 — área de widgets: permissão, fonte do layout, larguras, duplicar, atalho de edição, menu e layouts.
const source=readFileSync('gestor/modulos/dashboard/dashboard.js','utf8');
const start=source.indexOf('\tfunction initDashboardWidgets() {');
const init=source.slice(start,source.indexOf('\n\tinitDashboardWidgets();',start));
const component=readFileSync('gestor/modulos/dashboard/resources/pt-br/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html','utf8');
const block=(from,to)=>component.slice(component.indexOf(from),component.indexOf(to));
const menu=block('<details id="dashboard-options"','<!-- density-selector < -->')+'</div></details>';
const panel=block('<div id="dashboard-tab-widgets"','<!-- tab-content-widgets > -->').replace('class="dashboard-tab-panel space-y-6 hidden"','class="dashboard-tab-panel"');
const layouts=block('<div id="dashboard-widgets-layouts-modal"','<div id="dashboard-widget-config-modal"');
let saved, calls, profiles;
async function settle(){for(let i=0;i<10;i++)await Promise.resolve();}
function boot(layout,widgets){
 const gestor={raiz:'/', dashboard_user_prefs:{widgets_layout:layout,widgets}};
 const fetch=vi.fn(async (_url,options)=>{
  const params=Object.fromEntries(new URLSearchParams(options.body));calls.push(params);
  const action=params.ajaxOpcao;
  const data=action==='widgets-layouts'?profiles:action==='widget-render'?{html:'<p>ok</p>',css:'',edit_url:params.widget_id==='menus'?'/menus/editar/?id='+params.registro_id:(params.widget_id==='fora'?'https://outro.example/x':'')}:{};
  return {ok:true,json:async()=>({status:'Ok',data})};
 });
 new Function('document','gestor','fetch','getLocalStorage','setLocalStorage','dashboardSalvarPreferenciaBackend','sessionStorage',init+'\ninitDashboardWidgets();')(document,gestor,fetch,()=>null,()=>{},(key,value)=>{saved[key]=structuredClone(value);},sessionStorage);
}
const cards=()=>[...document.querySelectorAll('.dashboard-widget-card')];
const grid=()=>document.getElementById('dashboard-widgets-grid');
const w=(extra)=>Object.assign({id:'menus',name:'Menus / Main',registro_id:'main',instance_id:'a',width:4,height:1},extra);
const edit=()=>document.getElementById('dashboard-edit-mode').click();
const status=()=>document.getElementById('dashboard-widgets-layouts-status').textContent;

beforeEach(()=>{
 saved={};calls=[];sessionStorage.clear();
 profiles={perfis:[{id:'administradores',nome:'Administradores',publicado:false,total:0},{id:'consumidores',nome:'Consumidores',publicado:true,total:2}],todos:{publicado:false,total:0},perfil_atual:'administradores'};
 document.body.innerHTML=menu+panel+layouts;
});

describe('Dashboard widgets area (req-247)',()=>{
 it('accepts every width from 2 to 12 columns and keeps the old textual sizes',async()=>{
  boot([w({width:2}),w({instance_id:'b',width:7}),w({instance_id:'c',width:'col-span-8'}),w({instance_id:'d',width:1}),w({instance_id:'e',width:30})]);await settle();
  expect(cards().map(c=>c.getAttribute('data-widget-cols'))).toEqual(['2','7','8','4','4']);
  expect(init).toContain('resize.cols=Math.max(MIN_COLS,Math.min(MAX_COLS,Math.round(fraction)));');
 });

 it('duplicates a widget right after the original with its size and options',async()=>{
  boot([w({width:5,height_px:300,options:{header:false,background:'#112233'}}),w({instance_id:'b',id:'galleries'})]);await settle();
  cards()[0].querySelector('.dashboard-widget-duplicate-btn').click();
  expect(cards()).toHaveLength(2);
  edit();cards()[0].querySelector('.dashboard-widget-duplicate-btn').click();await settle();
  const list=saved.dashboard_widgets_layout;
  expect(list.map(x=>x.id)).toEqual(['menus','menus','galleries']);
  expect(list[1].instance_id).not.toBe('a');
  expect([list[1].width,list[1].height_px,list[1].options.header,list[1].options.background]).toEqual([5,300,false,'#112233']);
  list[1].options.header=true;
  expect(list[0].options.header).toBe(false);
 });

 it('offers the record edit link only for an address of the panel itself',async()=>{
  boot([w(),w({instance_id:'b',id:'galleries'}),w({instance_id:'c',id:'fora'})]);await settle();
  const links=cards().map(c=>c.querySelector('.dashboard-widget-edit-btn'));
  expect(links[0].hidden).toBe(false);
  expect(new URL(links[0].href).pathname+new URL(links[0].href).search).toBe('/menus/editar/?id=main');
  expect([links[0].target,links[0].rel]).toEqual(['_blank','noopener']);
  expect(links[1].hidden).toBe(true);
  expect(links[2].hidden).toBe(true);
 });

 it('shows the profile layout without any control to a view-only user',async()=>{
  boot([w({instance_id:'own'})],{pode_editar:false,fonte:'proprio',layout_perfil:[w({instance_id:'p1',id:'galleries',width:9})],salvos:[]});await settle();
  expect(cards().map(c=>c.dataset.widgetInstance)).toEqual(['p1']);
  expect(cards()[0].getAttribute('data-widget-cols')).toBe('9');
  edit();
  expect(grid().classList.contains('is-editing')).toBe(false);
  expect(document.getElementById('dashboard-edit-mode').disabled).toBe(true);
  cards()[0].querySelector('.dashboard-widget-remove-btn').click();
  cards()[0].querySelector('.dashboard-widget-duplicate-btn').click();
  document.getElementById('dashboard-btn-toggle-headers').click();
  document.getElementById('dashboard-btn-reset-widgets').click();
  document.getElementById('dashboard-widgets-source').click();
  document.getElementById('dashboard-btn-layouts').click();
  expect(cards()).toHaveLength(1);
  expect(saved).toEqual({});
  expect(document.getElementById('dashboard-widgets-layouts-modal').classList.contains('hidden')).toBe(true);
  expect(calls.every(c=>c.ajaxOpcao==='widget-render')).toBe(true);
 });

 it('tells a view-only user when the profile has no layout',async()=>{
  grid().setAttribute('data-label-empty-profile','Nada para o seu perfil');
  boot([],{pode_editar:false,layout_perfil:[],salvos:[]});await settle();
  expect(document.getElementById('dashboard-widgets-empty').classList.contains('hidden')).toBe(false);
  expect(document.querySelector('[data-empty-text]').textContent).toBe('Nada para o seu perfil');
 });

 it('lets an editor switch between own layout and profile default, and copy the default',async()=>{
  boot([w({instance_id:'own'})],{pode_editar:true,fonte:'proprio',layout_perfil:[w({instance_id:'p1',id:'galleries'}),w({instance_id:'p2',id:'forms-search'})],salvos:[]});await settle();
  const notice=document.getElementById('dashboard-widgets-source-notice'),toggle=document.getElementById('dashboard-widgets-source');
  expect(cards().map(c=>c.dataset.widgetInstance)).toEqual(['own']);
  expect(notice.classList.contains('hidden')).toBe(true);
  edit();toggle.click();await settle();
  expect(cards().map(c=>c.dataset.widgetInstance)).toEqual(['p1','p2']);
  expect([toggle.getAttribute('aria-checked'),saved.dashboard_widgets_fonte,notice.classList.contains('hidden')]).toEqual(['true','perfil',false]);
  // No padrão do perfil não se edita, e o próprio layout não é tocado.
  expect(grid().classList.contains('is-editing')).toBe(false);
  expect(document.getElementById('dashboard-btn-add-widget').disabled).toBe(true);
  cards()[0].querySelector('.dashboard-widget-remove-btn').click();
  expect(saved.dashboard_widgets_layout).toBeUndefined();
  toggle.click();await settle();
  expect(cards().map(c=>c.dataset.widgetInstance)).toEqual(['own']);
  toggle.click();await settle();
  document.getElementById('dashboard-btn-copy-profile').click();await settle();
  expect(saved.dashboard_widgets_fonte).toBe('proprio');
  expect(saved.dashboard_widgets_layout.map(x=>x.id)).toEqual(['galleries','forms-search']);
  expect(saved.dashboard_widgets_layout.some(x=>['p1','p2'].includes(x.instance_id))).toBe(false);
  expect(notice.classList.contains('hidden')).toBe(true);
 });

 it('hides every header at once and shows them again',async()=>{
  boot([w({options:{header:false}}),w({instance_id:'b'})]);await settle();
  const button=document.getElementById('dashboard-btn-toggle-headers');
  button.click();
  expect(cards().map(c=>c.classList.contains('is-headerless'))).toEqual([true,true]);
  expect(saved.dashboard_widgets_layout.map(x=>x.options.header)).toEqual([false,false]);
  button.click();
  expect(cards().map(c=>c.classList.contains('is-headerless'))).toEqual([false,false]);
 });

 it('asks the widgets panel for full screen',async()=>{
  boot([w()]);await settle();
  const panelEl=document.getElementById('dashboard-tab-widgets');
  panelEl.requestFullscreen=vi.fn(async()=>{});
  document.getElementById('dashboard-btn-widgets-fullscreen').click();
  expect(panelEl.requestFullscreen).toHaveBeenCalledTimes(1);
 });

 it('saves, applies and deletes named layouts',async()=>{
  boot([w({width:3}),w({instance_id:'b',id:'galleries'})],{pode_editar:true,fonte:'proprio',layout_perfil:[],salvos:[{id:'old',name:'Antigo',widgets:[w({instance_id:'z',id:'forms-search',width:10})]},{id:'broken'}]});await settle();
  document.getElementById('dashboard-btn-layouts').click();await settle();
  const modal=document.getElementById('dashboard-widgets-layouts-modal');
  expect(modal.classList.contains('hidden')).toBe(false);
  expect([...document.querySelectorAll('#dashboard-widgets-saved-list .dashboard-layout-name')].map(e=>e.textContent)).toEqual(['Antigo']);
  document.getElementById('dashboard-widgets-layout-save').click();
  expect(status()).toBe('@[[widgets-layouts-name-required]]@');
  expect(saved.dashboard_widgets_salvos).toBeUndefined();
  document.getElementById('dashboard-widgets-layout-name').value=' Operação ';
  document.getElementById('dashboard-widgets-layout-save').click();
  expect(saved.dashboard_widgets_salvos.map(l=>[l.name,l.widgets.length])).toEqual([['Operação',2],['Antigo',1]]);
  document.querySelector('[data-layout-apply="old"]').click();await settle();
  expect(saved.dashboard_widgets_layout.map(x=>[x.id,x.width])).toEqual([['forms-search',10]]);
  expect(cards()).toHaveLength(1);
  document.querySelector('[data-layout-delete="old"]').click();
  expect(saved.dashboard_widgets_salvos.map(l=>l.name)).toEqual(['Operação']);
  document.querySelector('.dashboard-widgets-layouts-close').click();
  expect(modal.classList.contains('hidden')).toBe(true);
 });

 it('publishes the layout on screen to the checked profiles and removes a published one',async()=>{
  boot([w({width:6})],{pode_editar:true,fonte:'proprio',layout_perfil:[],salvos:[]});await settle();
  document.getElementById('dashboard-btn-layouts').click();await settle();
  const rows=[...document.querySelectorAll('#dashboard-widgets-profile-list .dashboard-layout-row')];
  expect(rows.map(r=>r.querySelector('.dashboard-layout-check').value)).toEqual(['*','administradores','consumidores']);
  expect(rows.map(r=>!!r.querySelector('[data-layout-unpublish]'))).toEqual([false,false,true]);
  document.getElementById('dashboard-widgets-layout-publish').click();
  expect(status()).toBe('@[[widgets-layouts-sem-perfil]]@');
  rows[0].querySelector('input').checked=true;rows[2].querySelector('input').checked=true;
  document.getElementById('dashboard-widgets-layout-publish').click();await settle();
  const sent=calls.find(c=>c.ajaxOpcao==='widgets-layout-publicar');
  expect(JSON.parse(sent.perfis)).toEqual(['*','consumidores']);
  expect(JSON.parse(sent.layout).map(x=>[x.id,x.width])).toEqual([['menus',6]]);
  // `*` sem layout próprio do meu perfil: o padrão que vale para mim passa a ser o publicado.
  document.getElementById('dashboard-widgets-source').click();await settle();
  expect(cards().map(c=>c.getAttribute('data-widget-cols'))).toEqual(['6']);
  document.querySelector('[data-layout-unpublish="consumidores"]').click();await settle();
  expect(calls.find(c=>c.ajaxOpcao==='widgets-layout-remover').perfil).toBe('consumidores');
 });
});
