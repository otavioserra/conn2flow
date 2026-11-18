import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { Window } from 'happy-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const source = (name) => readFileSync('gestor/modulos/dashboard/dashboard.' + name + '.js', 'utf8');

let win;
let context;

beforeEach(() => {
  win = new Window({ url: 'https://site.test/' });
  context = vm.createContext({ window: win, document: win.document });
  Object.defineProperty(win.document, 'readyState', { value: 'complete' });
});

afterEach(() => win.happyDOM.close());

function resizeHost(offset, height = offset) {
  win.dispatchEvent(new win.MessageEvent('message', {
    origin: win.location.origin,
    data: { type: 'c2f-toolbar:resize', offset, height }
  }));
}

describe('REQ-302 — altura persistente da toolbar', () => {
  it.each([true, false])('mede a raiz e o dropdown separadamente (ResizeObserver: %s)', (observa) => {
    win.document.body.innerHTML = `
      <div id="c2f-toolbar-root">
        <button id="c2f-toolbar-edit"></button>
        <div id="c2f-toolbar-menu"><ul></ul></div>
        <div id="c2f-toolbar-editbar" style="display:none"><button id="c2f-editbar-cancel"></button></div>
      </div>`;
    const byId = (id) => win.document.getElementById(id);
    const root = byId('c2f-toolbar-root');
    const editbar = byId('c2f-toolbar-editbar');
    let mainHeight = 25.1;
    let notify;
    const observe = vi.fn();
    win.ResizeObserver = observa ? class {
      constructor(callback) { notify = callback; }
      observe = observe;
    } : undefined;
    root.getBoundingClientRect = () => {
      const height = mainHeight + (editbar.style.display === 'none' ? 0 : 59.2);
      return { height, bottom: height };
    };
    byId('c2f-toolbar-menu').querySelector('ul').getBoundingClientRect = () => ({ bottom: 200.2 });
    const post = vi.spyOn(win.parent, 'postMessage').mockImplementation(() => {});
    const sizes = () => post.mock.calls.map(([data]) => data)
      .filter((data) => data.type === 'c2f-toolbar:resize')
      .map(({ height, offset }) => [height, offset]);

    vm.runInContext(source('iframe-toolbar'), context);
    if (observa) expect(observe).toHaveBeenCalledWith(root, { box: 'border-box' });
    expect(sizes()).toEqual([[26, 26]]);
    byId('c2f-toolbar-menu').dispatchEvent(new win.MouseEvent('mouseenter'));
    expect(sizes().at(-1)).toEqual([209, 26]);
    byId('c2f-toolbar-edit').click();
    expect(sizes().at(-1)).toEqual([85, 85]);
    mainHeight = 40.4;
    if (observa) notify();
    else win.dispatchEvent(new win.Event('resize'));
    expect(sizes().at(-1)).toEqual([100, 100]);
    byId('c2f-editbar-cancel').click();
    expect(sizes()).toEqual([[26, 26], [209, 26], [85, 85], [100, 100], [41, 41]]);
  });
});

