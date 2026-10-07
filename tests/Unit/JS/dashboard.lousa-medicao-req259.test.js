import {readFileSync} from 'node:fs';
import {afterEach, beforeEach, describe, expect, it} from 'vitest';

// REQ-259 — a lousa publicada emite o evento de página `c2f:analytics` (visita, item visto, clique), no contrato que o
// módulo de análise escuta: `detail.event` com o nome e `detail.data` com os dados.
const source=readFileSync('gestor/modulos/dashboard/dashboard.widget.js','utf8');
let events, listener, observers;
class FakeObserver{
 constructor(callback,options){this.callback=callback;this.options=options;this.watched=[];observers.push(this);}
 observe(el){this.watched.push(el);}
 unobserve(el){this.watched=this.watched.filter(x=>x!==el);}
 show(el){this.callback([{isIntersecting:true,target:el}]);}
}
function page(id='vendas'){
 document.body.innerHTML='<div class="c2f-lousa" data-c2f-lousa data-lousa="'+id+'" data-mode="grade" data-lucide-url="">'
  +'<div class="c2f-lousa-item" data-item="1" data-tipo="menus"><div class="c2f-lousa-titulo">  Navegue\n aqui </div><div class="c2f-lousa-corpo"><nav><a href="/planos/?utm=x#topo">Planos   e preços</a></nav></div></div>'
  +'<div class="c2f-lousa-item is-objeto" data-item="2" data-tipo="objeto-button"><div class="c2f-lousa-corpo"><div class="c2f-lousa-objeto"><a class="c2f-lousa-acao" href="https://exemplo.com/x?token=segredo">Assine <b>já</b></a></div></div></div>'
  +'<div class="c2f-lousa-item is-objeto" data-item="3" data-tipo="objeto-text"><div class="c2f-lousa-corpo"><div class="c2f-lousa-objeto c2f-lousa-texto">Só texto</div></div></div>'
  +'</div><a id="fora" href="/fora/">Fora da lousa</a>';
}
function run(observer=FakeObserver){new Function('window','document','ResizeObserver','IntersectionObserver',source)(window,document,undefined,observer);}
const named=name=>events.filter(e=>e.event===name).map(e=>e.data);

beforeEach(()=>{
 events=[];observers=[];
 listener=e=>events.push(e.detail);
 document.addEventListener('c2f:analytics',listener);
});
afterEach(()=>{document.removeEventListener('c2f:analytics',listener);});

describe('Published board measurement (req-259)',()=>{
 it('tells the page the board was loaded, with its mode and item count',()=>{
  page();run();
  expect(named('lousa_view')).toEqual([{lousa:'vendas',modo:'grade',itens:3}]);
 });

 it('tells once when each item becomes visible',()=>{
  page();run();
  const items=[...document.querySelectorAll('.c2f-lousa-item')];
  expect([observers.length,observers[0].options.threshold,observers[0].watched.length]).toEqual([1,0.5,3]);
  observers[0].show(items[1]);
  observers[0].callback([{isIntersecting:false,target:items[0]}]);
  observers[0].show(items[0]);
  expect(named('lousa_item_view')).toEqual([
   {lousa:'vendas',item:2,tipo:'objeto-button',titulo:''},
   {lousa:'vendas',item:1,tipo:'menus',titulo:'Navegue aqui'},
  ]);
  // Quem já foi visto deixa de ser observado.
  expect(observers[0].watched).toEqual([items[2]]);
 });

 it('tells about clicks on links and buttons inside the board, without the address parameters',()=>{
  page();run();
  document.querySelector('nav a').dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true}));
  document.querySelector('.c2f-lousa-acao b').dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true}));
  expect(named('lousa_click')).toEqual([
   {lousa:'vendas',item:1,tipo:'menus',titulo:'Navegue aqui',texto:'Planos e preços',destino:'/planos/'},
   {lousa:'vendas',item:2,tipo:'objeto-button',titulo:'',texto:'Assine já',destino:'https://exemplo.com/x'},
  ]);
 });

 it('ignores clicks on plain content and outside the board',()=>{
  page();run();
  document.querySelector('.c2f-lousa-texto').dispatchEvent(new MouseEvent('click',{bubbles:true}));
  document.getElementById('fora').dispatchEvent(new MouseEvent('click',{bubbles:true,cancelable:true}));
  expect(named('lousa_click')).toEqual([]);
 });

 it('still reports the visit when the browser cannot observe visibility, and stays quiet without a board id',()=>{
  page();run(undefined);
  expect([named('lousa_view').length,named('lousa_item_view').length]).toEqual([1,0]);
  events=[];
  page('');run();
  expect(events).toEqual([]);
 });

 it('uses the contract the analytics module listens to and never sends anything itself',()=>{
  expect(source).toContain("new CustomEvent('c2f:analytics', {detail: {event: name, data: data}})");
  for(const banned of ['dataLayer','gtag(','fetch(','XMLHttpRequest','sendBeacon','document.cookie','localStorage'])expect(source).not.toContain(banned);
 });
});
