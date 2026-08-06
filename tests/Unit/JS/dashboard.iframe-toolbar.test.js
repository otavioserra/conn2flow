import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it } from 'vitest';

/**
 * BATCH-103/BATCH-105 — filtro e navegacao por teclado dos modulos da Editbar.
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

function pressionar(elemento, key) {
  elemento.dispatchEvent(new window.KeyboardEvent('keydown', { key, bubbles: true, cancelable: true }));
}

describe('dashboard.iframe-toolbar.js — filtro de modulos da Editbar (BATCH-103/BATCH-105)', () => {
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

  it('percorre somente resultados visiveis e volta ao filtro a partir do primeiro', () => {
    const campo = document.getElementById('c2f-modules-filter');
    const links = Array.from(document.querySelectorAll('.c2f-menu-item a'));
    digitar('pa');
    campo.focus();

    pressionar(campo, 'ArrowDown');
    expect(document.activeElement).toBe(links[0]);

    pressionar(links[0], 'ArrowDown');
    expect(document.activeElement).toBe(links[1]);

    // O proximo item do DOM esta filtrado; no ultimo resultado o foco permanece nele.
    pressionar(links[1], 'ArrowDown');
    expect(document.activeElement).toBe(links[1]);

    pressionar(links[1], 'ArrowUp');
    expect(document.activeElement).toBe(links[0]);
    pressionar(links[0], 'ArrowUp');
    expect(document.activeElement).toBe(campo);
  });
});

/**
 * BATCH-106 — botão "Opções de Exibição" da Editbar. O painel vive na página HOSPEDEIRA (junto do
 * motor), então a barra só publica a posição do botão, como já faz o painel "+".
 */
describe('dashboard.iframe-toolbar.js — botão de opções de exibição (BATCH-106)', () => {
  let postadas;

  beforeEach(() => {
    document.body.innerHTML = `
      <div id="c2f-toolbar-root" data-page-id="pagina-teste">
        <div id="c2f-toolbar-editbar">
          <button type="button" id="c2f-tb-view-options">Opções de Exibição</button>
        </div>
      </div>`;
    carregarBarra();
    postadas = [];
    window.parent.postMessage = (msg) => { postadas.push(msg); };
  });

  it('publica c2f-toolbar:edit-view-options com a posição do botão', () => {
    document.getElementById('c2f-tb-view-options')
      .dispatchEvent(new window.MouseEvent('click', { bubbles: true }));

    const msg = postadas.find((m) => m && m.type === 'c2f-toolbar:edit-view-options');
    expect(msg).toBeTruthy();
    expect(typeof msg.x).toBe('number');
    expect(typeof msg.y).toBe('number');
  });

  it('avisa o host (ui-dismiss) a cada clique na barra, para fechar os painéis abertos', () => {
    // Clique em área "vazia" da barra: o aviso de fechamento é publicado.
    // (Cada `carregarBarra()` do arquivo de teste soma um listener no mesmo `document`, então aqui
    // conta-se a presença, não a quantidade — em produção o script é carregado uma única vez.)
    document.getElementById('c2f-toolbar-editbar')
      .dispatchEvent(new window.MouseEvent('mousedown', { bubbles: true }));
    expect(postadas.filter((m) => m && m.type === 'c2f-toolbar:ui-dismiss').length).toBeGreaterThan(0);

    // Clique no botão que abre um painel: o mousedown fecha o que estava aberto ANTES do click
    // abrir o painel pedido — nessa ordem o botão continua funcionando.
    postadas = [];
    const botao = document.getElementById('c2f-tb-view-options');
    botao.dispatchEvent(new window.MouseEvent('mousedown', { bubbles: true }));
    botao.dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
    const tipos = postadas.map((m) => m && m.type).filter(Boolean);
    expect(tipos.indexOf('c2f-toolbar:ui-dismiss'))
      .toBeLessThan(tipos.indexOf('c2f-toolbar:edit-view-options'));
  });
});