describe('REQ-302 — deslocamento na página hospedeira', () => {
  let insertRule;

  // O parser do happy-dom descarta declarações calc() com var(). Inspecione a regra entregue ao CSSOM.
  const ruleFor = (element) => insertRule.mock.calls.map(([rule]) => rule)
    .find((rule) => rule.includes('{top:') && element.matches(rule.split(' {')[0]));

  beforeEach(() => {
    insertRule = vi.spyOn(win.CSSStyleSheet.prototype, 'insertRule');
    win.document.body.className = 'site c2f-toolbar-ativa';
    win.document.head.innerHTML = '<style>.pinned {position:sticky;top:3px}</style>';
    win.document.documentElement.style.cssText = 'margin-top: 2px; scroll-padding-top: 12px';
    win.document.body.innerHTML = `
      <iframe id="c2f-site-toolbar" style="position:fixed;top:0;height:20px"></iframe>
      <div id="c2f-he-element-navbar"><div id="editor-child" style="position:fixed;top:0"></div></div>
      <main id="c2f-page-content">
        <header id="header" style="position:fixed;top:5px">Header</header>
        <div id="sticky" class="pinned">Sticky</div>
        <div id="footer" style="position:fixed;bottom:0">Footer</div>
        <div id="flow">Flow</div>
      </main>`;
    vm.runInContext(source('toolbar'), context);
  });

  it('combina as margens existentes com a medição, preserva o HTML e exclui a UI', () => {
    const doc = win.document;
    const byId = (id) => doc.getElementById(id);
    const original = byId('c2f-page-content').innerHTML;
    resizeHost(36, 210);
    const rootStyle = doc.styleSheets[1].cssRules[0].style;

    expect(rootStyle.getPropertyValue('--c2f-toolbar-offset')).toBe('36px');
    const rootRule = insertRule.mock.calls.map(([rule]) => rule).findLast((rule) => rule.includes('margin-top:'));
    expect(rootRule).toContain('margin-top:calc(2px + var(--c2f-toolbar-offset))');
    expect(rootRule).toContain('scroll-padding-top:calc(12px + var(--c2f-toolbar-offset))');
    expect(byId('c2f-site-toolbar').style.height).toBe('210px');
    expect(ruleFor(byId('header'))).toContain('top:calc(5px + var(--c2f-toolbar-offset))');
    expect(ruleFor(byId('sticky'))).toContain('top:calc(3px + var(--c2f-toolbar-offset))');
    for (const id of ['c2f-site-toolbar', 'editor-child', 'footer', 'flow']) {
      expect(ruleFor(byId(id))).toBeUndefined();
    }
    expect(byId('c2f-page-content').innerHTML).toBe(original);

    resizeHost(81);
    expect(doc.styleSheets[1].cssRules[0].style.getPropertyValue('--c2f-toolbar-offset')).toBe('81px');
    expect(ruleFor(byId('header'))).toContain('top:calc(5px + var(--c2f-toolbar-offset))');
    expect(byId('c2f-page-content').innerHTML).toBe(original);
  });

  it('entrega o HTML de autoria ao endpoint de salvamento com elementos deslocados', () => {
    const doc = win.document;
    const root = doc.getElementById('c2f-page-content');
    const original = root.innerHTML.trim();
    resizeHost(76);

    context.Node = win.Node;
    context.DOMParser = win.DOMParser;
    context.$ = () => ({ ready: (init) => init() });
    win.__c2fHtmlEditorNoAutoInit = true;
    vm.runInContext(readFileSync('gestor/assets/interface/html-editor.js', 'utf8'), context);
    const editor = Object.create(win.HtmlEditorClass.prototype);
    editor.contentRoot = root;
    expect(editor.getCleanHtml()).toBe(original);

    // Injeta a instância no fluxo de salvamento sem inicializar a UI nem gravar no servidor.
    context.editor = editor;
    context.root = root;
    context.fetch = vi.fn(() => new Promise(() => {}));
    const host = source('toolbar');
    const end = host.lastIndexOf('})();');
    vm.runInContext(host.slice(0, end) + 'c2fEditor = editor; performSave("home", root);\n' + host.slice(end), context);
    const [url, request] = context.fetch.mock.calls[0];
    expect(url).toContain('ajaxOpcao=site-toolbar-save');
    expect(new URLSearchParams(request.body).get('html')).toBe(original);
  });

  it('recalcula após alterações da página e restaura elementos que deixam de estar ancorados', async () => {
    const doc = win.document;
    const sticky = doc.getElementById('sticky');
    resizeHost(36);
    expect(ruleFor(sticky)).toBeDefined();
    insertRule.mockClear();
    sticky.className = '';
    const late = doc.createElement('header');
    late.style.cssText = 'position: fixed; top: 9px;';
    sticky.before(late);
    await win.happyDOM.waitUntilComplete();
    expect(ruleFor(sticky)).toBeUndefined();
    expect(ruleFor(late)).toContain('top:calc(9px + var(--c2f-toolbar-offset))');

    insertRule.mockClear();
    late.style.top = '45px';
    late.getAnimations = () => [{
      transitionProperty: 'top',
      effect: { getKeyframes: () => [{ top: '45px' }, { top: '15px' }] }
    }];
    win.dispatchEvent(new win.Event('resize'));
    await win.happyDOM.waitUntilComplete();
    expect(ruleFor(late)).toContain('top:calc(15px + var(--c2f-toolbar-offset))');
    resizeHost(0);
    expect(doc.styleSheets[1].cssRules).toHaveLength(0);
    expect(late.style.top).toBe('45px');
  });
});
