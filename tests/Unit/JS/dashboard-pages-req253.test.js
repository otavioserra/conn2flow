import {readFileSync} from 'node:fs';
import {beforeEach, describe, expect, it} from 'vitest';

// REQ-253 — formulário das Páginas de Lousa: endereço que acompanha o nome e texto do título.
const source=readFileSync('gestor/modulos/dashboard-pages/dashboard-pages.js','utf8');
function boot(address='',checked=true){
 document.body.innerHTML='<input name="nome"><input name="caminho" data-dashboard-pages-caminho value="'+address+'">'
  +'<input type="checkbox" data-dashboard-pages-titulo'+(checked?' checked':'')+'><div data-dashboard-pages-titulo-texto><input name="title"></div>';
 new Function('document',source)(document);
 return {name:document.querySelector('[name="nome"]'),address:document.querySelector('[name="caminho"]'),toggle:document.querySelector('[data-dashboard-pages-titulo]'),text:document.querySelector('[data-dashboard-pages-titulo-texto]')};
}
const type=(el,value)=>{el.value=value;el.dispatchEvent(new Event('input'));};

beforeEach(()=>{document.body.innerHTML='';});

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

 it('does nothing on a page without the form',()=>{
  document.body.innerHTML='<p>lista</p>';
  expect(()=>new Function('document',source)(document)).not.toThrow();
 });
});
