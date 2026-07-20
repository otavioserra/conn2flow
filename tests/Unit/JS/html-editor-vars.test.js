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
});
