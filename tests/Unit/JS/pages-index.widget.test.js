import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { describe, expect, it, vi } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

function runScript() {
  const code = readFileSync(resolve(process.cwd(), 'gestor/modulos/pages-index/pages-index.widget.js'), 'utf8');
  vm.runInThisContext(code, { filename: 'gestor/modulos/pages-index/pages-index.widget.js' });
}

function widgetHtml(items = '') {
  return `
    <section class="conn2flow-pages-index" data-grupo-slug="resultado-geral" data-ordenacao="date_desc">
      <input class="pages-index-search">
      <select class="pages-index-sort"><option value="date_desc">Recentes</option><option value="title_asc">Titulo</option></select>
      <span data-page-count></span><span data-page-total></span>
      <div class="pages-index-items">${items}</div>
      <button class="pages-index-load-more"></button>
    </section>`;
}

function response(html, temMais = false, total = 1) {
  return Promise.resolve({
    ok: true,
    json: () => Promise.resolve({ status: 'Ok', html, tem_mais: temMais, total })
  });
}

describe('pages-index.widget.js', () => {
  it('reads search from the URL, requests it immediately and highlights title and summary case-insensitively', async () => {
    history.replaceState({}, '', '/pages-index-search/?origem=form&search=Fluxo');
    installJQueryStub();
    document.body.innerHTML = widgetHtml(`
      <a href="/inicial/"><h3>FLUXO inicial</h3><p>Resumo sobre fluxo integrado.</p></a>
    `);

    const fetchMock = vi.fn(() => response(`
      <a href="/resultado/"><h3>Conheca o FLUXO</h3><p>Este fluxo ficou simples.</p></a>
    `));
    globalThis.fetch = fetchMock;

    runScript();

    expect(document.querySelector('.pages-index-search').value).toBe('Fluxo');
    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(String(fetchMock.mock.calls[0][1].body)).toContain('params%5Bbusca%5D=Fluxo');

    await vi.waitFor(() => {
      expect(document.querySelectorAll('mark[data-pages-index-highlight]')).toHaveLength(2);
    });
    expect(Array.from(document.querySelectorAll('mark')).map((mark) => mark.textContent)).toEqual(['FLUXO', 'fluxo']);
  });

  it('updates the URL immediately, debounces for 300ms and aborts an obsolete request', async () => {
    vi.useFakeTimers();
    history.replaceState({}, '', '/pages-index-search/?origem=form');
    installJQueryStub();
    document.body.innerHTML = widgetHtml();

    const pending = [];
    globalThis.fetch = vi.fn((_url, options) => new Promise((resolve) => pending.push({ resolve, options })));
    runScript();

    const input = document.querySelector('.pages-index-search');
    input.value = 'primeiro termo';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    expect(location.search).toContain('origem=form');
    expect(location.search).toContain('search=primeiro+termo');

    await vi.advanceTimersByTimeAsync(299);
    expect(fetch).not.toHaveBeenCalled();
    await vi.advanceTimersByTimeAsync(1);
    expect(fetch).toHaveBeenCalledTimes(1);

    input.value = 'segundo termo';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    expect(pending[0].options.signal.aborted).toBe(true);
    expect(location.search).toContain('search=segundo+termo');

    await vi.advanceTimersByTimeAsync(300);
    expect(fetch).toHaveBeenCalledTimes(2);
    pending[1].resolve(await response('<a href="/segundo/"><h3>Segundo termo</h3></a>'));
    await vi.advanceTimersByTimeAsync(0);
    expect(document.querySelector('.pages-index-items').textContent).toContain('Segundo termo');
  });

  it('reuses cached searches and supports ArrowUp, ArrowDown and Enter navigation', async () => {
    vi.useFakeTimers();
    history.replaceState({}, '', '/pages-index-search/');
    installJQueryStub();
    document.body.innerHTML = widgetHtml();

    globalThis.fetch = vi.fn((_url, options) => {
      const body = new URLSearchParams(String(options.body));
      const term = body.get('params[busca]');
      return response(`
        <a href="/${term}-1/"><h3>${term} um</h3><p>Resumo ${term}</p></a>
        <a href="/${term}-2/"><h3>${term} dois</h3><p>Outro ${term}</p></a>
      `, false, 2);
    });
    const clickSpy = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
    runScript();

    const input = document.querySelector('.pages-index-search');
    input.value = 'cache';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(300);
    expect(fetch).toHaveBeenCalledTimes(1);

    input.value = 'outro';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(300);
    expect(fetch).toHaveBeenCalledTimes(2);

    input.value = 'cache';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(300);
    expect(fetch).toHaveBeenCalledTimes(2);
    expect(document.querySelector('.pages-index-items').textContent).toContain('cache um');

    input.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
    expect(document.querySelectorAll('.pages-index-items > *')[0].classList.contains('pages-index-item-active')).toBe(true);
    input.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
    expect(document.querySelectorAll('.pages-index-items > *')[1].getAttribute('aria-selected')).toBe('true');
    input.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowUp', bubbles: true }));
    expect(document.querySelectorAll('.pages-index-items > *')[0].getAttribute('aria-selected')).toBe('true');
    input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
    expect(clickSpy).toHaveBeenCalledTimes(1);
  });

  it('caches load-more pages and appends them again without another request', async () => {
    vi.useFakeTimers();
    history.replaceState({}, '', '/pages-index-search/');
    installJQueryStub();
    document.body.innerHTML = widgetHtml();

    globalThis.fetch = vi.fn((_url, options) => {
      const body = new URLSearchParams(String(options.body));
      const term = body.get('params[busca]');
      const page = Number(body.get('params[pagina]'));
      return response(`<a href="/${term}-${page}/"><h3>${term} pagina ${page}</h3></a>`, page === 1, 2);
    });
    runScript();

    const input = document.querySelector('.pages-index-search');
    const loadMore = document.querySelector('.pages-index-load-more');

    input.value = 'alpha';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(300);
    loadMore.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(0);
    expect(fetch).toHaveBeenCalledTimes(2);
    expect(document.querySelector('.pages-index-items').textContent).toContain('alpha pagina 2');

    input.value = 'beta';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(300);
    expect(fetch).toHaveBeenCalledTimes(3);

    input.value = 'alpha';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(300);
    loadMore.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(0);

    expect(fetch).toHaveBeenCalledTimes(3);
    expect(document.querySelector('.pages-index-items').textContent).toContain('alpha pagina 1');
    expect(document.querySelector('.pages-index-items').textContent).toContain('alpha pagina 2');
  });
});
