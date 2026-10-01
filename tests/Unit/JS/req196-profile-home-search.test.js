import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

describe('REQ-196 busca de página inicial', () => {
  afterEach(() => vi.useRealTimers());

  it('consulta páginas sob demanda e grava somente a rota escolhida', () => {
    vi.useFakeTimers();
    document.body.innerHTML = `
      <div class="profile-tabs"><a class="item"></a></div>
      <div class="home-page-search">
        <input id="pagina-inicial-busca" value="Portal" aria-expanded="false">
        <div id="pagina-inicial-suggestions" class="ui vertical menu" data-no-results="Nenhuma página" hidden></div>
      </div>
      <input type="hidden" name="pagina_inicial" value="portal/">
      <button class="home-page-clear"></button>`;
    globalThis.gestor = { raiz: '/', moduloCaminho: 'usuarios-perfis', moduloOpcao: 'adicionar' };
    const ajax = vi.fn(() => ({ abort: vi.fn() }));
    installJQueryStub({ ajax });
    vm.runInThisContext(readFileSync(resolve('gestor/modulos/usuarios-perfis/usuarios-perfis.js'), 'utf8'));

    const input = document.getElementById('pagina-inicial-busca');
    input.value = 'd';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    vi.advanceTimersByTime(350);
    expect(ajax).not.toHaveBeenCalled();
    expect(document.querySelector('[name="pagina_inicial"]').value).toBe('');

    input.value = 'dash';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    vi.advanceTimersByTime(300);
    expect(ajax).toHaveBeenCalledTimes(1);
    const request = ajax.mock.calls[0][0];
    expect(request.data).toMatchObject({ ajax: 'sim', ajaxOpcao: 'buscar-pagina-inicial', q: 'dash' });
    // req-204: o popup só aparece com resultado, e some de novo ao escolher (atributo `hidden`).
    const suggestions = document.getElementById('pagina-inicial-suggestions');
    const clear = document.querySelector('.home-page-clear');
    expect(suggestions.hidden).toBe(true);
    request.success({ status: 'Ok', results: [{ value: 'dashboard/', name: 'Dashboard (dashboard)' }] });
    expect(suggestions.hidden).toBe(false);
    document.querySelector('#pagina-inicial-suggestions .item').click();
    expect(suggestions.hidden).toBe(true);
    expect(clear.hidden).toBe(false);
    expect(document.querySelector('[name="pagina_inicial"]').value).toBe('dashboard/');
    expect(input.value).toBe('Dashboard (dashboard)');
    document.querySelector('.home-page-clear').click();
    expect(document.querySelector('[name="pagina_inicial"]').value).toBe('');
    expect(clear.hidden).toBe(true);

    input.value = 'dash';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    vi.advanceTimersByTime(300);
    ajax.mock.calls[1][0].success({ status: 'Ok', results: [{ value: 'dashboard/', name: 'Dashboard' }] });
    document.body.click();
  });
});
