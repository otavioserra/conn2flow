import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';
import { beforeEach, describe, expect, it } from 'vitest';
import { installJQueryStub } from './helpers/jquery-stub.js';

/**
 * req-117 (BATCH-117) — captura do CSS compilado pelo Tailwind Browser.
 *
 * O defeito medido: páginas salvas perdiam TODAS as utilities. A causa não era só "esperar pouco" —
 * o critério de escolha da folha era posicional ("a última <style> do <head> com regras"), e o
 * @tailwindcss/browser cria a folha de saída VAZIA, escrevendo nela só quando o build assíncrono
 * termina. Nesse intervalo qualquer <style> injetado em runtime (UI do editor, CSS de modelo, CSS da
 * IA) é o último com regras — e era isso que ia para a coluna `css_compiled`.
 *
 * O CSSOM aqui é simulado: happy-dom não implementa CSSLayerBlockRule nem monta `sheet.cssRules` a
 * partir de textContent. As classes falsas reproduzem o formato que o navegador entrega, incluindo o
 * `constructor.name` de que a detecção depende.
 */

// ===== CSSOM simulado =========================================================================

class CSSLayerBlockRule {
  constructor(name, cssRules) {
    this.name = name;
    this.cssRules = cssRules || [];
    this.cssText = '@layer ' + name + ' { ... }';
  }
}

class CSSLayerStatementRule {
  constructor(nameList) {
    this.nameList = nameList;
    this.cssText = '@layer ' + nameList.join(', ') + ';';
  }
}

function regra(selectorText, corpo) {
  return { type: 1, selectorText, cssText: selectorText + ' { ' + corpo + ' }' };
}

/**
 * Regra de `@layer theme` com um `style` navegável — o filtro de tokens percorre
 * `rule.style[i]` + `getPropertyValue`, como no CSSOM real.
 */
function regraTheme(selectorText, tokens) {
  const nomes = Object.keys(tokens);
  const style = {
    length: nomes.length,
    getPropertyValue: (nome) => tokens[nome] || ''
  };
  nomes.forEach((nome, i) => { style[i] = nome; });

  return {
    type: 1,
    selectorText,
    style,
    cssText: selectorText + ' { ' + nomes.map((n) => n + ': ' + tokens[n] + ';').join(' ') + ' }'
  };
}

function media(mediaText, cssRules) {
  return { type: 4, media: { mediaText }, cssRules, cssText: '@media ' + mediaText + ' { ... }' };
}

/** Prende uma lista de regras a um <style>, driblando a ausência de CSSOM real no happy-dom. */
function folha(atributos, cssRules) {
  const style = document.createElement('style');
  Object.keys(atributos || {}).forEach((nome) => style.setAttribute(nome, atributos[nome]));
  Object.defineProperty(style, 'sheet', { value: { cssRules }, configurable: true });
  document.head.appendChild(style);
  return style;
}

function saidaTailwind(utilities) {
  return [
    new CSSLayerStatementRule(['theme', 'base', 'components', 'utilities']),
    new CSSLayerBlockRule('theme', [regraTheme(':root', { '--color-x': 'red' })]),
    new CSSLayerBlockRule('base', [regra('*', 'box-sizing: border-box;')]),
    new CSSLayerBlockRule('utilities', utilities || [])
  ];
}

function loadEngine() {
  installJQueryStub();
  window.__c2fHtmlEditorNoAutoInit = true;
  if (!window.btoa) window.btoa = (s) => Buffer.from(s, 'binary').toString('base64');
  globalThis.fetch = () => Promise.resolve({ json: () => Promise.resolve({ status: 'error' }) });
  const code = readFileSync(resolve(process.cwd(), 'gestor/assets/interface/html-editor.js'), 'utf8');
  vm.runInThisContext(code, { filename: 'html-editor.js' });
  return window.HtmlEditorCssCapture;
}

