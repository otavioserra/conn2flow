---
id: "BL-051"
title: "BL-051 — Editbar / Site Toolbar: deslocamento dinâmico do layout principal e de elementos fixos/sticky no topo"
status: "PROMOTED"
date: "2026-11-18"
author: "architect"
target_repo: "conn2flow"
promoted_to: "REQ-302 (BATCH-311)"
summary_short: "A Site Toolbar / Editbar (#c2f-site-toolbar) sobrepõe cabeçalhos fixed/sticky (ex.: https://ia-parceria.local/) porque setPageOffset() aplica apenas margin-top no <html> e usa alturas fixas (30px / 74px)."
summary_medium: "Quando logado com permissão de edição, #c2f-site-toolbar aparece em todas as páginas, mas setPageOffset() em dashboard.toolbar.js aplica somente margin-top em document.documentElement.\nElementos com position: fixed; top: 0 (como #c2f-header e #c2f-progress em ia-parceria.local) ancoram no viewport e ficam sob a barra. Além disso, o offset em dashboard.iframe-toolbar.js é hardcoded (30 + 44px). Precisa medir a altura real dinamicamente e empurrar todo o layout (incluindo elementos fixed/sticky no topo)."
---

# BL-051 — Editbar / Site Toolbar: deslocamento dinâmico do layout principal e de elementos fixos/sticky no topo

- **Tipo:** Bug visual / UX / Core (`dashboard` + `html-editor`)
- **Status:** PROMOTED ➔ [req-302.md](../human-requests/req-302.md) (`BATCH-311`)
- **Criado em:** 2026-11-18
- **Origem:** Engenheiro Chefe Humano (observado em `https://ia-parceria.local/` e recorrente em várias páginas/projetos com header fixo).

## Sintoma

Quando o usuário está logado e possui permissão para editar a página, a barra superior (`#c2f-site-toolbar` / Editbar) é injetada automaticamente no topo das páginas públicas. Em diversas páginas e projetos — como em `https://ia-parceria.local/` — a barra fica **em cima do layout** (cobrindo o `<header>` principal da página e/ou barras de progresso no topo), tanto no estado padrão quanto ao abrir o modo de edição ("Editar Página").

## Diagnóstico Técnico (Causa-Raiz)

1. **`margin-top` no `<html>` não desloca elementos `position: fixed`**:
   - Em `gestor/modulos/dashboard/dashboard.toolbar.js`, `setPageOffset(offset)` aplica apenas `document.documentElement.style.setProperty('margin-top', o + 'px', 'important')`.
   - Cabeçalhos modernos (ex.: em `https://ia-parceria.local/`, `<header id="c2f-header" class="fixed inset-x-0 top-0 z-50 ...">` e `<div id="c2f-progress" class="fixed left-0 top-0 z-[60] ...">`) usam `position: fixed; top: 0`, que ancora no viewport (`y = 0`) e ignora o `margin-top` do `<html>`.
   - O mesmo ocorre com elementos `sticky top-0` quando dentro de contêineres ou quando o scroll começa, além de wrappers `h-screen` / `min-h-screen` que podem estourar `100vh + offset`.
2. **Altura fixa/hardcoded em vez de medição dinâmica**:
   - Em `gestor/modulos/dashboard/dashboard.iframe-toolbar.js`, o cálculo de `offset` usa valores fixos (`30 + (editOpen ? 44 : 0)`), e `dashboard.toolbar.js` usa `TOOLBAR_HEIGHT = 30`.
   - Se a barra mudar de altura (quebra de linha em telas menores, barra secundária do editor visual como `c2f-he-element-navbar`, variação de fonte/tema), o deslocamento fica dessincronizado.

## Solução Proposta (Diretrizes para Especificação)

1. **Medição dinâmica da altura real da barra**:
   - Medir dinamicamente a altura efetiva ocupada pelas barras persistentes no topo (barra principal + `#c2f-toolbar-editbar` quando aberta, sem contar dropdowns flutuantes temporários) via `ResizeObserver` / `getBoundingClientRect().height` e propagar o `offset` real em `c2f-toolbar:resize`.
   - Expor o valor atual em uma CSS Custom Property no `:root` (ex.: `--c2f-toolbar-offset: <N>px`) para consumo opcional por temas/layouts.
2. **Empurrar o layout principal e elementos `fixed`/`sticky` no topo**:
   - Garantir que, enquanto a toolbar estiver ativa, o layout da página seja deslocado para baixo pela altura exata (`offset`) da barra:
     - Deslocamento do fluxo normal (`<html>` / `<body>`, avaliando `padding-top` vs `margin-top` e ajuste de `min-height`/`scroll-padding-top` para âncoras).
     - Deslocamento automático de elementos do documento hospedeiro que estejam posicionados no topo com `position: fixed` (e `sticky` quando aplicável, preservando `top` original somado ao `offset` e restaurando quando a barra fecha/muda de altura, sem interferir nos elementos da própria UI do editor/toolbar).
     - Verificar `MutationObserver` para elementos fixos inseridos/alterados dinamicamente e recalcular em `resize`/mudança de estado da Editbar.
3. **Preservação de HTML limpo ao salvar**:
   - Garantir que nenhum atributo/estilo inline temporário de deslocamento (`data-c2f-orig-top`, `style="top: ..."`) vaze para o HTML salvo pelo `html-editor.js` (`getCleanHtml` / `reconstructOriginal`).

## Critério de Aceite Proposto

- Acessar `https://ia-parceria.local/` (e páginas do `conn2flow-site` com header `fixed`/`sticky`) logado com permissão de edição:
  - A barra `#c2f-site-toolbar` empurra todo o layout para baixo (incluindo `<header id="c2f-header">` e `#c2f-progress`), sem sobreposição.
  - Ao clicar em **"Editar Página"** (abrindo a Editbar) e ao fechar/cancelar, o layout acompanha dinamicamente a altura exata da barra em tempo real.
  - Em páginas sem elementos `fixed`, o comportamento continua empurrando o fluxo normalmente, sem espaços duplos nem vazamento de estilos temporários no HTML salvo.
