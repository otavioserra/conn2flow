---
id: "BATCH-309"
title: "REQ-300 — Backlogs promovidos: confirmações (BL-049), select AJAX híbrido (BL-048) e calendário de vagas (BL-050)"
status: "implemented-pending-homologation"
date: "2026-11-05"
author: "executor"
target_repo: "conn2flow"
request: "REQ-300"
topology: "solo"
autonomy: "autonomo_monitorado"
summary_short: "Sem confirm() nativo fora de reserva no núcleo, confirmar em forma objeto, select AJAX que preserva a lista inicial e descarta resposta atrasada, e controle `calendario` com vagas por lista ou AJAX por mês."
summary_medium: "BL-049 em admin-cron e admin-environment; BL-048 com lista inicial, debounce 300 ms, guarda de sequência e data-c2f-ajax-parametros; BL-050 com grade acessível, min/max, teclado, cache por mês, recarregar() e variáveis PT/EN. Adotado no autoagendamento público do lumix (navegador 6/6 no Lab)."
---

# BATCH-309 — Backlogs de controles (REQ-300)

Branch `main`, **sem commit**. Backlogs promovidos pelo operador em 2026-11-05: BL-048, BL-049, BL-050 (status `PROMOTED`).

## Itens

| # | Backlog | Implementação |
| --- | --- | --- |
| 1 | BL-049 | `admin-cron` (excluir tarefa) e `admin-environment` (revogar todos os navegadores) usam `c2fControles.dialogo.confirmar(..., {perigo:true})`, `confirm()` só como reserva. `dialogo.confirmar` aceita `{titulo, mensagem, ok, cancelar, perigo}`. Já eram reserva: `equipe`, `perfil-usuario`, `html-editor*`. |
| 2 | BL-048 | Select AJAX híbrido: opções do HTML guardadas como lista inicial e restauradas com termo vazio/curto e ao reabrir; `_trocarOpcoes()` mantém selecionadas; resposta fora de ordem descartada (`_buscaSeq`); debounce 250 → 300 ms; `data-c2f-ajax-parametros` (JSON). Paginação remota além de 50 fica com o endpoint de cada módulo. |
| 3 | BL-050 | Controle `calendario` (`input[type=date][data-c2f-calendario]` ou `c2fControles.calendario(input, opções)`): grade mensal (`role=grid`), `Intl` para mês/dia, semana no domingo (`data-c2f-calendario-inicio="1"` para segunda), `min`/`max`, teclado (setas, Home/End, PageUp/PageDown, Enter/Espaço), vagas por `data-c2f-calendario-disponiveis` ou AJAX por mês (`mes=AAAA-MM`, `{disponiveis}` em `data` ou direto) com cache, `aria-busy` e descarte de mês atrasado; `disponibilidade()`, `recarregar(parametros)`; input nativo oculto por `.c2fc-calendario-nativo` (vale também criado pela API JS) e fonte de verdade (`input`/`change`). CSS com tokens de ação e `--c2fc-disponivel*`. Variáveis `controles-mes-anterior`, `-proximo-mes`, `-disponivel`, `-indisponivel` (PT/EN). |

## Evidências

- Vitest `req300-controles.test.js` 16/16 (forma objeto, BL-049 sem nativo direto, híbrido + atrasada + parâmetros, debounce, seleção preservada, calendário min/max, vagas fixas, teclado, AJAX por mês com cache, recarregar); suíte 716/716.
- Lab (lumix REQ-124): página pública `/agendar/` com o calendário — dias destacados = `publico-dias`, fim de semana desabilitado, clique carrega horários, seleção `rgb(15,110,86)`, 390 px sem rolagem; 6/6. Dois defeitos achados na captura e corrigidos: input nativo visível quando criado pela API JS e "Novembro De 2026" (`capitalize` → `::first-letter`).

## Pendências

- [ ] Homologação humana e release.
- [ ] BL-049 fora do núcleo: `snapphoton-system`, `subscriptions` e `user-subscription` do lumix mantêm `confirm()` nativo.
