import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

/**
 * Testes do motor visual `html-editor.js` no contexto do Live Editor (BATCH-080):
 * botões extras da barra flutuante (Modelos/IA), abertura dos painéis vanilla e o tratamento
 * atômico de widgets de múltiplos elementos-raiz (resolveEditable → raiz do grupo).
 *
 * O arquivo é `$(document).ready(...)` e expõe `window.HtmlEditorClass`. Usamos o stub de jQuery
 * e `__c2fHtmlEditorNoAutoInit` para instanciar sob controle.
 */
function loadEngine() {
  installJQueryStub();
  window.__c2fHtmlEditorNoAutoInit = true;
  if (!window.btoa) window.btoa = (s) => Buffer.from(s, 'binary').toString('base64');
  globalThis.fetch = () => Promise.resolve({ json: () => Promise.resolve({ status: 'error' }) });
  const code = readFileSync(resolve(process.cwd(), 'gestor/assets/interface/html-editor.js'), 'utf8');
  vm.runInThisContext(code, { filename: 'html-editor.js' });
  return window.HtmlEditorClass;
}

describe('html-editor.js — Live Editor (BATCH-080)', () => {
  let Cls;

  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    Cls = loadEngine();
  });

  function makeEditor(opts) {
    const root = document.createElement('div');
    root.id = 'c2f-page-content';
    document.body.appendChild(root);
    return new Cls(Object.assign({ contentRoot: root }, opts || {}));
  }

  it('expõe openTemplatesPanel/openAiPanel (acionados pela editbar via message-bus)', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    expect(typeof ed.openTemplatesPanel).toBe('function');
    expect(typeof ed.openAiPanel).toBe('function');
  });

  it('abre os painéis de Modelos e IA ao acionar', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    const alvo = document.createElement('div');
    ed.contentRoot.appendChild(alvo);
    ed.selectedElement = alvo;

    ed.openTemplatesPanel();
    const tpl = document.getElementById('c2f-tpl-panel');
    expect(tpl).toBeTruthy();
    expect(tpl.style.display).toBe('block');
    expect(tpl.querySelector('#modelos-search-input')).toBeTruthy();
    expect(tpl.querySelector('#modelos-cards')).toBeTruthy();

    ed.openAiPanel();
    const ai = document.getElementById('c2f-ai-panel');
    expect(ai).toBeTruthy();
    expect(ai.style.display).toBe('block');
    expect(ai.querySelector('#c2f-ai-instruction')).toBeTruthy();
  });

  it('traduz os controles do Live Editor quando gestor.language começa com en', () => {
    window.gestor = { language: 'en-us' };
    try {
      const ed = makeEditor({ raiz: 'https://site.test/' });
      const alvo = document.createElement('div');
      ed.contentRoot.appendChild(alvo);
      ed.selectElement(alvo);

      expect(document.querySelector('.he-tb-drag').title).toBe('Drag / Move');
      expect(document.querySelector('.he-tb-widget-admin').title).toBe('Edit widget in module');
      expect(document.querySelector('#html-editor-tailwind-styler input').placeholder).toBe('Add classes (space/Enter)...');
      expect(document.querySelector('#html-editor-selection-breadcrumb .he-crumb-label').textContent).toBe('Ancestors:');
      expect(document.querySelector('.c2f-he-modal-cancel').textContent).toBe('Cancel');

      const align = ed.tailwindHelperConfig().find((group) => group.key === 'align');
      expect(align.section).toBe('Text');
      expect(align.title).toBe('Alignment');
      expect(align.buttons[0].title).toBe('Left');

      ed.openTemplatesPanel();
      const tpl = document.getElementById('c2f-tpl-panel');
      expect(tpl.textContent).toContain('Session templates');
      expect(tpl.querySelector('#modelos-search-input').placeholder).toBe('Search templates...');
      expect(tpl.textContent).toContain('Insert relative to selected element');

      ed.openAiPanel();
      const ai = document.getElementById('c2f-ai-panel');
      expect(ai.textContent).toContain('AI Assistant');
      expect(ai.textContent).toContain('Configuration');
      expect(ai.querySelector('#c2f-ai-instruction').placeholder).toContain('change the title');
      expect(ed.buildElement('p').textContent).toBe('New paragraph');
    } finally {
      delete window.gestor;
    }
  });

  it('trata o widget de múltiplos elementos como bloco atômico (resolveEditable → raiz do grupo)', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    const nav = document.createElement('nav');
    nav.innerHTML =
      '<a data-c2f-widget-id="W1" data-c2f-widget-root="1">A</a>' +
      '<a data-c2f-widget-id="W1">B</a>' +
      '<a data-c2f-widget-id="W1">C</a>';
    ed.contentRoot.appendChild(nav);
    const links = nav.querySelectorAll('a');
    // Clicar em QUALQUER link do grupo resolve para a raiz (1º) — o widget é UM átomo.
    expect(ed.resolveEditable(links[0])).toBe(links[0]);
    expect(ed.resolveEditable(links[1])).toBe(links[0]);
    expect(ed.resolveEditable(links[2])).toBe(links[0]);
  });

  it('background image picker no live usa o picker autônomo (imagePickerTarget=background)', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    let opened = false;
    ed.openLiveImagePicker = () => { opened = true; };
    ed.selectedElement = document.createElement('div');
    ed.requestBackgroundImage();
    expect(ed.imagePickerTarget).toBe('background');
    expect(opened).toBe(true);
  });

  it('reconhece os painéis Modelos/IA como isEditorOwned (cliques não vazam p/ a página)', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    const alvo = document.createElement('div');
    ed.contentRoot.appendChild(alvo);
    ed.selectedElement = alvo;
    ed.openTemplatesPanel();
    ed.openAiPanel();
    const tplInner = document.querySelector('#c2f-tpl-panel #modelos-cards');
    const aiInner = document.querySelector('#c2f-ai-panel #c2f-ai-instruction');
    expect(ed.isEditorOwned(tplInner)).toBe(true);
    expect(ed.isEditorOwned(aiInner)).toBe(true);
    // Um elemento da página (fora dos painéis) NÃO é do editor.
    expect(ed.isEditorOwned(alvo)).toBe(false);
  });

  // ===== BATCH-081 =====

  it('painel IA expõe os botões de CRUD de prompts e aiPromptClear limpa a instrução (§4)', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    const alvo = document.createElement('div');
    ed.contentRoot.appendChild(alvo);
    ed.selectedElement = alvo;

    ed.openAiPanel();
    const ai = document.getElementById('c2f-ai-panel');
    expect(ai.querySelector('#ai-prompt-new')).toBeTruthy();
    expect(ai.querySelector('#ai-prompt-edit')).toBeTruthy();
    expect(ai.querySelector('#ai-prompt-del')).toBeTruthy();
    expect(ai.querySelector('#ai-prompt-clear')).toBeTruthy();

    // Sem CodeMirror no ambiente de teste, os helpers usam o textarea (degradação graciosa).
    ed.aiSetInstruction('mude o título');
    expect(ed.aiGetInstruction()).toBe('mude o título');
    ed.aiPromptClear();
    expect(ed.aiGetInstruction()).toBe('');
  });

  it('deselectAll limpa a seleção ativa de forma determinística (§2)', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    const alvo = document.createElement('div');
    ed.contentRoot.appendChild(alvo);
    ed.selectedElement = alvo;

    ed.deselectAll();
    expect(ed.selectedElement).toBe(null);
    expect(ed.selectionOverlay.style.display).toBe('none');
  });

  it('openCustomCodePanel monta o painel e insertCustomHtml insere no DOM vivo (§5)', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    const alvo = document.createElement('div');
    alvo.id = 'ancora';
    ed.contentRoot.appendChild(alvo);
    ed.selectedElement = alvo;
    // Isola a inserção no DOM (evita re-render de seleção/histórico no ambiente de teste).
    ed.selectElement = () => {};
    ed.afterDomMutation = () => {};

    ed.openCustomCodePanel();
    const panel = document.getElementById('c2f-custom-panel');
    expect(panel).toBeTruthy();
    expect(panel.style.display).toBe('block');
    expect(panel.querySelector('#c2f-custom-code')).toBeTruthy();
    // O painel é UI do editor (cliques não vazam para a página).
    expect(ed.isEditorOwned(panel.querySelector('#c2f-custom-code'))).toBe(true);

    ed.insertCustomHtml('<p class="novo">Bloco</p>');
    const novo = ed.contentRoot.querySelector('p.novo');
    expect(novo).toBeTruthy();
    expect(alvo.nextElementSibling).toBe(novo);
  });

  // ===== BATCH-082 =====

  it('insertTemplate seleciona o 1º elemento inserido em replace/before/after (§2)', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    ed.afterDomMutation = () => {};

    function inserir(rel) {
      // Reinicia o conteúdo e a âncora a cada relação testada.
      ed.contentRoot.innerHTML = '<div id="anchor">âncora</div>';
      ed.selectedElement = ed.contentRoot.querySelector('#anchor');
      ed._tplRelation = rel;
      let selected = null;
      ed.selectElement = (n) => { selected = n; };
      ed.insertTemplate({ html: '<section class="s1">A</section><section class="s2">B</section>' });
      return selected;
    }

    ['after', 'before', 'replace'].forEach((rel) => {
      const sel = inserir(rel);
      expect(sel, rel).toBeTruthy();
      expect(sel.tagName).toBe('SECTION');
      expect(sel.className).toBe('s1'); // 1º elemento do bloco recém-inserido
    });
  });

  it('insertTemplate ignora nós de texto e seleciona o 1º ELEMENTO do bloco (§2)', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    ed.afterDomMutation = () => {};
    ed.contentRoot.innerHTML = '<div id="anchor">x</div>';
    ed.selectedElement = ed.contentRoot.querySelector('#anchor');
    ed._tplRelation = 'after';
    let selected = null;
    ed.selectElement = (n) => { selected = n; };

    ed.insertTemplate({ html: 'texto solto <p class="p1">P</p>' });
    expect(selected).toBeTruthy();
    expect(selected.tagName).toBe('P');
    expect(selected.className).toBe('p1');
  });
});
