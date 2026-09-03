import { describe, it, expect, beforeEach } from 'vitest';
import fs from 'node:fs';

/**
 * req-093 (BATCH-093): reversão das caixas de variável global no Editor HTML Clássico.
 *
 * `htmlEditorReconstructVars` é auto-contida (usa só document/atob/escape) — extraímos a função REAL
 * do arquivo e validamos o round-trip: a caixa renderizada (`.c2f-var-box` + data-c2f-marker) volta
 * ao marcador original que o backend guardou, garantindo que o save grave a variável, não o valor.
 */
function loadReconstruct() {
  const code = fs.readFileSync('gestor/assets/interface/html-editor-interface.js', 'utf8');
  const start = code.indexOf('function htmlEditorReconstructVars(');
  if (start < 0) throw new Error('htmlEditorReconstructVars não encontrada no arquivo');
  let i = code.indexOf('{', start), depth = 0, end = -1;
  for (; i < code.length; i++) {
    if (code[i] === '{') depth++;
    else if (code[i] === '}') { depth--; if (depth === 0) { end = i + 1; break; } }
  }
  const fnSrc = code.slice(start, end);
  return new Function(fnSrc + '\nreturn htmlEditorReconstructVars;')();
}

describe('html-editor-interface.js — reversão de variáveis (req-093)', () => {
  let reconstruct;

  beforeEach(() => {
    if (!window.btoa) window.btoa = (s) => Buffer.from(s, 'binary').toString('base64');
    if (!window.atob) window.atob = (s) => Buffer.from(s, 'base64').toString('binary');
    reconstruct = loadReconstruct();
  });

  // Igual ao b64encode do backend (base64 de UTF-8) para simular o data-c2f-marker.
  function b64(s) { return window.btoa(unescape(encodeURIComponent(s))); }

  it('reverte a caixa de variável ao marcador original [[var]] (grava a variável, não o valor)', () => {
    const html = '<p>Base: <span class="c2f-dyn-box c2f-var-box" data-c2f-marker="' +
      b64('[[pagina#url-raiz]]') + '" contenteditable="false">https://site.test/</span> fim</p>';
    const out = reconstruct(html);
    expect(out).toContain('[[pagina#url-raiz]]');
    expect(out).not.toContain('c2f-var-box');
    expect(out).not.toContain('https://site.test/'); // o valor renderizado NÃO vai para o banco
  });

  it('é no-op quando não há caixas (fluxo antigo intacto)', () => {
    const html = '<p>Sem variáveis <b>aqui</b></p>';
    expect(reconstruct(html)).toBe(html);
  });

  it('preserva o marcador EXATO que entrou, inclusive com cerco @[[var]]@', () => {
    const html = '<span data-c2f-marker="' + b64('@[[gestor#versao]]@') + '">9</span>';
    expect(reconstruct(html)).toBe('@[[gestor#versao]]@');
  });

  it('reverte múltiplas caixas na ordem correta', () => {
    const html = '<span data-c2f-marker="' + b64('[[a]]') + '">1</span>-' +
      '<span data-c2f-marker="' + b64('[[b]]') + '">2</span>';
    expect(reconstruct(html)).toBe('[[a]]-[[b]]');
  });

  it('restaura atributo com variável somente quando o valor resolvido não foi editado', () => {
    const html = '<img src="/images/logo.png" data-c2f-orig-src="[[pagina#url-raiz]]images/logo.png" ' +
      'data-c2f-resolved-src="/images/logo.png">';
    const out = reconstruct(html);
    expect(out).toContain('src="[[pagina#url-raiz]]images/logo.png"');
    expect(out).not.toContain('data-c2f-orig-src');
    expect(out).not.toContain('data-c2f-resolved-src');
  });

  it('preserva edição manual de atributo e remove os metadados de restauração', () => {
    const html = '<img src="/images/logo-novo.png" data-c2f-orig-src="[[pagina#url-raiz]]images/logo.png" ' +
      'data-c2f-resolved-src="/images/logo.png">';
    const out = reconstruct(html);
    expect(out).toContain('src="/images/logo-novo.png"');
    expect(out).not.toContain('data-c2f-orig-src');
    expect(out).not.toContain('[[pagina#url-raiz]]images/logo.png');
  });

  it('renderiza variáveis antes de abrir o editor visual de layouts', () => {
    const code = fs.readFileSync('gestor/assets/interface/html-editor-interface.js', 'utf8');
    const start = code.indexOf("if (alvo === 'layouts')");
    const end = code.indexOf('} else {', start);
    const layouts = code.slice(start, end);

    expect(layouts).toContain('htmlEditorRenderVars(htmlDoUsuario');
    expect(layouts).toContain('abrirEditorVisualSrcdoc((data && data.boxes) || htmlDoUsuario');
  });
});

