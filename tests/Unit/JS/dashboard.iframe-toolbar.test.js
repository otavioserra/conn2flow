import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it } from 'vitest';

/**
 * BATCH-103 (correção) — filtro de módulos da Editbar (`#c2f-modules-filter`).
 *
 * O filtro comparava texto cru: digitar 'pa' NÃO encontrava 'Páginas', porque o segundo caractere do
 * texto é acentuado. Agora a comparação ignora acentuação e caixa, igual ao filtro do menu do painel.
 *
 * O arquivo roda dentro do iframe da barra e se inicia sozinho (`initToolbar` no DOMContentLoaded);
 * montamos o DOM mínimo ANTES de carregá-lo, espelhando a marcação de `dashboard_site_toolbar_menu`.
 */
function montarBarra() {
  document.body.innerHTML = `
    <div id="c2f-toolbar-root" data-page-id="pagina-teste">
      <div id="c2f-toolbar-menu" class="relative group flex items-stretch">
        <button type="button">Módulos</button>
        <ul>
          <li class="px-2 pt-2 pb-1">
            <input id="c2f-modules-filter" type="text" autocomplete="off" placeholder="Filtre os módulos...">
          </li>
          <li class="c2f-group-header" data-group="publicacoes">Publicações</li>
          <li class="c2f-menu-item"><a href="/admin-paginas/" target="_parent">Páginas</a></li>
          <li class="c2f-menu-item"><a href="/pages-index/" target="_parent">Páginas Índice</a></li>
          <li class="c2f-group-header" data-group="config">Configurações</li>
          <li class="c2f-menu-item"><a href="/usuarios/" target="_parent">Usuários</a></li>
          <li class="c2f-menu-item"><a href="/admin-arquivos/" target="_parent">Arquivos</a></li>
        </ul>
      </div>
    </div>`;
}

function carregarBarra() {
  const code = readFileSync(
    resolve(process.cwd(), 'gestor/modulos/dashboard/dashboard.iframe-toolbar.js'), 'utf8');
  vm.runInThisContext(code, { filename: 'dashboard.iframe-toolbar.js' });
}

const itensVisiveis = () => Array.from(document.querySelectorAll('.c2f-menu-item'))
  .filter((li) => li.style.display !== 'none')
  .map((li) => li.textContent.trim());

const gruposVisiveis = () => Array.from(document.querySelectorAll('.c2f-group-header'))
  .filter((li) => li.style.display !== 'none')
  .map((li) => li.textContent.trim());

function digitar(valor) {
  const campo = document.getElementById('c2f-modules-filter');
  campo.value = valor;
  campo.dispatchEvent(new window.Event('input'));
}

describe('dashboard.iframe-toolbar.js — filtro de módulos da Editbar', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    montarBarra();
    carregarBarra();
  });

  it('encontra itens acentuados ao digitar sem acento (regressão do BATCH-103)', () => {
    digitar('pa');
    expect(itensVisiveis()).toEqual(['Páginas', 'Páginas Índice']);

    digitar('usuarios');
    expect(itensVisiveis()).toEqual(['Usuários']);

    digitar('indice');
    expect(itensVisiveis()).toEqual(['Páginas Índice']);
  });

  it('continua encontrando quando o termo é digitado COM acento', () => {
    digitar('páginas');
    expect(itensVisiveis()).toEqual(['Páginas', 'Páginas Índice']);
  });

  it('ignora a caixa do texto', () => {
    digitar('ARQUIVOS');
    expect(itensVisiveis()).toEqual(['Arquivos']);
  });

  it('oculta o cabeçalho do grupo que fica sem itens visíveis', () => {
    digitar('pa');
    expect(gruposVisiveis()).toEqual(['Publicações']);
  });

  it('limpar o campo restaura o menu inteiro', () => {
    digitar('pa');
    expect(itensVisiveis().length).toBe(2);

    digitar('');
    expect(itensVisiveis().length).toBe(4);
    expect(gruposVisiveis().length).toBe(2);
  });
});
