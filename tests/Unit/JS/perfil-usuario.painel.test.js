import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

/**
 * req-118 (BATCH-119) — painel do Perfil do Usuário em Tailwind.
 *
 * Carrega o arquivo REAL do módulo (IIFE que expõe `window.perfilUsuarioPainel`). O topo do arquivo
 * ainda tem o `$(document).ready` das telas de login/2FA, que continuam em Fomantic — daí o stub de
 * jQuery: sem ele o arquivo nem chega a ser avaliado, e o runtime novo (100% vanilla) não seria
 * exercitado no mesmo estado em que roda no navegador.
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

// Espelha o HTML real da página (resources/<lang>/pages/perfil-usuario) já com os blocos que o PHP
// preenche em runtime.
function montarPainel({ comSenha = true, comSessoes = true, comSeguranca = true, comHistorico = true } = {}) {
  document.body.innerHTML = `
    <div id="perfil-usuario-painel" data-perfil-aba-padrao="dados">
      <nav role="tablist">
        <button type="button" role="tab" data-perfil-aba="dados" class="border-transparent text-slate-500">Dados</button>
        <button type="button" role="tab" data-perfil-aba="seguranca" class="border-transparent text-slate-500">Segurança</button>
        <button type="button" role="tab" data-perfil-aba="sessoes" class="border-transparent text-slate-500">Sessões</button>
      </nav>
      <section data-perfil-painel="dados">
        ${comSenha ? `
        <div data-perfil-senha-bloco>
          <input type="password" data-perfil-senha>
          <div data-perfil-senha-barra class="w-0 bg-slate-300"></div>
          <p data-perfil-senha-rotulo class="text-slate-500"></p>
        </div>` : ''}
      </section>
      <section data-perfil-painel="seguranca" class="hidden">
        ${comSeguranca ? `
        <div id="seg-seguranca">
          <div id="seg-msg" class="hidden"></div>
          <input id="seg-2fa-codigo" value="123456">
          <input id="seg-2fa-senha" value="segredo">
          <button type="button" id="btn-2fa-ativar"></button>
          <button type="button" class="btn-social-desvincular" data-provider="google"></button>
        </div>` : ''}
      </section>
      <section data-perfil-painel="sessoes" class="hidden">
        ${comSessoes ? `
        <div id="seg-sessoes">
          <div id="sessoes-msg" class="hidden"></div>
          <button type="button" id="btn-sessoes-revogar-outras"></button>
          <div id="sessoes-lista">
            <article data-sessao-pubid="atual"><span>Este dispositivo</span></article>
            <article data-sessao-pubid="outra1"><button class="btn-sessao-revogar" data-pubid="outra1"></button></article>
            <article data-sessao-pubid="outra2"><button class="btn-sessao-revogar" data-pubid="outra2"></button></article>
          </div>
        </div>` : ''}
      </section>
    </div>
    ${comHistorico ? '<div data-c2f-historico><table></table></div>' : ''}
  `;
}

function aba(nome) {
  return document.querySelector(`[data-perfil-aba="${nome}"]`);
}

function painelDe(nome) {
  return document.querySelector(`[data-perfil-painel="${nome}"]`);
}

describe('Perfil do Usuário — painel Tailwind (req-118)', () => {
  let T;
  let respostas;
  let requisicoes;

  beforeEach(() => {
    document.body.innerHTML = '';
    window.localStorage.clear();

    requisicoes = [];
    respostas = { status: 'success', message: 'ok' };

    window.fetch = vi.fn((url, init) => {
      requisicoes.push({ url, body: init && init.body });
      return Promise.resolve({
        status: 200,
        json: () => Promise.resolve(respostas)
      });
    });

    window.confirm = vi.fn(() => true);

    T = carregarPainel();
  });

  // ===================================================================================
  // Abas
  // ===================================================================================

  describe('navegação entre abas', () => {
    it('abre na aba padrão e mostra apenas o painel dela', () => {
      montarPainel();
      T.iniciar();

      expect(painelDe('dados').classList.contains('hidden')).toBe(false);
      expect(painelDe('seguranca').classList.contains('hidden')).toBe(true);
      expect(painelDe('sessoes').classList.contains('hidden')).toBe(true);
      expect(aba('dados').getAttribute('aria-selected')).toBe('true');
    });

    it('troca de aba sem recarregar a página', () => {
      montarPainel();
      T.iniciar();

      aba('sessoes').dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(painelDe('sessoes').classList.contains('hidden')).toBe(false);
      expect(painelDe('dados').classList.contains('hidden')).toBe(true);
      expect(aba('sessoes').getAttribute('aria-selected')).toBe('true');
      expect(aba('dados').getAttribute('aria-selected')).toBe('false');
    });

    it('marca visualmente a aba ativa e desmarca a anterior', () => {
      montarPainel();
      T.iniciar();

      aba('seguranca').dispatchEvent(new window.Event('click', { bubbles: true }));

      // req-124 F6: o sublinhado da aba ativa segue a paleta azul Conn2Flow; o verde ficou reservado
      // ao que significa sucesso ou "ativo".
      expect(aba('seguranca').classList.contains('border-sky-600')).toBe(true);
      expect(aba('seguranca').classList.contains('border-transparent')).toBe(false);
      expect(aba('dados').classList.contains('border-transparent')).toBe(true);
      expect(aba('dados').classList.contains('border-sky-600')).toBe(false);
    });

    it('lembra a última aba escolhida entre visitas', () => {
      montarPainel();
      T.iniciar();
      aba('sessoes').dispatchEvent(new window.Event('click', { bubbles: true }));

      // Segunda visita: mesma marcação, runtime reiniciado.
      montarPainel();
      T.iniciar();

      expect(painelDe('sessoes').classList.contains('hidden')).toBe(false);
    });

    it('aba desconhecida na memória cai na primeira, sem deixar a tela vazia', () => {
      window.localStorage.setItem('c2f-perfil-aba', 'inexistente');
      montarPainel();
      T.iniciar();

      expect(painelDe('dados').classList.contains('hidden')).toBe(false);
    });

    // req-124 F5 (regressão): o histórico é desenhado pelo componente de edição da interface, fora do
    // painel de abas, e por isso ficava visível também sob "Segurança" e "Sessões" — onde não
    // descreve nada do que está na tela.
    it('o histórico de alterações só acompanha a aba de dados', () => {
      montarPainel();
      T.iniciar();

      const historico = document.querySelector('[data-c2f-historico]');

      expect(historico.classList.contains('hidden')).toBe(false);

      aba('seguranca').dispatchEvent(new window.Event('click', { bubbles: true }));
      expect(historico.classList.contains('hidden')).toBe(true);

      aba('sessoes').dispatchEvent(new window.Event('click', { bubbles: true }));
      expect(historico.classList.contains('hidden')).toBe(true);

      aba('dados').dispatchEvent(new window.Event('click', { bubbles: true }));
      expect(historico.classList.contains('hidden')).toBe(false);
    });

    it('sem histórico cadastrado o painel de abas continua funcionando', () => {
      // O PHP remove a célula inteira quando não há registros; o runtime não pode depender dela.
      montarPainel({ comHistorico: false });
      T.iniciar();

      aba('seguranca').dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(painelDe('seguranca').classList.contains('hidden')).toBe(false);
      expect(document.querySelector('[data-c2f-historico]')).toBe(null);
    });
  });

  describe('aba resolvida pela URL', () => {
    // A querystring vence a memória: quem clicou em "Alterar e-mail" precisa cair no formulário,
    // ainda que a última aba visitada tenha sido outra.
    it('?mudar-email=sim abre a aba de dados', () => {
      expect(T.abaDaUrl.call(null)).toBe(null);
    });

    it('reconhece cada gatilho de querystring e o hash', () => {
      const casos = [
        ['?mudar-nome=sim', 'dados'],
        ['?mudar-email=sim', 'dados'],
        ['?mudar-usuario=sim', 'dados'],
        ['?mudar-senha=sim', 'dados'],
        ['?configurar-seguranca=sim', 'seguranca']
      ];

      for (const [busca, esperado] of casos) {
        window.history.replaceState({}, '', busca);
        expect(T.abaDaUrl()).toBe(esperado);
      }

      // O caminho é reescrito por inteiro: um hash relativo preservaria a querystring anterior, e
      // a querystring vence o hash — que é justamente o próximo caso.
      window.history.replaceState({}, '', '/perfil-usuario/#sessoes');
      expect(T.abaDaUrl()).toBe('sessoes');

      window.history.replaceState({}, '', '/perfil-usuario/#qualquer-outra-coisa');
      expect(T.abaDaUrl()).toBe(null);

      // Querystring de alteração vence o hash: quem clicou em "Alterar e-mail" tem de cair no
      // formulário, mesmo vindo de um link ancorado em outra aba.
      window.history.replaceState({}, '', '/perfil-usuario/?mudar-email=sim#sessoes');
      expect(T.abaDaUrl()).toBe('dados');

      window.history.replaceState({}, '', '/');
    });
  });

  // ===================================================================================
  // Medidor de força de senha
  // ===================================================================================

  describe('medidor de força de senha', () => {
    it('senha vazia não pontua', () => {
      expect(T.forcaDaSenha('')).toBe(0);
      expect(T.forcaDaSenha(null)).toBe(0);
    });

    it('senha curta nunca passa de fraca, por mais variada que seja', () => {
      // O backend exige 12 caracteres (`min => 12`); aprovar 8 aqui seria mentir para o usuário,
      // que só descobriria no POST.
      expect(T.forcaDaSenha('Ab1!')).toBe(1);
      expect(T.forcaDaSenha('Ab1!Ab1!')).toBe(1);
    });

    it('pontua por comprimento e variedade de caracteres', () => {
      expect(T.forcaDaSenha('abcdefghijkl')).toBe(1);
      expect(T.forcaDaSenha('abcdefghijkL')).toBe(2);
      expect(T.forcaDaSenha('abcdefghijL1')).toBe(3);
      expect(T.forcaDaSenha('abcdefghiL1!')).toBe(4);
    });

    it('atualiza barra e rótulo conforme o usuário digita', () => {
      montarPainel();
      T.iniciar();

      const campo = document.querySelector('[data-perfil-senha]');
      const barra = document.querySelector('[data-perfil-senha-barra]');
      const rotulo = document.querySelector('[data-perfil-senha-rotulo]');

      expect(barra.classList.contains('w-0')).toBe(true);

      campo.value = 'abcdefghiL1!';
      campo.dispatchEvent(new window.Event('input', { bubbles: true }));

      expect(barra.classList.contains('w-full')).toBe(true);
      expect(barra.classList.contains('bg-emerald-500')).toBe(true);
      expect(barra.classList.contains('w-0')).toBe(false);
      expect(rotulo.textContent).not.toBe('');
    });

    it('apagar a senha devolve o medidor ao estado inicial', () => {
      montarPainel();
      T.iniciar();

      const campo = document.querySelector('[data-perfil-senha]');
      const barra = document.querySelector('[data-perfil-senha-barra]');

      campo.value = 'abcdefghiL1!';
      campo.dispatchEvent(new window.Event('input', { bubbles: true }));
      campo.value = '';
      campo.dispatchEvent(new window.Event('input', { bubbles: true }));

      expect(barra.classList.contains('w-0')).toBe(true);
      expect(barra.classList.contains('bg-slate-300')).toBe(true);
      expect(barra.classList.contains('w-full')).toBe(false);
    });

    it('sem o bloco de senha na página o painel continua funcionando', () => {
      // O bloco só é renderizado sob `?mudar-senha=sim`.
      montarPainel({ comSenha: false });
      expect(() => T.iniciar()).not.toThrow();
    });
  });

  // ===================================================================================
  // Sessões
  // ===================================================================================

  describe('revogação de sessões', () => {
    it('revoga a sessão e remove o cartão sem recarregar a página', async () => {
      montarPainel();
      T.iniciar();

      document.querySelector('[data-pubid="outra1"]').dispatchEvent(new window.Event('click', { bubbles: true }));
      await vi.waitFor(() => expect(document.querySelector('[data-sessao-pubid="outra1"]')).toBe(null));

      expect(document.querySelector('[data-sessao-pubid="outra2"]')).not.toBe(null);
      expect(document.querySelector('[data-sessao-pubid="atual"]')).not.toBe(null);
    });

    it('envia o pubID e a opção AJAX corretos', async () => {
      montarPainel();
      T.iniciar();

      document.querySelector('[data-pubid="outra2"]').dispatchEvent(new window.Event('click', { bubbles: true }));
      await vi.waitFor(() => expect(requisicoes.length).toBe(1));

      expect(requisicoes[0].body).toContain('ajaxOpcao=sessoes-revogar');
      expect(requisicoes[0].body).toContain('pubID=outra2');
      expect(requisicoes[0].body).toContain('ajax=sim');
    });

    it('cancelar a confirmação não dispara requisição alguma', () => {
      window.confirm = vi.fn(() => false);
      montarPainel();
      T.iniciar();

      document.querySelector('[data-pubid="outra1"]').dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(requisicoes.length).toBe(0);
      expect(document.querySelector('[data-sessao-pubid="outra1"]')).not.toBe(null);
    });

    it('a sessão atual não tem botão de revogar', () => {
      // Revogar a própria sessão pelo painel derrubaria o usuário no meio da operação.
      montarPainel();
      T.iniciar();

      const atual = document.querySelector('[data-sessao-pubid="atual"]');
      expect(atual.querySelector('.btn-sessao-revogar')).toBe(null);
    });

    it('desconectar os outros remove só os cartões revogáveis', async () => {
      montarPainel();
      T.iniciar();

      document.getElementById('btn-sessoes-revogar-outras').dispatchEvent(new window.Event('click', { bubbles: true }));
      await vi.waitFor(() => expect(document.querySelector('[data-sessao-pubid="outra1"]')).toBe(null));

      expect(document.querySelector('[data-sessao-pubid="outra2"]')).toBe(null);
      expect(document.querySelector('[data-sessao-pubid="atual"]')).not.toBe(null);
      expect(document.getElementById('btn-sessoes-revogar-outras').classList.contains('hidden')).toBe(true);
    });

    it('erro do servidor mostra mensagem e preserva o cartão', async () => {
      respostas = { status: 'error', message: 'Não foi possível revogar a sessão.' };
      montarPainel();
      T.iniciar();

      document.querySelector('[data-pubid="outra1"]').dispatchEvent(new window.Event('click', { bubbles: true }));

      const msg = document.getElementById('sessoes-msg');
      await vi.waitFor(() => expect(msg.classList.contains('hidden')).toBe(false));

      expect(msg.classList.contains('bg-red-50')).toBe(true);
      expect(msg.innerHTML).toContain('revogar');
      expect(document.querySelector('[data-sessao-pubid="outra1"]')).not.toBe(null);
    });

    it('sucesso pinta a mensagem como positiva', async () => {
      montarPainel();
      T.iniciar();

      document.querySelector('[data-pubid="outra1"]').dispatchEvent(new window.Event('click', { bubbles: true }));

      const msg = document.getElementById('sessoes-msg');
      await vi.waitFor(() => expect(msg.classList.contains('bg-emerald-50')).toBe(true));

      expect(msg.classList.contains('bg-red-50')).toBe(false);
    });

    it('some com o botão de desconectar quando não sobra outra sessão', async () => {
      montarPainel();
      T.iniciar();

      document.querySelector('[data-pubid="outra1"]').dispatchEvent(new window.Event('click', { bubbles: true }));
      await vi.waitFor(() => expect(document.querySelector('[data-sessao-pubid="outra1"]')).toBe(null));

      document.querySelector('[data-pubid="outra2"]').dispatchEvent(new window.Event('click', { bubbles: true }));
      await vi.waitFor(() =>
        expect(document.getElementById('btn-sessoes-revogar-outras').classList.contains('hidden')).toBe(true)
      );
    });
  });

  // ===================================================================================
  // Segurança (2FA e contas sociais)
  // ===================================================================================

  describe('ações de segurança', () => {
    it('ativar 2FA envia método e código', async () => {
      montarPainel();
      T.iniciar();

      document.getElementById('btn-2fa-ativar').dispatchEvent(new window.Event('click', { bubbles: true }));
      await vi.waitFor(() => expect(requisicoes.length).toBe(1));

      expect(requisicoes[0].body).toContain('ajaxOpcao=seguranca-2fa-ativar');
      expect(requisicoes[0].body).toContain('codigo=123456');
      expect(requisicoes[0].body).toContain('metodo=app');
    });

    it('desvincular conta social envia o provedor do próprio botão', async () => {
      montarPainel();
      T.iniciar();

      document.querySelector('.btn-social-desvincular').dispatchEvent(new window.Event('click', { bubbles: true }));
      await vi.waitFor(() => expect(requisicoes.length).toBe(1));

      expect(requisicoes[0].body).toContain('ajaxOpcao=seguranca-social-desvincular');
      expect(requisicoes[0].body).toContain('provider=google');
    });

    it('sem o bloco de segurança o painel não quebra', () => {
      montarPainel({ comSeguranca: false, comSessoes: false });
      expect(() => T.iniciar()).not.toThrow();
    });
  });

  it('fora da página do perfil o runtime não faz nada', () => {
    document.body.innerHTML = '<div id="outra-pagina"></div>';
    expect(() => T.iniciar()).not.toThrow();
    expect(requisicoes.length).toBe(0);
  });
});
