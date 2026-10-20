---
id: "BATCH-297"
title: "dist/ republicado a cada atualização e deploy (REQ-289)"
status: "implemented-pending-homologation"
date: "2026-10-20"
author: "executor"
target_repo: "conn2flow"
summary_short: "O navegador recebia o JS publicado em dist/ na última publicação manual (Lab: 16/10; produção: 15/10), e a aba de choques da 3.0.13 nunca chegou. Atualização do sistema e deploy de projeto agora republicam os itens do dist/ cuja fonte mudou."
---

# BATCH-297 — REQ-289

- Branch `feat/req-289` (base `main` com a 3.0.13). Requisição: [req-289.md](../human-requests/req-289.md).

## Evidências

- Playwright no Lab (sessão de admin por `c2f auth:cookie`; cookie não registrado): clique em "Todos" → URL `?choques=todos`, `window.__marca` perdida (recarregou), aba de choques fechada; script servido `dist/admin-atualizacoes/js.js` sem `choques-resolver-lote`/`DOMParser`.
- `dist/` do Lab publicado em 16/10 (30 arquivos de 08/10, os demais até 16/10); da produção, em 15/10.
- Simulação da função nova sobre cópia do `dist/` (só leitura no original): Lab 611 verificados, 2 republicados (`admin-atualizacoes/js.js`, `admin-categorias/js.js`); produção 611 verificados, 59 republicados.
- `testDistRepublicaOQueMudou` (min preferido, min vazio usa autoria, `?v=` muda, nada removido, segunda passada sem trabalho); PHPUnit completo 1.910 (só as 2 falhas de ambiente).

## Arquivos

- `gestor/bibliotecas/instalacao-manifesto.php`: `instalacao_dist_ressincronizar()`.
- `gestor/controladores/atualizacoes/atualizacoes-sistema.php`: `atualizacoesDistRessincronizar()` depois de mover os arquivos (CLI e painel).
- `gestor/controladores/api/api.php`: republicação depois de aplicar os arquivos do projeto.
- `tests/Unit/PHP/InstalacaoManifestoTest.php`.

## Pendências

- Release; no Lab, conferir a aba de choques (Playwright repetível: abrir a aba, "Todos", página 2, sem perder a marca).
- Na produção, a primeira atualização republica os 59 itens congelados.
