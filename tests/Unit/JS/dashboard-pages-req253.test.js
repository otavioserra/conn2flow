import {readFileSync} from 'node:fs';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

// REQ-253 — formulário das Páginas de Lousa: endereço que acompanha o nome e texto do título.
const source=readFileSync('gestor/modulos/dashboard-pages/dashboard-pages.js','utf8');
function boot(address='',checked=true){
 document.body.innerHTML='<input name="nome"><input name="caminho" data-dashboard-pages-caminho value="'+address+'">'
  +'<select data-dashboard-pages-modelo><option value=""></option><option value="simples">Simples</option><option value="larga">Larga</option></select>'
  +'<input type="checkbox" data-dashboard-pages-titulo'+(checked?' checked':'')+'><div data-dashboard-pages-titulo-texto><input name="title"></div>';
 new Function('document',source)(document);
 return {name:document.querySelector('[name="nome"]'),address:document.querySelector('[name="caminho"]'),toggle:document.querySelector('[data-dashboard-pages-titulo]'),text:document.querySelector('[data-dashboard-pages-titulo-texto]')};
}
const type=(el,value)=>{el.value=value;el.dispatchEvent(new Event('input'));};

beforeEach(()=>{document.body.innerHTML='';});
afterEach(()=>{vi.unstubAllGlobals();delete window.html_editor_set_html;delete window.html_editor_set_css;});
async function settle(){for(let i=0;i<8;i++)await Promise.resolve();}

describe('Board pages form (req-253)',()=>{
 it('follows the name with the address until the user edits the address',()=>{
  const f=boot();
  type(f.name,'Campanha de Outubro 2026');
  expect(f.address.value).toBe('campanha-de-outubro-2026/');
  type(f.name,'Promoção: verão!');
  expect(f.address.value).toBe('promocao-verao/');
  type(f.address,'Campanhas/Verão');
  type(f.name,'Outro nome');
  expect(f.address.value).toBe('Campanhas/Verão');
  f.address.dispatchEvent(new Event('blur'));
  expect(f.address.value).toBe('campanhas/verao/');
 });

 it('does not touch an address that already exists',()=>{
  const f=boot('campanhas/outubro/');
  type(f.name,'Nome novo');
  expect(f.address.value).toBe('campanhas/outubro/');
 });

 it('cleans the address like the server does',()=>{
  const f=boot();
  for(const [typed,want] of [['/a//b c/','a/b-c/'],['***',''],['../../etc/passwd','etc/passwd/'],['ÁÉÍ ção','aei-cao/']]){
   type(f.address,typed);f.address.dispatchEvent(new Event('blur'));
   expect(f.address.value).toBe(want);
  }
 });

 it('hides the title text while the page title is off',()=>{
  const f=boot('',false);
  expect(f.text.hidden).toBe(true);
  f.toggle.checked=true;f.toggle.dispatchEvent(new Event('change'));
  expect(f.text.hidden).toBe(false);
 });

 it('loads the chosen template into the HTML editor, only when the user changes it (req-254)',async()=>{
  const calls=[],editor={html:null,css:null};
  vi.stubGlobal('gestor',{raiz:'/',moduloOpcao:'editar',html_editor:{framework_css:'x'}});
  vi.stubGlobal('fetch',vi.fn(async (url,options)=>{calls.push([url,Object.fromEntries(new URLSearchParams(options.body))]);
   const id=new URLSearchParams(options.body).get('template_id');
   return {json:async()=>id==='larga'?{status:'Ok',html:'<section>[[lousa#widget]]</section>',css:'.a{}',framework_css:''}:{status:'Erro'}};}));
  window.html_editor_set_html=v=>{editor.html=v;};window.html_editor_set_css=v=>{editor.css=v;};
  boot('campanhas/outubro/');
  const select=document.querySelector('[data-dashboard-pages-modelo]');
  await settle();
  expect(calls).toEqual([]);
  select.value='larga';select.dispatchEvent(new Event('change'));await settle();
  expect(calls).toEqual([['/dashboard-pages/',{opcao:'editar',ajax:'sim',ajaxOpcao:'template-load',template_id:'larga'}]]);
  expect([editor.html,editor.css,gestor.html_editor.framework_css]).toEqual(['<section>[[lousa#widget]]</section>','.a{}',null]);
  // Modelo recusado pelo servidor e opção vazia não mexem no editor.
  select.value='simples';select.dispatchEvent(new Event('change'));await settle();
  select.value='';select.dispatchEvent(new Event('change'));await settle();
  expect([calls.length,editor.html]).toEqual([2,'<section>[[lousa#widget]]</section>']);
 });

 it('holds a submit made while the template is still loading and sends it afterwards (req-254)',async()=>{
  let release;const order=[];
  vi.stubGlobal('gestor',{raiz:'/',moduloOpcao:'adicionar'});
  vi.stubGlobal('fetch',vi.fn(()=>new Promise(resolve=>{release=()=>resolve({json:async()=>({status:'Ok',html:'<b>novo</b>',css:''})});})));
  window.html_editor_set_html=()=>order.push('editor');window.html_editor_set_css=()=>{};
  document.body.innerHTML='<form><input name="nome"><input name="caminho" data-dashboard-pages-caminho><select data-dashboard-pages-modelo><option value="larga">Larga</option></select></form>';
  new Function('document',source)(document);
  const form=document.querySelector('form'),select=document.querySelector('select');
  form.requestSubmit=()=>{order.push('reenvio');};
  form.addEventListener('submit',e=>{e.preventDefault();order.push('envio');});
  // Sem carga pendente o envio passa direto.
  form.dispatchEvent(new Event('submit',{cancelable:true}));
  select.dispatchEvent(new Event('change'));
  const held=new Event('submit',{cancelable:true});form.dispatchEvent(held);
  expect([order,held.defaultPrevented]).toEqual([['envio'],true]);
  release();await settle();await settle();
  expect(order).toEqual(['envio','editor','reenvio']);
 });

 it('does nothing on a page without the form',()=>{
  document.body.innerHTML='<p>lista</p>';
  expect(()=>new Function('document',source)(document)).not.toThrow();
 });
});
