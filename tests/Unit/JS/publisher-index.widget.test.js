import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { describe, expect, it, vi } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

function runScript(relativePath) {
  const code = readFileSync(resolve(process.cwd(), relativePath), 'utf8');
  vm.runInThisContext(code, { filename: relativePath });
}

describe('publisher-index.widget.js', () => {
  it('debounces search input and sends the current filters to the widget endpoint', () => {
    vi.useFakeTimers();

    const ajax = vi.fn((options) => {
      options.success?.({ status: 'Ok', html: '<article>Novo item</article>', tem_mais: true });
      options.complete?.();
    });
    installJQueryStub({ ajax });

    document.body.innerHTML = `
      <div class="conn2flow-publisher-index" data-grupo-slug="noticias" data-ordenacao="date_desc">
        <input class="publisher-index-search">
        <select class="publisher-index-sort"><option value="date_desc">Mais recentes</option><option value="title_asc">Titulo</option></select>
        <div class="publisher-index-items"></div>
        <button class="publisher-index-load-more"></button>
      </div>
    `;

    runScript('gestor/modulos/publisher-index/publisher-index.widget.js');

    const input = document.querySelector('.publisher-index-search');
    input.value = 'fluxo';
    input.dispatchEvent(new Event('input', { bubbles: true }));

    vi.advanceTimersByTime(299);
    expect(ajax).not.toHaveBeenCalled();

    vi.advanceTimersByTime(1);
    expect(ajax).toHaveBeenCalledTimes(1);
    expect(ajax.mock.calls[0][0].data).toContain('ajaxOpcao=publisher-index-load');
    expect(ajax.mock.calls[0][0].data).toContain('ajaxRegistroId=noticias');
    expect(ajax.mock.calls[0][0].data).toContain('params%5Bbusca%5D=fluxo');
    expect(document.querySelector('.publisher-index-items').innerHTML).toContain('Novo item');
  });

  it('increments the page when the load-more button is clicked', () => {
    const ajax = vi.fn((options) => {
      options.success?.({ status: 'Ok', html: '<article>Mais</article>', tem_mais: false });
      options.complete?.();
    });
    installJQueryStub({ ajax });

    document.body.innerHTML = `
      <div class="conn2flow-publisher-index" data-grupo-slug="blog" data-ordenacao="date_desc">
        <div class="publisher-index-items"></div>
        <button class="publisher-index-load-more"></button>
      </div>
    `;

    runScript('gestor/modulos/publisher-index/publisher-index.widget.js');
    document.querySelector('.publisher-index-load-more').dispatchEvent(new MouseEvent('click', { bubbles: true }));

    expect(ajax).toHaveBeenCalledTimes(1);
    expect(ajax.mock.calls[0][0].data).toContain('params%5Bpagina%5D=2');
    expect(document.querySelector('.publisher-index-items').innerHTML).toContain('Mais');
  });
});
