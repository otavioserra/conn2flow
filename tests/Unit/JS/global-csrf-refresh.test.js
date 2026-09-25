import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { describe, expect, it, vi } from 'vitest';

/**
 * req-175 (BATCH-180) — renovação silenciosa de CSRF (silent refresh) e retry transparente em
 * `gestor/assets/global/global.js`.
 *
 * O IIFE roda num contexto `vm` isolado, com `window`, `document`, `fetch`, `XMLHttpRequest` e
 * relógio próprios: assim cada teste controla a rede e o tempo sem interferir nos demais.
 */

const CODIGO = (() => {
  const arquivo = readFileSync(resolve(process.cwd(), 'gestor/assets/global/global.js'), 'utf8');
  return arquivo.slice(0, arquivo.search(/\r?\n\r?\n\$\(document\)\.ready/));
})();

const ROTA = '/site/_gestor-csrf-token/';

function json(status, corpo, cabecalhos = {}) {
  return new Response(JSON.stringify(corpo), {
    status,
    headers: Object.assign({ 'Content-Type': 'application/json' }, cabecalhos)
  });
}

const csrf403 = () => json(403, { status: 'error', code: 'CSRF_INVALID_OR_EXPIRED', message: 'Token CSRF inválido ou ausente.' });
const tokenOk = (token = 'tok-novo') => json(200, { status: 'success', token, authenticated: true });

class EventoFalso {
  constructor(type) { this.type = type; }
}

/**
 * Dublê de XHR com a ordem de despacho do DOM no alvo: ouvintes de CAPTURA primeiro, depois os
 * comuns e o `on<evento>`. `stopImmediatePropagation()` interrompe o resto.
 */
class XhrFalso {
  constructor() {
    this.ouvintes = [];
    this.readyState = 0;
    this.status = 0;
    this.responseType = '';
    this.responseText = '';
    this.cabResp = {};
    this.aberturas = [];
    this.envios = [];
    this.cabecalhos = [];
  }
  open(metodo, url) {
    this.aberturas.push([metodo, url]);
    this.cabecalhos = [];
    this.readyState = 1;
    this.status = 0;
  }
  setRequestHeader(nome, valor) { this.cabecalhos.push([nome, valor]); }
  send(corpo) { this.envios.push({ corpo, cabecalhos: this.cabecalhos.slice() }); }
  getResponseHeader(nome) { return this.cabResp[nome] ?? null; }
  addEventListener(tipo, callback, opcoes) {
    const captura = opcoes === true || !!(opcoes && opcoes.capture);
    this.ouvintes.push({ tipo, callback, captura });
  }
  dispatchEvent(evento) {
    let parar = false;
    evento.stopImmediatePropagation = () => { parar = true; };
    const lista = [
      ...this.ouvintes.filter((o) => o.tipo === evento.type && o.captura),
      ...this.ouvintes.filter((o) => o.tipo === evento.type && !o.captura)
    ];
    for (const o of lista) {
      o.callback.call(this, evento);
      if (parar) return;
    }
    const propriedade = this['on' + evento.type];
    if (typeof propriedade === 'function') propriedade.call(this, evento);
  }
  responder(status, corpo, cabecalhos = {}) {
    this.readyState = 4;
    this.status = status;
    this.responseText = JSON.stringify(corpo);
    this.cabResp = cabecalhos;
    ['readystatechange', 'load', 'loadend'].forEach((tipo) => this.dispatchEvent(new EventoFalso(tipo)));
  }
}

function criarMeta(valor) {
  const meta = {
    content: valor,
    getAttribute: () => meta.content,
    setAttribute: (_nome, v) => { meta.content = v; }
  };
  return meta;
}

function criarDocumento(meta, campos = []) {
  const ouvintes = {};
  return {
    visibilityState: 'visible',
    querySelector: (seletor) => (seletor.includes('csrf-token') ? meta : null),
    querySelectorAll: () => campos,
    addEventListener: (tipo, callback) => { (ouvintes[tipo] = ouvintes[tipo] || []).push(callback); },
    ouvintes
  };
}

