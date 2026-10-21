---
id: "BATCH-300"
title: "Docs na 3.0: seção MDD em /docs/mdd/ com 301, What's New da série 3.0 e rebuild (REQ-292)"
status: "implemented-pending-homologation"
date: "2026-10-21"
author: "executor"
target_repo: "conn2flow"
summary_short: "docs:build publica memory/ em /docs/mdd/ mantendo os ids; a sincronização grava paginas_301 quando o caminho muda; What's New 3.0.0–3.0.14; memórias de engenharia e índices derivados fora da publicação. Lab validado; produção depende de release do core."
---

# BATCH-300 — REQ-292

- **Branch:** `feat/req-292` (core) e `feat/req-292-docs-mdd` (conn2flow-site)
- **Requisição:** [req-292.md](../human-requests/req-292.md)

## Entregue

1. **Seção MDD** (`cli/src/Support/Docs/DocsBuilder.php`): `urlPath()` publica o prefixo interno `sdd/` como `sdd.slug` (padrão `mdd`). Os ids `docs-sdd-*` não mudam. O `llms.txt` usa o rótulo da seção, e a ajuda do `docs:build` foi atualizada.
2. **301 pela sincronização** (`gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php`): `sincronizarRegistrarPagina301()` roda nos três ramos de UPDATE de `sincronizarTabela`. Quando o `caminho` de uma página existente muda, grava o antigo em `paginas_301`, sem duplicar, e um erro ali não derruba a sincronização.
3. **Privacidade na fonte** (`cli/src/Support/Docs/SddSource.php`): ficam fora da publicação as memórias de engenharia com nome MDD (`03-memory-engineering-chief.md`, `04-memory-engineering-execution.md`), que o filtro antigo (`MEMORIA*`) deixava passar, e os `index.md` derivados (`# Index — ...`). O `memory/index.md` tomava o lugar da entrada da seção e trazia o resumo das memórias de engenharia. Nada disso chegou à produção.
4. **What's New 3.0** (`ai-workspace/{pt-br,en}/docs/whats-new/`): `3.0.md`, `3.0.0.md`, `3.0.4.md`, `3.0.9.md`, `3.0.12.md`, `3.0.14.md` e os índices. Nomes de função, rota e comando conferidos no código.
5. **Site** (`conn2flow-site`, `c8895cfa` e `649ca3df`): rótulos SDD para MDD e `sdd.slug = mdd` em `documentation.json`, mais a saída do `docs:build`.

## Validação

- PHPUnit `--filter 'Docs|Req292'`: 38 testes verdes, com `Req292PaginaCaminho301Test` novo (301 gravado uma vez em três sincronizações; sem a tabela a sincronização segue) e asserções novas em `DocsSddReq184Test` (id `docs-sdd` com caminho `docs/mdd/`, memórias de engenharia e índices fora).
- Vizinhos do `sincronizarTabela` (`Req196`, `ForcarAtualizacao`, `Req206`, `ProjectIdentityPassthrough`) verdes.
- Lab (`project:sync-core conn2flow-site-local --skip-css` e `project:deploy conn2flow-site-local`): 10 páginas novas em 200 (pt-br e en); `docs/sdd/`, `docs/sdd/README/`, `docs/sdd/process/01-WORKFLOW/` e `docs/sdd/decisions/DECISION-LOG/` em **301** para o equivalente em `docs/mdd/`; a entrada se chama "MDD do Core"; as memórias de engenharia não respondem.

> [!NOTE]
> As REQs 285 e 290 deixaram o PHPUnit isolado dos testes de docs dependente de ordem: `DocsBuildReq178Test`, `DocsSddReq184Test` e `DocsReq188Test` só passam depois do `DocsFunctionReferenceReq207Test` carregar `FunctionReferenceHtml`. Isso vem de antes do lote.

## Pendente (humano)

- **Produção:** o 301 depende do core novo no servidor. A ordem é release do core (3.0.15), depois `update:core conn2flow-site` e por último `project:deploy conn2flow-site`. Publicar as docs antes do core deixaria `docs/sdd/...` em 404.
- Em produção ficam ativas, com o caminho antigo, as páginas `docs-sdd-*` cujos arquivos foram para `archive/original/` (política "manter por padrão", DEC-136). Elas continuam respondendo 200 com o conteúdo anterior.
- Revisão geral das docs: REQ-293 (bibliotecas), REQ-294 (módulos) e REQ-295 (conceitos, guias, CLI, API e termos SDD).
