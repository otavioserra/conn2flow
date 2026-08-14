import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import fs from 'node:fs';

/**
 * req-109 (BATCH-109) — §7 e §8 do Editor Visual.
 *
 * §7: a URL do AJAX do módulo era montada como `gestor.raiz + gestor.moduloCaminho + '/'`, mas o
 *     backend já entrega `moduloCaminho` com a barra final — o resultado era `admin-paginas/editar//`.
 * §7: o `srcdoc` do preview herda a origem, mas não o `<head>` da página hospedeira: sem token
 *     explícito o `renderWidgets` recebia 403 "Token CSRF inválido ou ausente.".
 * §8: o salvamento (`previsualizarConfirmar`) submete o formulário por código e precisa do campo
 *     `_csrf_token`; sem token, avisa o usuário em vez de deixar o navegador exibir o JSON cru.
 */

const ARQUIVO = 'gestor/assets/interface/html-editor-interface.js';

function extrairFuncao(codigo, assinatura) {
  const start = codigo.indexOf(assinatura);
  if (start < 0) throw new Error(assinatura + ' não encontrada em ' + ARQUIVO);
  let i = codigo.indexOf('{', start);
  let depth = 0;
  for (; i < codigo.length; i++) {
    if (codigo[i] === '{') depth++;
    else if (codigo[i] === '}') {
      depth--;
      if (depth === 0) return codigo.slice(start, i + 1);
    }
  }
  throw new Error('Fim de ' + assinatura + ' não localizado');
}

const CODIGO = fs.readFileSync(ARQUIVO, 'utf8');

function carregarModuloUrl(gestorFake) {
  const src = extrairFuncao(CODIGO, 'function moduloUrl(');
  // eslint-disable-next-line no-new-func
  return new Function('gestor', src + '\nreturn moduloUrl;')(gestorFake);
}

function carregarAplicarCsrf({ gestor: gestorFake, jq, alerta }) {
  const src = extrairFuncao(CODIGO, 'function htmlEditorAplicarCsrfNoFormulario(')
    + '\n' + extrairFuncao(CODIGO, 'function htmlEditorIdiomaIngles(');
  // eslint-disable-next-line no-new-func
  return new Function('gestor', '$', 'alert', src + '\nreturn htmlEditorAplicarCsrfNoFormulario;')(gestorFake, jq, alerta);
}

describe('html-editor-interface.js — URL do módulo (req-109 §7)', () => {
  it('não produz barra dupla quando moduloCaminho já termina em barra', () => {
    const url = carregarModuloUrl({ raiz: '/site/', moduloCaminho: 'admin-paginas/editar/' });
    expect(url()).toBe('/site/admin-paginas/editar/');
  });

  it('acrescenta a barra final quando moduloCaminho não a tem', () => {
    const url = carregarModuloUrl({ raiz: '/site/', moduloCaminho: 'admin-paginas/editar' });
    expect(url()).toBe('/site/admin-paginas/editar/');
  });

  it('funciona com raiz absoluta sem colapsar o protocolo', () => {
    const url = carregarModuloUrl({ raiz: 'https://site.test/', moduloCaminho: 'admin-paginas/editar/' });
    expect(url()).toBe('https://site.test/admin-paginas/editar/');
  });

  it('tolera barra sobrando nas duas pontas', () => {
    const url = carregarModuloUrl({ raiz: '/site//', moduloCaminho: '/admin-paginas/editar//' });
    expect(url()).toBe('/site/admin-paginas/editar/');
  });

  it('degrada para a raiz quando não há moduloCaminho', () => {
    const url = carregarModuloUrl({ raiz: '/site/' });
    expect(url()).toBe('/site/');
  });
});

