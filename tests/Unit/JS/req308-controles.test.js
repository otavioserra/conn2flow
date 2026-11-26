import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * REQ-308 (BATCH-317) — padrão da UI 3.0 para todos os módulos e projetos:
 * abas responsivas com seletor compacto no mobile, <dialog> modal que fecha ao clicar fora e aviso (toast)
 * único com ícone, sem repetição e com limite na tela.
 */

function carregar() {
  delete window.c2fControles;
  delete window.jQuery;
  const codigo = readFileSync(resolve(process.cwd(), 'gestor/assets/interface/controles.js'), 'utf8');
  vm.runInThisContext(codigo, { filename: 'controles.js' });
  return window.c2fControles;
}

const microtarefas = () => new Promise((r) => setTimeout(r, 0));

function navegar(url) { window.history.replaceState(null, '', url); }

function abas(n = 4, atributos = '') {
  const botoes = Array.from({ length: n }, (_, i) => `<button type="button" data-c2f-aba="a${i}"${i === 0 ? ' aria-selected="true"' : ''}><svg data-c2f-aba-icone viewBox="0 0 24 24"><path d="M${i} 0"/></svg>Aba ${i}</button>`).join('');
  const paineis = Array.from({ length: n }, (_, i) => `<section data-c2f-painel="a${i}">P${i}</section>`).join('');
  return `<div id="g" data-c2f-abas ${atributos}><div class="faixa" aria-label="Seções">${botoes}</div>${paineis}</div>`;
}

