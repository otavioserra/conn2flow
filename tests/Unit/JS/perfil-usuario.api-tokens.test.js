import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

/**
 * req-119 (BATCH-120) — aba de Chaves de API e códigos de recuperação do 2FA.
 *
 * Dois segredos aparecem em texto puro UMA única vez cada: o Personal Access Token, na resposta da
 * criação, e os 10 códigos de recuperação, na resposta da ativação do 2FA. Em ambos os casos o banco
 * guarda só hashes — se a interface perder o valor, ele não volta. É isso que estes testes cobrem.
 */
function carregarPainel() {
  installJQueryStub();

  const code = readFileSync(
    resolve(process.cwd(), 'gestor/modulos/perfil-usuario/perfil-usuario.js'),
    'utf8'
  );
  vm.runInThisContext(code, { filename: 'perfil-usuario.js' });
  return window.perfilUsuarioPainel;
}

// Espelha o HTML montado por `perfil_usuario_editar_api_tokens()`.
function montarPainel({ comTokens = true } = {}) {
  document.body.innerHTML = `
    <div id="perfil-usuario-painel" data-perfil-aba-padrao="dados">
      <nav role="tablist">
        <button type="button" data-perfil-aba="dados" class="border-transparent text-slate-500"></button>
        <button type="button" data-perfil-aba="seguranca" class="border-transparent text-slate-500"></button>
        <button type="button" data-perfil-aba="api-tokens" class="border-transparent text-slate-500"></button>
      </nav>
      <section data-perfil-painel="dados"></section>
      <section data-perfil-painel="seguranca" class="hidden">
        <div id="seg-seguranca">
          <div id="seg-msg" class="hidden"></div>
          <input id="seg-2fa-codigo" value="123456">
          <button type="button" id="btn-2fa-ativar"></button>
        </div>
      </section>
      <section data-perfil-painel="api-tokens" class="hidden">
        ${comTokens ? `
        <div id="seg-api-tokens">
          <div id="api-tokens-msg" class="hidden"></div>
          <button type="button" id="btn-api-token-novo"></button>
          <div id="api-token-form" class="hidden">
            <input type="text" id="api-token-nome" value="">
            <select id="api-token-expiracao"><option value="30" selected>30</option><option value="0">0</option></select>
            <label><input type="checkbox" class="api-token-escopo" value="read" checked></label>
            <label><input type="checkbox" class="api-token-escopo" value="write"></label>
            <label><input type="checkbox" class="api-token-escopo" value="deploy"></label>
            <button type="button" id="btn-api-token-criar"></button>
            <button type="button" id="btn-api-token-cancelar"></button>
          </div>
          <div id="api-token-novo" class="hidden">
            <code id="api-token-valor"></code>
            <button type="button" id="btn-api-token-copiar"></button>
          </div>
          <table id="api-tokens-lista">
            <tbody>
              <tr data-token-id="7">
                <td>CLI Local</td>
                <td><span class="bg-emerald-50 text-emerald-700">Ativa</span></td>
                <td><button class="btn-api-token-revogar" data-id="7"></button></td>
              </tr>
            </tbody>
          </table>
        </div>` : ''}
      </section>
    </div>
  `;
}

function clicar(id) {
  document.getElementById(id).dispatchEvent(new window.Event('click', { bubbles: true }));
}

