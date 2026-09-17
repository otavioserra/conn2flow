import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * req-109 (BATCH-109) — auditoria da interceptação global de CSRF em `gestor/assets/global/global.js`.
 *
 * O foco é o caminho que estava descoberto: formulários submetidos por CÓDIGO. Nem
 * `HTMLFormElement.prototype.submit()` (nativo) nem `$(form).submit()` (jQuery) disparam o evento
 * `submit`, então o listener nativo de captura — única proteção até aqui — nunca era acionado e o
 * backend respondia 403 "Token CSRF inválido ou ausente." Era o caso do salvamento do Editor Visual.
 */

const ARQUIVO = resolve(process.cwd(), 'gestor/assets/global/global.js');

function codigoDoIife() {
  const arquivo = readFileSync(ARQUIVO, 'utf8');
  const fimIife = arquivo.search(/\r?\n\r?\n\$\(document\)\.ready/);
  return arquivo.slice(0, fimIife);
}

/** Stub mínimo de jQuery com os pontos que o global.js usa. */
function criarJQuery() {
  const registrados = { submit: [] };

  function jquery() {
    return {
      ajaxError() { },
      on(evento, seletor, callback) {
        if (evento === 'submit') registrados.submit.push(callback);
      }
    };
  }
  jquery.ajaxPrefilter = vi.fn();

  return { jquery, registrados };
}

function carregarGlobalJs({ token = '', comMeta = true, jqueryStub = null } = {}) {
  if (comMeta && token) {
    document.head.innerHTML = `<meta name="csrf-token" content="${token}">`;
  }

  window.gestor = Object.assign({}, window.gestor, {
    raiz: '/site/',
    csrfToken: comMeta ? undefined : token || undefined
  });

  if (jqueryStub) window.jQuery = jqueryStub;

  // Cada carga precisa reinstalar o envelope do prototype.
  delete HTMLFormElement.prototype.__c2fCsrf;

  // eslint-disable-next-line no-new-func
  new Function(codigoDoIife())();
}

function criarFormulario(metodo = 'post', action = '/site/admin-paginas/editar/') {
  const form = document.createElement('form');
  form.setAttribute('method', metodo);
  form.setAttribute('action', action);
  document.body.appendChild(form);
  return form;
}

function valorDoCampo(form) {
  const campo = form.querySelector('input[name="_csrf_token"]');
  return campo ? campo.value : null;
}

let submitOriginal;

beforeEach(() => {
  submitOriginal = HTMLFormElement.prototype.submit;
  HTMLFormElement.prototype.submit = vi.fn();
});

afterEach(() => {
  HTMLFormElement.prototype.submit = submitOriginal;
  delete HTMLFormElement.prototype.__c2fCsrf;
  delete window.gestorCsrf;
  window.gestor = { raiz: '/', moduloCaminho: 'gestor/modulo', moduloOpcao: 'editar', html_editor: {} };
});

describe('global.js - CSRF em formulários', () => {
  it('expõe a API pública com o nome de campo esperado pelo backend', () => {
    carregarGlobalJs({ token: 'tok-1' });

    expect(window.gestorCsrf.campo).toBe('_csrf_token');
    expect(window.gestorCsrf.header).toBe('X-CSRF-Token');
    expect(window.gestorCsrf.token()).toBe('tok-1');
  });

  it('anexa o campo oculto num formulário POST de mesma origem', () => {
    carregarGlobalJs({ token: 'tok-2' });
    const form = criarFormulario();

    expect(window.gestorCsrf.aplicarNoFormulario(form)).toBe(true);
    expect(valorDoCampo(form)).toBe('tok-2');
  });

  it('não duplica o campo quando aplicado mais de uma vez', () => {
    carregarGlobalJs({ token: 'tok-3' });
    const form = criarFormulario();

    window.gestorCsrf.aplicarNoFormulario(form);
    window.gestorCsrf.aplicarNoFormulario(form);

    expect(form.querySelectorAll('input[name="_csrf_token"]').length).toBe(1);
  });

  it('ignora formulários GET', () => {
    carregarGlobalJs({ token: 'tok-4' });
    const form = criarFormulario('get');

    expect(window.gestorCsrf.aplicarNoFormulario(form)).toBe(false);
    expect(valorDoCampo(form)).toBeNull();
  });

  it('ignora formulário apontado para outra origem', () => {
    carregarGlobalJs({ token: 'tok-5' });
    const form = criarFormulario('post', 'https://externo.test/receber');

    expect(window.gestorCsrf.aplicarNoFormulario(form)).toBe(false);
    expect(valorDoCampo(form)).toBeNull();
  });

  it('sem token disponível não inventa campo', () => {
    carregarGlobalJs({ token: '' });
    const form = criarFormulario();

    expect(window.gestorCsrf.aplicarNoFormulario(form)).toBe(false);
    expect(valorDoCampo(form)).toBeNull();
  });

  it('lê o token de gestor.csrfToken quando não existe a meta tag (iframe srcdoc do editor)', () => {
    carregarGlobalJs({ token: 'tok-iframe', comMeta: false });

    expect(window.gestorCsrf.token()).toBe('tok-iframe');
  });

  it('form.submit() nativo passa a anexar o token (regressão do salvamento do Editor Visual)', () => {
    carregarGlobalJs({ token: 'tok-6' });
    const form = criarFormulario();

    form.submit();

    expect(valorDoCampo(form)).toBe('tok-6');
    expect(submitOriginal).not.toBe(HTMLFormElement.prototype.submit);
  });

  it('o envelope do prototype não é instalado duas vezes', () => {
    carregarGlobalJs({ token: 'tok-7' });
    const envelope = HTMLFormElement.prototype.submit;

    // Segunda carga (ex.: asset incluído duas vezes) não deve empilhar outro envelope.
    // eslint-disable-next-line no-new-func
    new Function(codigoDoIife())();

    expect(HTMLFormElement.prototype.submit).toBe(envelope);
  });

  it('o handler delegado do jQuery anexa o token em $(form).submit()', () => {
    const { jquery, registrados } = criarJQuery();
    carregarGlobalJs({ token: 'tok-8', jqueryStub: jquery });
    const form = criarFormulario();

    expect(registrados.submit.length).toBe(1);
    registrados.submit[0].call(form);

    expect(valorDoCampo(form)).toBe('tok-8');
  });

  it('o listener nativo de captura continua cobrindo o submit do usuário', () => {
    carregarGlobalJs({ token: 'tok-9' });
    const form = criarFormulario();

    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

    expect(valorDoCampo(form)).toBe('tok-9');
  });
});

