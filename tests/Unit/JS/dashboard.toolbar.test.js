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
        'mapTree(root,backup);return varMap;},reconstruct:function(c){return reconstructOriginal(c);}};\n';
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
});
