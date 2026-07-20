import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { describe, expect, it, vi } from 'vitest';

function runScript(relativePath) {
  const code = readFileSync(resolve(process.cwd(), relativePath), 'utf8');
  vm.runInThisContext(code, { filename: relativePath });
}

describe('forms-search.widget.js', () => {
  it('injects the widget controller and AJAX signature into the CRUD preview srcdoc', () => {
    const source = readFileSync(resolve(process.cwd(), 'gestor/modulos/forms-search/forms-search.js'), 'utf8');
    expect(source).toContain("{ src: gestor.raiz + 'forms-search/widget.js?v=' + gestor.versao }");
    expect(source).toContain('widgetsToAjax: previewWidgetsToAjax');
    expect(source).toContain("forms-search->render(' + JSON.stringify({ form_id: currentSlug() }) + ')'");
    expect(source).toContain("return 'forms-search-preview';");
  });

  it('applies the autocomplete contract, pagination, keyboard selection, and local cache', async () => {
    vi.useFakeTimers();
    gestor.language = 'pt-br';
    gestor.widgetsToAjax = 'forms-search->render({"form_id":"busca-site"})';

    const fetchMock = vi.fn((_url, options) => {
      const body = String(options.body);
      const page = body.includes('params%5Bpage%5D=2') ? 2 : 1;
      return Promise.resolve({
        ok: true,
        json: () => Promise.resolve({
          status: 'Ok',
          results: [{
            title: page === 1 ? 'Busca avançada' : 'Busca adicional',
            summary: 'Resumo da busca',
            url: page === 1 ? '/avancada/' : '/adicional/'
          }],
          tem_mais: page === 1,
          pagina: page
        })
      });
    });
    globalThis.fetch = fetchMock;

    document.body.innerHTML = `
      <form class="conn2flow-search-form" data-form-id="busca-site">
        <input name="search" aria-expanded="false">
        <div class="forms-search-results"></div>
      </form>
    `;

    runScript('gestor/modulos/forms-search/forms-search.widget.js');
    const input = document.querySelector('input[name="search"]');

    input.value = 'bu';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(300);
    expect(fetchMock).not.toHaveBeenCalled();

    input.value = 'busca';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(299);
    expect(fetchMock).not.toHaveBeenCalled();
    await vi.advanceTimersByTimeAsync(1);
    await Promise.resolve();

    expect(fetchMock).toHaveBeenCalledTimes(1);
    const firstBody = String(fetchMock.mock.calls[0][1].body);
    expect(firstBody).toContain('ajaxOpcao=forms-search-autocomplete');
    expect(firstBody).toContain('ajaxRegistroId=busca-site');
    expect(firstBody).toContain('ajaxWidgets=forms-search-%3Erender');
    expect(firstBody).toContain('params%5Bsearch%5D=busca');
    expect(document.querySelector('.forms-search-result mark')?.textContent.toLowerCase()).toBe('busca');
    expect(document.querySelector('.forms-search-load-more')?.textContent).toBe('Carregar mais');

    input.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
    expect(document.querySelector('.forms-search-result')?.getAttribute('aria-selected')).toBe('true');

    document.querySelector('.forms-search-load-more').click();
    await vi.advanceTimersByTimeAsync(0);
    expect(fetchMock).toHaveBeenCalledTimes(2);
    expect(String(fetchMock.mock.calls[1][1].body)).toContain('params%5Bpage%5D=2');
    await vi.waitFor(() => expect(document.querySelectorAll('.forms-search-result')).toHaveLength(2));

    input.value = 'bu';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.value = 'busca';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    await vi.advanceTimersByTimeAsync(300);
    expect(fetchMock).toHaveBeenCalledTimes(2);
  });
});
