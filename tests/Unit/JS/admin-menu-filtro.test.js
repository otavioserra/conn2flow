import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it } from 'vitest';

/**
 * BATCH-103/BATCH-105 — filtro e navegacao por teclado do menu principal do painel,
 * espelhando o `c2f-modules-filter` da Editbar.
 *
 * Carrega o arquivo REAL (IIFE que expõe `window.gestorMenuFiltro`).
 */
function loadAdminJs() {
  const code = readFileSync(resolve(process.cwd(), 'gestor/assets/global/admin.js'), 'utf8');
  vm.runInThisContext(code, { filename: 'admin.js' });
  return window.gestorMenuFiltro;
}

// Espelha a marcação montada por `gestor_pagina_menu` a partir do componente menu-principal-sistema.
function montarMenu() {
  document.body.innerHTML = `
    <div class="ui basic segment menuConteiner">
      <div class="ui fluid icon input menuFiltro">
        <input type="text" id="gestor-menu-filtro" autocomplete="off" placeholder="Filtre os módulos...">
        <i class="search icon"></i>
      </div>
      <div class="ui tiny message menuFiltroVazio" style="display:none;">Nenhum módulo encontrado.</div>
      <div class="ui list">
        <div class="item">
          <div class="ui vertical fluid menu">
            <a class="item" href="/dashboard/"><div class="ui tiny header"><div class="content ajusteItem">Dashboard</div></div></a>
          </div>
        </div>
        <div class="item">
          <div class="ui small header">Publicações</div>
          <div class="ui vertical fluid menu">
            <a class="item" href="/admin-paginas/"><div class="ui tiny header"><div class="content ajusteItem">Páginas</div></div></a>
            <a class="item" href="/pages-index/"><div class="ui tiny header"><div class="content ajusteItem">Páginas Índice</div></div></a>
          </div>
        </div>
        <div class="item">
          <div class="ui small header">Configurações</div>
          <div class="ui vertical fluid menu">
            <a class="item" href="/usuarios/"><div class="ui tiny header"><div class="content ajusteItem">Usuários</div></div></a>
          </div>
        </div>
        <div class="item">
          <div class="ui vertical fluid menu">
            <a class="item" href="/signout/"><div class="ui tiny header"><div class="content ajusteItem">Sair</div></div></a>
          </div>
        </div>
      </div>
    </div>`;
}

const visiveis = () => Array.from(document.querySelectorAll('.menuConteiner a.item'))
  .filter((a) => a.style.display !== 'none')
  .map((a) => a.textContent.trim());

const blocosVisiveis = () => Array.from(document.querySelectorAll('.menuConteiner .ui.list > .item'))
  .filter((b) => b.style.display !== 'none').length;

function pressionar(elemento, key) {
  elemento.dispatchEvent(new window.KeyboardEvent('keydown', { key, bubbles: true, cancelable: true }));
}

describe('admin.js — filtro do menu principal (BATCH-103/BATCH-105)', () => {
  let api;

  beforeEach(() => {
    document.body.innerHTML = '';
    delete window.gestorMenuFiltro;
    montarMenu();
    api = loadAdminJs();
  });

  it('expõe a API e mantém o menu inteiro sem termo', () => {
    expect(typeof api.aplicar).toBe('function');
    expect(api.aplicar('')).toBe(5);
    expect(blocosVisiveis()).toBe(4);
    expect(document.querySelector('.menuFiltroVazio').style.display).toBe('none');
  });

  it('filtra os itens pelo texto digitado', () => {
    expect(api.aplicar('pag')).toBe(2);
    expect(visiveis()).toEqual(['Páginas', 'Páginas Índice']);
  });

  it('oculta o bloco do grupo (com o cabeçalho) quando nenhum item casa', () => {
    api.aplicar('pag');
    // Só o bloco de Publicações permanece.
    expect(blocosVisiveis()).toBe(1);
    const cabecalhos = Array.from(document.querySelectorAll('.menuConteiner .ui.list > .item'))
      .filter((b) => b.style.display !== 'none')
      .map((b) => (b.querySelector('.ui.small.header') || {}).textContent);
    expect(cabecalhos).toEqual(['Publicações']);
  });

  it('ignora acentuação e caixa na comparação', () => {
    expect(api.aplicar('usuarios')).toBe(1);
    expect(visiveis()).toEqual(['Usuários']);
    expect(api.aplicar('USUÁRIOS')).toBe(1);
    expect(api.aplicar('indice')).toBe(1);
    expect(visiveis()).toEqual(['Páginas Índice']);
  });

  it('filtra também os itens fixos (Dashboard e Sair), conforme decidido', () => {
    api.aplicar('sair');
    expect(visiveis()).toEqual(['Sair']);
    api.aplicar('dash');
    expect(visiveis()).toEqual(['Dashboard']);
  });

  it('mostra o aviso de vazio quando nada casa e o remove ao limpar', () => {
    expect(api.aplicar('inexistente')).toBe(0);
    expect(document.querySelector('.menuFiltroVazio').style.display).toBe('block');
    expect(blocosVisiveis()).toBe(0);

    expect(api.aplicar('')).toBe(5);
    expect(document.querySelector('.menuFiltroVazio').style.display).toBe('none');
    expect(blocosVisiveis()).toBe(4);
  });

  it('o campo reage ao digitar e o Esc limpa o filtro', () => {
    const campo = document.getElementById('gestor-menu-filtro');
    campo.value = 'pag';
    campo.dispatchEvent(new window.Event('input'));
    expect(visiveis()).toEqual(['Páginas', 'Páginas Índice']);

    campo.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'Escape' }));
    expect(campo.value).toBe('');
    expect(visiveis().length).toBe(5);
  });

  it('iniciar() é idempotente (não duplica o listener)', () => {
    api.iniciar();
    api.iniciar();
    const campo = document.getElementById('gestor-menu-filtro');
    expect(campo.getAttribute('data-gestor-menu-filtro')).toBe('1');
    campo.value = 'usuarios';
    campo.dispatchEvent(new window.Event('input'));
    expect(visiveis()).toEqual(['Usuários']);
  });

  it('percorre os resultados visiveis e volta ao filtro a partir do primeiro', () => {
    const campo = document.getElementById('gestor-menu-filtro');
    campo.value = 'pag';
    campo.dispatchEvent(new window.Event('input'));
    const resultados = Array.from(document.querySelectorAll('.menuConteiner a.item'))
      .filter((item) => item.style.display !== 'none');
    campo.focus();

    pressionar(campo, 'ArrowDown');
    expect(document.activeElement).toBe(resultados[0]);
    pressionar(resultados[0], 'ArrowDown');
    expect(document.activeElement).toBe(resultados[1]);

    // Nao ha ciclo: seta para baixo no ultimo mantem o ultimo resultado focado.
    pressionar(resultados[1], 'ArrowDown');
    expect(document.activeElement).toBe(resultados[1]);

    pressionar(resultados[1], 'ArrowUp');
    expect(document.activeElement).toBe(resultados[0]);
    pressionar(resultados[0], 'ArrowUp');
    expect(document.activeElement).toBe(campo);
  });

  it('nao tira o foco do filtro quando nao ha resultados', () => {
    const campo = document.getElementById('gestor-menu-filtro');
    campo.value = 'inexistente';
    campo.dispatchEvent(new window.Event('input'));
    campo.focus();

    pressionar(campo, 'ArrowDown');
    expect(document.activeElement).toBe(campo);
  });
});
