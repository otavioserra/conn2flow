import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * req-118 (BATCH-119) — runtime da interface administrativa em Tailwind.
 *
 * Substitui o `interface.js` legado quando a requisição é Tailwind pura: o legado chama `$.fn.modal`
 * do Fomantic em 16 pontos sem guarda e, sem o Fomantic na página, quebraria já no primeiro alerta —
 * e no modal de Área Restrita, que é uma trava de credenciais.
 *
 * O ponto central coberto aqui: **o contrato de dados é o mesmo**. As regras continuam chegando no
 * formato do validador do Fomantic (`{campo:{rules:[{type,prompt}]}}`), emitido sem alteração por
 * `interface_formulario_validacao()`.
 */
function carregarRuntime() {
  const code = readFileSync(resolve(process.cwd(), 'gestor/assets/interface/interface-tailwind.js'), 'utf8');
  vm.runInThisContext(code, { filename: 'interface-tailwind.js' });
  return window.gestorInterfaceTailwind;
}

function montarModais() {
  document.body.insertAdjacentHTML('beforeend', `
    <div data-c2f-modal="carregando" class="hidden"></div>
    <div data-c2f-modal="alerta" class="hidden">
      <p data-c2f-modal-mensagem></p>
      <button type="button" data-c2f-modal-aprovar></button>
    </div>
    <div data-c2f-modal="delecao" class="hidden">
      <button type="button" data-c2f-modal-negar></button>
      <button type="button" data-c2f-modal-aprovar></button>
    </div>
  `);
}

function montarFormulario(campos) {
  document.body.insertAdjacentHTML('beforeend', `
    <form data-c2f-form="tailwind">
      ${campos}
      <div data-c2f-form-erros class="hidden"></div>
      <button type="submit"></button>
    </form>
  `);
  return document.querySelector('form[data-c2f-form="tailwind"]');
}

