import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { describe, expect, it, vi } from 'vitest';

function carregarInterceptadores(fetchImpl = vi.fn()) {
  const arquivo = readFileSync(resolve(process.cwd(), 'gestor/assets/global/global.js'), 'utf8');
  const fimIife = arquivo.search(/\r?\n\r?\n\$\(document\)\.ready/);
  const codigo = arquivo.slice(0, fimIife);
  const assign = vi.fn();
  let ajaxError;

  function jquery() {
    return {
      ajaxError(callback) {
        ajaxError = callback;
      },
      // req-109: o global.js passou a registrar um handler delegado de `submit` no document para
      // cobrir formulários submetidos por código (`$(form).submit()` não dispara evento nativo).
      on: vi.fn()
    };
  }
  jquery.ajaxPrefilter = vi.fn();

  const windowFake = {
    gestor: { raiz: '/conn2flow-site/' },
    jQuery: jquery,
    fetch: fetchImpl,
    location: {
      href: 'http://localhost/conn2flow-site/admin-atualizacoes/',
      origin: 'http://localhost',
      assign
    }
  };
  const documentFake = {
    querySelector: vi.fn(() => null),
    addEventListener: vi.fn()
  };

  vm.runInNewContext(codigo, {
    window: windowFake,
    document: documentFake,
    gestor: windowFake.gestor,
    Headers,
    URL
  }, { filename: 'global.js' });

  return { windowFake, assign, obterAjaxError: () => ajaxError };
}

describe('global.js - expiracao de autenticacao durante AJAX', () => {
  it('redireciona uma resposta jQuery marcada como AUTH_REQUIRED', () => {
    const contexto = carregarInterceptadores();
    contexto.obterAjaxError()(null, {
      status: 401,
      responseJSON: { code: 'AUTH_REQUIRED', redirect: 'signin/' },
      getResponseHeader: vi.fn(() => '/conn2flow-site/signin/')
    });

    expect(contexto.assign).toHaveBeenCalledOnce();
    expect(contexto.assign).toHaveBeenCalledWith('http://localhost/conn2flow-site/signin/');
  });

  it('nao redireciona 401 comum de falta de permissao', () => {
    const contexto = carregarInterceptadores();
    contexto.obterAjaxError()(null, {
      status: 401,
      responseJSON: { redirect: 'dashboard/' },
      getResponseHeader: vi.fn(() => null)
    });

    expect(contexto.assign).not.toHaveBeenCalled();
  });

  it('redireciona fetch quando o backend sinaliza sessao expirada', async () => {
    const resposta = {
      status: 401,
      headers: { get: vi.fn(() => '/conn2flow-site/signin/') }
    };
    const fetchImpl = vi.fn(async () => resposta);
    const contexto = carregarInterceptadores(fetchImpl);

    await contexto.windowFake.fetch('/conn2flow-site/admin-atualizacoes/', { method: 'POST' });

    expect(contexto.assign).toHaveBeenCalledWith('http://localhost/conn2flow-site/signin/');
  });
});
