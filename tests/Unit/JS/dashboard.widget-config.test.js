import {readFileSync} from 'node:fs';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

// Configurações por widget no Dashboard: botão no cabeçalho do card e pop-up do componente real.
const source=readFileSync('gestor/modulos/dashboard/dashboard.js','utf8');
const start=source.indexOf('\tfunction initDashboardWidgets() {');
const init=source.slice(start,source.indexOf('\n\tinitDashboardWidgets();',start));
const component=lang=>readFileSync(`gestor/modulos/dashboard/resources/${lang}/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html`,'utf8');
function popup(lang){
 const html=component(lang),from=html.indexOf('<div id="dashboard-widget-config-modal"'),to=html.indexOf('<div id="dashboard-widgets-modal"');
 return html.slice(from,to);
}
let saved, renders;
async function settle(){for(let i=0;i<8;i++)await Promise.resolve();}
function boot(layout){
 const gestor={raiz:'/', dashboard_user_prefs:{widgets_layout:layout}};
 const fetch=vi.fn(async (_url,options)=>{
  if(new URLSearchParams(options.body).get('ajaxOpcao')==='widget-render')renders++;
  return {ok:true,json:async()=>({status:'Ok',data:{html:'<p>Rendered</p>',css:''}})};
 });
 new Function('document','gestor','fetch','getLocalStorage','setLocalStorage','dashboardSalvarPreferenciaBackend','sessionStorage',init+'\ninitDashboardWidgets();')(document,gestor,fetch,()=>null,()=>{},(_key,value)=>{saved=structuredClone(value);},sessionStorage);
}
const option=name=>document.querySelector('[data-widget-option="'+name+'"]');
const card=()=>document.querySelector('.dashboard-widget-card');
const edit=()=>document.getElementById('dashboard-edit-mode').click();
const widget=extra=>Object.assign({id:'menus',name:'Menus / Main',registro_id:'main',instance_id:'a',width:4,height:1},extra);

beforeEach(()=>{
 saved=null;renders=0;sessionStorage.clear();
 document.body.innerHTML='<button id="dashboard-edit-mode" role="switch" data-label-on="on" data-label-off="off"><span data-edit-label></span></button><div id="dashboard-widgets-empty"></div><div id="dashboard-widgets-grid" data-label-config="Settings"></div>'+popup('pt-br');
});
afterEach(()=>{vi.useRealTimers();});

describe('Dashboard widget settings',()=>{
 it('keeps the popup contract in both languages',()=>{
  for(const lang of ['pt-br','en']){
   const html=popup(lang);
   for(const name of ['header','title','background-custom','background','frame','padding','refresh'])expect(html).toContain('data-widget-option="'+name+'"');
   for(const cls of ['dashboard-widget-config-close','dashboard-widget-config-reset','dashboard-widget-config-save','data-widget-config-name'])expect(html).toContain(cls);
   // Mesmo estilo visual do menu de opções: linha do menu com o trilho da chave.
   expect(html.match(/dashboard-menu-item dashboard-widget-config-switch/g)).toHaveLength(3);
   expect(component(lang)).toContain('data-label-config="@[[widgets-label-config]]@"');
  }
 });

 it('opens from the card header, applies without reloading the frame and saves',async()=>{
  boot([widget()]);await settle();
  const frame=card().querySelector('iframe');
  expect(card().querySelector('.dashboard-widget-config-btn').getAttribute('aria-label')).toBe('Settings');
  card().querySelector('.dashboard-widget-config-btn').click();
  expect(document.getElementById('dashboard-widget-config-modal').classList.contains('hidden')).toBe(true);
  edit();card().querySelector('.dashboard-widget-config-btn').click();
  expect(document.getElementById('dashboard-widget-config-modal').classList.contains('hidden')).toBe(false);
  expect(document.querySelector('[data-widget-config-name]').textContent).toBe('Menus / Main');
  expect(option('header').checked).toBe(true);
  expect(option('background').disabled).toBe(true);
  option('header').checked=false;option('frame').checked=false;option('title').value='  Vendas  ';
  option('background-custom').checked=true;option('background-custom').dispatchEvent(new Event('change'));
  expect(option('background').disabled).toBe(false);
  option('background').value='#0f172a';option('padding').value='medium';option('refresh').value='300';
  document.querySelector('.dashboard-widget-config-save').click();
  expect(document.getElementById('dashboard-widget-config-modal').classList.contains('hidden')).toBe(true);
  expect(saved[0].options).toEqual({header:false,frame:false,title:'Vendas',background:'#0f172a',padding:'medium',refresh:300});
  expect(card().classList.contains('is-headerless')).toBe(true);
  expect(card().classList.contains('is-frameless')).toBe(true);
  expect(card().getAttribute('data-widget-tone')).toBe('dark');
  expect(card().style.backgroundColor).not.toBe('');
  expect(card().querySelector('.dashboard-widget-title').textContent).toBe('Vendas');
  expect(card().querySelector('.dashboard-widget-card-body').getAttribute('data-widget-padding')).toBe('medium');
  expect(card().querySelector('iframe')).toBe(frame);
  expect(renders).toBe(1);
 });

 it('restores stored options and discards values outside the lists',async()=>{
  boot([widget({options:{header:false,background:'red; x:url(1)',padding:'huge',refresh:7,title:'T'.repeat(200)}})]);await settle();
  expect(card().classList.contains('is-headerless')).toBe(true);
  expect(card().style.backgroundColor).toBe('');
  expect(card().getAttribute('data-widget-tone')).toBe('light');
  expect(card().querySelector('.dashboard-widget-card-body').getAttribute('data-widget-padding')).toBe('none');
  expect(card().querySelector('.dashboard-widget-title').textContent).toHaveLength(80);
  expect(card()._refreshTimer).toBeNull();
 });

 it('restores defaults in the form and saves nothing when closed without applying',async()=>{
  boot([widget({options:{header:false,background:'#ff0000',padding:'large',refresh:60}})]);await settle();
  edit();card().querySelector('.dashboard-widget-config-btn').click();
  expect(option('header').checked).toBe(false);expect(option('background').value).toBe('#ff0000');expect(option('padding').value).toBe('large');
  document.querySelector('.dashboard-widget-config-reset').click();
  expect(option('header').checked).toBe(true);expect(option('background-custom').checked).toBe(false);expect(option('padding').value).toBe('none');expect(option('refresh').value).toBe('0');
  document.querySelector('.dashboard-widget-config-close').click();
  expect(saved).toBeNull();
  expect(card().classList.contains('is-headerless')).toBe(true);
 });

 it('reloads the widget on the chosen interval, but not in edit mode',async()=>{
  vi.useFakeTimers();
  boot([widget({options:{refresh:60}})]);await settle();
  expect(renders).toBe(1);
  vi.advanceTimersByTime(60000);await settle();
  expect(renders).toBe(2);
  edit();vi.advanceTimersByTime(60000);await settle();
  expect(renders).toBe(2);
 });
});