function carregar({ rede, token = 'tok-velho', pai = null } = {}) {
  const relogio = { agora: 100000 };
  const meta = criarMeta(token);
  const campo = { value: token };
  const documentFake = criarDocumento(meta, [campo]);
  const assign = vi.fn();
  const XhrClasse = class extends XhrFalso { };

  const fetchImpl = vi.fn(async (input, init = {}) => {
    const url = typeof input === 'string' ? input : input.url;
    return rede(url, init);
  });

  const windowFake = {
    gestor: { raiz: '/site/', csrfToken: token },
    fetch: fetchImpl,
    location: {
      href: 'http://localhost/site/admin-paginas/editar/',
      origin: 'http://localhost',
      pathname: '/site/admin-paginas/editar/',
      assign
    }
  };
  windowFake.parent = pai || windowFake;
  windowFake.top = pai || windowFake;

  vm.runInNewContext(CODIGO, {
    window: windowFake,
    document: documentFake,
    gestor: windowFake.gestor,
    XMLHttpRequest: XhrClasse,
    Event: EventoFalso,
    Headers,
    URL,
    URLSearchParams,
    FormData,
    Response,
    setTimeout,
    Date: { now: () => relogio.agora }
  }, { filename: 'global.js' });

  const chamadasRota = () => fetchImpl.mock.calls.filter(([u]) => String(typeof u === 'string' ? u : u.url).startsWith(ROTA));
  const chamadasApp = () => fetchImpl.mock.calls.filter(([u]) => !String(typeof u === 'string' ? u : u.url).startsWith(ROTA));

  return { windowFake, documentFake, meta, campo, assign, fetchImpl, relogio, XhrClasse, chamadasRota, chamadasApp };
}

const esperar = (ms = 5) => new Promise((r) => setTimeout(r, ms));
const tokenEnviado = (init) => new Headers(init.headers || {}).get('X-CSRF-Token');

describe('global.js - silent refresh em fetch', () => {
  it('403 marcado por cabeçalho renova o token e repete o POST de forma transparente', async () => {
    let tentativas = 0;
    const ctx = carregar({
      rede: (url, init) => {
        if (url.startsWith(ROTA)) return tokenOk();
        tentativas++;
        return tentativas === 1
          ? json(403, { status: 'error' }, { 'X-Gestor-Csrf-Error': 'CSRF_INVALID_OR_EXPIRED' })
          : json(200, { status: 'ok', recebido: tokenEnviado(init) });
      }
    });

    const resposta = await ctx.windowFake.fetch('/site/admin-paginas/salvar/', { method: 'POST' });

    expect(resposta.status).toBe(200);
    expect(await resposta.json()).toEqual({ status: 'ok', recebido: 'tok-novo' });
    expect(ctx.chamadasRota()).toHaveLength(1);
    expect(tokenEnviado(ctx.chamadasApp()[0][1])).toBe('tok-velho');
  });

  it('reconhece o código CSRF_INVALID_OR_EXPIRED no corpo JSON quando falta o cabeçalho', async () => {
    let tentativas = 0;
    const ctx = carregar({
      rede: (url) => (url.startsWith(ROTA) ? tokenOk() : (++tentativas === 1 ? csrf403() : json(200, { ok: true })))
    });

    const resposta = await ctx.windowFake.fetch('/site/x/', { method: 'POST' });

    expect(resposta.status).toBe(200);
    expect(ctx.chamadasRota()).toHaveLength(1);
  });

  it('atualiza meta tag, gestor.csrfToken e campos ocultos com o token renovado', async () => {
    let tentativas = 0;
    const ctx = carregar({
      rede: (url) => (url.startsWith(ROTA) ? tokenOk('tok-renovado') : (++tentativas === 1 ? csrf403() : json(200, {})))
    });

    await ctx.windowFake.fetch('/site/x/', { method: 'POST' });

    expect(ctx.meta.content).toBe('tok-renovado');
    expect(ctx.windowFake.gestor.csrfToken).toBe('tok-renovado');
    expect(ctx.campo.value).toBe('tok-renovado');
    expect(ctx.windowFake.gestorCsrf.token()).toBe('tok-renovado');
  });

  it('403 legítimo de permissão (ACL) volta direto, sem renovação nem retry', async () => {
    const ctx = carregar({
      rede: (url) => (url.startsWith(ROTA) ? tokenOk() : json(403, { error: 403, status: 'error', message: 'Sem permissão' }))
    });

    const resposta = await ctx.windowFake.fetch('/site/x/', { method: 'POST' });

    expect(resposta.status).toBe(403);
    expect(ctx.chamadasRota()).toHaveLength(0);
    expect(ctx.chamadasApp()).toHaveLength(1);
  });

  it('repete no máximo uma vez: um segundo 403 de CSRF é devolvido a quem chamou', async () => {
    const ctx = carregar({ rede: (url) => (url.startsWith(ROTA) ? tokenOk() : csrf403()) });

    const resposta = await ctx.windowFake.fetch('/site/x/', { method: 'POST' });

    expect(resposta.status).toBe(403);
    expect(ctx.chamadasApp()).toHaveLength(2);
    expect(ctx.chamadasRota()).toHaveLength(1);
  });

  it('GET não entra em retry (CSRF só protege métodos mutáveis)', async () => {
    const ctx = carregar({ rede: (url) => (url.startsWith(ROTA) ? tokenOk() : csrf403()) });

    const resposta = await ctx.windowFake.fetch('/site/x/');

    expect(resposta.status).toBe(403);
    expect(ctx.chamadasRota()).toHaveLength(0);
  });

  it('atualiza o _csrf_token que viaja no corpo', async () => {
    let tentativas = 0;
    const ctx = carregar({
      rede: (url) => (url.startsWith(ROTA) ? tokenOk() : (++tentativas === 1 ? csrf403() : json(200, {})))
    });

    await ctx.windowFake.fetch('/site/x/', {
      method: 'POST',
      body: new URLSearchParams({ nome: 'a', _csrf_token: 'tok-velho' })
    });

    const corpoRepetido = ctx.chamadasApp()[1][1].body;
    expect(corpoRepetido.get('_csrf_token')).toBe('tok-novo');
    expect(corpoRepetido.get('nome')).toBe('a');
  });

  it('envia o caminho de retorno relativo à raiz para o pós-login', async () => {
    let tentativas = 0;
    const ctx = carregar({
      rede: (url) => (url.startsWith(ROTA) ? tokenOk() : (++tentativas === 1 ? csrf403() : json(200, {})))
    });

    await ctx.windowFake.fetch('/site/x/', { method: 'POST' });

    expect(ctx.chamadasRota()[0][0]).toBe(ROTA + '?retorno=admin-paginas%2Feditar%2F');
  });
});