describe('Interface administrativa Tailwind (req-118)', () => {
  let T;

  beforeEach(() => {
    document.body.innerHTML = '';
    delete window.gestor;
    window.gestor = { raiz: '/', interface: {} };
    T = carregarRuntime();
  });

  // ===================================================================================
  // Dicionário de regras — o mesmo do validador do Fomantic
  // ===================================================================================

  describe('regras de validação', () => {
    const form = () => montarFormulario('<input name="outro" value="abc">');

    it('notEmpty rejeita vazio e espaço em branco', () => {
      const f = form();
      expect(T.regraValida('notEmpty', '', f)).toBe(false);
      expect(T.regraValida('notEmpty', '   ', f)).toBe(false);
      expect(T.regraValida('notEmpty', 'x', f)).toBe(true);
    });

    it('minLength e maxLength usam o número declarado entre colchetes', () => {
      const f = form();
      expect(T.regraValida('minLength[3]', 'ab', f)).toBe(false);
      expect(T.regraValida('minLength[3]', 'abc', f)).toBe(true);
      expect(T.regraValida('maxLength[5]', 'abcdef', f)).toBe(false);
      expect(T.regraValida('maxLength[5]', 'abcde', f)).toBe(true);
    });

    it('minLength[12] aceita exatamente o mínimo exigido pelo backend', () => {
      expect(T.regraValida('minLength[12]', 'a'.repeat(11), form())).toBe(false);
      expect(T.regraValida('minLength[12]', 'a'.repeat(12), form())).toBe(true);
    });

    it('email valida o formato', () => {
      const f = form();
      expect(T.regraValida('email', 'joao@exemplo.com', f)).toBe(true);
      expect(T.regraValida('email', 'joao@exemplo', f)).toBe(false);
      expect(T.regraValida('email', 'sem-arroba', f)).toBe(false);
    });

    it('match compara com o valor de outro campo do mesmo formulário', () => {
      // É a regra que sustenta a confirmação de e-mail e de senha do perfil.
      const f = montarFormulario('<input name="senha-2" value="segredo123">');
      expect(T.regraValida('match[senha-2]', 'segredo123', f)).toBe(true);
      expect(T.regraValida('match[senha-2]', 'outra', f)).toBe(false);
    });

    it('match com campo ausente não reprova', () => {
      // O bloco de confirmação só existe sob a querystring correspondente; reprovar aqui travaria
      // um formulário que nem tem o campo comparado.
      expect(T.regraValida('match[inexistente]', 'x', form())).toBe(true);
    });

    it('regExp aplica a expressão declarada, com flags', () => {
      const f = form();
      expect(T.regraValida('regExp[/^[a-z]+$/]', 'abc', f)).toBe(true);
      expect(T.regraValida('regExp[/^[a-z]+$/]', 'ABC', f)).toBe(false);
      expect(T.regraValida('regExp[/^[a-z]+$/i]', 'ABC', f)).toBe(true);
    });

    it('regExp de complexidade de senha (a mesma emitida pelo PHP)', () => {
      const tipo = 'regExp[/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\\$%\\^&\\*])/]';
      const f = form();

      expect(T.regraValida(tipo, 'senhafraca', f)).toBe(false);
      expect(T.regraValida(tipo, 'SenhaForte1!', f)).toBe(true);
    });

    it('regExp inválida não reprova campo correto', () => {
      // O defeito seria do cadastro da regra; barrar o usuário aqui é falso negativo silencioso.
      expect(T.regraValida('regExp[/(/]', 'qualquer', form())).toBe(true);
    });

    it('tipo desconhecido é ignorado em vez de travar o formulário', () => {
      expect(T.regraValida('tipoQueAindaNaoExiste', '', form())).toBe(true);
    });
  });

  // ===================================================================================
  // Validação do formulário inteiro
  // ===================================================================================

  describe('validação do formulário', () => {
    it('acumula os prompts das regras violadas', () => {
      const f = montarFormulario('<input name="nome" value="">');
      const erros = T.validarFormulario(f, {
        nome: { rules: [{ type: 'notEmpty', prompt: 'Informe o nome.' }] }
      });

      expect(erros).toEqual(['Informe o nome.']);
    });

    it('para na primeira regra violada de cada campo', () => {
      // Mostrar "campo vazio" e "mínimo de 3 caracteres" ao mesmo tempo é ruído.
      const f = montarFormulario('<input name="nome" value="">');
      const erros = T.validarFormulario(f, {
        nome: {
          rules: [
            { type: 'notEmpty', prompt: 'Vazio.' },
            { type: 'minLength[3]', prompt: 'Curto.' }
          ]
        }
      });

      expect(erros).toEqual(['Vazio.']);
    });

    it('campo válido não gera erro e é marcado como válido', () => {
      const f = montarFormulario('<input name="nome" value="Maria Silva">');
      const erros = T.validarFormulario(f, {
        nome: { rules: [{ type: 'notEmpty', prompt: 'Vazio.' }] }
      });

      expect(erros).toEqual([]);
      expect(f.querySelector('[name="nome"]').getAttribute('aria-invalid')).toBe('false');
    });

    it('campo inválido recebe aria-invalid e realce', () => {
      const f = montarFormulario('<input name="nome" value="">');
      T.validarFormulario(f, { nome: { rules: [{ type: 'notEmpty', prompt: 'Vazio.' }] } });

      const campo = f.querySelector('[name="nome"]');
      expect(campo.getAttribute('aria-invalid')).toBe('true');
      expect(campo.classList.contains('border-red-500!')).toBe(true);
    });

    it('regra de campo ausente do DOM é ignorada', () => {
      // É o caso normal do perfil: cada bloco de alteração só é renderizado sob sua querystring.
      const f = montarFormulario('<input name="nome" value="Maria">');
      const erros = T.validarFormulario(f, {
        nome: { rules: [{ type: 'notEmpty', prompt: 'Vazio.' }] },
        email: { rules: [{ type: 'notEmpty', prompt: 'E-mail vazio.' }] }
      });

      expect(erros).toEqual([]);
    });

    it('campo desabilitado é ignorado', () => {
      const f = montarFormulario('<input name="senha" value="" disabled>');
      expect(T.validarFormulario(f, { senha: { rules: [{ type: 'notEmpty', prompt: 'Vazio.' }] } })).toEqual([]);
    });
  });

  // ===================================================================================
  // Submit
  // ===================================================================================

  describe('envio do formulário', () => {
    it('bloqueia o envio e lista os erros na caixa', () => {
      window.gestor.interface.regrasValidacao = {
        nome: { rules: [{ type: 'notEmpty', prompt: 'Informe o nome.' }] }
      };
      montarModais();
      const f = montarFormulario('<input name="nome" value="">');
      T.iniciar();

      const evento = new window.Event('submit', { bubbles: true, cancelable: true });
      f.dispatchEvent(evento);

      expect(evento.defaultPrevented).toBe(true);
      const caixa = f.querySelector('[data-c2f-form-erros]');
      expect(caixa.classList.contains('hidden')).toBe(false);
      expect(caixa.innerHTML).toContain('Informe o nome.');
    });

    it('deixa passar o envio válido e mostra o carregamento', () => {
      window.gestor.interface.regrasValidacao = {
        nome: { rules: [{ type: 'notEmpty', prompt: 'Informe o nome.' }] }
      };
      montarModais();
      const f = montarFormulario('<input name="nome" value="Maria">');
      T.iniciar();

      const evento = new window.Event('submit', { bubbles: true, cancelable: true });
      f.dispatchEvent(evento);

      expect(evento.defaultPrevented).toBe(false);
      expect(document.querySelector('[data-c2f-modal="carregando"]').classList.contains('hidden')).toBe(false);
    });

    it('anexa a query string do momento do envio (contrato herdado do legado)', () => {
      window.history.replaceState({}, '', '/perfil-usuario/?mudar-email=sim');
      montarModais();
      const f = montarFormulario('<input name="nome" value="Maria">');
      T.iniciar();

      const campo = f.querySelector('input[name="_c2f_query_string_before_submit"]');
      expect(campo).not.toBe(null);
      expect(campo.value).toBe('?mudar-email=sim');

      window.history.replaceState({}, '', '/');
    });

    it('sem regras declaradas o envio não é interceptado', () => {
      montarModais();
      const f = montarFormulario('<input name="nome" value="">');
      T.iniciar();

      const evento = new window.Event('submit', { bubbles: true, cancelable: true });
      f.dispatchEvent(evento);

      expect(evento.defaultPrevented).toBe(false);
    });
  });

  // ===================================================================================
  // Modais
  // ===================================================================================

  describe('modais', () => {
    it('carregamento abre e fecha', () => {
      montarModais();
      T.iniciar();

      const el = document.querySelector('[data-c2f-modal="carregando"]');

      T.carregarAbrir();
      expect(el.classList.contains('hidden')).toBe(false);

      T.carregarFechar();
      expect(el.classList.contains('hidden')).toBe(true);
    });

    it('duas chamadas simultâneas só fecham o loader na última resposta', () => {
      // Com booleano, a primeira resposta apagaria o loader e a segunda chamada ficaria sem
      // indicação nenhuma de progresso.
      montarModais();
      T.iniciar();

      const el = document.querySelector('[data-c2f-modal="carregando"]');

      T.carregarAbrir();
      T.carregarAbrir();
      T.carregarFechar();
      expect(el.classList.contains('hidden')).toBe(false);

      T.carregarFechar();
      expect(el.classList.contains('hidden')).toBe(true);
    });

    it('fechar a mais não deixa o contador negativo', () => {
      montarModais();
      T.iniciar();

      T.carregarFechar();
      T.carregarAbrir();

      expect(document.querySelector('[data-c2f-modal="carregando"]').classList.contains('hidden')).toBe(false);
    });

    it('alerta escreve a mensagem e abre', () => {
      montarModais();
      T.iniciar();

      T.alerta({ msg: 'Registro salvo.' });

      const el = document.querySelector('[data-c2f-modal="alerta"]');
      expect(el.classList.contains('hidden')).toBe(false);
      expect(el.querySelector('[data-c2f-modal-mensagem]').innerHTML).toBe('Registro salvo.');
    });

    it('alerta vindo do PHP é exibido na carga da página', () => {
      window.gestor.interface.alert = { msg: 'Vindo do backend.' };
      montarModais();
      T.iniciar();

      expect(document.querySelector('[data-c2f-modal="alerta"]').classList.contains('hidden')).toBe(false);
    });

    it('Esc fecha o modal comum', () => {
      montarModais();
      T.iniciar();
      T.alerta({ msg: 'x' });

      document.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));

      expect(document.querySelector('[data-c2f-modal="alerta"]').classList.contains('hidden')).toBe(true);
    });

    it('o botão de negar fecha o modal de deleção', () => {
      montarModais();
      T.iniciar();
      T.deletarConfirmacao();

      const el = document.querySelector('[data-c2f-modal="delecao"]');
      expect(el.classList.contains('hidden')).toBe(false);

      el.querySelector('[data-c2f-modal-negar]').dispatchEvent(new window.Event('click', { bubbles: true }));
      expect(el.classList.contains('hidden')).toBe(true);
    });

    it('confirmar a deleção navega para a URL informada pelo PHP', () => {
      window.gestor.interface.excluir_url = '/admin-paginas/excluir/?id=7';
      window.open = vi.fn();
      montarModais();
      T.iniciar();

      document
        .querySelector('[data-c2f-modal="delecao"] [data-c2f-modal-aprovar]')
        .dispatchEvent(new window.Event('click', { bubbles: true }));

      expect(window.open).toHaveBeenCalledWith('/admin-paginas/excluir/?id=7', '_self');
    });
  });

  describe('Área Restrita (step-up auth)', () => {
    function montarAutorizacao() {
      document.body.insertAdjacentHTML('beforeend', `
        <div data-c2f-modal="autorizacao-provisoria" data-c2f-modal-obrigatorio class="hidden">
          <a href="/dashboard/"></a>
        </div>
      `);
      return document.querySelector('[data-c2f-modal="autorizacao-provisoria"]');
    }

    it('abre sozinho quando o PHP o coloca na página', () => {
      // É a abertura que efetivamente bloqueia a tela: sem ela, a trava existiria no HTML e seria
      // invisível ao usuário.
      const el = montarAutorizacao();
      T.iniciar();

      expect(el.classList.contains('hidden')).toBe(false);
    });

    it('Esc não fecha a trava', () => {
      const el = montarAutorizacao();
      T.iniciar();

      document.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));

      expect(el.classList.contains('hidden')).toBe(false);
    });

    it('clique no fundo não fecha a trava', () => {
      // Fechar sem escolher equivaleria a burlar a confirmação de credenciais.
      const el = montarAutorizacao();
      T.iniciar();

      el.dispatchEvent(new window.MouseEvent('mousedown', { bubbles: true }));

      expect(el.classList.contains('hidden')).toBe(false);
    });
  });

  it('página sem modais nem formulários não quebra', () => {
    document.body.innerHTML = '<div></div>';
    expect(() => T.iniciar()).not.toThrow();
  });
});
