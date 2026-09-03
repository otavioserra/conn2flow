import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * REQ-038 / BATCH-165 — o controlador do painel Admin Cron nunca chegava a rodar.
 *
 * `gestor_pagina_javascript_incluir()` empilha a tag do módulo em `$_GESTOR['javascript-fim']`, e
 * `gestor_pagina_javascript()` a injeta no marcador `<!-- pagina#js -->`. Esse marcador vive no
 * `<head>` de TODOS os layouts do gestor (no `layout-administrativo-tailwind`, linha 30 de 102).
 * `admin-cron.js` lia o DOM na primeira instrução da IIFE, então `#admin-cron-painel` era sempre
 * `null` e o painel nascia inerte: tabela vazia e botões sem ouvinte.
 *
 * O teste avalia o arquivo REAL no mesmo estado do navegador — `document.readyState === 'loading'`
 * e `<body>` ainda vazio — e só então monta o painel e dispara `DOMContentLoaded`.
 */

const CAMINHO_SCRIPT = resolve(process.cwd(), 'gestor/modulos/admin-cron/admin-cron.js');

const TAREFAS = [
  {
    id: 'host-manager-provisionar-fila',
    nome: 'Fila de provisionamento',
    descricao: 'Provisiona contas pendentes',
    modulo: 'host-manager',
    frequencia: 'minutario',
    expressao_cron: '*/10 * * * *',
    funcao_callback: 'host_manager_cron_provisionar_fila',
    parametros: '',
    ativo: 1,
    ultimo_disparo: '2026-09-03 09:00:00',
    ultima_duracao_ms: 1200,
    ultimo_status: 'sucesso',
    ultimo_log: 'ok',
    origem: 'modulo',
    user_modified: 0
  }
];

/**
 * Espelha o painel real de `resources/<lang>/pages/admin-cron/admin-cron.html`, já com os
 * marcadores que o PHP substitui em runtime.
 */
function htmlDoPainel() {
  return `
    <div id="admin-cron-painel"
        data-cron-tarefas='${JSON.stringify(TAREFAS)}'
        data-cron-modulos='["host-manager"]'
        data-msg-empty="Nenhuma tarefa cadastrada."
        data-msg-confirm-delete="Confirma a exclusão?"
        data-msg-never-run="Nunca executada"
        data-msg-paused="Pausada"
        data-msg-logs-empty="Sem saída registrada."
        data-msg-generic-error="Não foi possível concluir a operação."
        data-label-run="Executar agora"
        data-label-pause="Pausar"
        data-label-activate="Ativar"
        data-label-edit="Editar"
        data-label-logs="Logs"
        data-label-delete="Excluir"
        data-label-origin-modulo="Módulo"
        data-label-origin-manual="Manual"
        data-label-status-sucesso="Sucesso"
        data-label-status-erro="Erro"
        data-label-status-aviso="Aviso"
        data-label-freq-minutario="Minutário"
        data-label-freq-horario="Horário"
        data-label-freq-diario="Diário"
        data-label-freq-mensal="Mensal"
        data-label-freq-customizado="Customizado"
        data-title-new="Nova tarefa"
        data-title-edit="Editar tarefa">
      <button type="button" id="cron-btn-sync"></button>
      <button type="button" id="cron-btn-new"></button>
      <div id="cron-mensagem" class="mb-4 hidden rounded-lg border px-4 py-3 text-sm"></div>
      <span id="cron-stat-total"></span>
      <span id="cron-stat-ativas"></span>
      <span id="cron-stat-execucoes"></span>
      <input id="cron-filtro-busca">
      <select id="cron-filtro-frequencia"><option value=""></option></select>
      <select id="cron-filtro-modulo"><option value=""></option></select>
      <table><tbody id="cron-tabela-corpo"></tbody></table>

      <div id="cron-modal" class="hidden">
        <h2 id="cron-modal-titulo"></h2>
        <button type="button" id="cron-modal-fechar"></button>
        <form id="cron-form">
          <p id="cron-form-aviso" class="hidden"></p>
          <input id="cron-form-id">
          <input id="cron-form-nome">
          <input id="cron-form-descricao">
          <select id="cron-form-frequencia">
            <option value="diario"></option>
            <option value="minutario"></option>
          </select>
          <input id="cron-form-expressao">
          <input id="cron-form-callback">
          <input id="cron-form-modulo">
          <input id="cron-form-parametros">
          <input type="checkbox" id="cron-form-ativo">
          <button type="button" id="cron-form-cancelar"></button>
          <button type="submit" id="cron-form-salvar"></button>
        </form>
      </div>

      <div id="cron-logs" class="hidden">
        <button type="button" id="cron-logs-fechar"></button>
        <span id="cron-logs-tarefa"></span>
        <span id="cron-logs-disparo"></span>
        <span id="cron-logs-duracao"></span>
        <span id="cron-logs-status"></span>
        <pre id="cron-logs-saida"></pre>
      </div>
    </div>`;
}

