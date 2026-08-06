import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

/**
 * req-106 (BATCH-106) — Painel de Opções de Exibição, Sidebar Lateral de CSS, Barra Superior de
 * Navegação de Elementos e ação "Substituir".
 *
 * Os três painéis que acompanhavam o elemento selecionado (styler, breadcrumb de ancestrais e barra
 * de filhos) passam a poder ser ENCAIXADOS em painéis fixos, ligados/desligados por toggles. Os
 * testes cobrem o encaixe (o mesmo nó é movido, nada é duplicado), a persistência do estado, o
 * isolamento da UI na extração do HTML, a nova ação de substituição e as expansões da sidebar.
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

describe('html-editor.js — opções de exibição (req-106)', () => {
  let Cls;

  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    document.documentElement.style.marginTop = '';
    try { window.localStorage.clear(); } catch (e) { /* storage indisponível: irrelevante aqui */ }
    Cls = loadEngine();
  });

  function makeEditor(opts) {
    const root = document.createElement('div');
    root.id = 'c2f-page-content';
    document.body.appendChild(root);
    return new Cls(Object.assign({ contentRoot: root }, opts || {}));
  }

  function selecionar(ed, html) {
    ed.contentRoot.innerHTML = html || '<section class="alvo p-4"><p>Texto</p></section>';
    const alvo = ed.contentRoot.firstElementChild;
    ed.selectElement(alvo);
    return alvo;
  }

  it('cria os dois painéis DESLIGADOS por padrão, com os rótulos exigidos', () => {
    const ed = makeEditor();

    const sidebar = document.getElementById('c2f-he-css-sidebar');
    const navbar = document.getElementById('c2f-he-element-navbar');
    expect(sidebar).toBeTruthy();
    expect(navbar).toBeTruthy();
    expect(sidebar.classList.contains('he-view-on')).toBe(false);
    expect(navbar.classList.contains('he-view-on')).toBe(false);
    expect(sidebar.querySelector('.c2f-he-css-sidebar-head').textContent).toBe('Sidebar Lateral de CSS');
    expect(navbar.querySelector('.c2f-he-navbar-label').textContent).toBe('Barra de Navegação de Elementos');
    expect(ed.getViewOption('cssSidebar')).toBe(false);
    expect(ed.getViewOption('elementNavbar')).toBe(false);
    // Desligados, os painéis continuam flutuando no body (comportamento legado preservado).
    expect(ed.styler.parentNode).toBe(document.body);
    expect(ed.breadcrumb.parentNode).toBe(document.body);
  });

  it('liga a Sidebar de CSS movendo o MESMO styler para dentro dela (e devolve ao desligar)', () => {
    const ed = makeEditor();
    selecionar(ed);

    ed.setViewOption('cssSidebar', true);
    const sidebar = document.getElementById('c2f-he-css-sidebar');
    expect(sidebar.classList.contains('he-view-on')).toBe(true);
    expect(ed.styler.parentNode).toBe(sidebar.querySelector('.c2f-he-css-sidebar-body'));
    expect(ed.styler.classList.contains('he-styler-docked')).toBe(true);
    expect(ed.styler.classList.contains('he-styler-stacked')).toBe(true);
    expect(ed.styler.style.top).toBe('');
    expect(document.querySelectorAll('#html-editor-tailwind-styler').length).toBe(1);

    ed.setViewOption('cssSidebar', false);
    expect(sidebar.classList.contains('he-view-on')).toBe(false);
    expect(ed.styler.parentNode).toBe(document.body);
    expect(ed.styler.classList.contains('he-styler-docked')).toBe(false);
  });

  it('liga a Barra de Navegação movendo breadcrumb e filhos para a coluna de 80% (e devolve ao desligar)', () => {
    const ed = makeEditor();
    selecionar(ed);

    ed.setViewOption('elementNavbar', true);
    const navbar = document.getElementById('c2f-he-element-navbar');
    const area = navbar.querySelector('.c2f-he-navbar-area');
    expect(navbar.classList.contains('he-view-on')).toBe(true);
    expect(ed.breadcrumb.parentNode).toBe(area);
    expect(ed.childrenBar.parentNode).toBe(area);
    expect(ed.breadcrumb.classList.contains('he-nav-docked')).toBe(true);
    // Encaixado, o breadcrumb não recebe mais posicionamento flutuante.
    expect(ed.breadcrumb.style.top).toBe('');
    expect(ed.breadcrumb.textContent).toContain('Ancestrais:');

    ed.setViewOption('elementNavbar', false);
    expect(ed.breadcrumb.parentNode).toBe(document.body);
    expect(ed.childrenBar.parentNode).toBe(document.body);
    expect(ed.breadcrumb.classList.contains('he-nav-docked')).toBe(false);
  });

  it('persiste as opções e uma instância nova (outra página) nasce com elas ligadas', () => {
    const ed = makeEditor();
    ed.setViewOption('cssSidebar', true);

    const guardado = JSON.parse(window.localStorage.getItem('c2f-he-view-options'));
    expect(guardado.cssSidebar).toBe(true);
    expect(guardado.elementNavbar).toBe(false);

    document.body.innerHTML = '';
    const outra = makeEditor();
    expect(outra.getViewOption('cssSidebar')).toBe(true);
    expect(document.getElementById('c2f-he-css-sidebar').classList.contains('he-view-on')).toBe(true);
  });

  it('encaixa a sidebar ABAIXO da navbar e respeita o offset da Editbar (sem sobreposição)', () => {
    // O Live Editor empurra a página com margin-top no <html>; é esse o offset persistente da barra.
    const toolbar = document.createElement('iframe');
    toolbar.id = 'c2f-site-toolbar';
    document.body.appendChild(toolbar);
    document.documentElement.style.marginTop = '74px';

    const ed = makeEditor();
    ed.setViewOption('elementNavbar', true);
    ed.setViewOption('cssSidebar', true);

    const navbar = document.getElementById('c2f-he-element-navbar');
    const sidebar = document.getElementById('c2f-he-css-sidebar');
    expect(ed.chromeTopOffset()).toBe(74);
    expect(navbar.style.top).toBe('74px');
    // 44px é a altura da barra (offsetHeight não é medido fora do navegador → fallback do layout).
    expect(sidebar.style.top).toBe('118px');
    expect(sidebar.style.height).toBe('calc(100vh - 118px)');

    // Só a navbar ligada: a sidebar (desligada) volta a começar no topo do conteúdo.
    ed.setViewOption('elementNavbar', false);
    expect(sidebar.style.top).toBe('74px');
  });

  it('esconde os painéis fixos ao desabilitar o editor e devolve ao reabilitar', () => {
    const ed = makeEditor();
    ed.setViewOption('cssSidebar', true);
    ed.setViewOption('elementNavbar', true);

    ed.disable();
    expect(document.getElementById('c2f-he-css-sidebar').classList.contains('he-view-on')).toBe(false);
    expect(document.getElementById('c2f-he-element-navbar').classList.contains('he-view-on')).toBe(false);

    ed.enable();
    expect(document.getElementById('c2f-he-css-sidebar').classList.contains('he-view-on')).toBe(true);
    expect(document.getElementById('c2f-he-element-navbar').classList.contains('he-view-on')).toBe(true);
  });

  it('mantém os painéis no preview de dispositivo (disable com manterPaineis)', () => {
    const ed = makeEditor();
    ed.setViewOption('cssSidebar', true);
    ed.setViewOption('elementNavbar', true);

    // Trocar desktop/tablet/mobile desabilita a edição, mas o usuário continua no modo de edição.
    ed.disable({ manterPaineis: true });
    expect(ed.isEnabled).toBe(false);
    expect(document.getElementById('c2f-he-css-sidebar').classList.contains('he-view-on')).toBe(true);
    expect(document.getElementById('c2f-he-element-navbar').classList.contains('he-view-on')).toBe(true);
  });

  it('trata os painéis como UI do editor (seleção e extração de HTML)', () => {
    const ed = makeEditor();
    ed.setViewOption('cssSidebar', true);
    ed.setViewOption('elementNavbar', true);

    const sidebar = document.getElementById('c2f-he-css-sidebar');
    const navbar = document.getElementById('c2f-he-element-navbar');
    expect(ed.isEditorOwned(sidebar)).toBe(true);
    expect(ed.isEditorOwned(navbar)).toBe(true);
    expect(ed.isEditorOwned(sidebar.querySelector('.c2f-he-css-sidebar-body'))).toBe(true);
    expect(ed.isEditorOwned(navbar.querySelector('.c2f-he-navbar-area'))).toBe(true);

    // Editor clássico: o contentRoot é o próprio body, onde os painéis vivem.
    const classico = new Cls({});
    classico.contentRoot.appendChild(Object.assign(document.createElement('p'), { textContent: 'conteudo' }));
    const html = classico.getCleanHtml();
    expect(html).toContain('conteudo');
    expect(html).not.toContain('c2f-he-css-sidebar');
    expect(html).not.toContain('c2f-he-element-navbar');
    expect(html).not.toContain('html-editor-tailwind-styler');
  });

  it('mostra o aviso de "nada selecionado" nos painéis fixos e o esconde ao selecionar', () => {
    const ed = makeEditor();
    ed.setViewOption('cssSidebar', true);
    ed.setViewOption('elementNavbar', true);

    const vazioSidebar = document.querySelector('.c2f-he-css-sidebar-empty');
    const vazioNavbar = document.querySelector('.c2f-he-navbar-empty');
    expect(vazioSidebar.style.display).toBe('block');
    expect(vazioNavbar.style.display).toBe('block');

    selecionar(ed);
    expect(vazioSidebar.style.display).toBe('none');
    expect(vazioNavbar.style.display).toBe('none');

    ed.clearSelection();
    expect(vazioSidebar.style.display).toBe('block');
    expect(vazioNavbar.style.display).toBe('block');

    // Bloco atômico (widget): o styler não abre, então a sidebar volta ao aviso — mas a barra de
    // navegação continua mostrando a trilha do elemento.
    ed.contentRoot.innerHTML = '<div class="conn2flow-widget-wrapper">W</div>';
    ed.selectElement(ed.contentRoot.firstElementChild);
    expect(ed.styler.style.display).toBe('none');
    expect(vazioSidebar.style.display).toBe('block');
    expect(vazioNavbar.style.display).toBe('none');
  });

  it('ignora chave desconhecida em setViewOption', () => {
    const ed = makeEditor();
    ed.setViewOption('naoExiste', true);
    expect(ed.viewOptions.naoExiste).toBeUndefined();
    expect(ed.getViewOption('naoExiste')).toBe(false);
  });

  // ===== Rodada 2 — legado flutuante aposentado e ancoragem dos painéis

  it('NÃO exibe mais os painéis flutuantes legados quando os toggles estão desligados', () => {
    const ed = makeEditor();
    selecionar(ed);

    // O conteúdo continua sendo renderizado (a barra fixa o consome), mas nada flutua sobre o
    // elemento selecionado.
    expect(ed.breadcrumb.textContent).toContain('Ancestrais:');
    expect(ed.breadcrumb.style.display).toBe('none');
    expect(ed.childrenBar.style.display).toBe('none');
    expect(ed.styler.style.display).toBe('none');
    // A barra flutuante de ações continua acompanhando o elemento (não faz parte do legado).
    expect(ed.toolbar.style.display).toBe('flex');
  });

  it('exibe breadcrumb, filhos e styler assim que o painel correspondente é ligado', () => {
    const ed = makeEditor();
    selecionar(ed);

    ed.setViewOption('elementNavbar', true);
    expect(ed.breadcrumb.style.display).toBe('flex');
    expect(ed.childrenBar.style.display).toBe('flex');
    expect(ed.styler.style.display).toBe('none'); // sidebar ainda desligada

    ed.setViewOption('cssSidebar', true);
    expect(ed.styler.style.display).toBe('block');

    ed.setViewOption('elementNavbar', false);
    expect(ed.breadcrumb.style.display).toBe('none');
    expect(ed.childrenBar.style.display).toBe('none');
  });

  it('o botão de fechar do cabeçalho desliga o painel (como o toggle)', () => {
    const ed = makeEditor();
    ed.setViewOption('cssSidebar', true);
    ed.setViewOption('elementNavbar', true);

    document.querySelector('#c2f-he-css-sidebar .c2f-he-panel-close')
      .dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    expect(ed.getViewOption('cssSidebar')).toBe(false);
    expect(document.getElementById('c2f-he-css-sidebar').classList.contains('he-view-on')).toBe(false);

    document.querySelector('#c2f-he-element-navbar .c2f-he-panel-close')
      .dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    expect(ed.getViewOption('elementNavbar')).toBe(false);
    expect(document.getElementById('c2f-he-element-navbar').classList.contains('he-view-on')).toBe(false);
  });

  it('alterna a sidebar entre esquerda e direita pelo botão de setas', () => {
    const ed = makeEditor();
    ed.setViewOption('cssSidebar', true);
    const sidebar = document.getElementById('c2f-he-css-sidebar');

    // Padrão: esquerda.
    expect(ed.getViewOption('cssSidebarRight')).toBe(false);
    expect(sidebar.style.left).toBe('0px');
    expect(sidebar.style.right).toBe('auto');

    document.querySelector('#c2f-he-css-sidebar .c2f-he-panel-side')
      .dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    expect(ed.getViewOption('cssSidebarRight')).toBe(true);
    expect(sidebar.classList.contains('he-view-right')).toBe(true);
    expect(sidebar.style.right).toBe('0px');
    expect(sidebar.style.left).toBe('auto');

    document.querySelector('#c2f-he-css-sidebar .c2f-he-panel-side')
      .dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    expect(ed.getViewOption('cssSidebarRight')).toBe(false);
    expect(sidebar.style.left).toBe('0px');
  });

  it('alterna a barra de navegação entre topo e base, com a sidebar reencaixando', () => {
    const toolbar = document.createElement('iframe');
    toolbar.id = 'c2f-site-toolbar';
    document.body.appendChild(toolbar);
    document.documentElement.style.marginTop = '74px';

    const ed = makeEditor();
    ed.setViewOption('elementNavbar', true);
    ed.setViewOption('cssSidebar', true);
    const navbar = document.getElementById('c2f-he-element-navbar');
    const sidebar = document.getElementById('c2f-he-css-sidebar');

    // Padrão: topo (a sidebar começa abaixo dela).
    expect(navbar.style.top).toBe('74px');
    expect(sidebar.style.top).toBe('118px');

    document.querySelector('#c2f-he-element-navbar .c2f-he-panel-side')
      .dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    expect(ed.getViewOption('elementNavbarBottom')).toBe(true);
    expect(navbar.classList.contains('he-view-bottom')).toBe(true);
    expect(navbar.style.bottom).toBe('0px');
    expect(navbar.style.top).toBe('auto');
    // Com a barra embaixo, a sidebar sobe até a Editbar e encurta na base.
    expect(sidebar.style.top).toBe('74px');
    expect(sidebar.style.height).toBe('calc(100vh - 118px)');

    document.querySelector('#c2f-he-element-navbar .c2f-he-panel-side')
      .dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    expect(navbar.style.top).toBe('74px');
    expect(sidebar.style.top).toBe('118px');
  });

  it('dismissFloatingUi fecha os painéis do motor que já fechavam pelo backdrop', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    const alvo = selecionar(ed);

    ed.openTemplatesPanel();
    ed.openAiPanel();
    ed.openCustomCodePanel();
    expect(document.getElementById('c2f-tpl-panel').style.display).toBe('block');
    expect(document.getElementById('c2f-ai-panel').style.display).toBe('block');
    expect(document.getElementById('c2f-custom-panel').style.display).toBe('block');

    ed.dismissFloatingUi();
    expect(document.getElementById('c2f-tpl-panel').style.display).toBe('none');
    expect(document.getElementById('c2f-ai-panel').style.display).toBe('none');
    expect(document.getElementById('c2f-custom-panel').style.display).toBe('none');
    // A seleção do conteúdo NÃO é perdida: fechar painel não é deselecionar.
    expect(ed.selectedElement).toBe(alvo);
  });

  it('dismissFloatingUi fecha o modal de edição só quando ele está aberto', () => {
    const ed = makeEditor({ raiz: 'https://site.test/' });
    let escondeu = 0;
    ed.hideModal = () => { escondeu++; };

    ed.isModalActive = false;
    ed.dismissFloatingUi();
    expect(escondeu).toBe(0);

    // Regressão: `closeEmbedModal` zera `isModalActive`; se o estado fosse lido depois dele, o modal
    // de edição nunca seria fechado por aqui.
    ed.isModalActive = true;
    ed.dismissFloatingUi();
    expect(escondeu).toBe(1);
  });

  // ===== Rodada 3 — resize-follow dos painéis do Live Editor
  //
  // O ambiente de teste não calcula layout, então as medidas são fornecidas por stub. O que importa
  // aqui é a ARITMÉTICA: o editor recebe o espaço até o fim do corpo MENOS o que vem depois dele, e
  // acompanha o crescimento/encolhimento da caixa.
  function montarPainelIA({ bodyBottom, editorTop, statusAltura }) {
    const painel = document.createElement('div');
    painel.innerHTML =
      '<div class="c2f-he-live-box"><div class="c2f-he-live-body">' +
      '<div class="aba"><div class="CodeMirror"></div></div>' +
      '<div id="c2f-ai-status"></div>' +
      '</div></div>';
    document.body.appendChild(painel);

    const body = painel.querySelector('.c2f-he-live-body');
    const cmEl = painel.querySelector('.CodeMirror');
    const status = painel.querySelector('#c2f-ai-status');
    const chamadas = [];

    body.getBoundingClientRect = () => ({ top: 0, bottom: bodyBottom, height: bodyBottom });
    cmEl.getBoundingClientRect = () => ({ top: editorTop, bottom: editorTop, height: 0 });
    Object.defineProperty(cmEl, 'offsetParent', { get: () => body, configurable: true });
    Object.defineProperty(status, 'offsetParent', { get: () => body, configurable: true });
    Object.defineProperty(status, 'offsetHeight', { get: () => statusAltura, configurable: true });
    cmEl.CodeMirror = { setSize: (l, h) => chamadas.push(h), refresh: () => { } };

    return { painel, cmEl, chamadas, crescerAte: (novo) => { bodyBottom = novo; } };
  }

  it('reserva espaço para o que vem DEPOIS do editor (o status do Assistente IA)', () => {
    const ed = makeEditor();
    const { painel, chamadas } = montarPainelIA({ bodyBottom: 600, editorTop: 200, statusAltura: 30 });

    ed.syncLiveBoxCodeMirrors(painel);
    // 600 (fim do corpo) − 200 (topo do editor) − 30 (status) − 8 (folga)
    expect(chamadas).toEqual([362]);
  });

  it('o editor CRESCE e ENCOLHE junto com a caixa (regressão: ficou preso na altura mínima)', () => {
    const ed = makeEditor();
    const ctx = montarPainelIA({ bodyBottom: 600, editorTop: 200, statusAltura: 30 });

    ed.syncLiveBoxCodeMirrors(ctx.painel);
    expect(ctx.chamadas).toEqual([362]);

    // Arrastar o canto inferior direito para BAIXO: o editor acompanha.
    ctx.crescerAte(800);
    ed.syncLiveBoxCodeMirrors(ctx.painel);
    expect(ctx.chamadas[ctx.chamadas.length - 1]).toBe(562);

    // Encolher a caixa: o editor encolhe junto.
    ctx.crescerAte(500);
    ed.syncLiveBoxCodeMirrors(ctx.painel);
    expect(ctx.chamadas[ctx.chamadas.length - 1]).toBe(262);

    // Sem mudança de tamanho não há novo `setSize` (guarda anti-loop do ResizeObserver) — e, o mais
    // importante, o editor NÃO encolhe sozinho a cada disparo.
    const total = ctx.chamadas.length;
    ed.syncLiveBoxCodeMirrors(ctx.painel);
    ed.syncLiveBoxCodeMirrors(ctx.painel);
    expect(ctx.chamadas.length).toBe(total);
  });

  it('ignora editores de abas ocultas', () => {
    const ed = makeEditor();
    const { painel, cmEl, chamadas } = montarPainelIA({ bodyBottom: 600, editorTop: 200, statusAltura: 30 });
    Object.defineProperty(cmEl, 'offsetParent', { get: () => null, configurable: true });

    ed.syncLiveBoxCodeMirrors(painel);
    expect(chamadas).toEqual([]);
  });

  it('persiste a ancoragem escolhida (nova instância mantém lado e ponta)', () => {
    const ed = makeEditor();
    ed.setViewOption('cssSidebar', true);
    ed.toggleViewPanelSide('cssSidebar');
    ed.toggleViewPanelSide('elementNavbar');

    document.body.innerHTML = '';
    const outra = makeEditor();
    expect(outra.getViewOption('cssSidebarRight')).toBe(true);
    expect(outra.getViewOption('elementNavbarBottom')).toBe(true);
    expect(document.getElementById('c2f-he-css-sidebar').style.right).toBe('0px');
  });
});

