import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * req-219 (BATCH-227) — biblioteca de controles do painel (`gestor/assets/interface/controles.js`):
 * diálogos, avisos, select, abas e a ponte da API do Fomantic quando ele não está na página.
 */

/** jQuery mínimo: o suficiente para a ponte (`$.fn`, `each`, coleção indexada). */
function miniJquery() {
  function Colecao(elementos) {
    elementos.forEach((e, i) => { this[i] = e; });
    this.length = elementos.length;
  }
  function $(x) {
    if (typeof x === 'string') return new Colecao(Array.from(document.querySelectorAll(x)));
    if (x instanceof Colecao) return x;
    return new Colecao(x && x.nodeType ? [x] : Array.from(x || []));
  }
  $.fn = Colecao.prototype;
  Colecao.prototype.each = function (f) { for (let i = 0; i < this.length; i++) f.call(this[i], i, this[i]); return this; };
  return $;
}

function carregar({ comJquery = true, comFomantic = false } = {}) {
  delete window.c2fControles;
  if (comJquery) {
    window.jQuery = miniJquery();
    if (comFomantic) window.jQuery.fn.dropdown = function fomanticOriginal() { return 'fomantic'; };
  } else {
    delete window.jQuery;
  }
  const codigo = readFileSync(resolve(process.cwd(), 'gestor/assets/interface/controles.js'), 'utf8');
  vm.runInThisContext(codigo, { filename: 'controles.js' });
  return window.c2fControles;
}

const espera = () => new Promise((r) => setTimeout(r, 0));

beforeEach(() => {
  document.body.innerHTML = '';
  window.gestor = { controlesTextos: { ok: 'OK', cancelar: 'Cancelar', buscar: 'Buscar…', semResultado: 'Nenhum resultado' } };
});

describe('diálogos', () => {
  it('confirmar resolve true no OK e false no Cancelar ou Escape, com os textos das variáveis', async () => {
    const c = carregar();
    let p = c.dialogo.confirmar('Excluir?', { perigo: true });
    const caixa = document.querySelector('[data-c2fc-dialogo="confirmar"]');
    expect(caixa.textContent).toContain('Excluir?');
    expect(caixa.querySelector('[data-c2fc-cancelar]').textContent).toBe('Cancelar');
    expect(caixa.querySelector('[data-c2fc-ok]').className).toContain('perigo');
    caixa.querySelector('[data-c2fc-ok]').click();
    expect(await p).toBe(true);
    expect(document.querySelector('[data-c2fc-dialogo]')).toBeNull();

    p = c.dialogo.confirmar('De novo?');
    document.querySelector('[data-c2fc-cancelar]').click();
    expect(await p).toBe(false);

    p = c.dialogo.confirmar('Escape?');
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    expect(await p).toBe(false);
  });

  it('perguntar devolve o texto digitado (ou null) e alerta resolve no OK; mensagem nunca vira HTML', async () => {
    const c = carregar();
    let p = c.dialogo.perguntar('Nome?', 'atual');
    const entrada = document.querySelector('.c2fc-entrada');
    expect(entrada.value).toBe('atual');
    entrada.value = 'novo';
    document.querySelector('[data-c2fc-ok]').click();
    expect(await p).toBe('novo');
    p = c.dialogo.perguntar('Nome?');
    document.querySelector('[data-c2fc-cancelar]').click();
    expect(await p).toBeNull();
    p = c.dialogo.alerta('<img src=x onerror=alert(1)>');
    expect(document.querySelector('.c2fc-dialogo img')).toBeNull();
    expect(document.querySelector('[data-c2fc-cancelar]')).toBeNull();
    document.querySelector('[data-c2fc-ok]').click();
    await p;
  });

  it('aviso aparece e some no tempo pedido', async () => {
    vi.useFakeTimers();
    const c = carregar();
    c.aviso('Salvo', 'sucesso', 1000);
    expect(document.querySelector('.c2fc-aviso-sucesso').textContent).toContain('Salvo');
    vi.advanceTimersByTime(1100);
    expect(document.querySelector('.c2fc-aviso')).toBeNull();
    vi.useRealTimers();
  });
});

