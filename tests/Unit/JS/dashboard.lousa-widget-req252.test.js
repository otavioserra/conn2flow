import {readFileSync} from 'node:fs';
import {beforeEach, describe, expect, it, vi} from 'vitest';

// REQ-252 — controlador público do widget "Lousa": arranjo igual ao do Dashboard, itens escondidos e ícones.
const source=readFileSync('gestor/modulos/dashboard/dashboard.widget.js','utf8');
const panel=readFileSync('gestor/modulos/dashboard/dashboard.js','utf8');
// O arranjo do Dashboard, tirado do próprio arquivo, serve de referência.
const from=panel.indexOf('function boardRows(widget)');
const reference=new Function('ROW',panel.slice(from,panel.indexOf('// Aplica o arranjo nos cards',from))+'\nreturn arrange;')(20);

function board(mode,items,width){
 document.body.innerHTML='<div class="c2f-lousa" data-c2f-lousa data-mode="'+mode+'" data-lucide-url="/assets/lucide.js">'+items.map(i=>
  '<div class="c2f-lousa-item" data-w="'+i.width+'" data-h="'+i.height_px+'" data-x="'+(i.x??'')+'" data-y="'+(i.y??'')+'"'+(i.hidden?' style="display:none"':'')+'>'+(i.html||'')+'</div>').join('')+'</div>';
 const el=document.querySelector('.c2f-lousa');
 Object.defineProperty(el,'clientWidth',{configurable:true,get:()=>el._width});
 el._width=width;
 return el;
}
function run(){new Function('window','document','ResizeObserver',source)(window,document,undefined);}
const placed=el=>[...el.children].map(c=>[c.style.gridColumn,c.style.gridRow]);
const expected=(items,cols)=>{const places=reference(items.map((i,n)=>Object.assign({instance_id:'i'+n,x:null,y:null},i)),cols,null);return items.map((_,n)=>{const p=places['i'+n];return [(p.x+1)+' / span '+p.w,(p.y+1)+' / span '+p.h];});};

beforeEach(()=>{
 window.happyDOM.settings.disableJavaScriptFileLoading=true;
 document.head.querySelectorAll('script').forEach(s=>s.remove());
 delete window.lucide;
});

describe('Dashboard board widget on a page (req-252)',()=>{
 const items=[
  {width:5,height_px:300,x:2,y:4},
  {width:3,height_px:120,x:8,y:0},
  {width:6,height_px:220,x:2,y:6},
  {width:4,height_px:200},
  {width:24,height_px:160,x:0,y:30},
  {width:2,height_px:120,x:23,y:0},
 ];

 it.each([[1300,12],[860,8],[2700,24],[640,6]])('arranges like the Dashboard at %i px (%i columns)',(width,cols)=>{
  const el=board('lousa',items,width);run();
  expect([el.getAttribute('data-cols'),el.style.getPropertyValue('--c2f-cols'),el.classList.contains('is-arranged')]).toEqual([String(cols),String(cols),true]);
  expect(placed(el)).toEqual(expected(items,cols));
 });

 it('stacks in one column under 640 px, in the order of the saved positions',()=>{
  const el=board('lousa',items,500);run();
  expect(el.getAttribute('data-cols')).toBe('1');
  expect(placed(el)).toEqual(expected(items,1));
  expect(placed(el).every(([column])=>column==='1 / span 1')).toBe(true);
  // Quem tem posição vem primeiro, por linha e coluna; quem não tem vai para o primeiro vão.
  const starts=placed(el).map(([,row])=>Number(row.split(' ')[0]));
  expect(starts[1]).toBeLessThan(starts[5]);expect(starts[5]).toBeLessThan(starts[0]);expect(starts[0]).toBeLessThan(starts[2]);
 });

 it('gives no place to an item hidden by the stylesheet',()=>{
  const list=items.map((i,n)=>Object.assign({},i,{hidden:n===1}));
  const el=board('lousa',list,1300);run();
  const visible=items.filter((_,n)=>n!==1), want=expected(visible,12);
  expect(placed(el).filter((_,n)=>n!==1)).toEqual(want);
  expect(placed(el)[1]).toEqual(['','']);
 });

 it('rearranges when the container width changes and only then',()=>{
  const el=board('lousa',items,1300);run();
  const before=placed(el);
  el.children[0].style.gridRow='99 / span 1';
  window.dispatchEvent(new Event('resize'));
  expect(el.children[0].style.gridRow).toBe('99 / span 1');
  el._width=860;window.dispatchEvent(new Event('resize'));
  expect(placed(el)).toEqual(expected(items,8));
  expect(placed(el)).not.toEqual(before);
 });

 it('leaves the grid mode to the stylesheet',()=>{
  const el=board('grade',items,1300);run();
  expect([el.classList.contains('is-arranged'),el.getAttribute('data-cols')]).toEqual([false,null]);
  expect(placed(el).every(([column,row])=>column===''&&row==='')).toBe(true);
 });

 it('draws icons with the page Lucide, or loads it once when the page has none',()=>{
  const icon=[{width:2,height_px:120,html:'<i data-lucide="star"></i>'}];
  window.lucide={createIcons:vi.fn()};
  board('grade',icon,1300);run();
  expect(window.lucide.createIcons).toHaveBeenCalledTimes(1);
  expect(document.head.querySelectorAll('script').length).toBe(0);

  delete window.lucide;
  board('grade',icon,1300);run();
  const scripts=document.head.querySelectorAll('script');
  expect([scripts.length,scripts[0].getAttribute('src')]).toEqual([1,'/assets/lucide.js']);

  // Sem ícone na lousa, nada é carregado.
  document.head.querySelectorAll('script').forEach(s=>s.remove());
  board('lousa',items,1300);run();
  expect(document.head.querySelectorAll('script').length).toBe(0);
 });

 it('keeps the same measures as the Dashboard',()=>{
  expect(source).toContain('var CELL = 90, GAP = 20, ROW = 20, MIN_COLS = 2, MAX_COLS = 24;');
  expect(panel).toContain('var MIN_COLS = 2, MAX_COLS = 24, GRID_COLS = 12, CELL = 90, GAP = 20, ROW = 20, MAX_ROW = 4000;');
 });
});
