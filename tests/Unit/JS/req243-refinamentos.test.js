import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { describe, expect, it, vi } from 'vitest';

/**
 * req-243 (BATCH-252) — refinamentos do painel Tailwind, rodada 2:
 * clique isolado na opção do select, select que acompanha quem escreve nele por script, select que entra
 * depois da carga, alerta com ênfase segura, bandeja do seletor de arquivos e as regras de folha do lote.
 */
const ler = (caminho) => readFileSync(resolve(process.cwd(), caminho), 'utf8');

function controles() {
  delete window.c2fControles;
  delete window.jQuery;
  vm.runInThisContext(ler('gestor/assets/interface/controles.js'), { filename: 'controles.js' });
  return window.c2fControles;
}

const SELECT = '<select><option value="">Todos</option><option value="a">Alfa</option><option value="b">Beta</option></select>';

describe('clique na opção do select não vaza (req-243)', () => {
  it('mousedown e click da opção param na opção', () => {
    const c = controles();
    document.body.innerHTML = '<div id="fundo">' + SELECT + '</div>';
    const fundo = document.getElementById('fundo');
    const vistos = [];
    fundo.addEventListener('mousedown', () => vistos.push('mousedown'));
    fundo.addEventListener('click', () => vistos.push('click'));
    c.select(document.querySelector('select'));
    document.querySelector('.c2fc-select-gatilho').click();
    vistos.length = 0;
    const opcao = document.querySelectorAll('.c2fc-select-opcao')[1];
    const baixo = new MouseEvent('mousedown', { bubbles: true, cancelable: true });
    opcao.dispatchEvent(baixo);
    const clique = new MouseEvent('click', { bubbles: true, cancelable: true });
    opcao.dispatchEvent(clique);
    expect(vistos).toEqual([]);
    expect(baixo.defaultPrevented).toBe(true);
    expect(clique.defaultPrevented).toBe(true);
    expect(document.querySelector('select').value).toBe('a');
  });

  it('o restante do gesto é engolido até o botão do mouse ser solto', async () => {
    const c = controles();
    document.body.innerHTML = SELECT + '<button id="atras">Editor visual</button>';
    const s = c.select(document.querySelector('select'));
    const atras = document.getElementById('atras');
    const acionado = vi.fn();
    const solto = vi.fn();
    atras.addEventListener('click', acionado);
    // Botões antigos do painel agem em mouseup: ele também não pode chegar.
    atras.addEventListener('mouseup', solto);
    s._engolirClique();
    atras.dispatchEvent(new MouseEvent('mouseup', { bubbles: true, cancelable: true }));
    expect(solto).not.toHaveBeenCalled();
    atras.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    expect(acionado).not.toHaveBeenCalled();
    await new Promise((r) => setTimeout(r, 5));
    atras.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    expect(acionado).toHaveBeenCalledTimes(1);
    atras.dispatchEvent(new MouseEvent('mouseup', { bubbles: true, cancelable: true }));
    expect(solto).toHaveBeenCalledTimes(1);
  });

  it('a opção de valor vazio ("Todos") pode ser escolhida de volta e dispara change', () => {
    const c = controles();
    document.body.innerHTML = SELECT;
    const nativo = document.querySelector('select');
    const s = c.select(nativo);
    s.definir('a');
    const mudou = vi.fn();
    nativo.addEventListener('change', mudou);
    document.querySelector('.c2fc-select-gatilho').click();
    document.querySelectorAll('.c2fc-select-opcao')[0].dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
    expect(nativo.value).toBe('');
    expect(mudou).toHaveBeenCalledTimes(1);
    expect(document.querySelector('.c2fc-select-gatilho').textContent).toBe('Todos');
  });
});

describe('select acompanha o script da página (req-243)', () => {
  it('escrever em select.value redesenha o gatilho sem evento change', () => {
    const c = controles();
    document.body.innerHTML = SELECT;
    const nativo = document.querySelector('select');
    c.select(nativo);
    nativo.value = 'b';
    expect(document.querySelector('.c2fc-select-gatilho').textContent).toBe('Beta');
    nativo.selectedIndex = 1;
    expect(document.querySelector('.c2fc-select-gatilho').textContent).toBe('Alfa');
    expect(nativo.value).toBe('a');
  });

  it('destruir devolve o select nativo sem os acessores do controle', () => {
    const c = controles();
    document.body.innerHTML = SELECT;
    const nativo = document.querySelector('select');
    c.select(nativo).destruir();
    expect(Object.prototype.hasOwnProperty.call(nativo, 'value')).toBe(false);
    nativo.value = 'b';
    expect(nativo.value).toBe('b');
    expect(document.querySelector('.c2fc-select')).toBeNull();
  });

  it('a biblioteca observa selects que entram depois da carga, fora de <template> e de controle pronto', () => {
    const fonte = ler('gestor/assets/interface/controles.js');
    expect(fonte).toContain('function observarSelects()');
    expect(fonte).toContain("no.closest('.c2fc-select')");
    expect(fonte).toContain("no.closest('template')");
    expect(fonte).toMatch(/function preparar\(\) \{[^}]*observarSelects\(\);/);
  });
});