describe('select', () => {
  function montar(html) { document.body.innerHTML = html; return document.querySelector('select'); }

  it('aprimora o select nativo, que continua sendo o que o formulário envia', () => {
    const c = carregar();
    const nativo = montar('<form><select name="layout"><option value="">Escolha</option><option value="a">Alfa</option><option value="b">Beta</option></select></form>');
    const s = c.select(nativo);
    const mudancas = [];
    nativo.addEventListener('change', () => mudancas.push(nativo.value));
    expect(document.querySelector('.c2fc-select-gatilho').textContent).toBe('Escolha');
    document.querySelector('.c2fc-select-gatilho').click();
    const opcoes = document.querySelectorAll('.c2fc-select-opcao');
    expect(opcoes.length).toBe(3);
    opcoes[2].dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
    expect(nativo.value).toBe('b');
    expect(s.valor()).toBe('b');
    expect(mudancas).toEqual(['b']);
    expect(new FormData(document.querySelector('form')).get('layout')).toBe('b');
  });

  it('busca sem acento, teclado e múltiplo com fichas removíveis', () => {
    const c = carregar();
    const nativo = montar('<select multiple data-c2f-busca><option value="1">Página inicial</option><option value="2">Contato</option><option value="3">Serviços</option></select>');
    const s = c.select(nativo);
    document.querySelector('.c2fc-select-gatilho').click();
    const busca = document.querySelector('.c2fc-select-busca');
    busca.value = 'pagina';
    busca.dispatchEvent(new Event('input'));
    expect(Array.from(document.querySelectorAll('.c2fc-select-opcao')).map((o) => o.textContent)).toEqual(['Página inicial']);
    busca.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter' }));
    s.definir(['1', '3']);
    expect(s.valor()).toEqual(['1', '3']);
    expect(document.querySelectorAll('.c2fc-ficha').length).toBe(2);
    document.querySelector('.c2fc-ficha button').click();
    expect(s.valor()).toEqual(['3']);
  });

  it('opções por AJAX no padrão do gestor (ajax=sim, ajaxOpcao, q)', async () => {
    const c = carregar();
    const nativo = montar('<select data-c2f-ajax-opcao="buscar-paginas" data-c2f-ajax-url="/admin-paginas/" data-c2f-ajax-minimo="2"><option value="">—</option></select>');
    const fetchMock = vi.fn(() => Promise.resolve({ json: () => Promise.resolve({ status: 'Ok', data: { items: [{ value: 'home', text: 'Home' }] } }) }));
    window.fetch = fetchMock;
    vi.useFakeTimers();
    c.select(nativo);
    document.querySelector('.c2fc-select-gatilho').click();
    const busca = document.querySelector('.c2fc-select-busca');
    busca.value = 'ho';
    busca.dispatchEvent(new Event('input'));
    vi.advanceTimersByTime(300);
    vi.useRealTimers();
    await espera(); await espera(); await espera();
    expect(fetchMock).toHaveBeenCalledTimes(1);
    const corpo = fetchMock.mock.calls[0][1].body.toString();
    expect(corpo).toContain('ajax=sim');
    expect(corpo).toContain('ajaxOpcao=buscar-paginas');
    expect(corpo).toContain('q=ho');
    expect(Array.from(nativo.options).map((o) => o.value)).toContain('home');
  });
});

describe('abas', () => {
  it('liga botões e painéis por data-c2f-aba / data-c2f-painel, com setas', () => {
    const c = carregar();
    document.body.innerHTML = '<div data-c2f-abas><div><button data-c2f-aba="a">A</button><button data-c2f-aba="b">B</button></div><div data-c2f-painel="a">pa</div><div data-c2f-painel="b">pb</div></div>';
    c.iniciar(document);
    const [a, b] = document.querySelectorAll('[data-c2f-aba]');
    expect(document.querySelector('[data-c2f-painel="b"]').hidden).toBe(true);
    b.click();
    expect(b.getAttribute('aria-selected')).toBe('true');
    expect(document.querySelector('[data-c2f-painel="a"]').hidden).toBe(true);
    b.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight' }));
    expect(a.getAttribute('aria-selected')).toBe('true');
  });
});

