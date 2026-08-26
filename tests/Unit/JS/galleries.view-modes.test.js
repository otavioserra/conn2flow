import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

function carregarHelpers() {
  const code = readFileSync(resolve(process.cwd(), 'gestor/modulos/galleries/galleries.js'), 'utf8');
  const modulo = { exports: {} };
  const jqueryFalso = () => ({ ready: () => {}, length: 0 });
  // eslint-disable-next-line no-new-func
  new Function('module', 'exports', '$', 'window', code)(modulo, modulo.exports, jqueryFalso, globalThis);
  return modulo.exports;
}

const {
  GALLERIES_VIEWS,
  GALLERIES_IMAGE_POSITIONS,
  galleriesNormalizarView,
  galleriesNormalizarImagePosition,
} = carregarHelpers();

const fonteGalleries = readFileSync(resolve(process.cwd(), 'gestor/modulos/galleries/galleries.js'), 'utf8');
const cssInjetado = fonteGalleries.replace(/'\s*\+\s*'/g, '');

describe('galleries.js — visualização compacta e alinhamento (req-137/139)', () => {
  it('expõe e normaliza exatamente os três modos de visualização', () => {
    expect(GALLERIES_VIEWS).toEqual(['large', 'medium', 'small']);
    GALLERIES_VIEWS.forEach((view) => expect(galleriesNormalizarView(view)).toBe(view));
    ['', 'grande', 'LARGE', 'tiny', null, undefined, 0, {}].forEach((entrada) => {
      expect(galleriesNormalizarView(entrada)).toBe('large');
    });
  });

  it('mantém o modo grande como lista e transforma os compactos em grade 6/10', () => {
    expect(cssInjetado).toContain('#gallery-items{margin-top:12px;display:flex;flex-direction:column;');
    expect(cssInjetado).not.toMatch(/#gallery-items\.view-large[^}]*flex-wrap/);
    expect(cssInjetado).toContain('#gallery-items.view-medium,#gallery-items.view-small{flex-direction:row;flex-wrap:wrap;');
    expect(cssInjetado).toContain('#gallery-items.view-medium .gallery-item{width:calc(16.666% - 8px)');
    expect(cssInjetado).toContain('#gallery-items.view-small .gallery-item{width:calc(10% - 6px)');
    expect(cssInjetado).toContain('.view-medium .gallery-item-thumb{width:100%;height:110px;}');
    expect(cssInjetado).toContain('.view-small .gallery-item-thumb{width:100%;height:65px;}');
  });

  it('mantém o overlay fora do fluxo e com o tratamento visual do admin-arquivos', () => {
    expect(cssInjetado).toMatch(/\.gallery-item-actions\{position:absolute;[^}]*opacity:0;[^}]*pointer-events:none;/);
    expect(cssInjetado).toContain('.gallery-item:hover .gallery-item-actions');
    expect(cssInjetado).toContain('.gallery-item:focus-within .gallery-item-actions{opacity:1;pointer-events:auto;}');
    expect(cssInjetado).toContain('.gallery-item-actions .button{pointer-events:auto;');
    expect(cssInjetado).toContain('background:rgba(0,0,0,0.55);border-radius:4px;');
    expect(cssInjetado).toContain('.gallery-item.sortable-chosen .gallery-item-actions');
  });

  it('esconde os campos inline nos compactos e oferece a engrenagem/modal', () => {
    expect(cssInjetado).toContain('#gallery-items.view-medium .gallery-item-caption,#gallery-items.view-medium .gallery-item-link-wrap,');
    expect(cssInjetado).toContain('#gallery-items.view-small .gallery-item-caption,#gallery-items.view-small .gallery-item-link-wrap{display:none;}');
    expect(cssInjetado).toContain('.gallery-item-settings{display:inline-flex!important;}');
    expect(fonteGalleries).toContain('function openItemSettings(itemId)');
    expect(fonteGalleries).toContain("$(document).on('click', '.gallery-item-settings'");
  });

  it('realça o card sem depender de elemento no fluxo', () => {
    expect(cssInjetado).toContain('border-color:#2185d0;box-shadow:0 4px 14px rgba(33,133,208,0.22);');
    expect(cssInjetado).toContain('.gallery-item-actions{position:absolute;');
  });

  it('normaliza o alinhamento vertical para a allowlist do schema', () => {
    expect(GALLERIES_IMAGE_POSITIONS).toEqual(['top', 'center', 'bottom']);
    GALLERIES_IMAGE_POSITIONS.forEach((position) => {
      expect(galleriesNormalizarImagePosition(position)).toBe(position);
    });
    expect(galleriesNormalizarImagePosition(' TOP ')).toBe('top');
    ['', 'middle', null, undefined, {}, '<script>'].forEach((position) => {
      expect(galleriesNormalizarImagePosition(position)).toBe('center');
    });
  });

  it('serializa, hidrata e reage ao dropdown de alinhamento', () => {
    expect(fonteGalleries).toContain("out.image_position = galleriesNormalizarImagePosition($('#gallery-image-position').val() || schema.image_position)");
    expect(fonteGalleries).toContain("$('#gallery-image-position').val(galleriesNormalizarImagePosition(schema.image_position))");
    expect(fonteGalleries).toContain("$(document).on('change', '#gallery-image-position'");
  });
});
