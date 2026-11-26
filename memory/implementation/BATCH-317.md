---
id: "BATCH-317"
title: "REQ-308 — UI 3.0: abas responsivas, <dialog> que fecha ao clicar fora e aviso único"
status: "implemented-pending-review"
date: "2026-11-26"
author: "executor"
target_repo: "conn2flow"
request: "REQ-308"
topology: "dupla"
autonomy: "autonomo_monitorado"
summary_short: "Abas com seletor compacto no mobile, fechamento seguro de <dialog> pelo fundo e o toast homologado do SnapPhoton viram padrão do controles.js; o lumix (REQ-142) só consome."
---

# BATCH-317 — Padrão único de abas, diálogos e avisos na UI 3.0

Lote da [REQ-308](../human-requests/req-308.md) no `conn2flow` (`C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`), branch `feat/req-308-ui3-abas-mobile-e-dialog-backdrop` criada da `main` em `88d0b08e`. Topologia dupla, autonomia `autonomo_monitorado`. Coordenado com `lumix` REQ-142 / BATCH-290, executado pelo mesmo agente.

## Adendo humano (2026-11-26)

O operador pediu que o comportamento das abas do SnapPhoton seja o **padrão da UI 3.0** e que o projeto apenas consuma, "não pontual": mudou um, todos os menus de abas funcionam igual. O mesmo vale para modal, alerta e toast. Por isso o lote foi além do texto da REQ-308 em dois pontos, ambos no mesmo arquivo de controles:

- **Aviso (toast)**: o toast próprio do SnapPhoton (`window.photonToast`, REQ-137) era uma segunda implementação ao lado de `c2fControles.aviso`. O comportamento homologado dele foi para o core.
- **Especificidade**: regras genéricas de módulo (`.pep button { background: … }` no Prontuário) venciam os botões do seletor e o × do aviso. As regras do core passaram a ser escopadas no contêiner do controle.

## Rodada 1 — implementação

- **Abas (`data-c2f-abas`)**: grupos com 3+ abas geram `.c2fc-abas-seletor` antes da faixa. Gatilho com rótulo "Seção atual" (`controles-secao-atual`, ou `data-c2f-abas-rotulo-mobile`), nome e ícone da aba ativa e seta; painel com todas as abas (grade de 2 colunas a partir de 4). O ícone vem de `[data-c2f-aba-icone]` (só aparece no seletor) ou do primeiro `<svg>`. Escolher uma opção **clica na aba original**, então os módulos que escutam o clique continuam funcionando. Um `MutationObserver` acompanha quando o módulo troca `aria-selected`, `hidden` ou `disabled` por conta própria. Esc, clique fora e setas funcionam, e `destruir()` desliga tudo. Opt-out: `data-c2f-abas-sem-seletor-mobile`. No CSS, a faixa usa `flex-wrap: wrap; overflow-x: visible`, e abaixo de 640 px fica `display: none !important` quando há seletor (troca estrutural, vence a regra do projeto).
- **`<dialog>` modal**: ouvintes delegados em `document` (`pointerdown`, com `mousedown` de reserva, e `click`, em captura). O dialog só fecha quando o gesto começa **e** termina no próprio `<dialog>`, fora do `getBoundingClientRect()`. Não fecha com `clientX/Y = 0`, com `data-c2f-sem-fechar-fora` nem quando o `<dialog>` foi aberto com `show()`. Antes de fechar, dispara `cancel` cancelável e depois chama `dialog.close()`, então a limpeza feita no evento `close` continua valendo.
- **Aviso**: tipos em português ou inglês, ícone SVG por tipo, mensagem repetida substitui a anterior, até 3 na tela, tempo pausado no hover e forma `aviso(msg, tipo, {tempo})`. A posição usa o token `--c2fc-avisos-base`. Com `<dialog>` modal aberto, a área de avisos entra nele (top layer) e volta à página no `close`. A ponte `$.fn.toast` foi mantida, com o × ainda como último filho.
- Variável global `controles-secao-atual` (PT-BR/EN), `controles_textos()`, `interface` 1.1.6 → 1.1.7, tokens de assets e referência `ai-workspace/*/docs/reference/libraries/controles.md`.

## Evidências

| Verificação | Resultado |
| --- | --- |
| Vitest novo `tests/Unit/JS/req308-controles.test.js` | 18/18: seletor gerado com ≥ 3 abas, rótulos, opt-out, clique repassado à aba e memória REQ-306, restauração por `?tab=`/sessão, sincronização por `MutationObserver`, Esc/fora/setas, `destruir()`, CSS; `<dialog>` fecha fora, não fecha arrasto de dentro, teclado, opt-out, `cancel` com `preventDefault` e `show()`; aviso dentro do modal e de volta; tipos, ícone, sem repetição, limite 3, pausa |
| Vitest completo | 743/743 em 66 arquivos (antes 725) |
| `assets:minify --verificar` | 0 derivados desatualizados (`controles.min.js` regenerado) |
| Lint PHP | `controles.php` e `interface.php` sem erros |
| Lab (via lumix, `project:update-all snapphoton-local` 2× + `project:deploy` + `css:rebuild`) | 0 choques e 0 remoções do lote. Os 32 choques gravados são retiradas de 30/10, pendentes de decisão humana (DEC-136). Playwright autenticado: ver [BATCH-290 do lumix](../../../lumix/memory/implementation/BATCH-290.md) |

A suíte PHPUnit completa não foi rodada: a mudança PHP se limita a uma chave na lista de `controles_textos()` e ao número de versão. Os derivados que o pipeline regravou fora do lote (tokens de `admin-arquivos`/`admin-comunicacao`, manifesto do Tailwind e troca de fim de linha em `*Data.json`/`precompiled.css`) foram restaurados antes do commit.

## Handoff

Projeto `conn2flow`, raiz `C:\Users\otavi\OneDrive\Documentos\GIT\conn2flow`, REQ-308, BATCH-317, topologia dupla, autonomia `autonomo_monitorado`, commit seletivo local, sem push. Pendências: revisão do Macro-Arquiteto, homologação humana no Lab e release do core. O Dev do SnapPhoton só recebe o padrão depois do release, porque a REQ-142 depende dele. Telas do core que ainda usam `data-tab` próprio (`admin-environment`, `usuarios-perfis`, ver BATCH-315) são candidatas a adotar o controle.
