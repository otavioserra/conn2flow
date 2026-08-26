import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

/**
 * BATCH-140 (req-137) — despacho em lote do gerenciador de arquivos no modo picker (iframe).
 *
 * A barra de seleção só oferecia "Excluir selecionados": quem montava uma galeria precisava clicar
 * arquivo por arquivo. O botão "Selecionar selecionados" despacha o lote inteiro de uma vez.
 *
 * As regras testadas aqui são os helpers puros do topo do arquivo; o restante do módulo vive dentro
 * de `$(document).ready()`, que o `$` falso abaixo deliberadamente NÃO executa.
 */
function carregarHelpers() {
  const code = readFileSync(
    resolve(process.cwd(), 'gestor/modulos/admin-arquivos/admin-arquivos.js'), 'utf8');
  const modulo = { exports: {} };
  const jqueryFalso = () => ({ ready: () => {}, length: 0 });
  // eslint-disable-next-line no-new-func
  new Function('module', 'exports', '$', 'window', code)(modulo, modulo.exports, jqueryFalso, globalThis);
  return modulo.exports;
}

const {
  adminArquivosArquivosSelecionados,
  adminArquivosPickVisivel,
  adminArquivosPayloadPicker
} = carregarHelpers();

const ARQUIVO = {
  caminho: 'fotos/praia.jpg',
  tipo: 'arquivo',
  nome: 'praia.jpg',
  mime: 'image/jpeg',
  imgSrc: '/contents/thumbs/praia.jpg',
  data: '2026-08-26'
};

const PASTA = { caminho: 'fotos', tipo: 'pasta', nome: 'fotos', mime: '', imgSrc: '', data: '2026-08-20' };

describe('admin-arquivos.js — seleção em lote no picker (req-137)', () => {
  it('despacha somente arquivos, ignorando as pastas marcadas junto', () => {
    const selecionados = {
      'fotos': PASTA,
      'fotos/praia.jpg': ARQUIVO,
      'fotos/serra.png': { ...ARQUIVO, caminho: 'fotos/serra.png', nome: 'serra.png', mime: 'image/png' }
    };

    const arquivos = adminArquivosArquivosSelecionados(selecionados);

    expect(arquivos).toHaveLength(2);
    expect(arquivos.map((a) => a.caminho)).toEqual(['fotos/praia.jpg', 'fotos/serra.png']);
  });

  it('devolve lista vazia quando o usuário marcou apenas pastas', () => {
    expect(adminArquivosArquivosSelecionados({ 'fotos': PASTA })).toEqual([]);
  });

  it('não quebra com mapa vazio nem com registro nulo remanescente', () => {
    expect(adminArquivosArquivosSelecionados({})).toEqual([]);
    expect(adminArquivosArquivosSelecionados({ 'x': null })).toEqual([]);
  });

  it('ignora propriedades herdadas de Object.prototype', () => {
    // Nome de arquivo é livre: `constructor` e `toString` existem em qualquer objeto e, lidos por
    // truthiness, virariam itens fantasmas na lista despachada.
    expect(adminArquivosArquivosSelecionados({})).toEqual([]);
    expect(adminArquivosArquivosSelecionados({}).length).toBe(0);
    const comHomonimo = { 'constructor': ARQUIVO };
    expect(adminArquivosArquivosSelecionados(comHomonimo)).toEqual([ARQUIVO]);
  });

  it('só exibe o botão dentro do iframe e com ao menos um arquivo marcado', () => {
    expect(adminArquivosPickVisivel(true, [ARQUIVO])).toBe(true);
    // Fora do iframe não há pai para receber a mensagem: o botão continua oculto.
    expect(adminArquivosPickVisivel(false, [ARQUIVO])).toBe(false);
    // Só pastas marcadas: nada a despachar.
    expect(adminArquivosPickVisivel(true, [])).toBe(false);
    expect(adminArquivosPickVisivel(undefined, [ARQUIVO])).toBe(false);
  });

  it('monta o mesmo payload do envio individual, com o MIME no campo `tipo`', () => {
    // Os consumidores (galleries, html-editor, interface-v2) testam `dados.tipo` com /image\//:
    // mandar 'arquivo' ali faria toda imagem ser recusada como "não é uma imagem".
    expect(adminArquivosPayloadPicker(ARQUIVO)).toEqual({
      id: 'fotos/praia.jpg',
      caminho: 'fotos/praia.jpg',
      imgSrc: '/contents/thumbs/praia.jpg',
      nome: 'praia.jpg',
      data: '2026-08-26',
      tipo: 'image/jpeg'
    });
  });

  it('normaliza campos ausentes para string vazia em vez de undefined', () => {
    // `JSON.stringify` descarta chaves com `undefined`; o consumidor receberia um objeto incompleto.
    const payload = adminArquivosPayloadPicker({ caminho: 'doc.pdf' });

    expect(payload).toEqual({
      id: 'doc.pdf', caminho: 'doc.pdf', imgSrc: '', nome: '', data: '', tipo: ''
    });
    expect(JSON.parse(JSON.stringify(payload))).toEqual(payload);
  });

  it('tolera item nulo sem lançar', () => {
    expect(adminArquivosPayloadPicker(null).caminho).toBe('');
  });
});
