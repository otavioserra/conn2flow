import { describe, expect, it } from 'vitest';
import fs from 'node:fs';

const sourcePath = 'gestor/assets/interface/html-editor-interface.js';

function extrairFuncao(nome, dependencias = '') {
  const code = fs.readFileSync(sourcePath, 'utf8');
  const start = code.indexOf('function ' + nome + '(');
  if (start < 0) throw new Error(nome + ' não encontrada no arquivo');
  let i = code.indexOf('{', start), depth = 0, end = -1;
  for (; i < code.length; i++) {
    if (code[i] === '{') depth++;
    else if (code[i] === '}') {
      depth--;
      if (depth === 0) {
        end = i + 1;
        break;
      }
    }
  }
  return new Function(dependencias + '\n' + code.slice(start, end) + '\nreturn ' + nome + ';')();
}

describe('html-editor-interface.js — preview de templates Tailwind (req-154)', () => {
  it('não mistura a folha sem camada do Fomantic no preview Tailwind', () => {
    const includes = extrairFuncao('htmlEditorPreviewFrameworkIncludes');

    expect(includes('tailwindcss')).not.toContain('semantic.min.css');
    expect(includes('tailwindcss')).toContain('jquery.min.js');
    expect(includes('tailwindcss')).toContain('semantic.min.js');
    expect(includes('fomantic-ui')).toContain('semantic.min.css');
    expect(includes('fomantic-ui')).toContain('semantic.min.js');
  });

  it('usa o isolamento de framework nos previews de página e de layout', () => {
    const code = fs.readFileSync(sourcePath, 'utf8');
    const start = code.indexOf('function previewHtmlConteudo(');
    const end = code.indexOf('function previewHtml()', start);
    const preview = code.slice(start, end);

    expect(preview.match(/htmlEditorPreviewFrameworkIncludes\(framework\)/g)).toHaveLength(2);
    expect(preview).not.toContain('semantic.min.css');
  });

  it('preserva o baseline da página ao acrescentar o sidecar de uma seção', () => {
    const atualizar = extrairFuncao(
      'htmlEditorCssPrecompiledAtualizar',
      "const gestor = { html_editor: { cssPrecompiledBase64: '' } }; " +
      "const window = { htmlEditorCssPrecompiled: 'PAGINA' }; " +
      "const htmlEditorDecodeBase64 = () => '';"
    );

    expect(atualizar('SECAO', true)).toBe('PAGINA\nSECAO');
    expect(atualizar('SECAO', false)).toBe('SECAO');
  });

  it('parte do baseline inicial e não duplica o mesmo sidecar consecutivo', () => {
    const atualizar = extrairFuncao(
      'htmlEditorCssPrecompiledAtualizar',
      "const gestor = { html_editor: { cssPrecompiledBase64: 'BASE' } }; " +
      "const window = {}; const htmlEditorDecodeBase64 = () => 'INICIAL';"
    );

    expect(atualizar('SECAO', true)).toBe('INICIAL\nSECAO');

    const atualizarComAtual = extrairFuncao(
      'htmlEditorCssPrecompiledAtualizar',
      "const gestor = { html_editor: { cssPrecompiledBase64: '' } }; " +
      "const window = { htmlEditorCssPrecompiled: 'PAGINA\\nSECAO' }; " +
      "const htmlEditorDecodeBase64 = () => '';"
    );
    expect(atualizarComAtual('SECAO', true)).toBe('PAGINA\nSECAO');
  });
});