describe('global.js - fila de renovação concorrente', () => {
  it('várias requisições falhando juntas disparam UMA renovação e todas são repetidas', async () => {
    let liberarRota;
    const rotaPendente = new Promise((r) => { liberarRota = r; });
    const vistas = new Map();
    const ctx = carregar({
      rede: async (url, init) => {
        if (url.startsWith(ROTA)) {
          await rotaPendente;
          return tokenOk();
        }
        const n = (vistas.get(url) || 0) + 1;
        vistas.set(url, n);
        return n === 1 ? csrf403() : json(200, { url, token: tokenEnviado(init) });
      }
    });

    const promessas = ['/site/a/', '/site/b/', '/site/c/'].map((u) => ctx.windowFake.fetch(u, { method: 'POST' }));
    await esperar();
    liberarRota();
    const respostas = await Promise.all(promessas);

    expect(respostas.map((r) => r.status)).toEqual([200, 200, 200]);
    expect(ctx.chamadasRota()).toHaveLength(1);
    for (const r of respostas) expect((await r.json()).token).toBe('tok-novo');
  });

  it('falha que chega depois de uma renovação concluída reaproveita o token atual', async () => {
    const vistas = new Map();
    const ctx = carregar({
      rede: (url) => {
        if (url.startsWith(ROTA)) return tokenOk();
        const n = (vistas.get(url) || 0) + 1;
        vistas.set(url, n);
        return n === 1 ? csrf403() : json(200, {});
      }
    });

    await ctx.windowFake.fetch('/site/a/', { method: 'POST' });
    // Simula uma requisição que partiu com o token antigo antes da renovação.
    ctx.windowFake.gestorCsrf.aplicarToken('tok-novo');
    const antigo = { method: 'POST', headers: { 'X-CSRF-Token': 'tok-velho' } };
    ctx.meta.content = 'tok-velho';
    const promessa = ctx.windowFake.fetch('/site/b/', antigo);
    ctx.meta.content = 'tok-novo';
    await promessa;

    expect(ctx.chamadasRota()).toHaveLength(1);
  });
});

