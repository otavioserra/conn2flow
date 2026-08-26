import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

/**
 * req-138 (BATCH-141) — o widget de imagem do `interface-v2` recusava toda imagem válida.
 *
 * A guarda era `dados.tipo?.match(/image\//) === 'image/'`. `String.prototype.match()` sem a flag
 * `g` devolve um ARRAY (`['image/', index: 0, input: ...]`) ou `null` — nunca uma string. A
 * comparação estrita com `'image/'` era, portanto, sempre falsa: o ramo de sucesso nunca executava
 * e o handler caía direto no `else`, alertando "não é uma imagem" para uma imagem legítima.
 *
 * O handler vive dentro de uma classe com campos privados (`#objPai`), então o que se testa aqui é
 * a REGRA de decisão isolada, mais uma guarda estática sobre o código-fonte real.
 */
const fonteInterfaceV2 = readFileSync(
  resolve(process.cwd(), 'gestor/assets/interface-v2/interface-v2.js'), 'utf8');

// Forma antiga e forma nova, lado a lado, para tornar a diferença verificável.
const guardaAntiga = (tipo) => tipo?.match(/image\//) === 'image/';
const guardaNova = (tipo) => Boolean(tipo && /^image\//.test(tipo));

describe('interface-v2.js — aceitação de imagem no picker (req-138)', () => {
  it('demonstra por que a guarda antiga recusava até imagem válida', () => {
    // `match()` acha o trecho, mas devolve um array — nunca a string comparada.
    expect('image/jpeg'.match(/image\//)).not.toBeNull();
    expect(Array.isArray('image/jpeg'.match(/image\//))).toBe(true);
    expect(guardaAntiga('image/jpeg')).toBe(false);
    expect(guardaAntiga('image/png')).toBe(false);
  });

  it('aceita os MIMEs reais que o backend passou a devolver (req-138)', () => {
    // Estes são os valores normalizados por `arquivo_mime_por_extensao()`.
    ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/x-icon',
      'image/avif', 'image/tiff', 'image/bmp'].forEach((mime) => {
      expect(guardaNova(mime)).toBe(true);
    });
  });

  it('continua recusando o que não é imagem', () => {
    ['application/pdf', 'application/json', 'video/mp4', 'audio/mpeg',
      'application/octet-stream', 'text/plain'].forEach((mime) => {
      expect(guardaNova(mime)).toBe(false);
    });
  });

  it('não é enganada por `image/` no meio da string nem por valor ausente', () => {
    // O `^` importa: sem ele, `match(/image\//)` casaria em qualquer posição.
    expect(guardaNova('application/x-image/fake')).toBe(false);
    expect(guardaNova('')).toBe(false);
    expect(guardaNova(undefined)).toBe(false);
    expect(guardaNova(null)).toBe(false);
  });

  it('o arquivo real usa a guarda corrigida e não reintroduziu a comparação quebrada', () => {
    expect(fonteInterfaceV2).toContain("/^image\\//.test(dados.tipo)");
    // Comparar o retorno de `.match()` com uma string é sempre falso: não pode voltar.
    expect(fonteInterfaceV2).not.toMatch(/\.match\([^)]*\)\s*===\s*['"]image\//);
  });

  it('todos os consumidores do canal usam a mesma forma de teste de prefixo', () => {
    // Divergência entre consumidores foi justamente o que deixou este bug passar despercebido.
    const consumidores = [
      'gestor/assets/interface-v2/interface-v2.js',
      'gestor/assets/interface/html-editor.js',
      'gestor/assets/interface/html-editor-interface.js',
      'gestor/modulos/galleries/galleries.js'
    ];

    consumidores.forEach((caminho) => {
      const fonte = readFileSync(resolve(process.cwd(), caminho), 'utf8');
      expect(fonte, caminho).not.toMatch(/\.match\([^)]*\)\s*===\s*['"]image\//);
    });
  });
});
