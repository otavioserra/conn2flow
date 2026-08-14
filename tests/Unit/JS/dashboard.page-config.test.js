import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import fs from 'node:fs';

/**
 * req-110 (BATCH-110) — painel "Configurações da Página" da Editbar.
 *
 * O painel vive na página HOSPEDEIRA (`dashboard.toolbar.js`), e não no iframe da barra, porque é
 * ali que o seletor de arquivos pode ser sobreposto ao conteúdo — dentro do iframe da Editbar ele
 * ficaria confinado à altura da barra.
 */
describe('Live Editor — painel de Configurações da Página (req-110)', () => {
  let T;
  let respostas;
  let requisicoes;

  function carregarToolbar() {
    let code = fs.readFileSync('gestor/modulos/dashboard/dashboard.toolbar.js', 'utf8');
    const idx = code.lastIndexOf('})();');
    if (idx < 0) throw new Error('IIFE close não encontrado em dashboard.toolbar.js');

    const hook = 'window.__c2fPageConfig={' +
      'open:function(x,y,id){return openPageConfigPanel(x,y,id);},' +
      'panel:function(){return pageConfigPanel;},' +
      'picker:function(){return pageConfigPickerOverlay;},' +
      'openPicker:function(){return openPageConfigPicker();},' +
      'aplicarSelecao:function(p){return aplicarSelecaoDeImagem(p);},' +
      'imagem:function(){return pageConfigImagem;},' +
      'dismiss:function(){return dismissHostPanels();},' +
      'versaoMotor:function(){return versaoHtmlEditor();}};\n';

    code = code.slice(0, idx) + hook + code.slice(idx);
    (0, eval)(code);

    T = window.__c2fPageConfig;
    if (!T) throw new Error('Hook de teste __c2fPageConfig não foi inicializado');
  }

  beforeEach(() => {
    requisicoes = [];
    respostas = {
      'site-toolbar-page-config': {
        status: 'Ok',
        data: {
          nome: 'Sobre nós',
          caminho: 'sobre-nos/',
          og_titulo: 'Título social atual',
          og_descricao: 'Descrição atual',
          meta_descricao: 'Descrição para o Google',
          meta_keywords: 'foto, ensaio',
          imagem_destaque: 'imagens/banner.jpg',
          imagem_url: '/imagens/banner.jpg'
        }
      },
      'site-toolbar-page-config-save': { status: 'Ok', data: {} }
    };

    window.gestor = { raiz: '/', language: 'pt-br' };

    global.fetch = vi.fn((url, init) => {
      requisicoes.push({ url: String(url), init: init || null });
      // Casa pelo valor EXATO de ajaxOpcao: `site-toolbar-page-config` é prefixo de
      // `site-toolbar-page-config-save`, e um match por substring devolveria a resposta errada.
      const m = /ajaxOpcao=([^&]+)/.exec(String(url));
      const chave = m ? decodeURIComponent(m[1]) : '';
      return Promise.resolve({ json: () => Promise.resolve(respostas[chave] || null) });
    });

    carregarToolbar();
  });

  afterEach(() => {
    document.body.innerHTML = '';
    delete window.__c2fPageConfig;
    delete window.gestor;
    vi.restoreAllMocks();
  });

  const aguardar = () => new Promise((r) => setTimeout(r, 0));

  it('abre o painel carregando os metadados atuais da página', async () => {
    T.open(100, 40, 'sobre-nos');
    await aguardar();

    const painel = T.panel();
    expect(painel.style.display).toBe('block');
    expect(painel.querySelector('#c2f-pc-og-titulo').value).toBe('Título social atual');
    expect(painel.querySelector('#c2f-pc-og-descricao').value).toBe('Descrição atual');
    expect(painel.innerHTML).toContain('sobre-nos/');
    expect(requisicoes[0].url).toContain('ajaxOpcao=site-toolbar-page-config');
    expect(requisicoes[0].url).toContain('page_id=sobre-nos');
  });

  it('mostra a imagem de destaque quando existe e o botão de remover', async () => {
    T.open(100, 40, 'sobre-nos');
    await aguardar();

    const painel = T.panel();
    expect(painel.querySelector('.c2f-page-config-image img')).toBeTruthy();
    expect(painel.querySelector('[data-page-config-action="clear"]').style.display).not.toBe('none');
  });

  it('sem imagem avisa que o compartilhamento usa o padrão do site', async () => {
    respostas['site-toolbar-page-config'].data.imagem_destaque = '';
    T.open(100, 40, 'sobre-nos');
    await aguardar();

    const painel = T.panel();
    expect(painel.querySelector('.c2f-page-config-image img')).toBeNull();
    expect(painel.textContent).toContain('padrão do site');
    expect(painel.querySelector('[data-page-config-action="clear"]').style.display).toBe('none');
  });

  it('a seleção do gerenciador de arquivos vira a imagem de destaque e fecha o seletor', async () => {
    T.open(100, 40, 'sobre-nos');
    await aguardar();

    T.openPicker();
    expect(T.picker().style.display).toBe('block');

    const aplicou = T.aplicarSelecao({ caminho: 'imagens/novo-banner.png', nome: 'novo-banner.png' });

    expect(aplicou).toBe(true);
    expect(T.imagem()).toBe('imagens/novo-banner.png');
    expect(T.picker().style.display).toBe('none');
    expect(T.panel().querySelector('.c2f-page-config-image').textContent).toContain('imagens/novo-banner.png');
  });

  it('seleção sem o seletor aberto é ignorada (não sequestra outro picker da página)', async () => {
    T.open(100, 40, 'sobre-nos');
    await aguardar();

    expect(T.aplicarSelecao({ caminho: 'imagens/x.png' })).toBe(false);
    expect(T.imagem()).toBe('imagens/banner.jpg');
  });

  it('remover limpa a imagem sem apagar os demais campos', async () => {
    T.open(100, 40, 'sobre-nos');
    await aguardar();

    T.panel().querySelector('[data-page-config-action="clear"]').click();

    expect(T.imagem()).toBe('');
    expect(T.panel().querySelector('#c2f-pc-og-titulo').value).toBe('Título social atual');
  });

  it('salvar envia POST com os três campos e confirma na interface', async () => {
    T.open(100, 40, 'sobre-nos');
    await aguardar();

    const painel = T.panel();
    painel.querySelector('#c2f-pc-og-titulo').value = 'Novo título';
    painel.querySelector('#c2f-pc-og-descricao').value = 'Nova descrição';
    painel.querySelector('[data-page-config-action="save"]').click();
    await aguardar();

    const envio = requisicoes[requisicoes.length - 1];
    expect(envio.url).toContain('ajaxOpcao=site-toolbar-page-config-save');
    expect(envio.init.method).toBe('POST');

    const corpo = new URLSearchParams(envio.init.body);
    expect(corpo.get('page_id')).toBe('sobre-nos');
    expect(corpo.get('og_titulo')).toBe('Novo título');
    expect(corpo.get('og_descricao')).toBe('Nova descrição');
    expect(corpo.get('imagem_destaque')).toBe('imagens/banner.jpg');

    expect(painel.querySelector('.c2f-page-config-status').textContent).toContain('Salvo');
  });

  it('erro do backend é mostrado ao usuário em vez de silenciado', async () => {
    respostas['site-toolbar-page-config-save'] = { status: 'error', message: 'Sem permissão para esta página.' };

    T.open(100, 40, 'sobre-nos');
    await aguardar();
    T.panel().querySelector('[data-page-config-action="save"]').click();
    await aguardar();

    expect(T.panel().querySelector('.c2f-page-config-status').textContent).toBe('Sem permissão para esta página.');
  });

  it('falha ao carregar não deixa o painel travado em "Carregando"', async () => {
    respostas['site-toolbar-page-config'] = { status: 'error', message: 'Página não encontrada.' };

    T.open(100, 40, 'inexistente');
    await aguardar();

    expect(T.panel().textContent).toContain('Página não encontrada.');
  });

  // ===== req-112: meta tags clássicas + isolamento de eventos

  it('carrega e salva também meta descrição e palavras-chave', async () => {
    T.open(100, 40, 'sobre-nos');
    await aguardar();

    const painel = T.panel();
    expect(painel.querySelector('#c2f-pc-meta-descricao').value).toBe('Descrição para o Google');
    expect(painel.querySelector('#c2f-pc-meta-keywords').value).toBe('foto, ensaio');

    painel.querySelector('#c2f-pc-meta-descricao').value = 'Nova meta';
    painel.querySelector('#c2f-pc-meta-keywords').value = 'a, b, c';
    painel.querySelector('[data-page-config-action="save"]').click();
    await aguardar();

    const corpo = new URLSearchParams(requisicoes[requisicoes.length - 1].init.body);
    expect(corpo.get('meta_descricao')).toBe('Nova meta');
    expect(corpo.get('meta_keywords')).toBe('a, b, c');
  });

  it('o painel não deixa eventos vazarem para a página editada atrás dele', async () => {
    T.open(100, 40, 'sobre-nos');
    await aguardar();

    // O motor do editor escuta no documento; sem o isolamento, passar o mouse sobre o painel
    // realçava o elemento de trás e o primeiro clique era consumido pela seleção.
    const noDocumento = vi.fn();
    ['mousedown', 'click', 'mousemove', 'mouseover'].forEach(function (evento) {
      document.addEventListener(evento, noDocumento);
    });

    const painel = T.panel();
    ['mousedown', 'click', 'mousemove', 'mouseover'].forEach(function (evento) {
      painel.dispatchEvent(new Event(evento, { bubbles: true }));
    });

    expect(noDocumento).not.toHaveBeenCalled();
  });

  it('o isolamento NÃO impede o botão dentro do painel de funcionar', async () => {
    // Regressão: barrar a propagação na fase de CAPTURA impediria o evento de chegar ao botão.
    T.open(100, 40, 'sobre-nos');
    await aguardar();

    T.panel().querySelector('[data-page-config-action="clear"]').click();

    expect(T.imagem()).toBe('');
  });

  // ===== req-112 (rodada 3): cache-bust do motor vem da biblioteca html-editor

  it('a versão do motor vem de gestor.htmlEditorVersao', () => {
    window.gestor.htmlEditorVersao = '1.5.11';

    expect(T.versaoMotor()).toBe('1.5.11');
  });

  it('sem a variável, cai na versão do sistema em vez de string fixa', () => {
    delete window.gestor.htmlEditorVersao;
    window.gestor.versao = '2.9.34';

    expect(T.versaoMotor()).toBe('2.9.34');
  });

  it('sem nenhuma das duas, devolve vazio sem quebrar a montagem da URL', () => {
    delete window.gestor.htmlEditorVersao;
    delete window.gestor.versao;

    expect(T.versaoMotor()).toBe('');
  });

  it('clique na Editbar fecha o painel — mas não enquanto o seletor de arquivos está aberto', async () => {
    T.open(100, 40, 'sobre-nos');
    await aguardar();

    T.openPicker();
    T.dismiss();
    expect(T.panel().style.display).toBe('block');

    T.picker().style.display = 'none';
    T.dismiss();
    expect(T.panel().style.display).toBe('none');
  });
});
