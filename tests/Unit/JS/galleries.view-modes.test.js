import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

/**
 * BATCH-140 (req-137) — tamanhos de miniatura da lista curada do módulo `galleries`.
 *
 * A lista tinha miniatura fixa em 200x140: uma galeria com dezenas de fotos ficava impraticável de
 * reordenar. O seletor Grande/Médio/Pequeno troca a classe do CONTÊINER e persiste a preferência.
 *
 * Como a preferência vem do localStorage, o valor lido pode ser lixo (versão anterior, outra aba,
 * edição manual): a normalização é a regra que impede uma classe inválida de chegar ao DOM.
 */
function carregarHelpers() {
  const code = readFileSync(resolve(process.cwd(), 'gestor/modulos/galleries/galleries.js'), 'utf8');
  const modulo = { exports: {} };
  // `$` falso que NÃO executa o callback do ready: só os helpers puros do topo são avaliados.
  const jqueryFalso = () => ({ ready: () => {}, length: 0 });
  // eslint-disable-next-line no-new-func
  new Function('module', 'exports', '$', 'window', code)(modulo, modulo.exports, jqueryFalso, globalThis);
  return modulo.exports;
}

const { GALLERIES_VIEWS, galleriesNormalizarView } = carregarHelpers();

describe('galleries.js — modos de tamanho das miniaturas (req-137)', () => {
  it('expõe exatamente os três modos previstos, na ordem do seletor', () => {
    expect(GALLERIES_VIEWS).toEqual(['large', 'medium', 'small']);
  });

  it('mantém intacto qualquer modo válido', () => {
    GALLERIES_VIEWS.forEach((view) => {
      expect(galleriesNormalizarView(view)).toBe(view);
    });
  });

  it('cai em `large` diante de valor desconhecido, ausente ou de outro tipo', () => {
    ['', 'grande', 'LARGE', 'tiny', null, undefined, 0, {}].forEach((entrada) => {
      expect(galleriesNormalizarView(entrada)).toBe('large');
    });
  });

  it('gera sempre uma classe de modo utilizável no contêiner', () => {
    ['small', 'lixo-do-localstorage'].forEach((entrada) => {
      const classe = 'view-' + galleriesNormalizarView(entrada);
      expect(GALLERIES_VIEWS.map((v) => 'view-' + v)).toContain(classe);
    });
  });
});
