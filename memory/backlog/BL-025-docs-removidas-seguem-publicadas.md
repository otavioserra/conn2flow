# BL-025 — Página de docs removida continua publicada no banco

- **Tipo**: Bug / Docs
- **Status**: IN-DISCUSSION
- **Severidade sugerida**: BAIXA (conteúdo antigo acessível por URL direta; some do menu)
- **Origem**: republicação das docs no Lab depois do `ai:archive-sdd` (BATCH-191), 2026-09-27
- **Componentes**: `cli/src/Support/Docs/DocsBuilder.php`, contrato `deletar` (`project_tables_config.json` → `schema-metadata.json`), `executarDelecoes()`

## Contexto observado

1. O arquivamento moveu 17 arquivos do SDD para `archive/`, que não é publicado. O `docs:build` removeu as pastas dos recursos (305 páginas, antes 320).
2. O atualizador de banco ignora órfãos: as linhas de `paginas`/`publisher_pages` continuam lá. `/docs/sdd/human-requests/req-169/` segue respondendo 200 no Lab, com o conteúdo antigo.
3. É o mesmo comportamento geral do pipeline ("nunca remove"), já discutido na req-146 / BL-011, aplicado às páginas geradas.

## Proposta

1. O `docs:build` conhece as páginas que deixaram de existir (ele já calcula as pastas a remover). Gravar essas chaves (`language|modulo|id`) na lista `deletar` do `project_tables_config.json` do projeto, para `paginas` e `publisher_pages`, com `modulo = documentation`.
2. A lista pode ser podada depois de um deploy confirmado, ou mantida (o DELETE por chave natural é idempotente).
3. Alternativa: um `paginas_301` automático para a URL equivalente em `archive/`, se o archive passar a ser publicado.

## Critérios de aceite (rascunho)

- Depois de arquivar um item do SDD e rodar `docs:build` + `project:update-all`, a URL antiga dá 404 (ou 301).
- Páginas editadas no painel (`user_modified=1`) não são apagadas sem aviso.