describe('html-editor.js — ação Substituir (req-106 §4)', () => {
  let Cls;

  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    try { window.localStorage.clear(); } catch (e) { /* noop */ }
    Cls = loadEngine();
  });

  function makeEditor() {
    const root = document.createElement('div');
    root.id = 'c2f-page-content';
    document.body.appendChild(root);
    return new Cls({ contentRoot: root });
  }

  it('o botão nasce oculto e só aparece com cópia guardada E elemento selecionado', () => {
    const ed = makeEditor();
    const botao = document.querySelector('.he-tb-replace');
    expect(botao).toBeTruthy();
    expect(botao.style.display).toBe('none');

    ed.contentRoot.innerHTML = '<div class="a">A</div><div class="b">B</div>';
    ed.selectElement(ed.contentRoot.querySelector('.a'));
    // Ainda sem cópia guardada.
    expect(botao.style.display).toBe('none');

    ed.copySelected();
    ed.selectElement(ed.contentRoot.querySelector('.b'));
    expect(botao.style.display).toBe('inline-flex');

    ed.clearSelection();
    ed.updatePasteButton();
    expect(botao.style.display).toBe('none');
  });

  it('troca o elemento selecionado pelo bloco copiado e seleciona o novo objeto', () => {
    const ed = makeEditor();
    ed.contentRoot.innerHTML = '<div class="origem">ORIGEM</div><div class="destino">DESTINO</div>';
    ed.selectElement(ed.contentRoot.querySelector('.origem'));
    ed.copySelected();

    const destino = ed.contentRoot.querySelector('.destino');
    ed.selectElement(destino);
    ed.replaceSelected();

    expect(ed.contentRoot.querySelector('.destino')).toBeNull();
    const copias = ed.contentRoot.querySelectorAll('.origem');
    expect(copias.length).toBe(2);
    // O bloco novo ocupa a POSIÇÃO do substituído e fica selecionado.
    expect(copias[1]).toBe(ed.contentRoot.children[1]);
    expect(ed.selectedElement).toBe(copias[1]);
  });

  it('não faz nada sem cópia guardada nem sem elemento selecionado', () => {
    const ed = makeEditor();
    ed.contentRoot.innerHTML = '<div class="alvo">ALVO</div>';
    const alvo = ed.contentRoot.querySelector('.alvo');

    ed.selectElement(alvo);
    ed.replaceSelected(); // sem clipboard
    expect(ed.contentRoot.querySelector('.alvo')).toBe(alvo);

    ed.copySelected();
    ed.clearSelection();
    ed.replaceSelected(); // sem seleção
    expect(ed.contentRoot.children.length).toBe(1);
  });

  it('renumera ids de widget do bloco substituído (não colide com o original da página)', () => {
    const ed = makeEditor();
    ed.contentRoot.innerHTML =
      '<div class="origem" data-c2f-widget-id="w1" data-widget-type="menus">M</div>' +
      '<div class="destino">D</div>';
    ed.selectElement(ed.contentRoot.querySelector('.origem'));
    ed.copySelected();
    ed.selectElement(ed.contentRoot.querySelector('.destino'));
    ed.replaceSelected();

    const ids = Array.from(ed.contentRoot.querySelectorAll('[data-c2f-widget-id]'))
      .map((el) => el.getAttribute('data-c2f-widget-id'));
    expect(ids.length).toBe(2);
    expect(ids[0]).not.toBe(ids[1]);
  });
});

