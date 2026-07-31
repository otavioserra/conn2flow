# BATCH-090 — Gerenciador de Arquivos por Árvore Física e CRUD de Diretórios (req-090)

Status: **complete** (validação estática) — deploy + homologação runtime pendentes com o operador.
Intake: [req-090.md](../human-requests/req-090.md) · Decisão: [DEC-090](../decisions/DECISION-LOG.md#dec-090---2026-07-17---accepted)

## Objetivo

Migrar o módulo `admin-arquivos` e o Image Picker de uma abordagem baseada na tabela `arquivos` para uma **árvore física de diretórios** sob `$_GESTOR['contents-path']`, com CRUD completo de pastas, 4 modos de visualização, upload drag&drop, miniaturas dinâmicas em lote e retrocompatibilidade do picker.

## Escopo entregue

### 1. Segurança (biblioteca pura, testável) — `gestor/bibliotecas/arquivo.php` (1.0.0 → 1.1.0)
- `arquivo_nome_sanitizar`, `arquivo_extensao_perigosa`, `arquivo_caminho_relativo_seguro`, `arquivo_caminho_resolver`, `arquivo_mini_caminho_relativo`, `arquivo_tipo_por_extensao`.
- Atende as 5 diretrizes do Engenheiro Chefe (path traversal, uploads perigosos, paginação, cache-bust, sanitização de nomes).

### 2. Banco — migração `20260717120000_create_arquivos_disco_categorias_table.php`
- Nova tabela `arquivos_disco_categorias` (`caminho`, `caminho_hash` MD5, `id_categorias`, índice único). Legada `arquivos_categorias` preservada.

### 3. Backend — `gestor/modulos/admin-arquivos/admin-arquivos.php` (reescrito)
- Rotas AJAX: `listar` (varredura física paginada/ordenada/filtrada + breadcrumb + thumbsMissing), `uploadFile` (destino, sanitização, bloqueio de extensão, colisão, mini imediata, categorias por caminho), `miniaturas` (lote, SimpleImage), `excluir` (lote, pasta recursiva), `pasta-criar`, `renomear` (move associações), `categorias-arquivo`.
- Páginas: `listar-arquivos` e `upload` (bootstrap de JS vars + selects Fomantic de categorias/ordenação; jQuery File Upload).

### 4. Frontend — `gestor/modulos/admin-arquivos/admin-arquivos.js` (reescrito)
- Listagem: render client-side por JSON, **4 modos** (grande/médio/pequeno/listagem, `localStorage`), breadcrumb navegável, miniaturas lazy em lote com barra de status, seleção em lote + exclusão em massa, CRUD de pastas, filtros (data/categoria/ordenação).
- Upload: drag&drop, seletor de tamanho de pré-visualização (S/M/L/XL), navegação/criação da pasta de destino, bloqueio client-side de extensão perigosa.
- Helpers puros exportados para teste Node (`module.exports`).

### 5. Views/i18n
- `admin-arquivos.html` / `admin-arquivos-adicionar.html` (pt-br + en) reescritos; CSS dos 4 modos e do dropzone/preview; ~24 variáveis novas por idioma; `admin-arquivos.json` 1.0.4 → 1.1.0 (config `lista`/`upload`).

### 6. Retrocompatibilidade do Image Picker — `gestor/bibliotecas/interface.php`
- `imagepick`: identificador não-numérico é tratado como caminho físico; metadados via disco; miniatura física preferida com cache-bust. IDs numéricos legados seguem pela tabela `arquivos`. `interface.js` inalterado (já encaminhava id+caminho).

## Validação estática
- `php -l`: `arquivo.php`, `admin-arquivos.php`, `interface.php`, migração, teste → OK.
- `node --check admin-arquivos.js` → OK; JSON válido.
- Novo `tests/Unit/PHP/AdminArquivosSegurancaTest.php` → **16/16 (53 assertions)**.
- `composer test` → **106/106 (367 assertions, 4 skipped)**.

## Pendências runtime (operador)
- `🗃️ Projects - Update => Core` + migração `20260717120000`.
- Homologar: 4 modos de visualização, criar/renomear/excluir pastas, upload drag&drop com miniaturas dinâmicas e status, picker retrocompatível (registro numérico legado vs caminho físico novo).
