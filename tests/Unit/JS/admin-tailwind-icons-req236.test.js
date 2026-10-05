import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import {beforeEach,afterEach,it,expect,vi} from 'vitest';
let callbacks;
beforeEach(()=>{
 callbacks=[];
 vi.stubGlobal('MutationObserver',class {constructor(callback){callbacks.push(callback);}observe(){}});
 vi.stubGlobal('requestAnimationFrame',callback=>callback());
 document.body.innerHTML='<div id="c2f-admin-shell"><aside data-admin-sidebar></aside><div data-admin-conteudo><main data-admin-main><i class="check circle icon"></i><i class="external alternate icon"></i><i class="invalid compound icon"></i></main></div></div>';
 window.lucide={createIcons:vi.fn()};
 vm.runInThisContext(readFileSync(process.env.C2F_ADMIN_TAILWIND_SOURCE || 'gestor/assets/global/admin-tailwind.js','utf8'));
 window.gestorAdminTailwind.iniciar();
});
afterEach(()=>{vi.unstubAllGlobals();delete window.lucide;document.body.innerHTML='';});
it('maps legacy compound icons to SVG names without an icon font',()=>{
 expect([...document.querySelectorAll('i.icon')].map(el=>el.getAttribute('data-lucide'))).toEqual(['circle-check','external-link','circle']);
 expect(document.querySelector('i.icon').getAttribute('width')).toBe('16');
});
it('converts icons inserted by asynchronous module updates',()=>{
 const row=document.createElement('div');row.innerHTML='<i class="key icon"></i>';document.querySelector('main').appendChild(row);
 callbacks.at(-1)([{addedNodes:[row]}]);
 expect(row.querySelector('i').getAttribute('data-lucide')).toBe('key');
});
it('does not schedule another redraw for generated SVG nodes',()=>{
 const before=window.lucide.createIcons.mock.calls.length;
 callbacks.at(-1)([{addedNodes:[document.createElementNS('http://www.w3.org/2000/svg','svg')]}]);
 expect(window.lucide.createIcons.mock.calls.length).toBe(before);
});