/** Força `document.readyState` para o valor que o navegador reporta durante o parse do `<head>`. */
function fingirDocumentoCarregando(valor) {
  Object.defineProperty(document, 'readyState', {
    configurable: true,
    get: () => valor
  });
}

function avaliarScript() {
  const codigo = readFileSync(CAMINHO_SCRIPT, 'utf8');
  vm.runInThisContext(codigo, { filename: 'admin-cron.js' });
}

describe('admin-cron.js — inicialização do painel', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    fingirDocumentoCarregando('loading');
  });

  it('espera o DOM quando é avaliado no <head>, antes de o painel existir', () => {
    // Estado real: o marcador `<!-- pagina#js -->` está no <head>; não há <body> montado.
    expect(document.getElementById('admin-cron-painel')).toBeNull();
    avaliarScript();

    // O <body> chega depois, como no parse normal da página.
    document.body.innerHTML = htmlDoPainel();
    fingirDocumentoCarregando('interactive');
    document.dispatchEvent(new Event('DOMContentLoaded'));

    const corpo = document.getElementById('cron-tabela-corpo');
    expect(corpo.innerHTML).toContain('Fila de provisionamento');
    expect(corpo.innerHTML).toContain('host-manager-provisionar-fila');
    expect(corpo.innerHTML).toContain('*/10 * * * *');
    // O filtro de módulos também é preenchido a partir do dataset entregue pelo PHP.
    expect(document.getElementById('cron-filtro-modulo').options.length).toBe(2);
  });

  it('abre o modal de nova tarefa no clique de "Nova tarefa"', () => {
    avaliarScript();
    document.body.innerHTML = htmlDoPainel();
    fingirDocumentoCarregando('interactive');
    document.dispatchEvent(new Event('DOMContentLoaded'));

    const modal = document.getElementById('cron-modal');
    expect(modal.classList.contains('hidden')).toBe(true);

    document.getElementById('cron-btn-new').dispatchEvent(new Event('click', { bubbles: true }));

    expect(modal.classList.contains('hidden')).toBe(false);
    expect(modal.classList.contains('flex')).toBe(true);
    expect(document.getElementById('cron-modal-titulo').textContent).toBe('Nova tarefa');
  });

  it('dispara a sincronização de módulos no clique de "Sincronizar"', async () => {
    const fetchMock = vi.fn(() =>
      Promise.resolve({
        ok: true,
        status: 200,
        json: () => Promise.resolve({ status: 'Ok', message: 'Sincronizado.', data: { tarefas: TAREFAS, estatisticas: { total: 1, ativas: 1, execucoes: 0 } } })
      })
    );
    vi.stubGlobal('fetch', fetchMock);

    avaliarScript();
    document.body.innerHTML = htmlDoPainel();
    fingirDocumentoCarregando('interactive');
    document.dispatchEvent(new Event('DOMContentLoaded'));

    document.getElementById('cron-btn-sync').dispatchEvent(new Event('click', { bubbles: true }));

    expect(fetchMock).toHaveBeenCalledTimes(1);
    const corpo = fetchMock.mock.calls[0][1].body;
    expect(corpo.get('ajax')).toBe('sim');
    expect(corpo.get('ajaxOpcao')).toBe('sincronizar');
    expect(corpo.get('opcao')).toBe('painel');

    await vi.waitFor(() => {
      expect(document.getElementById('cron-stat-total').textContent).toBe('1');
    });
    expect(document.getElementById('cron-mensagem').textContent).toBe('Sincronizado.');
  });

  it('inicializa de imediato quando o documento já está montado', () => {
    document.body.innerHTML = htmlDoPainel();
    fingirDocumentoCarregando('complete');

    avaliarScript();

    expect(document.getElementById('cron-tabela-corpo').innerHTML).toContain('Fila de provisionamento');
  });

  it('não quebra quando a página carregada não é a do painel', () => {
    fingirDocumentoCarregando('complete');
    expect(() => avaliarScript()).not.toThrow();
  });
});