describe('global.js - sessão encerrada', () => {
  it('renovação com 401 AUTH_EXPIRED leva ao login e devolve o 403 original', async () => {
    const ctx = carregar({
      rede: (url) => (url.startsWith(ROTA)
        ? json(401, { status: 'error', code: 'AUTH_EXPIRED', redirect: 'signin/' }, { 'X-Gestor-Auth-Redirect': '/site/signin/' })
        : csrf403())
    });

    const resposta = await ctx.windowFake.fetch('/site/x/', { method: 'POST' });

    expect(resposta.status).toBe(403);
    expect(ctx.chamadasApp()).toHaveLength(1);
    expect(ctx.assign).toHaveBeenCalledOnce();
    expect(ctx.assign).toHaveBeenCalledWith('http://localhost/site/signin/');
  });

  it('sem cabeçalho de destino, cai no signin da raiz', async () => {
    const ctx = carregar({
      rede: (url) => (url.startsWith(ROTA) ? json(401, { status: 'error', code: 'AUTH_EXPIRED' }) : csrf403())
    });

    await ctx.windowFake.fetch('/site/x/', { method: 'POST' });

    expect(ctx.assign).toHaveBeenCalledWith('http://localhost/site/signin/');
  });
});

describe('global.js - propagação para a página hospedeira (iframe)', () => {
  it('o token renovado chega a parent.gestor.csrfToken e à meta do pai', async () => {
    const metaPai = criarMeta('tok-velho');
    const pai = {
      gestor: { raiz: '/site/', csrfToken: 'tok-velho' },
      document: criarDocumento(metaPai)
    };
    const ctx = carregar({ rede: () => tokenOk('tok-pai'), pai });

    await ctx.windowFake.gestorCsrf.renovar();

    expect(pai.gestor.csrfToken).toBe('tok-pai');
    expect(metaPai.content).toBe('tok-pai');
    expect(ctx.meta.content).toBe('tok-pai');
  });
});

describe('global.js - visibilitychange', () => {
  function alternar(ctx, estado) {
    ctx.documentFake.visibilityState = estado;
    ctx.documentFake.ouvintes.visibilitychange.forEach((cb) => cb());
  }

  it('renova o token ao voltar para uma aba que ficou em segundo plano', async () => {
    const ctx = carregar({ rede: () => tokenOk('tok-foco') });

    alternar(ctx, 'hidden');
    ctx.relogio.agora += 45000;
    alternar(ctx, 'visible');
    await esperar();

    expect(ctx.chamadasRota()).toHaveLength(1);
    expect(ctx.meta.content).toBe('tok-foco');
  });

  it('ausência curta não consulta o servidor', async () => {
    const ctx = carregar({ rede: () => tokenOk() });

    alternar(ctx, 'hidden');
    ctx.relogio.agora += 5000;
    alternar(ctx, 'visible');
    await esperar();

    expect(ctx.chamadasRota()).toHaveLength(0);
  });

  it('não repete a consulta se o token foi renovado há menos de 30 segundos', async () => {
    const ctx = carregar({ rede: () => tokenOk() });

    // Um retry em segundo plano renovou o token enquanto a aba estava oculta.
    alternar(ctx, 'hidden');
    ctx.relogio.agora += 20000;
    await ctx.windowFake.gestorCsrf.renovar();
    ctx.relogio.agora += 15000;
    alternar(ctx, 'visible');
    await esperar();
    expect(ctx.chamadasRota()).toHaveLength(1);

    // Passados 30 s da última renovação, uma nova ausência longa volta a consultar.
    alternar(ctx, 'hidden');
    ctx.relogio.agora += 31000;
    alternar(ctx, 'visible');
    await esperar();
    expect(ctx.chamadasRota()).toHaveLength(2);
  });

  it('dentro de um iframe da mesma origem, deixa a checagem para a página hospedeira', async () => {
    const pai = { gestor: { raiz: '/site/' }, gestorCsrf: {}, document: criarDocumento(criarMeta('x')) };
    const ctx = carregar({ rede: () => tokenOk(), pai });

    alternar(ctx, 'hidden');
    ctx.relogio.agora += 45000;
    alternar(ctx, 'visible');
    await esperar();

    expect(ctx.chamadasRota()).toHaveLength(0);
  });

  it('checagem proativa com sessão expirada NÃO redireciona (preserva o formulário)', async () => {
    const ctx = carregar({
      rede: () => json(401, { status: 'error', code: 'AUTH_EXPIRED' }, { 'X-Gestor-Auth-Redirect': '/site/signin/' })
    });

    alternar(ctx, 'hidden');
    ctx.relogio.agora += 45000;
    alternar(ctx, 'visible');
    await esperar();

    expect(ctx.chamadasRota()).toHaveLength(1);
    expect(ctx.assign).not.toHaveBeenCalled();
  });
});