describe('html-editor-interface.js — CSRF no salvamento (req-109 §8)', () => {
  let form;
  let jq;
  let alerta;

  function jqueryFake() {
    const fn = (seletor) => {
      if (typeof seletor === 'string' && seletor.indexOf('<input') === 0) {
        const el = document.createElement('input');
        el.type = 'hidden';
        el.name = '_csrf_token';
        return {
          appendTo: (alvo) => {
            alvo.__el.appendChild(el);
            return { length: 1, val: (v) => { el.value = v; } };
          }
        };
      }
      if (seletor === '.ui.form.interfaceFormPadrao') {
        return {
          length: form ? 1 : 0,
          __el: form,
          find: (sel) => {
            const achado = form ? form.querySelector(sel) : null;
            return {
              length: achado ? 1 : 0,
              val: (v) => { if (achado) achado.value = v; }
            };
          }
        };
      }
      // meta[name="csrf-token"]
      const achado = document.querySelector(seletor);
      return { attr: (nome) => (achado ? achado.getAttribute(nome) : undefined) };
    };
    return fn;
  }

  beforeEach(() => {
    form = document.createElement('form');
    form.className = 'ui form interfaceFormPadrao';
    form.setAttribute('method', 'post');
    document.body.appendChild(form);
    jq = jqueryFake();
    alerta = vi.fn();
  });

  afterEach(() => {
    document.body.innerHTML = '';
    document.head.innerHTML = '';
  });

  it('anexa o campo _csrf_token com o token do gestor e autoriza o submit', () => {
    const aplicar = carregarAplicarCsrf({ gestor: { csrfToken: 'tok-editor', language: 'pt-br' }, jq, alerta });

    expect(aplicar()).toBe(true);
    expect(form.querySelector('input[name="_csrf_token"]').value).toBe('tok-editor');
    expect(alerta).not.toHaveBeenCalled();
  });

  it('reaproveita o campo existente em vez de duplicar', () => {
    form.innerHTML = '<input type="hidden" name="_csrf_token" value="antigo">';
    const aplicar = carregarAplicarCsrf({ gestor: { csrfToken: 'novo', language: 'pt-br' }, jq, alerta });

    expect(aplicar()).toBe(true);
    expect(form.querySelectorAll('input[name="_csrf_token"]').length).toBe(1);
    expect(form.querySelector('input[name="_csrf_token"]').value).toBe('novo');
  });

  it('cai para a meta tag quando gestor.csrfToken não existe', () => {
    document.head.innerHTML = '<meta name="csrf-token" content="tok-meta">';
    const aplicar = carregarAplicarCsrf({ gestor: { language: 'pt-br' }, jq, alerta });

    expect(aplicar()).toBe(true);
    expect(form.querySelector('input[name="_csrf_token"]').value).toBe('tok-meta');
  });

  it('sem token avisa o usuário e BLOQUEIA o submit (nada de JSON cru na tela)', () => {
    const aplicar = carregarAplicarCsrf({ gestor: { language: 'pt-br' }, jq, alerta });

    expect(aplicar()).toBe(false);
    expect(alerta).toHaveBeenCalledOnce();
    expect(alerta.mock.calls[0][0]).toContain('sessão expirou');
  });

  it('o aviso respeita o idioma inglês', () => {
    const aplicar = carregarAplicarCsrf({ gestor: { language: 'en' }, jq, alerta });

    expect(aplicar()).toBe(false);
    expect(alerta.mock.calls[0][0]).toContain('session expired');
  });

  it('sem formulário na página o salvamento segue sem bloqueio', () => {
    form.remove();
    form = null;
    const aplicar = carregarAplicarCsrf({ gestor: {}, jq: jqueryFake(), alerta });

    expect(aplicar()).toBe(true);
    expect(alerta).not.toHaveBeenCalled();
  });
});

describe('html-editor-interface.js — renderWidgets dentro do srcdoc (req-109 §7)', () => {
  let chamadas;

  beforeEach(() => {
    chamadas = [];
    window.jQuery = { ajax: (opcoes) => { chamadas.push(opcoes); } };
    window.gestor = {
      raiz: '/site/',
      moduloCaminho: 'admin-paginas/editar/',
      moduloOpcao: 'editar',
      csrfToken: 'tok-srcdoc'
    };
    document.body.innerHTML = '<div><!-- widgets#publisher-index:1 < --><!-- widgets#publisher-index:1 > --></div>';
  });

  afterEach(() => {
    delete window.jQuery;
    document.body.innerHTML = '';
  });

  function executarBootstrap() {
    const src = extrairFuncao(CODIGO, 'function widgetPreviewBootstrap(');
    // eslint-disable-next-line no-new-func
    new Function(src + '\nwidgetPreviewBootstrap();')();
  }

  it('dispara o AJAX na URL do módulo sem barra dupla', () => {
    executarBootstrap();

    expect(chamadas.length).toBe(1);
    expect(chamadas[0].url).toBe('/site/admin-paginas/editar/');
    expect(chamadas[0].url).not.toContain('//admin');
    expect(chamadas[0].url.endsWith('//')).toBe(false);
  });

  it('envia o token CSRF no cabeçalho e no corpo', () => {
    executarBootstrap();

    expect(chamadas[0].headers['X-CSRF-Token']).toBe('tok-srcdoc');
    expect(chamadas[0].data._csrf_token).toBe('tok-srcdoc');
    expect(chamadas[0].data.ajaxOpcao).toBe('html-editor-widget-render');
  });

  it('sem token o AJAX continua sendo disparado (backend decide), sem campo vazio', () => {
    delete window.gestor.csrfToken;
    executarBootstrap();

    expect(chamadas.length).toBe(1);
    expect(chamadas[0].data._csrf_token).toBeUndefined();
    expect(chamadas[0].headers).toEqual({});
  });
});