describe('ponte Fomantic', () => {
  it('com o Fomantic na página, não toca em nada', () => {
    carregar({ comFomantic: true });
    expect(window.jQuery.fn.dropdown()).toBe('fomantic');
    expect(window.c2fControles.ponteAtiva).toBeUndefined();
  });

  it('dropdown: onChange, get value, set selected (silencioso) e refresh sobre <select class="ui dropdown">', () => {
    carregar();
    document.body.innerHTML = '<select class="ui dropdown framework"><option value="fomantic-ui">Fomantic</option><option value="tailwindcss">Tailwind</option></select>';
    const $ = window.jQuery;
    const vistos = [];
    $('.framework').dropdown({ onChange: (v) => vistos.push(v) });
    expect($('.framework').dropdown('get value')).toBe('fomantic-ui');
    $('.framework').dropdown('set selected', 'tailwindcss', true);
    expect($('.framework').dropdown('get value')).toBe('tailwindcss');
    expect(vistos).toEqual([]);
    $('.framework').dropdown('set selected', 'fomantic-ui');
    expect(vistos).toEqual(['fomantic-ui']);
    expect($('.framework').dropdown('refresh')).toBeTruthy();
  });

  it('dropdown sobre a div do Fomantic (input escondido + .menu .item) envia o mesmo nome', () => {
    carregar();
    document.body.innerHTML = '<form><div class="ui selection dropdown alvo"><input type="hidden" name="tipo" value="sessao"><div class="text">Escolha</div><div class="menu"><div class="item" data-value="sessao">Sessão</div><div class="item" data-value="tudo">Tudo</div></div></div></form>';
    const $ = window.jQuery;
    expect($('.alvo').dropdown('get value')).toBe('sessao');
    $('.alvo').dropdown('set selected', 'tudo');
    expect(new FormData(document.querySelector('form')).get('tipo')).toBe('tudo');
  });

  it('checkbox: is checked, check/uncheck e callbacks; tab: change tab', () => {
    carregar();
    document.body.innerHTML = '<div class="ui toggle checkbox"><input type="checkbox" name="raiz"><label></label></div>'
      + '<div class="menu"><a class="item active" data-tab="codigo">C</a><a class="item" data-tab="visual">V</a></div>'
      + '<div class="ui tab active" data-tab="codigo">c</div><div class="ui tab" data-tab="visual">v</div>';
    const $ = window.jQuery;
    const marcados = [];
    $('.ui.checkbox').checkbox({ onChecked: () => marcados.push('sim') });
    expect($('.ui.checkbox').checkbox('is checked')).toBe(false);
    $('.ui.checkbox').checkbox('check');
    expect($('.ui.checkbox').checkbox('is checked')).toBe(true);
    expect(marcados).toEqual(['sim']);
    $('.ui.checkbox').checkbox('set unchecked');
    expect(document.querySelector('input[name="raiz"]').checked).toBe(false);
    $('.menu .item').tab();
    document.querySelector('[data-tab="visual"].item').click();
    expect(document.querySelector('.ui.tab[data-tab="visual"]').classList.contains('active')).toBe(true);
    expect(document.querySelector('.ui.tab[data-tab="codigo"]').classList.contains('active')).toBe(false);
  });

  it('tab: grupos aninhados não se desligam, onLoad na troca e na inicialização (como o editor HTML usa)', () => {
    carregar();
    document.body.innerHTML = '<div class="menu externo"><a class="item active" data-tab="pagina">P</a><a class="item" data-tab="codigo">C</a></div>'
      + '<div><div class="tab active" data-tab="pagina">p</div><div class="tab" data-tab="codigo">'
      + '<div class="menu interno"><a class="item active" data-tab="html">H</a><a class="item" data-tab="css">S</a></div>'
      + '<div><div class="tab active" data-tab="html">h</div><div class="tab" data-tab="css">s</div></div></div></div>';
    const $ = window.jQuery;
    const carregadas = [];
    $('.externo .item').tab('change tab', 'codigo');
    $('.externo .item').tab({ onLoad: (nome) => carregadas.push('externo:' + nome) });
    $('.interno .item').tab({ onLoad: (nome) => carregadas.push('interno:' + nome) });
    expect(carregadas).toEqual(['externo:codigo', 'interno:html']);
    document.querySelector('.item[data-tab="css"]').click();
    const ativo = (n) => document.querySelector('.tab[data-tab="' + n + '"]').classList.contains('active');
    expect(ativo('css')).toBe(true);
    expect(ativo('html')).toBe(false);
    expect(ativo('codigo')).toBe(true);
    expect(ativo('pagina')).toBe(false);
    expect(carregadas[2]).toBe('interno:css');
    $('.externo .item').tab('change tab', 'pagina');
    expect(ativo('pagina')).toBe(true);
    expect(ativo('css')).toBe(true);
    expect(carregadas[3]).toBe('externo:pagina');
  });

  it('req-224: toast vira aviso com ações; data-checked="checked" marca o checkbox; sem Fomantic liga a classe das dicas', async () => {
    carregar();
    document.body.innerHTML = '<input type="checkbox" name="a" data-checked="checked"><input type="checkbox" name="b" data-checked="">';
    window.c2fControles.marcarDataChecked(document);
    expect(document.querySelector('[name="a"]').checked).toBe(true);
    expect(document.querySelector('[name="b"]').checked).toBe(false);
    expect(document.documentElement.classList.contains('c2fc-sem-fomantic')).toBe(true);
    const $ = window.jQuery;
    const cliques = [];
    $('body').toast({ title: 'Atualização', message: 'Nova <b>versão</b>', class: 'success', actions: [{ text: 'Atualizar', click: () => cliques.push('sim') }] });
    const aviso = document.querySelector('.c2fc-aviso.c2fc-aviso-sucesso');
    expect(aviso.textContent).toContain('Atualização — Nova versão');
    aviso.querySelector('.c2fc-aviso-acao').click();
    expect(cliques).toEqual(['sim']);
    expect(document.querySelector('.c2fc-aviso.c2fc-aviso-sucesso')).toBeNull();
  });

  it('modal: show/hide, onApprove que devolve false mantém aberto', () => {
    carregar();
    document.body.innerHTML = '<div class="ui modal m"><div class="header">T</div><div class="content">x</div><div class="actions"><button class="ui approve button">OK</button><button class="ui deny button">Não</button></div></div>';
    const $ = window.jQuery;
    let permitir = false;
    $('.m').modal({ onApprove: () => permitir });
    $('.m').modal('show');
    const fundo = document.querySelector('[data-c2fc-ponte-modal]');
    expect(fundo.classList.contains('c2fc-oculto')).toBe(false);
    document.querySelector('.approve').click();
    expect(fundo.classList.contains('c2fc-oculto')).toBe(false);
    permitir = true;
    document.querySelector('.approve').click();
    expect(fundo.classList.contains('c2fc-oculto')).toBe(true);
    $('.m').modal('show');
    document.querySelector('.deny').click();
    expect(fundo.classList.contains('c2fc-oculto')).toBe(true);
  });
});
