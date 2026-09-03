import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

/**
 * req-096 (BATCH-096) — mídia/documento embutido no motor visual `html-editor.js`:
 *  - invólucro atômico `.conn2flow-embed-wrapper` (badge + escudo + alças) sobre
 *    `object/iframe/embed/video/audio` e sobre o contêiner do PDF.js;
 *  - reversão limpa no save (`getCleanHtml`) e nos snapshots de undo;
 *  - geração dos 3 motores de PDF (nativo+fallback, PDF.js e Google Viewer).
 *
 * req-097 (BATCH-097) — correções de homologação: destino da inserção sem alvo (conteúdo × layout),
 * isolamento dos elementos de sistema do Live Editor, idempotência da extração (leitor PDF.js e
 * resíduos de invólucro), escudo exclusivo do modo de edição, z-index do seletor de arquivos e os
 * 5 tipos de embed do painel "+".
 *
 * Carrega o arquivo REAL (o motor é `$(document).ready(...)` e expõe `window.HtmlEditorClass`).
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

describe('html-editor.js — embeds e motores de PDF (BATCH-096)', () => {
  let Cls;

  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    Cls = loadEngine();
  });

  function makeEditor(html, opts) {
    const root = document.createElement('div');
    root.id = 'c2f-page-content';
    if (html) root.innerHTML = html;
    document.body.appendChild(root);
    return new Cls(Object.assign({ contentRoot: root }, opts || {}));
  }

  // ===== Invólucro atômico e proteção de eventos

  it('envolve object/iframe/embed/video/audio em .conn2flow-embed-wrapper com badge, escudo e 4 alças', () => {
    const ed = makeEditor(
      '<object data="/docs/a.pdf" type="application/pdf"></object>' +
      '<iframe src="/pagina-interna/"></iframe>' +
      '<embed src="/x.swf">' +
      '<video src="/v.mp4"></video>' +
      '<audio src="/a.mp3"></audio>'
    );

    const wrappers = ed.contentRoot.querySelectorAll('.conn2flow-embed-wrapper');
    expect(wrappers.length).toBe(5);
    wrappers.forEach((w) => {
      expect(w.querySelector('.conn2flow-embed-label')).toBeTruthy();
      expect(w.querySelector('.c2f-embed-shield')).toBeTruthy();
      expect(w.querySelectorAll('.c2f-embed-handle').length).toBe(4);
      expect(w.querySelector('.conn2flow-embed-inner').firstElementChild).toBeTruthy();
    });
    // Badge identifica o tipo detectado (PDF via type/extensão, mídia via tag).
    expect(ed.contentRoot.querySelector('.conn2flow-embed-wrapper .conn2flow-embed-label').textContent)
      .toBe('Objeto PDF');
    expect(ed.contentRoot.querySelectorAll('.conn2flow-embed-wrapper')[3]
      .getAttribute('data-c2f-embed-kind')).toBe('video');
  });

  it('trata o invólucro como bloco atômico: escudo, alça e embed resolvem para o wrapper', () => {
    const ed = makeEditor('<iframe src="/pagina-interna/"></iframe>');
    const wrapper = ed.contentRoot.querySelector('.conn2flow-embed-wrapper');
    const shield = wrapper.querySelector('.c2f-embed-shield');
    const handle = wrapper.querySelector('.c2f-embed-handle');
    const iframe = wrapper.querySelector('iframe');

    expect(ed.resolveEditable(shield)).toBe(wrapper);
    expect(ed.resolveEditable(handle)).toBe(wrapper);
    expect(ed.resolveEditable(iframe)).toBe(wrapper);
    expect(ed.getEditType(wrapper)).toBe('embed');
  });

  it('wrapEmbeds é idempotente (não aninha invólucros ao rodar de novo)', () => {
    const ed = makeEditor('<iframe src="/pagina-interna/"></iframe>');
    ed.wrapEmbeds();
    ed.wrapEmbeds();
    expect(ed.contentRoot.querySelectorAll('.conn2flow-embed-wrapper').length).toBe(1);
  });

  it('não envolve embeds que pertencem à UI do editor (ex.: iframe do seletor de arquivos)', () => {
    const ed = makeEditor('');
    const overlay = document.createElement('div');
    overlay.id = 'c2f-he-imagepick-overlay';
    overlay.innerHTML = '<iframe class="c2f-he-ip-frame"></iframe>';
    ed.contentRoot.appendChild(overlay);
    ed.wrapEmbeds();
    expect(ed.contentRoot.querySelectorAll('.conn2flow-embed-wrapper').length).toBe(0);
  });

  it('não envolve embeds dentro de blocos dinâmicos (widget renderizado / caixa de variável)', () => {
    const ed = makeEditor(
      '<div class="conn2flow-widget-wrapper" data-widget-type="galleries" data-widget-slug="x">' +
      '<div class="conn2flow-widget-inner"><iframe src="/embutido-do-widget/"></iframe></div></div>' +
      '<nav data-c2f-widget-id="W1" data-c2f-widget-root="1"><video src="/w.mp4"></video></nav>' +
      '<span class="c2f-dyn-box"><audio src="/var.mp3"></audio></span>' +
      '<iframe src="/conteudo-proprio/"></iframe>'
    );

    const wrappers = ed.contentRoot.querySelectorAll('.conn2flow-embed-wrapper');
    // Só o embed que é conteúdo próprio da página recebe invólucro.
    expect(wrappers.length).toBe(1);
    expect(wrappers[0].querySelector('iframe').getAttribute('src')).toBe('/conteudo-proprio/');
    // O widget continua sendo o bloco atômico do embed interno.
    const iframeDoWidget = ed.contentRoot.querySelector('iframe[src="/embutido-do-widget/"]');
    expect(ed.resolveEditable(iframeDoWidget).classList.contains('conn2flow-widget-wrapper')).toBe(true);
  });

  // ===== Reversão no save

  it('getCleanHtml remove o invólucro e persiste apenas a tag limpa', () => {
    const ed = makeEditor('<p>antes</p><object data="/docs/a.pdf" type="application/pdf"></object><p>depois</p>');
    expect(ed.contentRoot.querySelector('.conn2flow-embed-wrapper')).toBeTruthy();

    const clean = ed.getCleanHtml();
    expect(clean).not.toContain('conn2flow-embed-wrapper');
    expect(clean).not.toContain('c2f-embed-shield');
    expect(clean).not.toContain('c2f-embed-handle');
    expect(clean).not.toContain('conn2flow-embed-label');
    expect(clean).toContain('<object data="/docs/a.pdf" type="application/pdf"></object>');
    // O DOM vivo continua protegido (o unwrap acontece só no clone extraído).
    expect(ed.contentRoot.querySelector('.conn2flow-embed-wrapper')).toBeTruthy();
  });

  it('snapshot do histórico guarda a tag limpa e applyState reconstrói o invólucro', () => {
    const ed = makeEditor('<video src="/v.mp4"></video>');
    const snap = ed.captureSnapshot();
    expect(snap.html).not.toContain('conn2flow-embed-wrapper');
    expect(snap.html).toContain('<video src="/v.mp4">');

    ed.applyState(snap.html);
    const wrapper = ed.contentRoot.querySelector('.conn2flow-embed-wrapper');
    expect(wrapper).toBeTruthy();
    expect(wrapper.querySelector('video')).toBeTruthy();
  });

  it('extrai mídia por template inerte, sem clonar diretamente nós que podem disparar rede', () => {
    const ed = makeEditor('<img src="[[pagina#url-raiz]]images/logo.png"><video src="[[pagina#url-raiz]]video.mp4"></video>');
    const clean = ed.getCleanHtml();

    expect(clean).toContain('src="[[pagina#url-raiz]]images/logo.png"');
    expect(clean).toContain('src="[[pagina#url-raiz]]video.mp4"');
  });

  // ===== Detecção de tipo

  it('detecta os tipos de PDF (nativo, PDF.js e Google Viewer) e os tipos de mídia', () => {
    const ed = makeEditor('');
    const make = (html) => { const d = document.createElement('div'); d.innerHTML = html; return d.firstElementChild; };

    expect(ed.embedKind(make('<object data="/a.pdf" type="application/pdf"></object>'))).toBe('pdf-native');
    expect(ed.embedKind(make('<div class="conn2flow-pdfjs" data-pdf-src="/a.pdf"></div>'))).toBe('pdfjs');
    expect(ed.embedKind(make('<iframe src="https://docs.google.com/viewer?url=x&embedded=true"></iframe>')))
      .toBe('pdf-google');
    expect(ed.embedKind(make('<iframe src="/manual.pdf"></iframe>'))).toBe('pdf-iframe');
    expect(ed.embedKind(make('<iframe src="/pagina-interna/"></iframe>'))).toBe('iframe');
    expect(ed.embedKind(make('<audio src="/a.mp3"></audio>'))).toBe('audio');
    expect(ed.isPdfUrl('/docs/manual.PDF?v=2')).toBe(true);
    expect(ed.isPdfUrl('/docs/manual.mp4')).toBe(false);
  });

  // ===== Os 3 motores de exibição de PDF

  it('Opção A — objeto nativo com fallback amigável e params internos', () => {
    const ed = makeEditor('');
    const html = ed.buildPdfNativeMarkup({
      src: '/docs/a.pdf', width: '100', widthUnit: '%', height: '600', heightUnit: 'px',
      title: 'Manual', params: [{ name: 'zoom', value: '100' }], engine: 'native'
    });

    expect(html).toContain('<object');
    expect(html).toContain('data="/docs/a.pdf"');
    expect(html).toContain('type="application/pdf"');
    expect(html).toContain('style="width:100%;height:600px;position:relative;z-index:1"');
    expect(html).toContain('title="Manual"');
    expect(html).toContain('<param name="zoom" value="100">');
    expect(html).toContain('conn2flow-pdf-fallback');
    expect(html).toContain('href="/docs/a.pdf"');
    expect(html).toContain('</object>');
  });

  it('Opção A — HTML de fallback customizado substitui a mensagem padrão', () => {
    const ed = makeEditor('');
    const html = ed.buildPdfNativeMarkup({
      src: '/docs/a.pdf', width: '', height: '', params: [],
      fallbackHtml: '<p>Baixe o arquivo</p>'
    });
    expect(html).toContain('<div class="conn2flow-pdf-fallback"><p>Baixe o arquivo</p></div>');
    expect(html).not.toContain('conn2flow-pdf-fallback-btn');
  });

  it('Opção B — contêiner do PDF.js com todos os parâmetros do leitor', () => {
    const ed = makeEditor('');
    const html = ed.buildPdfJsMarkup({
      src: '/docs/a.pdf', width: '100', widthUnit: '%', height: '80', heightUnit: 'vh',
      pdfZoom: 'page-fit', pdfToolbar: false, pdfPage: '3', pdfScroll: 'page', title: 'Manual'
    });

    expect(html).toContain('class="conn2flow-pdfjs"');
    expect(html).toContain('data-pdf-src="/docs/a.pdf"');
    expect(html).toContain('data-pdf-zoom="page-fit"');
    expect(html).toContain('data-pdf-toolbar="0"');
    expect(html).toContain('data-pdf-page="3"');
    expect(html).toContain('data-pdf-scroll="page"');
    expect(html).toContain('style="width:100%;height:80vh;position:relative;z-index:1"');
  });

  it('Opção C — iframe do Google Viewer com a URL absoluta codificada', () => {
    const ed = makeEditor('');
    ed.absoluteUrl = (u) => (/^https?:/i.test(u) ? u : 'https://site.test' + u);
    const html = ed.buildPdfGoogleMarkup({
      src: '/docs/a b.pdf', width: '100', widthUnit: '%', height: '600', heightUnit: 'px',
      allowfullscreen: true
    });

    expect(html).toContain('<iframe');
    expect(html).toContain('class="conn2flow-pdf-google"');
    expect(html).toContain('src="https://docs.google.com/viewer?url=' +
      encodeURIComponent('https://site.test/docs/a b.pdf') + '&amp;embedded=true"');
    expect(html).toContain('allowfullscreen');
  });

  it('buildEmbedMarkup roteia pelo motor escolhido e mantém mídia fora do fluxo de PDF', () => {
    const ed = makeEditor('');
    const pdf = { kind: 'pdf-native', src: '/docs/a.pdf', width: '', height: '', params: [] };

    expect(ed.buildEmbedMarkup(Object.assign({}, pdf, { engine: 'native' }))).toContain('type="application/pdf"');
    expect(ed.buildEmbedMarkup(Object.assign({}, pdf, { engine: 'pdfjs' }))).toContain('class="conn2flow-pdfjs"');
    expect(ed.buildEmbedMarkup(Object.assign({}, pdf, { engine: 'google' }))).toContain('docs.google.com/viewer');

    const video = ed.buildEmbedMarkup({
      kind: 'video', tag: 'video', src: '/v.mp4', engine: 'native', controls: true, loop: true,
      muted: true, poster: '/p.jpg', width: '640', widthUnit: 'px', height: '360', heightUnit: 'px'
    });
    expect(video).toContain('<video');
    expect(video).toContain('src="/v.mp4"');
    expect(video).toContain('controls');
    expect(video).toContain('loop');
    expect(video).toContain('muted');
    expect(video).toContain('poster="/p.jpg"');
    expect(video).not.toContain('application/pdf');
  });

  // ===== Modal em 4 abas

  it('openEmbedModal monta as 4 abas, popula os campos e reconhece o modal como UI do editor', () => {
    const ed = makeEditor('<object data="/docs/a.pdf" type="application/pdf" style="width:100%;height:500px" ' +
      'title="Manual"><param name="zoom" value="100"></object>');
    const wrapper = ed.contentRoot.querySelector('.conn2flow-embed-wrapper');

    ed.openEmbedModal(wrapper);
    const modal = document.getElementById('c2f-he-embed-modal');
    expect(modal).toBeTruthy();
    expect(modal.style.display).toBe('block');
    expect(modal.querySelectorAll('.c2f-he-embed-tab').length).toBe(4);
    expect(modal.querySelectorAll('.c2f-he-embed-pane').length).toBe(4);
    expect(modal.querySelector('#c2f-he-embed-src').value).toBe('/docs/a.pdf');
    expect(modal.querySelector('#c2f-he-embed-width').value).toBe('100');
    expect(modal.querySelector('#c2f-he-embed-width-unit').value).toBe('%');
    expect(modal.querySelector('#c2f-he-embed-height').value).toBe('500');
    expect(modal.querySelector('#c2f-he-embed-title').value).toBe('Manual');
    expect(modal.querySelectorAll('.c2f-he-embed-param-row').length).toBe(1);
    expect(modal.querySelector('input[name="c2f-he-embed-engine"][value="native"]').checked).toBe(true);
    // Cliques no modal não podem vazar para a seleção do conteúdo atrás.
    expect(ed.isEditorOwned(modal.querySelector('#c2f-he-embed-src'))).toBe(true);

    ed.showEmbedTab('engine');
    expect(modal.querySelector('[data-pane="engine"]').style.display).toBe('block');
    expect(modal.querySelector('[data-pane="general"]').style.display).toBe('none');
  });

  it('applyEmbedModal troca o motor do PDF preservando fonte e dimensões, e re-envolve o novo embed', () => {
    const ed = makeEditor('<object data="/docs/a.pdf" type="application/pdf" style="width:100%;height:500px"></object>');
    const wrapper = ed.contentRoot.querySelector('.conn2flow-embed-wrapper');

    ed.openEmbedModal(wrapper);
    const modal = document.getElementById('c2f-he-embed-modal');
    modal.querySelector('input[name="c2f-he-embed-engine"][value="pdfjs"]').checked = true;
    modal.querySelector('#c2f-he-embed-pdfjs-page').value = '2';
    ed.applyEmbedModal();

    const novoWrapper = ed.contentRoot.querySelector('.conn2flow-embed-wrapper');
    expect(novoWrapper).toBeTruthy();
    expect(novoWrapper.getAttribute('data-c2f-embed-kind')).toBe('pdfjs');
    const viewer = novoWrapper.querySelector('.conn2flow-pdfjs');
    expect(viewer).toBeTruthy();
    expect(viewer.getAttribute('data-pdf-src')).toBe('/docs/a.pdf');
    expect(viewer.getAttribute('data-pdf-page')).toBe('2');
    expect(viewer.style.width).toBe('100%');
    expect(viewer.style.height).toBe('500px');
    expect(document.getElementById('c2f-he-embed-modal').style.display).toBe('none');

    // O save continua limpo depois da troca de motor.
    const clean = ed.getCleanHtml();
    expect(clean).toContain('class="conn2flow-pdfjs"');
    expect(clean).not.toContain('conn2flow-embed-wrapper');
  });

  it('aviso da aba de motores só aparece quando a fonte não é PDF', () => {
    const ed = makeEditor('<iframe src="/pagina-interna/"></iframe>');
    const wrapper = ed.contentRoot.querySelector('.conn2flow-embed-wrapper');
    ed.openEmbedModal(wrapper);
    const modal = document.getElementById('c2f-he-embed-modal');
    expect(modal.querySelector('#c2f-he-embed-engine-warning').style.display).toBe('block');

    modal.querySelector('#c2f-he-embed-src').value = '/docs/manual.pdf';
    ed.syncEmbedEngineAvailability();
    expect(modal.querySelector('#c2f-he-embed-engine-warning').style.display).toBe('none');
  });

  it('invólucro acompanha o embed: block com largura fluida, fit-content com largura fixa em px', () => {
    const ed = makeEditor(
      '<object data="/a.pdf" type="application/pdf" style="width:100%;height:500px"></object>' +
      '<iframe src="/pagina-interna/" style="width:640px;height:360px"></iframe>'
    );
    const [fluido, fixo] = ed.contentRoot.querySelectorAll('.conn2flow-embed-wrapper');

    expect(fluido.style.display).toBe('block');
    expect(fluido.style.width).toBe('');
    expect(fixo.style.display).toBe('inline-block');
    expect(fixo.style.width).toBe('fit-content');
  });

  it('applyEmbedSize grava as dimensões no style e remove os atributos legados width/height', () => {
    const ed = makeEditor('<iframe src="/pagina-interna/" width="300" height="200"></iframe>');
    const iframe = ed.contentRoot.querySelector('iframe');
    ed.applyEmbedSize(iframe, '640px', '480px');
    expect(iframe.hasAttribute('width')).toBe(false);
    expect(iframe.hasAttribute('height')).toBe(false);
    expect(iframe.style.width).toBe('640px');
    expect(iframe.style.height).toBe('480px');
  });

  // ===== req-097 (BATCH-097) — correções de homologação

  // No Live Editor a raiz editável é o LAYOUT (`#c2f-layout-root`), que contém `#c2f-page-content`.
  function makeLiveEditor(conteudoHtml) {
    const layout = document.createElement('div');
    layout.id = 'c2f-layout-root';
    layout.innerHTML = '<header>cabeçalho do layout</header>' +
      '<div id="c2f-page-content">' + (conteudoHtml || '') + '</div>' +
      '<footer>rodapé do layout</footer>';
    document.body.appendChild(layout);
    return new Cls({ contentRoot: layout, raiz: 'https://site.test/' });
  }

  it('Fix 1: inserção sem alvo vai para o CONTEÚDO da página, não para a raiz do layout', () => {
    const ed = makeLiveEditor('<p>parágrafo</p>');
    const conteudo = ed.contentRoot.querySelector('#c2f-page-content');

    expect(ed.insertionRoot()).toBe(conteudo);

    ed.selectedElement = null;
    ed.insertCustomHtml('<object data="/docs/a.pdf" type="application/pdf"></object>');

    // O embed nasce dentro do conteúdo (senão seria salvo em layouts.html e apareceria em todas as
    // páginas e dentro do iframe da Editbar).
    expect(conteudo.querySelector('object')).toBeTruthy();
    expect(ed.contentRoot.querySelector(':scope > object')).toBeNull();
    expect(ed.contentRoot.querySelectorAll('object').length).toBe(1);
  });

  it('Fix 1: iframe da Editbar e preview de dispositivo não são envolvidos nem extraídos', () => {
    const ed = makeLiveEditor('<p>conteúdo</p>');
    const conteudo = ed.contentRoot.querySelector('#c2f-page-content');
    const toolbar = document.createElement('iframe');
    toolbar.id = 'c2f-site-toolbar';
    conteudo.appendChild(toolbar);
    const preview = document.createElement('div');
    preview.id = 'c2f-device-preview';
    preview.innerHTML = '<iframe src="/preview/"></iframe>';
    conteudo.appendChild(preview);

    ed.wrapEmbeds();
    expect(ed.contentRoot.querySelectorAll('.conn2flow-embed-wrapper').length).toBe(0);
    expect(ed.isEditorOwned(toolbar)).toBe(true);
    expect(ed.resolveEditable(toolbar)).toBeNull();

    const clean = ed.getCleanHtml();
    expect(clean).not.toContain('c2f-site-toolbar');
    expect(clean).not.toContain('c2f-device-preview');
  });

  it('Fix 1: extração é idempotente — leitor PDF.js renderizado volta ao contêiner vazio', () => {
    const ed = makeEditor('<div class="conn2flow-pdfjs" data-pdf-src="/a.pdf" data-pdf-zoom="page-width"></div>');
    const host = ed.contentRoot.querySelector('.conn2flow-pdfjs');
    // Simula o runtime: marca como pronto e desenha a toolbar/canvas dentro do contêiner.
    host.setAttribute('data-c2f-pdfjs-ready', '1');
    host.innerHTML = '<div class="conn2flow-pdfjs-toolbar">x</div><div class="conn2flow-pdfjs-pages"><canvas></canvas></div>';

    const clean = ed.getCleanHtml();
    expect(clean).toContain('data-pdf-src="/a.pdf"');
    expect(clean).not.toContain('data-c2f-pdfjs-ready');
    expect(clean).not.toContain('canvas');
    expect(clean).not.toContain('conn2flow-pdfjs-toolbar');
    // O leitor segue renderizado no DOM vivo (a limpeza acontece só no clone extraído).
    expect(host.querySelector('canvas')).toBeTruthy();
  });

  it('Fix 1: resíduos de invólucro persistidos por versão anterior são limpos no save', () => {
    const ed = makeEditor(
      '<div class="conn2flow-embed-wrapper"><div class="conn2flow-embed-label">Objeto PDF</div>' +
      '<div class="conn2flow-embed-inner"><object data="/a.pdf" type="application/pdf"></object></div>' +
      '<div class="c2f-embed-shield"></div><span class="c2f-embed-handle c2f-embed-handle-se"></span></div>'
    );

    const clean = ed.getCleanHtml();
    expect(clean).toContain('<object data="/a.pdf" type="application/pdf"></object>');
    expect(clean).not.toContain('conn2flow-embed-wrapper');
    expect(clean).not.toContain('c2f-embed-shield');
    expect(clean).not.toContain('c2f-embed-handle');
    expect(clean).not.toContain('conn2flow-embed-label');
  });

  it('Fix 2: disable() devolve o embed sem escudo ao DOM vivo e enable() reconstrói o invólucro', () => {
    const ed = makeEditor('<object data="/a.pdf" type="application/pdf"></object>');
    expect(ed.contentRoot.querySelector('.c2f-embed-shield')).toBeTruthy();

    ed.disable();
    // Fora do modo de edição o PDF volta a receber o ponteiro (sem escudo e sem invólucro).
    expect(ed.contentRoot.querySelector('.conn2flow-embed-wrapper')).toBeNull();
    expect(ed.contentRoot.querySelector('.c2f-embed-shield')).toBeNull();
    expect(ed.contentRoot.querySelector('object')).toBeTruthy();

    ed.enable();
    expect(ed.contentRoot.querySelector('.conn2flow-embed-wrapper')).toBeTruthy();
    expect(ed.contentRoot.querySelector('.c2f-embed-shield')).toBeTruthy();
  });

  it('Fix 2: markup gerado nasce posicionado (não fica sob camadas absolutas do template)', () => {
    const ed = makeEditor('');
    const base = { src: '/docs/a.pdf', width: '100', widthUnit: '%', height: '600', heightUnit: 'px', params: [] };

    expect(ed.buildPdfNativeMarkup(base)).toContain('position:relative;z-index:1');
    expect(ed.buildPdfJsMarkup(base)).toContain('position:relative;z-index:1');
    // Estilos extras do usuário vencem: nada é duplicado quando ele define os seus.
    const custom = ed.buildPdfNativeMarkup(Object.assign({}, base, { styleExtra: 'position:absolute;z-index:5' }));
    expect(custom).toContain('position:absolute;z-index:5');
    expect(custom).not.toContain('position:relative');
  });

  it('Fix 3: o modal de embed fica abaixo do overlay do seletor de arquivos', () => {
    const ed = makeEditor('<iframe src="/pagina-interna/"></iframe>');
    const wrapper = ed.contentRoot.querySelector('.conn2flow-embed-wrapper');
    ed.openEmbedModal(wrapper);
    ed.openFilePickerOverlay('https://site.test/');

    const modalZ = parseInt(document.getElementById('c2f-he-embed-modal').style.zIndex, 10);
    const pickerZ = parseInt(document.getElementById('c2f-he-imagepick-overlay').style.zIndex, 10);
    expect(pickerZ).toBeGreaterThan(modalZ);
  });

  it('Item 6: buildElement cria os 5 tipos de embed e o painel insere já envolvido', () => {
    const ed = makeEditor('');
    ['object', 'iframe', 'embed', 'video', 'audio'].forEach((tipo) => {
      const el = ed.buildElement(tipo);
      expect(el.tagName.toLowerCase(), tipo).toBe(tipo);
      expect(ed.config.embedTags.includes(tipo)).toBe(true);
    });
    expect(ed.buildElement('object').getAttribute('type')).toBe('application/pdf');
    expect(ed.buildElement('video').hasAttribute('controls')).toBe(true);
    expect(ed.buildElement('audio').hasAttribute('controls')).toBe(true);

    // Inserido no conteúdo, o elemento ganha invólucro no ciclo de mutação.
    ed.contentRoot.appendChild(ed.buildElement('iframe'));
    ed.wrapEmbeds();
    const wrapper = ed.contentRoot.querySelector('.conn2flow-embed-wrapper');
    expect(wrapper).toBeTruthy();
    expect(wrapper.getAttribute('data-c2f-embed-tag')).toBe('iframe');
  });

  // ===== BATCH-100 — mídia: URL de arquivo e dimensionamento por tipo

  it('urlDeArquivo codifica o caminho para o atributo src (espaço vira %20, sem duplo encode)', () => {
    const ed = makeEditor('');
    expect(ed.urlDeArquivo('files/videos/2026-07-30 16-03-46.mp4', 'https://site.test/'))
      .toBe('https://site.test/files/videos/2026-07-30%2016-03-46.mp4');
    // Caminho já codificado não é re-codificado.
    expect(ed.urlDeArquivo('files/a%20b.pdf', 'https://site.test/'))
      .toBe('https://site.test/files/a%20b.pdf');
    // URL absoluta é preservada (só normalizada).
    expect(ed.urlDeArquivo('https://cdn.test/x y.mp4', 'https://site.test/'))
      .toBe('https://cdn.test/x%20y.mp4');
  });

  it('áudio não recebe altura fixa (o player tem altura própria)', () => {
    const ed = makeEditor('<audio src="/a.ogg" controls></audio>');
    const wrapper = ed.contentRoot.querySelector('.conn2flow-embed-wrapper');

    // Default de leitura: sem altura para áudio; vídeo e documento têm a sua.
    expect(ed.embedReadConfig(wrapper).height).toBe('');
    expect(ed.embedDefaultSize('video').height).toBe('360');
    expect(ed.embedDefaultSize('object').height).toBe('600');

    // Mesmo recebendo altura na configuração, o markup de áudio sai sem height.
    const markup = ed.buildMediaMarkup({
      tag: 'audio', kind: 'audio', src: '/a.ogg', controls: true,
      width: '100', widthUnit: '%', height: '600', heightUnit: 'px'
    });
    expect(markup).toContain('style="width:100%');
    expect(markup).not.toContain('height:600px');

    // O redimensionamento por alça ajusta só a largura.
    const audio = wrapper.querySelector('audio');
    ed.applyEmbedSize(audio, '480px', '200px');
    expect(audio.style.width).toBe('480px');
    expect(audio.style.height).toBe('');
  });

  // ===== BATCH-101 — embed sem arquivo escolhido não emite atributo de fonte vazio

  it('fonte vazia OMITE o atributo (src="" faz o navegador carregar a própria página como mídia)', () => {
    const ed = makeEditor('');
    const vazio = { src: '', width: '100', widthUnit: '%', params: [] };

    // Mídia: sem src (o player de áudio com src="" entra em erro e o Chrome o colapsa).
    const audio = ed.buildMediaMarkup(Object.assign({ tag: 'audio', kind: 'audio', controls: true }, vazio));
    expect(audio).not.toContain('src=');
    expect(audio).toContain('<audio');
    expect(audio).toContain('controls');

    // Documento nativo: sem data.
    const objeto = ed.buildPdfNativeMarkup(Object.assign({}, vazio, { height: '600', heightUnit: 'px' }));
    expect(objeto).not.toContain('data=""');
    expect(objeto).toContain('type="application/pdf"');

    // PDF.js: sem data-pdf-src.
    expect(ed.buildPdfJsMarkup(vazio)).not.toContain('data-pdf-src=""');

    // Google Viewer: sem src apontando para um viewer com url vazia.
    const google = ed.buildPdfGoogleMarkup(vazio);
    expect(google).not.toContain('src=');
    expect(google).toContain('conn2flow-pdf-google');
  });

  it('com fonte preenchida os atributos continuam sendo emitidos', () => {
    const ed = makeEditor('');
    const cfg = { src: '/docs/a.pdf', width: '100', widthUnit: '%', height: '600', heightUnit: 'px', params: [] };

    expect(ed.buildPdfNativeMarkup(cfg)).toContain('data="/docs/a.pdf"');
    expect(ed.buildPdfJsMarkup(cfg)).toContain('data-pdf-src="/docs/a.pdf"');
    expect(ed.buildPdfGoogleMarkup(cfg)).toContain('docs.google.com/viewer?url=');
    expect(ed.buildMediaMarkup(Object.assign({ tag: 'video', kind: 'video' }, cfg))).toContain('src="/docs/a.pdf"');
  });

  it('traduz badges e o modal quando gestor.language começa com en', () => {
    window.gestor = { language: 'en-us' };
    try {
      const ed = makeEditor('<object data="/docs/a.pdf" type="application/pdf"></object>');
      const wrapper = ed.contentRoot.querySelector('.conn2flow-embed-wrapper');
      expect(wrapper.querySelector('.conn2flow-embed-label').textContent).toBe('PDF Object');

      ed.openEmbedModal(wrapper);
      const modal = document.getElementById('c2f-he-embed-modal');
      expect(modal.textContent).toContain('Edit embedded element');
      expect(modal.textContent).toContain('Display Engine (PDF)');
      expect(modal.textContent).toContain('Native object with fallback');
      expect(modal.querySelector('.c2f-he-embed-apply').textContent).toBe('Apply');
    } finally {
      delete window.gestor;
    }
  });
});