describe('REQ-308: abas com seletor compacto no mobile', () => {
  beforeEach(() => { window.sessionStorage.clear(); navegar('/ficha/'); });

  it('gera o seletor antes da faixa para 3+ abas, com rótulo, ícone da aba ativa e grade a partir de 4', () => {
    document.body.innerHTML = abas(4);
    const c = carregar();
    const a = c.criar('abas', document.getElementById('g'));
    const seletor = document.querySelector('.c2fc-abas-seletor');
    expect(seletor.nextElementSibling.classList.contains('c2fc-abas-com-seletor')).toBe(true);
    const gatilho = seletor.querySelector('.c2fc-abas-gatilho');
    expect(gatilho.getAttribute('aria-expanded')).toBe('false');
    expect(gatilho.querySelector('small').textContent).toBe('Current section');
    expect(gatilho.querySelector('.c2fc-abas-gatilho-atual').textContent).toBe('Aba 0');
    expect(gatilho.querySelector('.c2fc-abas-gatilho-icone path').getAttribute('d')).toBe('M0 0');
    const painel = seletor.querySelector('.c2fc-abas-painel');
    expect(painel.hidden).toBe(true);
    expect(painel.getAttribute('aria-label')).toBe('Seções');
    expect(painel.classList.contains('c2fc-abas-painel-grade')).toBe(true);
    expect(painel.querySelectorAll('.c2fc-abas-opcao')).toHaveLength(4);
    expect(painel.querySelector('[aria-current="true"]').getAttribute('data-c2f-abas-opcao')).toBe('a0');
    expect(a.valor()).toBe('a0');
  });

  it('usa o texto do sistema e o rótulo próprio do grupo', () => {
    window.gestor.controlesTextos = { secaoAtual: 'Seção atual' };
    try {
      document.body.innerHTML = abas(3) + abas(3, 'data-c2f-abas-rotulo-mobile="Etapa atual"').replace('id="g"', 'id="h"');
      const c = carregar();
      c.iniciar(document);
      const rotulos = Array.from(document.querySelectorAll('.c2fc-abas-gatilho small')).map((s) => s.textContent);
      expect(rotulos).toEqual(['Seção atual', 'Etapa atual']);
      expect(document.querySelector('.c2fc-abas-painel').classList.contains('c2fc-abas-painel-grade')).toBe(false);
    } finally {
      delete window.gestor.controlesTextos;
    }
  });

  it('não gera seletor com menos de 3 abas nem com o opt-out', () => {
    document.body.innerHTML = abas(2) + abas(5, 'data-c2f-abas-sem-seletor-mobile').replace('id="g"', 'id="h"');
    carregar().iniciar(document);
    expect(document.querySelector('.c2fc-abas-seletor')).toBeNull();
    expect(document.querySelector('.c2fc-abas-com-seletor')).toBeNull();
  });

  it('escolher no painel clica na aba original, troca o painel, fecha e grava a memória da sessão', () => {
    document.body.innerHTML = abas(4, 'data-c2f-abas-chave="ficha"');
    const c = carregar();
    const a = c.criar('abas', document.getElementById('g'));
    const cliqueModulo = vi.fn();
    a.botoes[2].addEventListener('click', cliqueModulo);
    const gatilho = document.querySelector('.c2fc-abas-gatilho');
    gatilho.click();
    expect(gatilho.getAttribute('aria-expanded')).toBe('true');
    expect(document.activeElement.getAttribute('data-c2f-abas-opcao')).toBe('a0');
    document.querySelector('[data-c2f-abas-opcao="a2"]').click();
    expect(cliqueModulo).toHaveBeenCalledTimes(1);
    expect(a.valor()).toBe('a2');
    expect(a.paineis[2].hidden).toBe(false);
    expect(document.querySelector('.c2fc-abas-painel').hidden).toBe(true);
    expect(document.activeElement).toBe(gatilho);
    expect(gatilho.querySelector('.c2fc-abas-gatilho-atual').textContent).toBe('Aba 2');
    expect(window.sessionStorage.getItem('c2f:abas:/ficha/:ficha')).toBe('a2');
  });

  it('definir() e a restauração por ?tab= e pela sessão atualizam o seletor', () => {
    document.body.innerHTML = abas(4, 'data-c2f-abas-chave="ficha"');
    navegar('/ficha/?tab=a3');
    let c = carregar();
    let a = c.criar('abas', document.getElementById('g'));
    expect(document.querySelector('.c2fc-abas-gatilho-atual').textContent).toBe('Aba 3');
    a.definir('a1');
    expect(document.querySelector('.c2fc-abas-gatilho-atual').textContent).toBe('Aba 1');
    expect(document.querySelector('[aria-current="true"]').getAttribute('data-c2f-abas-opcao')).toBe('a1');

    navegar('/ficha/');
    document.body.innerHTML = abas(4, 'data-c2f-abas-chave="ficha"');
    c = carregar();
    a = c.criar('abas', document.getElementById('g'));
    expect(a.valor()).toBe('a1');
    expect(document.querySelector('.c2fc-abas-gatilho-atual').textContent).toBe('Aba 1');
  });

  it('acompanha o módulo que troca aria-selected, hidden e disabled por conta própria', async () => {
    document.body.innerHTML = abas(4, 'data-c2f-abas-sem-memoria');
    const a = carregar().criar('abas', document.getElementById('g'));
    a.botoes[0].setAttribute('aria-selected', 'false');
    a.botoes[3].setAttribute('aria-selected', 'true');
    a.botoes[1].hidden = true;
    a.botoes[2].disabled = true;
    await microtarefas();
    expect(document.querySelector('.c2fc-abas-gatilho-atual').textContent).toBe('Aba 3');
    expect(document.querySelector('[data-c2f-abas-opcao="a1"]').hidden).toBe(true);
    expect(document.querySelector('[data-c2f-abas-opcao="a2"]').disabled).toBe(true);
    expect(document.querySelector('.c2fc-abas-painel').classList.contains('c2fc-abas-painel-grade')).toBe(false);
  });

  it('Esc fecha e devolve o foco ao gatilho; clique fora fecha; setas andam entre as opções', () => {
    document.body.innerHTML = abas(4) + '<p id="fora">fora</p>';
    carregar().criar('abas', document.getElementById('g'));
    const gatilho = document.querySelector('.c2fc-abas-gatilho');
    const painel = document.querySelector('.c2fc-abas-painel');
    gatilho.click();
    document.activeElement.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
    expect(document.activeElement.getAttribute('data-c2f-abas-opcao')).toBe('a1');
    const esc = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true });
    document.activeElement.dispatchEvent(esc);
    expect(esc.defaultPrevented).toBe(true);
    expect(painel.hidden).toBe(true);
    expect(document.activeElement).toBe(gatilho);
    gatilho.click();
    expect(painel.hidden).toBe(false);
    document.getElementById('fora').click();
    expect(painel.hidden).toBe(true);
  });

  it('destruir() remove o seletor e o ouvinte do documento', () => {
    document.body.innerHTML = abas(4);
    const remover = vi.spyOn(document, 'removeEventListener');
    const a = carregar().criar('abas', document.getElementById('g'));
    a.destruir();
    expect(document.querySelector('.c2fc-abas-seletor')).toBeNull();
    expect(document.querySelector('.c2fc-abas-com-seletor')).toBeNull();
    expect(remover).toHaveBeenCalledWith('click', expect.any(Function));
  });

  it('CSS: faixa quebra linha sem rolagem lateral e troca pelo seletor abaixo de 640 px', () => {
    const css = readFileSync(resolve(process.cwd(), 'gestor/assets/interface/controles.css'), 'utf8');
    expect(css).toMatch(/\.c2fc-abas-lista \{ display: flex; flex-wrap: wrap;/);
    expect(css).toMatch(/\.c2fc-abas-lista \{ max-width: 100%; overflow-x: visible; \}/);
    const mobile = css.slice(css.indexOf('@media (max-width: 639.98px) {\n    .c2fc-abas-seletor'));
    expect(mobile).toMatch(/\.c2fc-abas-seletor \{ display: block; \}/);
    expect(mobile).toMatch(/\.c2fc-abas-lista\.c2fc-abas-com-seletor \{ display: none !important; \}/);
  });
});

