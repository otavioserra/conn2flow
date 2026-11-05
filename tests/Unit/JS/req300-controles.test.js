import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { afterEach, describe, expect, it, vi } from 'vitest';

/**
 * REQ-300 — UI 3.0 tematizável por projeto (BATCH-308) e backlogs de controles (BATCH-309).
 */

function carregar() {
  delete window.c2fControles;
  delete window.jQuery;
  const codigo = readFileSync(resolve(process.cwd(), 'gestor/assets/interface/controles.js'), 'utf8');
  vm.runInThisContext(codigo, { filename: 'controles.js' });
  return window.c2fControles;
}

const espera = (ms = 0) => new Promise((r) => setTimeout(r, ms));
const css = () => readFileSync(resolve(process.cwd(), 'gestor/assets/interface/controles.css'), 'utf8');
const respostaJson = (dados) => Promise.resolve({ json: () => Promise.resolve(dados) });

afterEach(() => { document.body.innerHTML = ''; vi.useRealTimers(); });

describe('BATCH-308: tokens de ação no controles.css', () => {
  it('declara os tokens em :root com os valores sky de sempre', () => {
    const fonte = css();
    const raiz = fonte.slice(fonte.indexOf('\n:root {'), fonte.indexOf('}', fonte.indexOf('\n:root {')));
    expect(raiz).toContain('--c2fc-acao: #0284c7;');
    expect(raiz).toContain('--c2fc-acao-escuro: #0369a1;');
    expect(raiz).toContain('--c2fc-acao-anel: rgba(14, 165, 233, .25);');
  });

  it('nenhum hex de ação fora dos tokens, exceto o azul semântico de informação', () => {
    const linhas = css().split('\n').filter((l) => /#(0284c7|0369a1|075985|f0f9ff|e0f2fe|bae6fd|7dd3fc|0ea5e9)/i.test(l) && !/--c2fc-acao/.test(l));
    expect(linhas.every((l) => /^\s*\.(c2fc-nota|c2fc-alerta-info)\b/.test(l))).toBe(true);
    for (const seletor of ['.c2fc-botao-primario {', '.c2fc-chave input:checked + .c2fc-chave-trilho {', '.c2fc-select-opcao[aria-selected="true"] {']) {
      const regra = css().split('\n').find((l) => l.startsWith(seletor));
      expect(regra, seletor).toMatch(/var\(--c2fc-acao/);
    }
  });

  it('container de painel por variável com os valores atuais como padrão', () => {
    const fonte = css();
    expect(fonte).toContain('max-width: var(--c2f-painel-largura, 72rem)');
    expect(fonte).toContain('padding: var(--c2f-painel-respiro, .5rem 0)');
    expect(fonte).toContain('padding: var(--c2f-painel-respiro-sm, 1.25rem 1rem)');
  });
});

describe('BATCH-308: diálogo dentro de <dialog> modal', () => {
  it('sem modal aberto o overlay vai para o body', async () => {
    const api = carregar();
    const resposta = api.dialogo.confirmar('Excluir?');
    const overlay = document.querySelector('.c2fc-overlay[data-c2fc-dialogo="confirmar"]');
    expect(overlay.parentNode).toBe(document.body);
    overlay.querySelector('[data-c2fc-cancelar]').click();
    expect(await resposta).toBe(false);
  });

  it('com <dialog> modal aberto o overlay nasce dentro do mais recente e responde normalmente', async () => {
    // jsdom tem showModal() mas não implementa `:modal` (sempre false); emula o navegador.
    const proto = window.HTMLDialogElement.prototype;
    const showModal = proto.showModal, matches = window.Element.prototype.matches;
    proto.showModal = function () { this.c2fTesteModal = true; return showModal.call(this); };
    proto.matches = function (sel) { return sel === ':modal' ? !!(this.open && this.c2fTesteModal) : matches.call(this, sel); };
    try {
      const api = carregar();
      document.body.innerHTML = '<dialog id="a"></dialog><dialog id="b"><form></form></dialog><dialog id="naoModal" open></dialog>';
      document.getElementById('a').showModal();
      document.getElementById('b').showModal();
      const resposta = api.dialogo.confirmar('Revogar?', { perigo: true });
      const overlay = document.querySelector('.c2fc-overlay[data-c2fc-dialogo="confirmar"]');
      expect(overlay.parentNode.id).toBe('b');
      expect(overlay.querySelector('[data-c2fc-ok]').className).toContain('c2fc-botao-perigo');
      overlay.querySelector('[data-c2fc-ok]').click();
      expect(await resposta).toBe(true);
      expect(document.querySelector('.c2fc-overlay')).toBeNull();
    } finally {
      proto.showModal = showModal;
      delete proto.matches;
    }
  });

  it('dialog fechado não hospeda o overlay', async () => {
    const api = carregar();
    document.body.innerHTML = '<dialog id="fechado"></dialog>';
    const resposta = api.dialogo.alerta('Pronto');
    expect(document.querySelector('.c2fc-overlay').parentNode).toBe(document.body);
    document.querySelector('.c2fc-overlay [data-c2fc-ok]').click();
    await resposta;
  });
});

describe('BATCH-309: BL-049 confirmar em forma objeto', () => {
  it('aceita { titulo, mensagem, ok, cancelar, perigo }', async () => {
    const api = carregar();
    const resposta = api.dialogo.confirmar({ titulo: 'Excluir tarefa', mensagem: 'Excluir a tarefa X?', ok: 'Excluir', cancelar: 'Voltar', perigo: true });
    const caixa = document.querySelector('.c2fc-overlay .c2fc-dialogo');
    expect(caixa.querySelector('.c2fc-dialogo-titulo').textContent).toBe('Excluir tarefa');
    expect(caixa.querySelector('.c2fc-dialogo-corpo').textContent).toBe('Excluir a tarefa X?');
    expect(caixa.querySelector('[data-c2fc-ok]').textContent).toBe('Excluir');
    expect(caixa.querySelector('[data-c2fc-cancelar]').textContent).toBe('Voltar');
    caixa.querySelector('[data-c2fc-cancelar]').click();
    expect(await resposta).toBe(false);
  });

  it('núcleo sem window.confirm direto fora de reserva', () => {
    for (const arquivo of ['gestor/modulos/admin-cron/admin-cron.js', 'gestor/modulos/admin-environment/admin-environment.js']) {
      const fonte = readFileSync(resolve(process.cwd(), arquivo), 'utf8');
      const linhas = fonte.split('\n').filter((l) => l.includes('window.confirm('));
      expect(linhas.length, arquivo).toBe(1);
      expect(linhas[0], arquivo).toContain('Promise.resolve(window.confirm(');
      expect(fonte, arquivo).toContain('window.c2fControles.dialogo.confirmar(');
    }
  });
});

describe('BATCH-309: BL-048 select AJAX híbrido', () => {
  it('lista inicial volta ao apagar o termo; resposta atrasada é descartada; parâmetros por atributo', async () => {
    const api = carregar();
    document.body.innerHTML = '<select data-c2f-ajax-opcao="pacientes" data-c2f-ajax-url="/p/" data-c2f-ajax-minimo="2" data-c2f-ajax-parametros=\'{"status":"ativo"}\'>'
      + '<option value="">—</option><option value="1">Ana</option><option value="2">Bruno</option></select>';
    const nativo = document.querySelector('select');
    const pendentes = [];
    window.fetch = vi.fn(() => new Promise((ok) => pendentes.push(ok)));
    vi.useFakeTimers();
    api.select(nativo);
    document.querySelector('.c2fc-select-gatilho').click();
    const busca = document.querySelector('.c2fc-select-busca');
    const digitar = (v) => { busca.value = v; busca.dispatchEvent(new Event('input')); vi.advanceTimersByTime(300); };
    digitar('ca');
    digitar('car');
    vi.useRealTimers();
    expect(window.fetch).toHaveBeenCalledTimes(2);
    expect(window.fetch.mock.calls[0][1].body.toString()).toContain('status=ativo');
    pendentes[1](respostaJson({ data: { items: [{ value: '9', text: 'Carla' }] } }));
    await espera(); await espera(); await espera();
    pendentes[0](respostaJson({ data: { items: [{ value: '8', text: 'Caio (atrasado)' }] } }));
    await espera(); await espera(); await espera();
    const valores = () => Array.from(nativo.options).map((o) => o.value).filter(Boolean);
    expect(valores()).toEqual(['9']);
    busca.value = '';
    busca.dispatchEvent(new Event('input'));
    expect(valores()).toEqual(['1', '2']);
    expect(document.querySelectorAll('.c2fc-select-opcao').length).toBe(3);
  });

  it('debounce de 300 ms: digitação rápida faz uma busca só', () => {
    const api = carregar();
    document.body.innerHTML = '<select data-c2f-ajax-opcao="x" data-c2f-ajax-minimo="1"><option value="">—</option></select>';
    window.fetch = vi.fn(() => new Promise(() => {}));
    vi.useFakeTimers();
    api.select(document.querySelector('select'));
    document.querySelector('.c2fc-select-gatilho').click();
    const busca = document.querySelector('.c2fc-select-busca');
    for (const v of ['a', 'an', 'ana']) { busca.value = v; busca.dispatchEvent(new Event('input')); vi.advanceTimersByTime(250); }
    expect(window.fetch).toHaveBeenCalledTimes(0);
    vi.advanceTimersByTime(60);
    expect(window.fetch).toHaveBeenCalledTimes(1);
    expect(window.fetch.mock.calls[0][1].body.toString()).toContain('q=ana');
  });

  it('opção escolhida na busca remota continua quando a lista inicial volta', async () => {
    const api = carregar();
    document.body.innerHTML = '<select data-c2f-ajax-opcao="x" data-c2f-ajax-minimo="1"><option value="">—</option><option value="1">Ana</option></select>';
    const nativo = document.querySelector('select');
    window.fetch = vi.fn(() => respostaJson({ items: [{ id: 7, name: 'Zeca' }] }));
    vi.useFakeTimers();
    api.select(nativo);
    document.querySelector('.c2fc-select-gatilho').click();
    const busca = document.querySelector('.c2fc-select-busca');
    busca.value = 'z'; busca.dispatchEvent(new Event('input')); vi.advanceTimersByTime(300);
    vi.useRealTimers();
    await espera(); await espera(); await espera();
    nativo.value = '7';
    busca.value = ''; busca.dispatchEvent(new Event('input'));
    expect(nativo.value).toBe('7');
    expect(Array.from(nativo.options).map((o) => o.value).filter(Boolean).sort()).toEqual(['1', '7']);
  });
});

describe('BATCH-309: BL-050 calendário', () => {
  const montarCal = (attrs) => {
    document.body.innerHTML = `<form><input type="date" name="data" data-c2f-calendario ${attrs}></form>`;
    return document.querySelector('input');
  };
  const dias = () => Array.from(document.querySelectorAll('.c2fc-calendario-dia'));
  const dia = (iso) => document.querySelector(`.c2fc-calendario-dia[data-data="${iso}"]`);

  it('monta pela inicialização, oculta o nativo e respeita min/max', () => {
    const api = carregar();
    const input = montarCal('value="2030-03-10" min="2030-03-05" max="2030-03-20"');
    api.iniciar(document);
    expect(input.classList.contains('c2fc-nativo')).toBe(true);
    expect(input.classList.contains('c2fc-calendario-nativo')).toBe(true);
    expect(dias().length).toBe(31);
    expect(dia('2030-03-04').disabled).toBe(true);
    expect(dia('2030-03-05').disabled).toBe(false);
    expect(dia('2030-03-21').disabled).toBe(true);
    expect(dia('2030-03-10').getAttribute('aria-selected')).toBe('true');
    expect(document.querySelector('[data-c2fc-mes-anterior]').disabled).toBe(true);
    expect(document.querySelector('[data-c2fc-mes-proximo]').disabled).toBe(true);
  });

  it('com lista de vagas só os dias listados escolhem; escolher grava no input e dispara change', () => {
    const api = carregar();
    const input = montarCal('value="" min="2030-01-01" data-c2f-calendario-disponiveis=\'["2030-01-08","2030-01-15"]\'');
    const mudou = vi.fn();
    input.addEventListener('change', mudou);
    const cal = api.calendario(input);
    cal.definir('2030-01-09');
    expect(input.value).toBe('');
    expect(dia('2030-01-08').className).toContain('c2fc-dia-disponivel');
    expect(dia('2030-01-09').disabled).toBe(true);
    dia('2030-01-08').click();
    expect(input.value).toBe('2030-01-08');
    expect(mudou).toHaveBeenCalled();
    expect(dia('2030-01-08').getAttribute('aria-selected')).toBe('true');
    expect(document.querySelector('.c2fc-calendario-legenda')).not.toBeNull();
  });

  it('teclado: setas movem o foco, Enter escolhe, PageDown troca o mês', () => {
    const api = carregar();
    const input = montarCal('value="2030-06-10"');
    api.calendario(input);
    const grade = document.querySelector('.c2fc-calendario-grade');
    const tecla = (key) => grade.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));
    tecla('ArrowRight');
    expect(document.activeElement.getAttribute('data-data')).toBe('2030-06-11');
    tecla('ArrowDown');
    expect(document.activeElement.getAttribute('data-data')).toBe('2030-06-18');
    tecla('Enter');
    expect(input.value).toBe('2030-06-18');
    tecla('PageDown');
    expect(dia('2030-07-18')).not.toBeNull();
  });

  it('vagas por AJAX por mês, com cache, e mês antigo que chega atrasado não redesenha', async () => {
    const api = carregar();
    const input = montarCal('value="2030-04-10" data-c2f-calendario-opcao="vagas" data-c2f-calendario-url="/agendar/" data-c2f-calendario-parametros=\'{"profissional":"3"}\'');
    const pendentes = [];
    window.fetch = vi.fn(() => new Promise((ok) => pendentes.push(ok)));
    const cal = api.calendario(input);
    expect(window.fetch).toHaveBeenCalledTimes(1);
    const corpo = window.fetch.mock.calls[0][1].body.toString();
    expect(corpo).toContain('ajaxOpcao=vagas');
    expect(corpo).toContain('mes=2030-04');
    expect(corpo).toContain('profissional=3');
    expect(dia('2030-04-12').disabled).toBe(true);
    cal.irMes(1);
    expect(window.fetch).toHaveBeenCalledTimes(2);
    pendentes[1](respostaJson({ data: { disponiveis: ['2030-05-02'] } }));
    await espera(); await espera(); await espera();
    expect(dia('2030-05-02').disabled).toBe(false);
    pendentes[0](respostaJson({ disponiveis: ['2030-04-12'] }));
    await espera(); await espera(); await espera();
    expect(dia('2030-05-02')).not.toBeNull();
    cal.irMes(-1);
    expect(window.fetch).toHaveBeenCalledTimes(2);
    expect(dia('2030-04-12').disabled).toBe(false);
  });

  it('recarregar troca os parâmetros e descarta o cache por mês', async () => {
    const api = carregar();
    const input = montarCal('value="2030-04-10"');
    window.fetch = vi.fn(() => respostaJson({ data: { disponiveis: ['2030-04-11'] } }));
    const cal = api.calendario(input, { ajax: { opcao: 'vagas', url: '/a/', parametros: { servico: 'a' } } });
    await espera(); await espera(); await espera();
    expect(dia('2030-04-11').disabled).toBe(false);
    window.fetch = vi.fn(() => respostaJson({ data: { disponiveis: ['2030-04-20'] } }));
    cal.recarregar({ servico: 'b' });
    expect(window.fetch.mock.calls[0][1].body.toString()).toContain('servico=b');
    await espera(); await espera(); await espera();
    expect(dia('2030-04-11').disabled).toBe(true);
    expect(dia('2030-04-20').disabled).toBe(false);
  });
});
