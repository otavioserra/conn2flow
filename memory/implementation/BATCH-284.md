---
id: "BATCH-284"
title: "Visibilidade e link direto privado nas páginas (admin-paginas e publisher-pages)"
status: "implemented-pending-homologation"
date: "2026-10-15"
author: "executor"
target_repo: "conn2flow"
summary_short: "Linha Visibilidade entre Tipo e Agendamento: visível publicamente (padrão) e link direto privado com Copiar e Gerar novo link; sem permissão marcado ao adicionar."
summary_medium: "paginas.visivel/hash_privado; página não visível só abre com ?acesso=<hash>, liberada na sessão, ou para quem administra páginas; senão segue o 404. Sai do sitemap e dos índices.\nPHPUnit 1.838 (15 novos), E2E do roteador 12/12, navegador 9/9, gravação real 3/3 no Lab."
---

Projeto: conn2flow. Raiz: C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow.
REQ-275; BATCH-284; topologia dupla; autonomo_monitorado; branch `feat/req-275` (da `main` com a req-274). Vai na 3.0.4.

## Live Todo

- [x] Migração `20261015160000`: `visivel` (padrão 1) e `hash_privado` (único, preenchido para as páginas existentes).
- [x] Funções puras em `bibliotecas/gestor.php` e biblioteca `paginas-visibilidade.php`.
- [x] Roteador: `gestor_roteador_pagina_visibilidade()` antes da checagem de permissão.
- [x] `admin-paginas` e `publisher-pages`: adicionar, editar e clonar (PHP, 12 HTML, componente, variáveis, JS).
- [x] Sitemap, `pages-index` e `publisher-index` sem as não visíveis.
- [x] PHPUnit, E2E, navegador e gravação real no Lab.
- [x] Relatório, commit e push.
- [ ] Release 3.0.4 e homologação humana.

## Regras

| Regra | Como ficou |
| --- | --- |
| Controles separados | "Sem permissão / acesso público" segue isolado; só passou a vir marcado no adicionar. Editar e clonar mostram o banco |
| Não visível | Abre com `?acesso=<hash>` (libera a página na sessão), já liberada, ou para usuário com acesso a `admin-paginas`/`publisher-pages`. Senão segue o caminho do 404, igual a página inexistente |
| Link | Absoluto, mesma base do "ver página" (`#url#`) + `?acesso=`. `?hash=` não, para não colidir com o checkout de planos do site |
| Gerar novo link | Salvamento normal do formulário (`regenerar_hash_privado=1`, com o token dele); o link antigo para |
| Clonar | Copia a visibilidade, não o hash: a cópia nasce com link próprio |
| Indexação | Página servida pelo link leva `X-Robots-Tag: noindex, nofollow`; fora do sitemap e dos índices |
| Atualização | Roteador lê a visibilidade numa consulta à parte; antes da migração, a página segue visível em vez de quebrar a busca |

## Evidências

| Verificação | Resultado |
| --- | --- |
| `PaginasVisibilidadeReq275Test` | 15 testes (funções puras, migração, roteador, módulos, formulários e variáveis nos dois idiomas, listagens, sitemap) |
| PHPUnit do core (`ssh lab`) | 1.838 testes, 0 falhas |
| Pipeline `project:update-all conn2flow-site-local --skip-css` | saída 0; migração aplicada (2,1 s) |
| E2E do roteador (`comece-a-construir`) | 12/12: hash e `visivel=1` nas páginas existentes; escondida → `/404` sem link, com link errado e em outra sessão; link certo → 200 com `noindex`; mesma sessão sem parâmetro → 200; admin → 200; hash novo invalida o antigo |
| Navegador ([req275-browser.cjs](../validation/req275-browser.cjs)) | 9/9 a 1280 e 390 px nos 4 formulários: 200, sem erro de console, sem rolagem horizontal, sem marcador cru, "Visível" e "sem permissão" marcados no adicionar, link e Copiar no editar; Copiar põe o link na área de transferência; cancelar "Gerar novo link" não envia nada. Capturas em `memory/validation/req275/` |
| Gravação real ([req275-save.cjs](../validation/req275-save.cjs)) | 3/3: esconder grava `visivel=0` e mantém o hash; gerar novo link troca o hash e mostra o novo; religar grava `visivel=1` |
