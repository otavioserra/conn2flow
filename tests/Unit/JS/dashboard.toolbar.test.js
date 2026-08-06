import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import fs from 'node:fs';

describe('Live Editor - dashboard.toolbar.js (BATCH-079)', () => {
  let T;

  beforeEach(() => {
    // Injeta suporte básico de base64 se não existir no environment
    if (!window.btoa) {
      window.btoa = (s) => Buffer.from(s, 'binary').toString('base64');
    }
    if (!window.atob) {
      window.atob = (s) => Buffer.from(s, 'base64').toString('binary');
    }

    // Carrega o motor real e injeta o hook de teste antes do fecho da IIFE.
    let code = fs.readFileSync('gestor/modulos/dashboard/dashboard.toolbar.js', 'utf8');
    const idx = code.lastIndexOf('})();');
    if (idx < 0) {
      throw new Error('IIFE close não encontrado em dashboard.toolbar.js');
    }
    const hook = 'window.__c2fToolbar={runMap:function(root,backup){varMap={};varSeq=0;mapRoot=root;' +
        'mapTree(root,backup);return varMap;},reconstruct:function(c){return reconstructOriginal(c);},' +
        'handleWidgetRender:function(s,w){return handleEngineWidgetRender(s,w);},' +
        'buildAddPanel:function(){return buildAddPanel();},backupColumn:function(title,items,type){return backupColumn(title,items,type);},' +
        'translate:function(pt,en){return t(pt,en);},' +
        // BATCH-106: painel de opções de exibição + injeção de um editor de teste (c2fEditor é
        // privado à IIFE e no runtime só existe depois de "Editar Página").
        'openViewOptions:function(x,y){return openViewOptionsPanel(x,y);},' +
        'openAdd:function(x,y){return openAddPanel(x,y);},' +
        'dismissPanels:function(){return dismissHostPanels();},' +
        'setEditor:function(e){c2fEditor=e;}};\n';
    code = code.slice(0, idx) + hook + code.slice(idx);
    
    // Eval no contexto da sandbox do vitest/happy-dom
    (0, eval)(code);

    T = window.__c2fToolbar;
    if (!T) {
      throw new Error('Hook de teste __c2fToolbar não foi inicializado');
    }
  });

  afterEach(() => {
    document.body.innerHTML = '';
    delete window.__c2fToolbar;
    delete window.gestor;
  });

  const OPEN = (s) => '<!-- widgets#' + s + ' < -->';
  const CLOSE = (s) => '<!-- widgets#' + s + ' > -->';

  function createEl(html, id) {
    const d = document.createElement('div');
    if (id) d.id = id;
    d.innerHTML = html;
    return d;
  }

  it('Cenário A — widget de menu preenche o <nav> → deve mapear no PAI', () => {
    const sig = 'menus->render({"grupo_slug":"main"})';
    const root = createEl('<nav id="mainnav">' + OPEN(sig) + '<a href="/a">A</a><a href="/b">B</a><a href="/c">C</a>' + CLOSE(sig) + '</nav>', 'c2f-page-content');
    const backup = createEl('<nav id="mainnav">' + OPEN(sig) + '<a>mock</a>' + CLOSE(sig) + '</nav>');

    T.runMap(root, backup);
    const nav = root.querySelector('#mainnav');
    const anchors = root.querySelectorAll('#mainnav > a');

    expect(nav.getAttribute('data-c2f-widget-parent')).toBe('1');
    expect(nav.getAttribute('data-c2f-widget-root')).toBe('1');
    expect(nav.getAttribute('data-widget-type')).toBe('menus');
    expect(nav.getAttribute('data-widget-slug')).toBe('main');
    
    Array.prototype.forEach.call(anchors, (a) => {
      expect(a.getAttribute('data-c2f-widget-id')).toBeNull();
    });

    const out = T.reconstruct(root);
    expect(out).toContain('<!-- widgets#menus->render');
    expect(out).toContain('<nav id="mainnav">');
    expect(out).toContain('<a>mock</a>');
    expect(out).not.toContain('>A</a>');
    expect(out).not.toContain('data-c2f-widget');
  });

  it('Cenário B — dois widgets idênticos consecutivos no <nav> → deve gerar widgets independentes', () => {
    const sig = 'menus->render({"grupo_slug":"m"})';
    const root = createEl(OPEN(sig) + '<div class="card">A</div>' + CLOSE(sig) + OPEN(sig) + '<div class="card">B</div>' + CLOSE(sig), 'c2f-page-content');
    const backup = createEl(OPEN(sig) + '<a>mock</a>' + CLOSE(sig) + OPEN(sig) + '<a>mock</a>' + CLOSE(sig));

    T.runMap(root, backup);
    const cards = root.querySelectorAll('.card');
    const id0 = cards[0].getAttribute('data-c2f-widget-id');
    const id1 = cards[1].getAttribute('data-c2f-widget-id');

    expect(id0).toBeTruthy();
    expect(id1).toBeTruthy();
    expect(id0).not.toBe(id1);
    expect(cards[0].getAttribute('data-c2f-widget-root')).toBe('1');
    expect(cards[1].getAttribute('data-c2f-widget-root')).toBe('1');

    const out = T.reconstruct(root);
    const openCount = (out.match(/ < -->/g) || []).length;
    const closeCount = (out.match(/ > -->/g) || []).length;
    expect(openCount).toBe(2);
    expect(closeCount).toBe(2);
  });

  it('Cenário C — widget + rodapé estático no mesmo contêiner → deve preservar o rodapé', () => {
    const sig = 'menus->render({"grupo_slug":"m"})';
    const root = createEl(OPEN(sig) + '<div class="card">A</div>' + CLOSE(sig) + OPEN(sig) + '<div class="card">B</div>' + CLOSE(sig) + '<footer id="ft">F</footer>', 'c2f-page-content');
    const backup = createEl(OPEN(sig) + '<a>mock</a>' + CLOSE(sig) + OPEN(sig) + '<a>mock</a>' + CLOSE(sig) + '<footer id="ft">F</footer>');

    T.runMap(root, backup);
    const cards = root.querySelectorAll('.card');
    const footer = root.querySelector('#ft');

    expect(cards[0].getAttribute('data-c2f-widget-id')).not.toBe(cards[1].getAttribute('data-c2f-widget-id'));
    expect(footer.getAttribute('data-c2f-widget-id')).toBeNull();
    expect(footer.getAttribute('data-c2f-marker')).toBeNull();

    const out = T.reconstruct(root);
    expect(out).toContain('<footer id="ft">F</footer>');
    expect((out.match(/ < -->/g) || []).length).toBe(2);
  });

  // ===== BATCH-082 =====

  it('§1 — ponte de widget: c2f-he:widget-render → AJAX → posta c2f-he:widget-rendered', async () => {
    let fetchedUrl = null, fetchedBody = null;
    const origFetch = globalThis.fetch;
    globalThis.fetch = (url, opts) => {
      fetchedUrl = url; fetchedBody = (opts && opts.body) || '';
      return Promise.resolve({ json: () => Promise.resolve({ status: 'Ok', data: { html: '<b>ok</b>' } }) });
    };
    const posted = [];
    const origPost = window.postMessage;
    window.postMessage = (msg) => { posted.push(msg); };

    try {
      await T.handleWidgetRender('menus->render({"grupo_slug":"m"})', 'W1');
    } finally {
      window.postMessage = origPost;
      globalThis.fetch = origFetch;
    }

    expect(fetchedUrl).toContain('ajaxOpcao=site-toolbar-widget-render');
    expect(fetchedBody).toContain('params[signature]=');
    expect(posted.length).toBe(1);
    const msg = JSON.parse(posted[0]);
    expect(msg.action).toBe('c2f-he:widget-rendered');
    expect(msg.wrapperId).toBe('W1');
    expect(msg.html).toBe('<b>ok</b>');
  });

  it('§1 — ponte de widget ignora assinatura/wrapper vazios (sem fetch)', () => {
    let called = false;
    const origFetch = globalThis.fetch;
    globalThis.fetch = () => { called = true; return Promise.resolve({ json: () => Promise.resolve({}) }); };
    try {
      T.handleWidgetRender('', 'W1');
      T.handleWidgetRender('sig', '');
    } finally {
      globalThis.fetch = origFetch;
    }
    expect(called).toBe(false);
  });

  it('normaliza variáveis DIGITADAS ([[x]] → @[[x]]@) e mantém as já cercadas (idempotente)', () => {
    const el = createEl('<p>Base: [[pagina#url-raiz]] · widget [[widgets#promo]] · já @[[usuario#nome]]@</p>');
    const out = T.reconstruct(el);
    // Variáveis/widgets digitados ganham o cerco @…@.
    expect(out).toContain('@[[pagina#url-raiz]]@');
    expect(out).toContain('@[[widgets#promo]]@');
    // A que já vinha cercada permanece sem duplicar o cerco.
    expect(out).toContain('@[[usuario#nome]]@');
    expect(out).not.toContain('@@[[');
    expect(out).not.toContain(']]@@');
    // Não sobra nenhuma variável sem cerco.
    expect(/(^|[^@])\[\[pagina#url-raiz\]\]([^@]|$)/.test(out)).toBe(false);
  });

  it('traduz o painel de adição e estados de backup para inglês', () => {
    window.gestor = { language: 'en-us' };
    const panel = T.buildAddPanel();

    expect(panel.textContent).toContain('HTML Elements');
    expect(panel.textContent).toContain('Paragraph');
    expect(panel.textContent).toContain('Custom Code');
    expect(panel.textContent).toContain('Load more');
    expect(panel.querySelector('.c2f-add-widget-search').placeholder).toBe('Search widgets...');
    expect(T.backupColumn('Page Backups', [], 'page')).toContain('No backups');
    expect(T.translate('Falha ao restaurar o backup.', 'Failed to restore the backup.')).toBe('Failed to restore the backup.');
  });

  // ===== BATCH-106 — painel de Opções de Exibição =====

  it('abre o painel de opções com os dois toggles e reflete o estado do editor', () => {
    T.setEditor({
      viewOptions: { cssSidebar: true, elementNavbar: false },
      getViewOption: function (k) { return !!this.viewOptions[k]; },
      setViewOption: function (k, on) { this.viewOptions[k] = !!on; }
    });

    T.openViewOptions(120, 74);
    const painel = document.getElementById('c2f-view-options-panel');
    expect(painel).toBeTruthy();
    expect(painel.style.display).toBe('block');
    expect(painel.style.top).toBe('78px');
    expect(painel.textContent).toContain('Estilização de Elementos');
    expect(painel.textContent).toContain('Navegação de Elementos');

    const toggles = painel.querySelectorAll('[data-view-option]');
    expect(toggles.length).toBe(2);
    // Sincroniza com o estado real do motor ao abrir (recuperado do localStorage no boot).
    expect(painel.querySelector('[data-view-option="cssSidebar"]').checked).toBe(true);
    expect(painel.querySelector('[data-view-option="elementNavbar"]').checked).toBe(false);
  });

  it('o toggle aciona setViewOption no editor', () => {
    const chamadas = [];
    T.setEditor({
      viewOptions: { cssSidebar: false, elementNavbar: false },
      getViewOption: function (k) { return !!this.viewOptions[k]; },
      setViewOption: function (k, on) { chamadas.push([k, on]); this.viewOptions[k] = !!on; }
    });

    T.openViewOptions(10, 40);
    const campo = document.querySelector('#c2f-view-options-panel [data-view-option="elementNavbar"]');
    campo.checked = true;
    campo.dispatchEvent(new window.Event('change', { bubbles: true }));

    expect(chamadas).toEqual([['elementNavbar', true]]);
  });

  it('dismissHostPanels fecha os painéis flutuantes do host (clique na Editbar)', () => {
    T.setEditor({ getViewOption: function () { return false; }, setViewOption: function () { } });
    T.openViewOptions(10, 40);
    T.openAdd(10, 40);

    const viewPanel = document.getElementById('c2f-view-options-panel');
    const addPanel = document.getElementById('c2f-add-panel');
    expect(viewPanel.style.display).toBe('block');
    expect(addPanel.style.display).toBe('block');

    T.dismissPanels();
    expect(viewPanel.style.display).toBe('none');
    expect(addPanel.style.display).toBe('none');
  });

  it('dismissHostPanels também fecha a UI flutuante do motor (Modelos, IA, modais)', () => {
    let chamou = 0;
    T.setEditor({
      getViewOption: function () { return false; },
      setViewOption: function () { },
      dismissFloatingUi: function () { chamou++; }
    });

    T.dismissPanels();
    expect(chamou).toBe(1);
  });

  it('dismissHostPanels não quebra sem editor instanciado (fora do modo de edição)', () => {
    T.setEditor(null);
    expect(() => T.dismissPanels()).not.toThrow();
  });

  it('traduz o painel de opções de exibição para inglês', () => {
    window.gestor = { language: 'en-gb' };
    T.setEditor({ getViewOption: function () { return false; }, setViewOption: function () { } });
    T.openViewOptions(10, 40);

    const painel = document.getElementById('c2f-view-options-panel');
    expect(painel.textContent).toContain('Display Options');
    expect(painel.textContent).toContain('Element Styling');
    expect(painel.textContent).toContain('Element Navigation');
  });

});