describe('html-editor.js — captura do CSS compilado (req-117)', () => {
  let api;

  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    api = loadEngine();
  });

  it('expõe a API de captura para os dois editores (clássico e Editbar)', () => {
    expect(api).toBeTruthy();
    ['extract', 'findOutputStyle', 'hasGeneratedUtilities', 'isTailwindOutput', 'filterRules']
      .forEach((metodo) => expect(typeof api[metodo]).toBe('function'));
  });

  // ===== Detecção de utilities geradas =========================================================

  it('reconhece @layer utilities preenchido', () => {
    expect(api.hasGeneratedUtilities(saidaTailwind([regra('.text-3xl', 'font-size: 1.875rem;')]))).toBe(true);
  });

  it('NÃO aceita @layer utilities vazio — é o estado inicial da folha do browser', () => {
    expect(api.hasGeneratedUtilities(saidaTailwind([]))).toBe(false);
  });

  it('encontra utilities aninhadas em @media', () => {
    const rules = saidaTailwind([]);
    rules.push(new CSSLayerBlockRule('utilities', [media('(min-width: 64rem)', [regra('.lg\\:flex', 'display: flex;')])]));
    expect(api.hasGeneratedUtilities(rules)).toBe(true);
  });

  it('não confunde @keyframes (que também tem name e cssRules) com bloco de camada', () => {
    const keyframes = { type: 7, name: 'utilities', cssRules: [regra('0%', 'opacity: 0;')], cssText: '@keyframes utilities { }' };
    expect(api.isLayerBlock(keyframes)).toBe(false);
    expect(api.hasGeneratedUtilities([keyframes])).toBe(false);
  });

  // ===== Identificação da folha de saída ========================================================

  it('reconhece a saída do Tailwind pelas camadas nomeadas', () => {
    expect(api.isTailwindOutput(saidaTailwind([]))).toBe(true);
    expect(api.isTailwindOutput([regra('.ha', 'color: red;')])).toBe(false);
  });

  it('escolhe a folha do Tailwind mesmo quando o motor injetou <style> DEPOIS dela', () => {
    const tailwind = folha({}, saidaTailwind([regra('.text-3xl', 'font-size: 1.875rem;')]));
    // Reproduz os <style> que o motor cria em runtime (html-editor.js): sem a marcação de papel,
    // qualquer um deles seria "o último com regras".
    folha({ 'data-c2f-tailwind-role': 'editor-ui' }, [regra('#html-editor-tailwind-styler', 'position: fixed;')]);
    folha({ 'data-c2f-tailwind-role': 'authored-runtime' }, [regra('.modelo', 'color: blue;')]);

    expect(api.findOutputStyle(document)).toBe(tailwind);
  });

  it('ignora o css_compiled ANTERIOR da página pública, que também traz @layer utilities', () => {
    // Sem a marca `data-c2f-css-role`, esta folha seria aceita como saída do browser e a captura
    // congelaria no valor já gravado, edição após edição.
    folha({ 'data-c2f-css-role': 'compiled' }, saidaTailwind([regra('.text-3xl', 'font-size: 1.875rem;')]));
    expect(api.findOutputStyle(document)).toBe(null);
  });

  it('ignora as folhas pré-compiladas do runtime público (data-tailwind-role)', () => {
    folha({ 'data-tailwind-role': 'layout-precompiled' }, saidaTailwind([regra('.flex', 'display: flex;')]));
    expect(api.findOutputStyle(document)).toBe(null);
  });

  // ===== extract() ==============================================================================

  it('não entrega resultado enquanto as utilities não chegam (documento COM classes)', () => {
    folha({}, saidaTailwind([]));
    document.body.innerHTML = '<div class="text-3xl">Oi</div>';

    const resultado = api.extract(document);
    expect(resultado.ready).toBe(false);
    expect(resultado.motivo).toBe('sem-utilities');
    expect(resultado.css).toBe('');
  });

  it('entrega de imediato quando o documento não tem classe alguma a compilar', () => {
    folha({}, saidaTailwind([]));
    document.body.innerHTML = '<div>Sem classes</div>';

    expect(api.extract(document).ready).toBe(true);
  });

  it('informa "sem-folha" quando o runtime do Tailwind ainda não injetou nada', () => {
    document.body.innerHTML = '<div class="text-3xl">Oi</div>';
    const resultado = api.extract(document);
    expect(resultado.ready).toBe(false);
    expect(resultado.motivo).toBe('sem-folha');
  });

  it('grava as utilities quando a compilação termina', () => {
    folha({}, saidaTailwind([regra('.text-3xl', 'font-size: 1.875rem;'), regra('.space-y-5', 'margin-top: 1.25rem;')]));
    document.body.innerHTML = '<div class="text-3xl space-y-5">Oi</div>';

    const resultado = api.extract(document);
    expect(resultado.ready).toBe(true);
    expect(resultado.css).toContain('.text-3xl');
    expect(resultado.css).toContain('.space-y-5');
    expect(resultado.css).toContain('@layer utilities');
  });

  // ===== Delta contra a cascata real (política aprovada em 2026-08-17) ==========================

  it('com layout pré-compilado no DOM, grava só o DELTA de utilities', () => {
    // A página `sobre` do photon: 62% do output do Tailwind (theme + Preflight) é duplicata literal
    // do que o layout já entrega, e das 24 utilities só 19 são novas.
    folha({ 'data-tailwind-role': 'layout-precompiled' }, [
      new CSSLayerBlockRule('theme', [regraTheme(':root', { '--color-x': 'red' })]),
      new CSSLayerBlockRule('base', [regra('*', 'box-sizing: border-box;')]),
      new CSSLayerBlockRule('utilities', [regra('.flex', 'display: flex;')])
    ]);
    folha({}, saidaTailwind([regra('.flex', 'display: flex;'), regra('.text-3xl', 'font-size: 1.875rem;')]));
    document.body.innerHTML = '<div class="flex text-3xl">Oi</div>';

    const css = api.extract(document).css;
    expect(css).toContain('.text-3xl');   // utility nova
    expect(css).not.toContain('.flex');   // já vem do layout
    expect(css).not.toContain('box-sizing'); // Preflight não é regravado
    expect(css).not.toContain('--color-x'); // tokens do tema não são regravados
  });

  // ===== @layer theme: delta por DECLARAÇÃO ======================================================
  //
  // Regressão medida em 2026-08-17, reportada na homologação: "durante a edição fica perfeito, ao
  // salvar fica diferente". A versão anterior descartava `@layer theme` inteiro quando o layout já
  // tinha um — mas o Tailwind v4 só emite a variável que ele VÊ usada, e o theme do layout só tem os
  // tokens que o LAYOUT usa. Utility nova da página referenciava `var(--text-3xl)` inexistente, a
  // declaração era invalidada e a propriedade caía para o inicial: 13 de 21 elementos mudavam
  // (`font-size` 48px → 16px, `color` → preto). É o finding F1 do review batendo na própria captura.

  it('grava o token de tema que a utility NOVA precisa e o layout não tem', () => {
    folha({ 'data-tailwind-role': 'layout-precompiled' }, [
      new CSSLayerBlockRule('theme', [regraTheme(':root, :host', { '--color-photon-accent': 'rgb(29,158,117)' })]),
      new CSSLayerBlockRule('utilities', [regra('.flex', 'display: flex;')])
    ]);
    folha({}, [
      new CSSLayerBlockRule('theme', [regraTheme(':root, :host', {
        '--color-photon-accent': 'rgb(29,158,117)', // já vem do layout
        '--text-3xl': '1.875rem',                   // só o browser conhece: `.text-3xl` é nova
        '--color-slate-300': 'oklch(.869 .022 252.894)'
      })]),
      new CSSLayerBlockRule('utilities', [regra('.text-3xl', 'font-size: var(--text-3xl);')])
    ]);
    document.body.innerHTML = '<div class="text-3xl text-slate-300">Oi</div>';

    const css = api.extract(document).css;
    expect(css).toContain('--text-3xl');            // sem isto, a utility nova não pinta
    expect(css).toContain('--color-slate-300');
    expect(css).not.toContain('--color-photon-accent'); // o build já entrega: não regravar
  });

  it('omite a regra de tema inteira quando nenhum token é novo', () => {
    folha({ 'data-tailwind-role': 'layout-precompiled' }, [
      new CSSLayerBlockRule('theme', [regraTheme(':root, :host', { '--color-x': 'red', '--color-y': 'blue' })])
    ]);
    folha({}, [
      new CSSLayerBlockRule('theme', [regraTheme(':root, :host', { '--color-x': 'red', '--color-y': 'blue' })]),
      new CSSLayerBlockRule('utilities', [regra('.text-3xl', 'font-size: 1.875rem;')])
    ]);
    document.body.innerHTML = '<div class="text-3xl">Oi</div>';

    const css = api.extract(document).css;
    expect(css).not.toContain('--color-x');
    expect(css).not.toContain('--color-y');
    expect(css).toContain('.text-3xl');
  });

  it('sem theme no baseline, grava todos os tokens', () => {
    folha({}, [
      new CSSLayerBlockRule('theme', [regraTheme(':root, :host', { '--color-x': 'red' })]),
      new CSSLayerBlockRule('utilities', [regra('.text-3xl', 'font-size: 1.875rem;')])
    ]);
    document.body.innerHTML = '<div class="text-3xl">Oi</div>';

    expect(api.extract(document).css).toContain('--color-x');
  });

  it('filterThemeRule devolve só as declarações ausentes', () => {
    const rule = regraTheme(':root', { '--a': '1', '--b': '2', '--c': '3' });
    const saida = api.filterThemeRule(rule, { ':root': { '--a': true, '--c': true } });

    expect(saida).toContain('--b: 2;');
    expect(saida).not.toContain('--a:');
    expect(saida).not.toContain('--c:');
  });

  it('descarta @layer base do baseline mesmo quando o Preflight NÃO é byte-idêntico', () => {
    // Regressão medida com Chromium real (2026-08-17): o Preflight do @tailwindcss/browser 4.3.0
    // traz `::file-selector-button` no seletor e o do bundle offline do layout não. Com filtro por
    // assinatura, NENHUMA regra casava e o Preflight inteiro era regravado — e como o css_compiled
    // entra DEPOIS do pré-compilado na cascata, a versão do editor venceria a do build em produção.
    // Por isso `theme` e `base` são decididas por CAMADA, não regra a regra.
    folha({ 'data-tailwind-role': 'layout-precompiled' }, [
      new CSSLayerBlockRule('theme', [regraTheme(':root, :host', { '--color-x': 'red' })]),
      new CSSLayerBlockRule('base', [regra('*, ::after, ::before, ::backdrop', 'box-sizing: border-box;')])
    ]);
    folha({}, [
      new CSSLayerBlockRule('theme', [regraTheme(':root, :host', { '--color-x': 'red' })]),
      // Seletor com um pseudo-elemento a mais: a assinatura NÃO casa com a do baseline.
      new CSSLayerBlockRule('base', [regra('*, ::after, ::before, ::backdrop, ::file-selector-button', 'box-sizing: border-box;')]),
      new CSSLayerBlockRule('utilities', [regra('.text-3xl', 'font-size: 1.875rem;')])
    ]);
    document.body.innerHTML = '<div class="text-3xl">Oi</div>';

    const css = api.extract(document).css;
    expect(css).not.toContain('box-sizing');
    expect(css).not.toContain('--color-x');
    expect(css).toContain('.text-3xl');
  });

  it('camada de fundação ausente do baseline continua sendo gravada', () => {
    // Layout que entrega utilities mas não `base` (bundle parcial): o Preflight ainda precisa vir
    // da captura, senão a página fica sem reset.
    folha({ 'data-tailwind-role': 'layout-precompiled' }, [
      new CSSLayerBlockRule('utilities', [regra('.flex', 'display: flex;')])
    ]);
    folha({}, saidaTailwind([regra('.text-3xl', 'font-size: 1.875rem;')]));
    document.body.innerHTML = '<div class="text-3xl">Oi</div>';

    const css = api.extract(document).css;
    expect(css).toContain('box-sizing');
    expect(css).toContain('.text-3xl');
  });

  it('collectBaselineLayers ignora camada declarada mas vazia', () => {
    folha({ 'data-tailwind-role': 'layout-precompiled' }, [new CSSLayerBlockRule('base', [])]);
    expect(api.collectBaselineLayers(document).base).toBeUndefined();
  });

  it('sem cascata pré-compilada nenhuma, grava o output completo (página autossuficiente)', () => {
    folha({}, saidaTailwind([regra('.text-3xl', 'font-size: 1.875rem;')]));
    document.body.innerHTML = '<div class="text-3xl">Oi</div>';

    const css = api.extract(document).css;
    expect(css).toContain('box-sizing');
    expect(css).toContain('--color-x');
    expect(css).toContain('.text-3xl');
  });

  it('aceita a folha baseline do editor clássico como cascata', () => {
    folha({ 'data-c2f-tailwind-role': 'baseline' }, [
      new CSSLayerBlockRule('base', [regra('*', 'box-sizing: border-box;')])
    ]);
    folha({}, saidaTailwind([regra('.text-3xl', 'font-size: 1.875rem;')]));
    document.body.innerHTML = '<div class="text-3xl">Oi</div>';

    const css = api.extract(document).css;
    expect(css).not.toContain('box-sizing');
    expect(css).toContain('.text-3xl');
  });

  it('preserva a estrutura de @media ao filtrar', () => {
    folha({}, [new CSSLayerBlockRule('utilities', [
      media('(min-width: 64rem)', [regra('.lg\\:flex', 'display: flex;')])
    ])]);
    document.body.innerHTML = '<div class="lg:flex">Oi</div>';

    const css = api.extract(document).css;
    expect(css).toContain('@media (min-width: 64rem)');
    expect(css).toContain('.lg\\:flex');
  });

  it('folha de origem cruzada não derruba a captura', () => {
    const style = document.createElement('style');
    Object.defineProperty(style, 'sheet', {
      get() { throw new Error('SecurityError'); },
      configurable: true
    });
    document.head.appendChild(style);

    expect(() => api.findOutputStyle(document)).not.toThrow();
    expect(api.findOutputStyle(document)).toBe(null);
  });
});