describe('Perfil — Chaves de API e códigos de recuperação (req-119)', () => {
  let T;
  let respostas;
  let requisicoes;

  beforeEach(() => {
    document.body.innerHTML = '';
    window.localStorage.clear();

    requisicoes = [];
    respostas = { status: 'success', message: 'ok', token: 'c2f_pat_abcdef0123456789', prefixo: 'c2f_pat_abcdef01' };

    window.fetch = vi.fn((url, init) => {
      requisicoes.push({ url, body: init && init.body });
      return Promise.resolve({ status: 200, json: () => Promise.resolve(respostas) });
    });

    window.confirm = vi.fn(() => true);

    T = carregarPainel();
  });

  // ===================================================================================
  // Criação
  // ===================================================================================

  describe('criação de chave', () => {
    it('o formulário nasce escondido e o botão o revela', () => {
      montarPainel();
      T.iniciar();

      const form = document.getElementById('api-token-form');
      expect(form.classList.contains('hidden')).toBe(true);

      clicar('btn-api-token-novo');
      expect(form.classList.contains('hidden')).toBe(false);
    });

    it('cancelar esconde o formulário de volta', () => {
      montarPainel();
      T.iniciar();

      clicar('btn-api-token-novo');
      clicar('btn-api-token-cancelar');

      expect(document.getElementById('api-token-form').classList.contains('hidden')).toBe(true);
    });

    it('exige nome antes de chamar o servidor', () => {
      montarPainel();
      T.iniciar();

      clicar('btn-api-token-criar');

      expect(requisicoes.length).toBe(0);
      expect(document.getElementById('api-tokens-msg').classList.contains('hidden')).toBe(false);
    });

    it('nome só com espaços também é recusado', () => {
      montarPainel();
      T.iniciar();

      document.getElementById('api-token-nome').value = '   ';
      clicar('btn-api-token-criar');

      expect(requisicoes.length).toBe(0);
    });

    it('envia nome, expiração e escopos marcados', () => {
      montarPainel();
      T.iniciar();

      document.getElementById('api-token-nome').value = 'CLI Local';
      document.querySelector('.api-token-escopo[value="write"]').checked = true;

      clicar('btn-api-token-criar');

      expect(requisicoes.length).toBe(1);
      const corpo = requisicoes[0].body;
      expect(corpo).toContain('ajaxOpcao=api-token-gerar');
      expect(corpo).toContain('nome=CLI+Local');
      expect(corpo).toContain('expiracao=30');
      expect(corpo).toContain('escopos%5B%5D=read');
      expect(corpo).toContain('escopos%5B%5D=write');
      expect(corpo).not.toContain('escopos%5B%5D=deploy');
    });

    it('mostra o token recém-criado — a única vez em que ele existe em claro', async () => {
      montarPainel();
      T.iniciar();

      document.getElementById('api-token-nome').value = 'CLI Local';
      clicar('btn-api-token-criar');

      const caixa = document.getElementById('api-token-novo');
      await vi.waitFor(() => expect(caixa.classList.contains('hidden')).toBe(false));

      expect(document.getElementById('api-token-valor').textContent).toBe('c2f_pat_abcdef0123456789');
      expect(document.getElementById('api-token-form').classList.contains('hidden')).toBe(true);
    });

    it('erro do servidor não abre a caixa do token', async () => {
      respostas = { status: 'error', message: 'Não foi possível criar a chave de API.' };
      montarPainel();
      T.iniciar();

      document.getElementById('api-token-nome').value = 'CLI Local';
      clicar('btn-api-token-criar');

      const msg = document.getElementById('api-tokens-msg');
      await vi.waitFor(() => expect(msg.classList.contains('hidden')).toBe(false));

      expect(msg.classList.contains('bg-red-50')).toBe(true);
      expect(document.getElementById('api-token-novo').classList.contains('hidden')).toBe(true);
    });

    it('a página não recarrega depois de criar — recarregar apagaria o token da tela', async () => {
      montarPainel();
      T.iniciar();

      document.getElementById('api-token-nome').value = 'CLI Local';
      clicar('btn-api-token-criar');

      await vi.waitFor(() =>
        expect(document.getElementById('api-token-novo').classList.contains('hidden')).toBe(false)
      );
      expect(document.getElementById('api-token-valor').textContent).not.toBe('');
    });
  });

  describe('cópia do token', () => {
    it('usa a Clipboard API quando disponível', async () => {
      const writeText = vi.fn(() => Promise.resolve());
      Object.defineProperty(window.navigator, 'clipboard', { value: { writeText }, configurable: true });

      montarPainel();
      T.iniciar();
      document.getElementById('api-token-valor').textContent = 'c2f_pat_abcdef0123456789';

      clicar('btn-api-token-copiar');

      expect(writeText).toHaveBeenCalledWith('c2f_pat_abcdef0123456789');
    });

    it('sem Clipboard API seleciona o texto para permitir Ctrl+C', () => {
      // Contexto não seguro (HTTP) não expõe a Clipboard API; deixar o botão inerte tiraria a única
      // chance do usuário de guardar o token.
      Object.defineProperty(window.navigator, 'clipboard', { value: undefined, configurable: true });

      montarPainel();
      T.iniciar();
      document.getElementById('api-token-valor').textContent = 'c2f_pat_abcdef0123456789';

      expect(() => clicar('btn-api-token-copiar')).not.toThrow();
    });
  });

  // ===================================================================================
  // Revogação
  // ===================================================================================

  describe('revogação de chave', () => {
    it('pede confirmação e envia o id', async () => {
      montarPainel();
      T.iniciar();

      document.querySelector('.btn-api-token-revogar').dispatchEvent(new window.Event('click', { bubbles: true }));
      await vi.waitFor(() => expect(requisicoes.length).toBe(1));

      expect(window.confirm).toHaveBeenCalled();
      expect(requisicoes[0].body).toContain('ajaxOpcao=api-token-revogar');
      expect(requisicoes[0].body).toContain('id=7');
    });

    it('cancelar não envia nada', () => {
      window.confirm = vi.fn(() => false);
      montarPainel();
      T.iniciar();

      document.querySelector('.btn-api-token-revogar').dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(requisicoes.length).toBe(0);
    });

    it('a linha permanece na tabela por auditoria, só muda de estado', async () => {
      // Apagar a linha destruiria "criada em" e "último uso" — a auditoria da própria credencial.
      montarPainel();
      T.iniciar();

      document.querySelector('.btn-api-token-revogar').dispatchEvent(new window.Event('click', { bubbles: true }));

      await vi.waitFor(() => expect(document.querySelector('.btn-api-token-revogar')).toBe(null));

      const linha = document.querySelector('[data-token-id="7"]');
      expect(linha).not.toBe(null);

      const etiqueta = linha.querySelector('span');
      expect(etiqueta.className).not.toContain('bg-emerald-50');
      expect(etiqueta.className).toContain('bg-slate-100');
    });

    it('sem a aba de tokens o painel continua funcionando', () => {
      // Perfil não autorizado pela política da API não recebe a aba.
      montarPainel({ comTokens: false });
      expect(() => T.iniciar()).not.toThrow();
    });
  });

  // ===================================================================================
  // Códigos de recuperação
  // ===================================================================================

  describe('códigos de recuperação do 2FA', () => {
    const codigos = ['ABCD-2345', 'EFGH-6789', 'JKMN-3456', 'PQRS-7890', 'TUVW-2345',
                     'XYZA-6789', 'BCDE-3456', 'FGHJ-7890', 'KMNP-2345', 'QRST-6789'];

    it('desenha os dez códigos devolvidos pela ativação', () => {
      montarPainel();
      T.iniciar();

      const desenhou = T.mostrarRecoveryCodes({ recovery_codes: codigos, recovery_title: 'Códigos', recovery_help: 'Guarde.' });

      expect(desenhou).toBe(true);
      const itens = document.querySelectorAll('#seg-recovery-codes li');
      expect(itens.length).toBe(10);
      expect(itens[0].textContent).toBe('ABCD-2345');
    });

    it('resposta sem códigos não desenha nada', () => {
      montarPainel();
      T.iniciar();

      expect(T.mostrarRecoveryCodes({ status: 'success' })).toBe(false);
      expect(T.mostrarRecoveryCodes({ recovery_codes: [] })).toBe(false);
      expect(document.getElementById('seg-recovery-codes')).toBe(null);
    });

    it('ativar o 2FA NÃO recarrega a página quando vêm códigos', async () => {
      // Recarregar destruiria os códigos antes de o usuário anotá-los, e não há endpoint que os
      // recupere — o banco só guarda os hashes.
      respostas = { status: 'success', message: '2FA ativado', recovery_codes: codigos };
      montarPainel();
      T.iniciar();

      clicar('btn-2fa-ativar');

      await vi.waitFor(() => expect(document.getElementById('seg-recovery-codes')).not.toBe(null));
      expect(document.querySelectorAll('#seg-recovery-codes li').length).toBe(10);
    });

    it('os códigos são escritos como texto, nunca como HTML', () => {
      montarPainel();
      T.iniciar();

      T.mostrarRecoveryCodes({ recovery_codes: ['<img src=x onerror=alert(1)>'] });

      const item = document.querySelector('#seg-recovery-codes li');
      expect(item.querySelector('img')).toBe(null);
      expect(item.textContent).toBe('<img src=x onerror=alert(1)>');
    });

    it('sem o bloco de segurança na página nada é desenhado', () => {
      document.body.innerHTML = '<div id="perfil-usuario-painel"></div>';
      T.iniciar();

      expect(T.mostrarRecoveryCodes({ recovery_codes: codigos })).toBe(false);
    });
  });
});