describe('global.js - silent refresh em XMLHttpRequest (e $.ajax, que delega ao XHR)', () => {
  function novoXhr(ctx) {
    const xhr = new ctx.XhrClasse();
    xhr.onload = vi.fn();
    return xhr;
  }

  it('segura o 403 de CSRF, renova e reenvia o mesmo objeto; quem chamou só vê o sucesso', async () => {
    const ctx = carregar({ rede: () => tokenOk() });
    const xhr = novoXhr(ctx);
    const ouvinteComum = vi.fn();
    xhr.addEventListener('load', ouvinteComum);

    xhr.open('POST', '/site/admin-arquivos/');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('nome=a&_csrf_token=tok-velho');
    xhr.responder(403, { status: 'error', code: 'CSRF_INVALID_OR_EXPIRED' });

    expect(xhr.onload).not.toHaveBeenCalled();
    expect(ouvinteComum).not.toHaveBeenCalled();

    await esperar();

    expect(xhr.aberturas).toHaveLength(2);
    const reenvio = xhr.envios[1];
    expect(reenvio.corpo).toBe('nome=a&_csrf_token=tok-novo');
    expect(reenvio.cabecalhos).toContainEqual(['Content-Type', 'application/x-www-form-urlencoded']);
    expect(reenvio.cabecalhos.filter(([n]) => n.toLowerCase() === 'x-csrf-token')).toEqual([['X-CSRF-Token', 'tok-novo']]);

    xhr.responder(200, { status: 'ok' });

    expect(xhr.onload).toHaveBeenCalledOnce();
    expect(ouvinteComum).toHaveBeenCalledOnce();
    expect(xhr.status).toBe(200);
  });

  it('403 de permissão chega imediatamente a quem chamou, sem renovação', async () => {
    const ctx = carregar({ rede: () => tokenOk() });
    const xhr = novoXhr(ctx);

    xhr.open('POST', '/site/x/');
    xhr.send();
    xhr.responder(403, { error: 403, status: 'error' });
    await esperar();

    expect(xhr.onload).toHaveBeenCalledOnce();
    expect(ctx.chamadasRota()).toHaveLength(0);
    expect(xhr.aberturas).toHaveLength(1);
  });

  it('se a renovação falhar, entrega a resposta 403 original', async () => {
    const ctx = carregar({ rede: () => json(500, {}) });
    const xhr = novoXhr(ctx);

    xhr.open('POST', '/site/x/');
    xhr.send();
    xhr.responder(403, {}, { 'X-Gestor-Csrf-Error': 'CSRF_INVALID_OR_EXPIRED' });
    expect(xhr.onload).not.toHaveBeenCalled();

    await esperar();

    expect(xhr.onload).toHaveBeenCalledOnce();
    expect(xhr.status).toBe(403);
    expect(xhr.aberturas).toHaveLength(1);
  });

  it('segundo 403 de CSRF após o retry é entregue sem novo laço', async () => {
    const ctx = carregar({ rede: () => tokenOk() });
    const xhr = novoXhr(ctx);

    xhr.open('POST', '/site/x/');
    xhr.send();
    xhr.responder(403, { code: 'CSRF_INVALID_OR_EXPIRED' });
    await esperar();
    xhr.responder(403, { code: 'CSRF_INVALID_OR_EXPIRED' });
    await esperar();

    expect(xhr.onload).toHaveBeenCalledOnce();
    expect(xhr.aberturas).toHaveLength(2);
    expect(ctx.chamadasRota()).toHaveLength(1);
  });

  it('XHR síncrono não entra em retry', async () => {
    const ctx = carregar({ rede: () => tokenOk() });
    const xhr = novoXhr(ctx);

    xhr.open('POST', '/site/x/', false);
    xhr.send();
    xhr.responder(403, { code: 'CSRF_INVALID_OR_EXPIRED' });
    await esperar();

    expect(xhr.onload).toHaveBeenCalledOnce();
    expect(ctx.chamadasRota()).toHaveLength(0);
  });
});