/**
 * BATCH-103 — busca da aba "Modelos" do Editor HTML.
 *
 * Duas correções cobertas aqui, ambas com a função REAL extraída do arquivo:
 *  - `htmlEditorNormalizarBusca`: digitar 'pa' precisa encontrar 'Páginas' (a comparação crua falhava
 *    porque o segundo caractere do texto é acentuado);
 *  - `modelosBuscaSincronizarIcones`: alterna a lupa com o "x" de limpar, que não existia neste campo.
 */
function extrairFuncao(nome, extra) {
  const code = fs.readFileSync('gestor/assets/interface/html-editor-interface.js', 'utf8');
  const start = code.indexOf('function ' + nome + '(');
  if (start < 0) throw new Error(nome + ' não encontrada no arquivo');
  let i = code.indexOf('{', start), depth = 0, end = -1;
  for (; i < code.length; i++) {
    if (code[i] === '{') depth++;
    else if (code[i] === '}') { depth--; if (depth === 0) { end = i + 1; break; } }
  }
  const fnSrc = code.slice(start, end);
  return new Function('$', (extra || '') + '\n' + fnSrc + '\nreturn ' + nome + ';');
}

describe('html-editor-interface.js — busca de modelos (BATCH-103)', () => {
  it('normaliza acentuação e caixa na busca (pa encontra Páginas)', () => {
    // A constante do range de acentos é declarada fora da função; recriamos igual ao arquivo.
    const preludio = "var RE_ACENTOS_BUSCA = new RegExp('[' + String.fromCharCode(0x300) + '-' + " +
      "String.fromCharCode(0x36f) + ']', 'g');";
    const normalizar = extrairFuncao('htmlEditorNormalizarBusca', preludio)(null);

    expect(normalizar('Páginas')).toBe('paginas');
    expect(normalizar('Páginas').includes(normalizar('pa'))).toBe(true);
    expect(normalizar('Usuários').includes(normalizar('usuarios'))).toBe(true);
    expect(normalizar('  ARQUIVOS  ')).toBe('arquivos');
    expect(normalizar(null)).toBe('');
  });

  it('alterna a lupa com o x de limpar conforme o campo tem texto', () => {
    const chamadas = [];
    // Stub mínimo de jQuery: registra o seletor e o estado passado ao toggleClass.
    const $ = (seletor) => ({
      toggleClass: (classe, estado) => { chamadas.push({ seletor, classe, estado }); }
    });
    const sincronizar = extrairFuncao('modelosBuscaSincronizarIcones')($);

    sincronizar('pag');
    expect(chamadas).toEqual([
      { seletor: '.modelos-search-clear', classe: 'hidden', estado: false }, // x visível
      { seletor: '.modelos-search-icon', classe: 'hidden', estado: true }    // lupa oculta
    ]);

    chamadas.length = 0;
    sincronizar('   ');
    expect(chamadas).toEqual([
      { seletor: '.modelos-search-clear', classe: 'hidden', estado: true },  // x oculto
      { seletor: '.modelos-search-icon', classe: 'hidden', estado: false }   // lupa visível
    ]);
  });
});
