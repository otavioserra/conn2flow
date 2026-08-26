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

const fonteGalleries = readFileSync(resolve(process.cwd(), 'gestor/modulos/galleries/galleries.js'), 'utf8');
// O CSS é injetado como concatenação de strings; junta tudo para conferir as regras declaradas.
const cssInjetado = fonteGalleries.replace(/'\s*\+\s*'/g, '');

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

  it('os modos compactos viram GRADE, e não lista de uma foto por linha', () => {
    // O ponto do lote: encolher só a miniatura não resolvia nada — a CAIXA continuava ocupando a
    // linha inteira. Sem `flex-wrap`, as fotos não se acomodam lado a lado.
    expect(cssInjetado).toContain('#gallery-items.view-medium,#gallery-items.view-small{flex-direction:row;flex-wrap:wrap;');
    // A caixa vira card vertical e ganha largura fracionária: é isso que produz as colunas.
    expect(cssInjetado).toContain('#gallery-items.view-medium .gallery-item{width:calc(25% - 6px)');
    expect(cssInjetado).toContain('#gallery-items.view-small .gallery-item{width:calc(12.5% - 6px)');
    expect(cssInjetado).toMatch(/view-medium \.gallery-item,#gallery-items\.view-small \.gallery-item\{flex-direction:column/);
  });

  it('o modo grande continua sendo a lista de curadoria detalhada', () => {
    // A grade é exclusiva dos modos compactos: o `large` não pode ganhar `flex-wrap`.
    expect(cssInjetado).toContain('#gallery-items{margin-top:12px;display:flex;flex-direction:column;');
    expect(cssInjetado).not.toMatch(/#gallery-items\.view-large[^}]*flex-wrap/);
  });

  it('os controles do card ficam fora do fluxo, revelados no hover', () => {
    // `position: absolute` é o que garante que a grade não estremeça quando o cursor passa.
    expect(cssInjetado).toMatch(/\.gallery-item-remove\{position:absolute;[^}]*opacity:0;/);
    expect(cssInjetado).toContain('.gallery-item:hover .gallery-item-handle');
    // Durante o arraste o cursor sai do card de origem; a classe do Sortable segura os controles.
    expect(cssInjetado).toContain('.gallery-item.sortable-chosen .gallery-item-handle');
  });

  it('o modo pequeno esconde legenda e painel de link, que não cabem na caixa mínima', () => {
    // Os valores seguem no array `items` (a serialização lê de lá, não do DOM): nada se perde.
    expect(cssInjetado).toContain('#gallery-items.view-small .gallery-item-caption,');
    expect(cssInjetado).toMatch(/view-small \.gallery-item-link-fields\{display:none;\}/);
  });

  it('gera sempre uma classe de modo utilizável no contêiner', () => {
    ['small', 'lixo-do-localstorage'].forEach((entrada) => {
      const classe = 'view-' + galleriesNormalizarView(entrada);
      expect(GALLERIES_VIEWS.map((v) => 'view-' + v)).toContain(classe);
    });
  });
});