describe('alerta com ênfase segura (req-243)', () => {
  it('só as tags de ênfase viram elemento, sem atributos; o resto entra como texto', () => {
    const c = controles();
    const p = document.createElement('p');
    c.formatado(p, 'O arquivo <b onclick="x()">NÃO</b> é <a href="javascript:x">imagem</a><script>x()</script><img src=x onerror=x()>.');
    expect(p.querySelector('b').textContent).toBe('NÃO');
    expect(p.querySelector('b').attributes.length).toBe(0);
    expect(p.querySelector('a, script, img')).toBeNull();
    expect(p.textContent).toBe('O arquivo NÃO é imagem.');
  });

  it('o seletor de imagem do painel abre o alerta com a mensagem formatada', () => {
    const fonte = ler('gestor/assets/interface/controles.js');
    expect(fonte).toContain("config.alertas.naoImagem) || texto('atencao'), null, { formatado: true }");
  });
});

describe('seletor de arquivos: bandeja e conclusão (req-243)', () => {
  // O arquivo é script de página (usa `$` solto): carregado com um jQuery de mentira, só para ler o que exporta.
  const modulo = { exports: {} };
  const jqueryFalso = () => ({ ready() {} });
  new Function('module', 'exports', '$', 'window', ler('gestor/modulos/admin-arquivos/admin-arquivos.js'))(modulo, modulo.exports, jqueryFalso, globalThis);
  const arquivos = modulo.exports;

  it('no seletor múltiplo a bandeja fica à vista mesmo sem seleção', () => {
    expect(arquivos.adminArquivosBandejaVisivel(true, true, [])).toBe(true);
    expect(arquivos.adminArquivosBandejaVisivel(true, false, [])).toBe(false);
    expect(arquivos.adminArquivosBandejaVisivel(true, false, [{ caminho: 'a.png' }])).toBe(true);
    expect(arquivos.adminArquivosBandejaVisivel(false, true, [{ caminho: 'a.png' }])).toBe(false);
  });

  it('o aviso de conclusão usa identificador próprio, que os consumidores do canal de arquivos ignoram', () => {
    const msg = JSON.parse(arquivos.adminArquivosMensagemSeletor('concluir'));
    expect(msg).toEqual({ moduloId: 'admin-arquivos-seletor', acao: 'concluir' });
    expect(msg.data).toBeUndefined();
  });

  it('a galeria fecha o modal quando o seletor conclui ou cancela', () => {
    const fonte = ler('gestor/modulos/galleries/galleries.js');
    expect(fonte).toContain("data.moduloId === 'admin-arquivos-seletor'");
    expect(fonte).toMatch(/acao === 'concluir' \|\| data\.acao === 'cancelar'\) \$\('\.modal\.iframePagina'\)\.modal\('hide'\)/);
  });

  it('a alça de arrasto da galeria é sólida', () => {
    const fonte = ler('gestor/modulos/galleries/galleries.js');
    expect(fonte).toContain(".gallery-item-handle{cursor:grab;opacity:1;}");
    expect(fonte).not.toContain(".gallery-item-handle{cursor:grab;opacity:0.6;}");
  });
});

describe('folha de controles (req-243)', () => {
  const folha = ler('gestor/assets/interface/controles.css');

  it('o palco do editor visual guarda a altura: a regra do iframe vale só em .iframe-container', () => {
    expect(folha).toContain('.c2fc-ponte-modal.fullscreen > .content:has(.iframe-container) {');
    expect(folha).toContain('.c2fc-ponte-modal.fullscreen > .content .iframe-container iframe {');
    expect(folha).not.toContain('.c2fc-ponte-modal.fullscreen > .content:has(iframe)');
  });

  it('histórico quebra linha na largura disponível', () => {
    expect(folha).toContain('[data-c2f-historico] table { table-layout: fixed; width: 100%; min-width: 0; }');
    expect(folha).toMatch(/\[data-c2f-historico\] td,[^{]*\{ white-space: normal; overflow-wrap: anywhere;/);
  });

  it('grade de modelos, mapeamento em três colunas, ícone sem contorno e dica vazia sem balão', () => {
    expect(folha).toContain('.c2fc-modelos-grade { grid-template-columns: repeat(auto-fill, minmax(180px, 240px));');
    expect(folha).toContain('@media (min-width: 768px) { .c2fc-mapa { grid-template-columns: repeat(3, minmax(0, 1fr)); } }');
    expect(folha).toContain('svg.icon.outline, svg.lucide.outline, i.icon.outline { outline: 0 !important; }');
    expect(folha).toContain('[data-c2f-dica=""]::after');
    expect(folha).toContain('.field-row .fields > .two.wide { flex: 0 0 auto; display: flex; flex-direction: row;');
  });

  it('o painel tira a classe outline do ícone convertido para Lucide', () => {
    expect(ler('gestor/assets/global/admin-tailwind.js')).toContain("icone.classList.remove('outline');");
  });
});

describe('admin-cron: botões de ação (req-243)', () => {
  it('botão da linha sai com classe do painel e dica com a tarefa, com aspas escapadas', () => {
    const fonte = ler('gestor/modulos/admin-cron/admin-cron.js');
    expect(fonte).toContain("'class=\"c2fc-botao c2fc-botao-pequeno'");
    expect(fonte).toContain("escapar(dica).replace(/\"/g, '&quot;')");
    expect((fonte.match(/botaoHtml\('[a-z]+', t\.id, [^)]+, t\.nome\)/g) || []).length).toBe(5);
  });
});
