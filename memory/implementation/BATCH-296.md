---
id: "BATCH-296"
title: "404 sem espera: busca de outro idioma pelo índice, sem ler as páginas inteiras (REQ-288)"
status: "implemented-pending-homologation"
date: "2026-10-20"
author: "executor"
target_repo: "conn2flow"
summary_short: "Toda URL inexistente esperava ~6 s em produção (~1 s no Lab) porque a busca de outro idioma lia todas as páginas com html/css. Agora busca só o id pelo índice (language, caminho) e lê a página por id: 7.849 ms → 26 ms em produção."
---

# BATCH-296 — REQ-288

- Branch `feat/req-288` (base `main`). Requisição: [req-288.md](../human-requests/req-288.md).

## Live Todo

- [x] Medir de fora (produção e Lab).
- [x] Reproduzir no PHP CLI do Lab e achar a pausa com `strace` (só leitura).
- [x] Corrigir a busca de outro idioma e a lista de idiomas da página.
- [x] Teste de contrato; suíte completa.
- [ ] Release e medição depois da atualização (Engenheiro Chefe).

## Evidências

| Medida | Antes | Depois |
| --- | --- | --- |
| Produção, URL inexistente (de fora) | 6,4 s até o 302 | — (depois da release) |
| Produção, consulta de outro idioma | 7.849 ms | 26 ms |
| Lab, URL inexistente (PHP CLI) | 1,095 s | — (depois da release) |
| Lab, consulta de outro idioma | ~1.000 ms | 12 ms (`EXPLAIN`: `idx_paginas_language_caminho`, 2 linhas) |

- `strace` (Lab): `poll` de 0,99 s na resposta do `SELECT layout_id,…,html,…,css_compiled… FROM paginas WHERE caminho=… AND (tipo…)`, sem `language`.
- `Roteador404RapidoReq288Test` 2/2; PHPUnit completo 1.909 (só as 2 falhas de ambiente do Windows).

## Arquivos

- `gestor/gestor.php`: busca de outro idioma em dois passos (id pelo índice; página por id); lista de idiomas da página com `language IN`.
- `tests/Unit/PHP/Roteador404RapidoReq288Test.php`.

## Observação

`language IN` usa os idiomas do `.env` (`LANGUAGES`). Página gravada num idioma que não está habilitado deixa de ser achada por essas duas buscas; com o `.env` vazio, não há filtro (comportamento antigo).