describe('REQ-308: <dialog> modal fecha ao clicar fora', () => {
  const proto = window.HTMLDialogElement.prototype;
  const showModal = proto.showModal;
  const matches = window.Element.prototype.matches;

  beforeEach(() => {
    // happy-dom/jsdom não implementam `:modal`: emula o navegador.
    proto.showModal = function () { this.c2fTesteModal = true; return showModal.call(this); };
    proto.matches = function (sel) { return sel === ':modal' ? !!(this.open && this.c2fTesteModal) : matches.call(this, sel); };
  });
  afterEach(() => { proto.showModal = showModal; delete proto.matches; });

  function abrir(atributos = '') {
    document.body.innerHTML = `<dialog id="d" ${atributos}><textarea id="t">texto</textarea></dialog>`;
    const d = document.getElementById('d');
    d.showModal();
    d.getBoundingClientRect = () => ({ left: 100, top: 100, right: 300, bottom: 300, width: 200, height: 200 });
    return d;
  }
  function gesto(inicio, fim, x0, y0, x1 = x0, y1 = y0) {
    inicio.dispatchEvent(new MouseEvent('pointerdown', { bubbles: true, clientX: x0, clientY: y0 }));
    fim.dispatchEvent(new MouseEvent('click', { bubbles: true, clientX: x1, clientY: y1 }));
  }

  it('fecha quando o gesto começa e termina no fundo, fora da caixa, e a limpeza do `close` roda', () => {
    carregar();
    const d = abrir();
    const limpeza = vi.fn();
    d.addEventListener('close', limpeza);
    gesto(d, d, 20, 20);
    expect(d.open).toBe(false);
    expect(limpeza).toHaveBeenCalledTimes(1);
  });

  it('não fecha quando o gesto começa dentro (seleção de texto) e solta no fundo', () => {
    carregar();
    const d = abrir();
    gesto(document.getElementById('t'), d, 150, 150, 20, 20);
    expect(d.open).toBe(true);
  });

  it('não fecha com clique dentro da caixa nem com clique de teclado (0, 0)', () => {
    carregar();
    const d = abrir();
    gesto(d, d, 150, 150);
    expect(d.open).toBe(true);
    gesto(d, d, 0, 0);
    expect(d.open).toBe(true);
  });

  it('respeita data-c2f-sem-fechar-fora e o preventDefault no `cancel`', () => {
    carregar();
    let d = abrir('data-c2f-sem-fechar-fora');
    gesto(d, d, 20, 20);
    expect(d.open).toBe(true);
    d = abrir();
    d.addEventListener('cancel', (ev) => ev.preventDefault());
    gesto(d, d, 20, 20);
    expect(d.open).toBe(true);
  });

  it('não fecha <dialog> aberto sem modal (show())', () => {
    carregar();
    document.body.innerHTML = '<dialog id="d"></dialog>';
    const d = document.getElementById('d');
    d.show();
    d.getBoundingClientRect = () => ({ left: 100, top: 100, right: 300, bottom: 300 });
    gesto(d, d, 20, 20);
    expect(d.open).toBe(true);
  });

  it('o aviso aberto com um modal entra nele e volta para a página quando ele fecha', () => {
    const c = carregar();
    const d = abrir();
    c.aviso('Salvo', 'success', 0);
    expect(d.querySelector('.c2fc-avisos')).not.toBeNull();
    d.close();
    expect(document.querySelector('.c2fc-avisos').parentNode).toBe(document.body);
  });
});