/**
 * req-163 (BATCH-168) — `XMLHttpRequest` cru. Uploads com progresso usam XHR porque `fetch` não
 * expõe `upload.onprogress`; sem o envelope do prototype eles voltavam 403 com o usuário logado.
 *
 * O XHR do happy-dom é trocado por um dublê que só registra o que chegaria ao nativo, para que o
 * teste observe os cabeçalhos efetivamente enviados sem abrir conexão.
 */
describe('global.js - CSRF em XMLHttpRequest', () => {
  let xhrOriginal;
  let assignOriginal;

  class XhrFalso {
    constructor() {
      this.enviados = [];
      this.ouvintes = {};
      this.status = 0;
      this.respostaCabecalhos = {};
    }
    open() { }
    setRequestHeader(nome, valor) { this.enviados.push([nome, valor]); }
    send() { }
    addEventListener(evento, callback) { (this.ouvintes[evento] = this.ouvintes[evento] || []).push(callback); }
    getResponseHeader(nome) { return this.respostaCabecalhos[nome] || null; }
    disparar(evento) { (this.ouvintes[evento] || []).forEach((callback) => callback()); }
  }

  function cabecalhosCsrf(xhr) {
    return xhr.enviados.filter(([nome]) => nome.toLowerCase() === 'x-csrf-token');
  }

  beforeEach(() => {
    xhrOriginal = globalThis.XMLHttpRequest;
    globalThis.XMLHttpRequest = class extends XhrFalso { };
    window.XMLHttpRequest = globalThis.XMLHttpRequest;
    assignOriginal = window.location.assign;
  });

  afterEach(() => {
    globalThis.XMLHttpRequest = xhrOriginal;
    window.XMLHttpRequest = xhrOriginal;
  });

  it('injeta X-CSRF-Token num POST de mesma origem', () => {
    carregarGlobalJs({ token: 'tok-xhr-1' });
    const xhr = new XMLHttpRequest();

    xhr.open('POST', '/site/admin-arquivos/');
    xhr.send(new FormData());

    expect(cabecalhosCsrf(xhr)).toEqual([['X-CSRF-Token', 'tok-xhr-1']]);
  });

  it('não injeta em GET', () => {
    carregarGlobalJs({ token: 'tok-xhr-2' });
    const xhr = new XMLHttpRequest();

    xhr.open('GET', '/site/admin-arquivos/');
    xhr.send();

    expect(cabecalhosCsrf(xhr)).toEqual([]);
  });

  it('não injeta em POST para outra origem', () => {
    carregarGlobalJs({ token: 'tok-xhr-3' });
    const xhr = new XMLHttpRequest();

    xhr.open('POST', 'https://externo.test/upload');
    xhr.send();

    expect(cabecalhosCsrf(xhr)).toEqual([]);
  });

  it('não duplica nem sobrescreve o token informado manualmente (inclusive pelo prefilter do jQuery)', () => {
    carregarGlobalJs({ token: 'tok-xhr-4' });
    const xhr = new XMLHttpRequest();

    xhr.open('POST', '/site/admin-arquivos/');
    xhr.setRequestHeader('x-csrf-token', 'manual');
    xhr.send();

    expect(cabecalhosCsrf(xhr)).toEqual([['x-csrf-token', 'manual']]);
  });

  it('o envelope do prototype não é instalado duas vezes', () => {
    carregarGlobalJs({ token: 'tok-xhr-5' });
    const envelope = XMLHttpRequest.prototype.send;

    // eslint-disable-next-line no-new-func
    new Function(codigoDoIife())();
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/site/x/');
    xhr.send();

    expect(XMLHttpRequest.prototype.send).toBe(envelope);
    expect(cabecalhosCsrf(xhr).length).toBe(1);
  });

  it('redireciona para o login em 401 com X-Gestor-Auth-Redirect', () => {
    const assign = vi.fn();
    Object.defineProperty(window.location, 'assign', { configurable: true, value: assign });
    try {
      carregarGlobalJs({ token: 'tok-xhr-6' });
      const xhr = new XMLHttpRequest();
      xhr.open('POST', '/site/admin-arquivos/');
      xhr.send();

      xhr.status = 401;
      xhr.respostaCabecalhos['X-Gestor-Auth-Redirect'] = '/site/signin/';
      xhr.disparar('load');

      expect(assign).toHaveBeenCalledWith(new URL('/site/signin/', window.location.href).href);
    } finally {
      Object.defineProperty(window.location, 'assign', { configurable: true, value: assignOriginal });
    }
  });
});
