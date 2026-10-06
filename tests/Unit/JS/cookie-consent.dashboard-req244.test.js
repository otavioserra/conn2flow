import {readFileSync} from 'node:fs';
import {afterEach, expect, it, vi} from 'vitest';

const source=readFileSync('gestor/modulos/cookie-consent/cookie-consent.widget.js','utf8');
afterEach(()=>{
 vi.restoreAllMocks();
 document.documentElement.removeAttribute('data-c2f-dashboard-widget');
 document.body.innerHTML='';
 delete window.c2fConsent;
});

it('initializes and accepts consent in the opaque Dashboard without reading or writing cookies',()=>{
 document.documentElement.setAttribute('data-c2f-dashboard-widget','');
 document.body.innerHTML='<div data-c2f-cookie-consent><div data-cc-banner><button data-cc-accept>Accept</button></div><div data-cc-panel hidden><input data-cc-category="necessary" disabled checked><input data-cc-category="analytics"></div><button data-cc-open hidden>Open</button></div>';
 const get=vi.spyOn(document,'cookie','get').mockImplementation(()=>{throw new DOMException('Sandboxed','SecurityError');});
 const set=vi.spyOn(document,'cookie','set').mockImplementation(()=>{throw new DOMException('Sandboxed','SecurityError');});
 new Function('MutationObserver',source)(undefined);
 document.dispatchEvent(new Event('DOMContentLoaded'));
 expect(document.querySelector('[data-c2f-cookie-consent]').classList.contains('is-ready')).toBe(true);
 document.querySelector('[data-cc-accept]').click();
 expect(window.c2fConsent.get()).toEqual({categories:{necessary:true,analytics:true},decided:true});
 expect(get).not.toHaveBeenCalled();
 expect(set).not.toHaveBeenCalled();
});