describe('REQ-308: aviso (toast) único da plataforma', () => {
  beforeEach(() => { document.body.innerHTML = ''; });

  it('aceita tipos em inglês, desenha ícone e mantém o fechar como último filho', () => {
    const c = carregar();
    c.aviso('Erro ao salvar', 'error', 0);
    const item = document.querySelector('.c2fc-aviso.c2fc-aviso-erro');
    expect(item.getAttribute('role')).toBe('alert');
    expect(item.firstChild.getAttribute('class')).toBe('c2fc-aviso-icone');
    expect(item.querySelector('.c2fc-aviso-texto').textContent).toBe('Erro ao salvar');
    expect(item.lastChild.classList.contains('c2fc-aviso-fechar')).toBe(true);
    c.aviso('ok', 'warning', 0);
    c.aviso('x', 'desconhecido', 0);
    expect(document.querySelector('.c2fc-aviso-alerta')).not.toBeNull();
    expect(document.querySelectorAll('.c2fc-aviso:not(.c2fc-aviso-erro):not(.c2fc-aviso-alerta)')).toHaveLength(1);
  });

  it('a mesma mensagem não empilha e no máximo 3 ficam na tela', () => {
    const c = carregar();
    c.aviso('Salvo', 'sucesso', 0);
    c.aviso('Salvo', 'sucesso', 0);
    expect(document.querySelectorAll('.c2fc-aviso')).toHaveLength(1);
    ['b', 'c', 'd'].forEach((t) => c.aviso(t, 'info', 0));
    const textos = Array.from(document.querySelectorAll('.c2fc-aviso-texto')).map((s) => s.textContent);
    expect(textos).toEqual(['b', 'c', 'd']);
  });

  it('o tempo pausa com o mouse em cima e recomeça ao sair; a forma objeto define o tempo', () => {
    vi.useFakeTimers();
    try {
      const c = carregar();
      c.aviso('Pausa', 'sucesso', { tempo: 1000 });
      const item = document.querySelector('.c2fc-aviso');
      item.dispatchEvent(new MouseEvent('mouseenter'));
      vi.advanceTimersByTime(5000);
      expect(item.isConnected).toBe(true);
      item.dispatchEvent(new MouseEvent('mouseleave'));
      vi.advanceTimersByTime(1100);
      expect(item.isConnected).toBe(false);
    } finally {
      vi.useRealTimers();
    }
  });
});