describe('html-editor.js — expansões da Sidebar de CSS (req-106 §2 e §5)', () => {
  let Cls;

  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    try { window.localStorage.clear(); } catch (e) { /* noop */ }
    Cls = loadEngine();
  });

  function makeEditor() {
    const root = document.createElement('div');
    root.id = 'c2f-page-content';
    document.body.appendChild(root);
    return new Cls({ contentRoot: root });
  }

  function selecionar(ed, classe) {
    ed.contentRoot.innerHTML = '<section class="' + classe + '">X</section>';
    const alvo = ed.contentRoot.firstElementChild;
    ed.selectElement(alvo);
    return alvo;
  }

  it('separa classes Tailwind das classes customizadas do projeto', () => {
    const ed = makeEditor();
    selecionar(ed, 'p-4 text-lg card-destaque minha-secao');

    const tailwind = Array.from(ed.styler.querySelectorAll('.he-tw-tags b[data-class]'))
      .map((b) => b.getAttribute('data-class'));
    const custom = Array.from(ed.styler.querySelectorAll('.he-custom-tags b[data-class]'))
      .map((b) => b.getAttribute('data-class'));

    expect(tailwind).toContain('p-4');
    expect(tailwind).toContain('text-lg');
    expect(custom).toContain('card-destaque');
    expect(custom).toContain('minha-secao');
    expect(custom).not.toContain('p-4');
  });

  it('classifica valores arbitrários e variantes como Tailwind e agrupa por variante', () => {
    const ed = makeEditor();
    expect(ed.isTailwindClass('w-[350px]')).toBe(true);
    expect(ed.isTailwindClass('bg-[#1a2b3c]')).toBe(true);
    expect(ed.isTailwindClass('md:flex')).toBe(true);
    expect(ed.isTailwindClass('hover:underline')).toBe(true);
    expect(ed.isTailwindClass('rodape-institucional')).toBe(false);

    selecionar(ed, 'flex md:grid hover:underline');
    const variantes = Array.from(ed.styler.querySelectorAll('.he-tw-tags .he-tw-variant'))
      .map((v) => v.getAttribute('data-variant'));
    expect(variantes).toEqual(['', 'md:', 'hover:']);
  });

  it('remove classe customizada pelo "x" do bloco dedicado', () => {
    const ed = makeEditor();
    const alvo = selecionar(ed, 'p-4 card-destaque');
    const x = ed.styler.querySelector('.he-custom-tags b[data-class="card-destaque"]');
    x.dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    expect(alvo.classList.contains('card-destaque')).toBe(false);
    expect(alvo.classList.contains('p-4')).toBe(true);
  });

  it('autocomplete filtra o dicionário e aplica a classe escolhida', () => {
    const ed = makeEditor();
    const alvo = selecionar(ed, 'p-4');
    const input = ed.styler.querySelector('.he-class-input');
    const caixa = ed.styler.querySelector('.he-class-suggest');

    input.value = 'g';
    input.dispatchEvent(new window.Event('input'));
    expect(caixa.classList.contains('active')).toBe(false); // menos de 2 caracteres

    input.value = 'grid-cols-';
    input.dispatchEvent(new window.Event('input'));
    const itens = Array.from(caixa.querySelectorAll('.he-class-suggest-item'))
      .map((i) => i.getAttribute('data-class'));
    expect(caixa.classList.contains('active')).toBe(true);
    expect(itens.length).toBeGreaterThan(0);
    expect(itens.every((c) => c.indexOf('grid-cols-') !== -1)).toBe(true);

    ed.addClassFromSuggestion('grid-cols-3');
    expect(alvo.classList.contains('grid-cols-3')).toBe(true);
    expect(caixa.classList.contains('active')).toBe(false);
    expect(input.value).toBe('');
  });

  it('aplica valores manuais/digitais como estilo inline e sincroniza os campos', () => {
    const ed = makeEditor();
    const alvo = selecionar(ed, 'p-4');

    ed.applyManualStyle('width', '350px');
    ed.applyManualStyle('color', '#123456');
    ed.applyManualStyle('padding', '1.5rem');
    expect(alvo.style.getPropertyValue('width')).toBe('350px');
    expect(alvo.style.getPropertyValue('padding')).toBe('1.5rem');

    ed.renderStyler(alvo);
    const campoLargura = ed.styler.querySelector('[data-manual-prop="width"]');
    expect(campoLargura.value).toBe('350px');

    // Valor vazio remove a propriedade.
    ed.applyManualStyle('width', '  ');
    expect(alvo.style.getPropertyValue('width')).toBe('');
  });

  it('aplica e limpa o CSS inline customizado pelo campo textual', () => {
    const ed = makeEditor();
    const alvo = selecionar(ed, 'p-4');
    const campo = ed.styler.querySelector('.he-inline-css');

    campo.value = 'color:#123456; padding:12px';
    ed.styler.querySelector('.he-inline-css-apply').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    expect(alvo.getAttribute('style')).toContain('#123456');

    campo.value = '';
    ed.styler.querySelector('.he-inline-css-apply').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    expect(alvo.hasAttribute('style')).toBe(false);
  });

  // ===== Rodada 3 — CodeMirror no campo de CSS inline

  it('converte o campo de CSS inline em CodeMirror quando a Sidebar de CSS está ligada', () => {
    const ed = makeEditor();
    ed.setViewOption('cssSidebar', true);
    const alvo = selecionar(ed, 'p-4');

    expect(ed._inlineCssCm).toBeTruthy();
    // Idempotente: renderizar de novo não cria uma segunda instância.
    const primeira = ed._inlineCssCm;
    ed.renderStyler(alvo);
    expect(ed._inlineCssCm).toBe(primeira);
  });

  it('CSS inline no CodeMirror: lê o style do elemento e aplica de volta (inclusive no blur)', () => {
    const ed = makeEditor();
    ed.setViewOption('cssSidebar', true);
    const alvo = selecionar(ed, 'p-4');
    alvo.setAttribute('style', 'color: rgb(1, 2, 3);');
    ed.renderStyler(alvo);
    expect(ed._inlineCssCm.getValue()).toBe('color: rgb(1, 2, 3);');

    ed._inlineCssCm.setValue('padding:12px');
    ed.styler.querySelector('.he-inline-css-apply').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    expect(alvo.getAttribute('style')).toBe('padding:12px');

    // O `blur` do editor aplica sem depender do botão.
    ed._inlineCssCm.setValue('margin:4px');
    ed._inlineCssCm.__emit('blur');
    expect(alvo.getAttribute('style')).toBe('margin:4px');

    // Valor idêntico não empilha novo passo de undo.
    const antes = ed.undoStack.length;
    ed._inlineCssCm.__emit('blur');
    expect(ed.undoStack.length).toBe(antes);
  });

  it('mantém o textarea quando o CodeMirror não está disponível (degradação graciosa)', () => {
    const original = globalThis.CodeMirror;
    globalThis.CodeMirror = undefined;
    try {
      const ed = makeEditor();
      ed.setViewOption('cssSidebar', true);
      const alvo = selecionar(ed, 'p-4');
      expect(ed._inlineCssCm).toBeFalsy();

      const campo = ed.styler.querySelector('.he-inline-css');
      campo.value = 'color:#abcdef';
      ed.styler.querySelector('.he-inline-css-apply').dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
      expect(alvo.getAttribute('style')).toBe('color:#abcdef');
    } finally {
      globalThis.CodeMirror = original;
    }
  });

  it('lista os estilos computados do elemento selecionado', () => {
    const ed = makeEditor();
    const alvo = selecionar(ed, 'p-4');
    alvo.style.setProperty('display', 'flex');
    ed.renderStyler(alvo);

    const props = Array.from(ed.styler.querySelectorAll('.he-computed .he-computed-row'))
      .map((l) => l.getAttribute('data-prop'));
    expect(props).toContain('display');
    const linhaDisplay = ed.styler.querySelector('.he-computed .he-computed-row[data-prop="display"] span');
    expect(linhaDisplay.textContent).toBe('flex');
  });

  it('expande o dicionário de sugestões (grid, flex, arbitrários e variantes)', () => {
    const ed = makeEditor();
    const sugestoes = ed.tailwindSuggestions();

    ['grid-cols-3', 'col-span-2', 'flex-1', 'items-baseline', 'gap-x-4', 'shadow-2xl',
      'opacity-75', 'z-40', 'border-dashed', 'w-[350px]', 'bg-[#1a2b3c]', 'md:hidden', 'hover:underline']
      .forEach((c) => expect(sugestoes).toContain(c));

    // O datalist do documento é alimentado pela mesma lista.
    const datalist = document.getElementById('html-editor-tw-classes');
    expect(datalist.querySelectorAll('option').length).toBe(sugestoes.length);
  });
});
