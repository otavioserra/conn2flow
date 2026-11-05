---
id: "BATCH-308"
title: "REQ-300 — UI 3.0 tematizável por projeto: tokens de ação, diálogo em modal, controles e layout por projeto, container de painel"
status: "implemented-pending-homologation"
date: "2026-11-05"
author: "executor"
target_repo: "conn2flow"
request: "REQ-300"
topology: "solo"
autonomy: "autonomo_monitorado"
summary_short: "Cor de ação dos controles por tokens (sem mudança visual), diálogo nasce dentro do <dialog> modal, `project-controles-layouts`, `project-paginas-layout` e `.c2f-painel-pagina` por variável; equipe adota."
summary_medium: "controles.css com 9 tokens --c2fc-acao* (47 hex trocados; equivalência byte a byte provada com os padrões), modalAberto() no diálogo, roteador com controles e layout por configuração de projeto, equipe com container por variável e acentos por token. Vitest e PHPUnit novos; adotado pelo lumix (REQ-124) no Lab."
---

# BATCH-308 — Tema e layout por projeto (REQ-300)

Branch `main`, **sem commit**. Requisição: [req-300.md](../human-requests/req-300.md). Projeto coordenado: lumix REQ-124 / BATCH-272.

## Itens

| # | Item | Implementação |
| --- | --- | --- |
| 1 | Tokens de ação | `:root` no `controles.css` com `--c2fc-acao`, `-escuro`, `-texto`, `-claro`, `-suave`, `-borda`, `-realce`, `-foco`, `-anel` (padrões = sky de sempre). 47 hex de ação viram `var()`; `.c2fc-nota`, `.c2fc-alerta-info` e `.c2fc-cor-*` ficam literais (semânticos). Prova: resolvendo os tokens pelos padrões, o CSS é idêntico ao original. |
| 2 | Diálogo em modal | `modalAberto()` (último `dialog[open]` que casa `:modal`; navegador sem `:modal`, qualquer `dialog[open]`) recebe o overlay de `dialogo.*`; senão `<body>`. |
| 3 | Controles por layout | `$_GESTOR['project-controles-layouts']`: o roteador inclui a biblioteca também nesses layouts (e no layout final definido pelo projeto). |
| 4 | Container de painel | `.c2f-painel-pagina` no `controles.css`: `max-width: var(--c2f-painel-largura, 72rem)`, respiro `var(--c2f-painel-respiro, .5rem 0)` / `var(--c2f-painel-respiro-sm, 1.25rem 1rem)` (os valores da `equipe`). A `equipe` adota; selo de assentos, círculos de estado vazio e cartão de módulo marcado (`equipe.js`) passam a `bg-(--c2fc-acao-*)` (Tailwind 4.3 compila `var()`). Nota de informação da `equipe` segue sky. |
| 5 | Layout por página do projeto | `gestor_roteador_layout_projeto()` + `$_GESTOR['project-paginas-layout']` (`id => layout`), antes do layout por perfil; bundle só se o CSS da página cobre o layout novo, senão sidecar. **Limite medido no Lab**: com sidecar, utilities da página vencem as responsivas do layout (o `hidden` da `equipe` apagou o `lg:flex` da barra do `photon-admin`). Documentado no código; o lumix usa a página declarada no projeto (cópia idêntica) para layouts Tailwind com barra responsiva. |

## Arquivos

`gestor/assets/interface/controles.{css,js,min.js}`, `gestor/assets/minify-manifest.json`, `gestor/gestor.php`, `gestor/modulos/equipe/{equipe.js,equipe.min.js}`, `gestor/modulos/equipe/resources/{pt-br,en}/pages/equipe/equipe.html`, `gestor/db/data/*Data.json` (pipeline), `tests/Unit/PHP/Req300LayoutEControlesPorProjetoTest.php` (novo), `tests/Unit/JS/req300-controles.test.js` (novo, compartilhado com o BATCH-309).
Fora do lote: `AGENTS.md`, `CLAUDE.md`, `GEMINI.md` e skills/hooks modificados por outro agente.

## Evidências

- Vitest **716/716** (64 arquivos; +16 do REQ-300). Falsificado: sem a guarda de sequência do select, o teste de resposta atrasada falha.
- PHPUnit **5/5** no `Req300*`; suíte completa 1997 testes com as mesmas 23 falhas preexistentes no Windows (CRLF em `dashboard.php`/`api.php` intocados lidos com `\n` literal, OpenSSL sem `openssl.cnf`, e `MemoryDualReq266` contando 46 skills por uma skill nova de outro agente).
- `assets:minify`: 0 falhas. `resources:sync` do núcleo: "nenhum problema" (aviso de `dist/` sem `PUBLIC_PATH` na árvore de desenvolvimento).
- Lab (via lumix, `project:update-all snapphoton-local` + `project:deploy`): diálogo de revogar o iCal da agenda **dentro** do `<dialog>` de integrações e clicável, sem código no módulo; `equipe` com controles, tokens esmeralda e container de 80rem.

## Pendências

- [ ] Homologação humana; release do núcleo (o `?v=` do `controles.css` segue a versão da biblioteca/gestor).
- [ ] Proposta: herdar o HTML de página de módulo do núcleo na compilação do projeto (aí `project-paginas-layout` serve também a layouts Tailwind responsivos, sem cópia).
