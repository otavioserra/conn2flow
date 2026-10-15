---
id: "BATCH-285"
title: "Confirmação padrão no link privado: correção do bloqueio do release 3.0.4"
status: "implemented-pending-homologation"
date: "2026-10-15"
author: "executor"
target_repo: "conn2flow"
summary_short: "Diálogo assíncrono nos dois editores de páginas; Vitest completo com 700 testes aprovados."
summary_medium: "Corrige window.confirm introduzido na REQ-275, preservando cancelamento e salvamento com token.\nVersões e minificados atualizados; publicação entregue ao agente de release pelo humano."
---

# BATCH-285 — REQ-276

Projeto: conn2flow. Raiz: C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow.
Branch: `feat/req-276`. Topologia: solo (auto-revisão). Autonomia: autonomo_monitorado para a correção; próximo agente acionado pelo humano. Requisição: [REQ-276](../human-requests/req-276.md).

## Live Todo

- [x] Registrar pedido e delimitar a correção; reservar REQ-276 na branch remota.
- [x] Reproduzir a falha do release no teste existente.
- [x] Substituir `window.confirm` por `await window.c2fControles.dialogo.confirmar` nos dois handlers.
- [x] Versões: `admin-paginas` 1.2.8 → 1.2.9; `publisher-pages` 1.0.7 → 1.0.8.
- [x] Gerar minificados e manifesto pelo `assets:minify` oficial.
- [x] Validar cancelamento, espera assíncrona, confirmação e fallback de submissão; executar Vitest completo.
- [x] Auto-revisar escopo, i18n e preservação do salvamento; preparar handoff.
- [ ] Homologação humana.

## Defeito e correção

O [run 38777228301](https://github.com/otavioserra/conn2flow/actions/runs/38777228301) de `gestor-v3.0.4`, commit `2eeb6234`, parou no Vitest: `PublisherPagesTailwindReq221.test.js:36` rejeita diálogos nativos. A REQ-275 adicionou `window.confirm()` ao botão de regeneração do link privado em `publisher-pages` e `admin-paginas`.

Os handlers agora são assíncronos e aguardam a confirmação padrão. O cancelamento retorna antes de mudar `regenerar_hash_privado`; a aceitação continua com o salvamento normal e seu token. A mensagem continua vindo do `data-confirm` já traduzido. Nenhum PHP, HTML, CSS, regra de acesso, workflow ou versão do sistema foi alterado.

## Evidências de validação (2026-10-15)

| Verificação | Resultado |
| --- | --- |
| Vitest existente antes da correção | `PublisherPagesTailwindReq221.test.js`: 1 falha, 2 aprovados; mesma asserção do GitHub |
| Testes dirigidos após a correção | `PublisherPagesTailwindReq221`, `PageVisibilityConfirmationReq276`, `controles`: 23/23 |
| Regressão nova nos dois módulos | 6/6: espera antes da decisão, cancelamento sem envio/alteração; aceite via requestSubmit e fallback via submit; token mantido |
| Vitest completo | `node_modules/.bin/vitest.cmd run --reporter=dot`: 63 arquivos, 700 testes aprovados; saída 0 |
| Sintaxe | `node --check` nos dois fontes e dois minificados: saída 0 |
| Build oficial | `php cli/c2f.php assets:minify`: dois derivados gerados, 70 coerentes; manifesto atualizado apenas para os dois módulos |
| Auto-revisão | Contrato de diálogos restaurado, sem novos textos de produto, salvamento/token preservados; teste existente mantido intacto |

## Limites conhecidos / o que não foi exercitado

- A suíte completa ainda escreve mensagens de rede `ECONNREFUSED` de Happy DOM; o run original já tinha essas mensagens e a falha bloqueante era a asserção do diálogo. Nesta execução completa nenhum teste falhou.
- `assets:minify` emitiu FALHA para `admin-categorias.js`; `assets:minify --verificar` aponta somente esse arquivo. Fonte idêntico ao HEAD (`git diff --exit-code HEAD -- ...` saída 0), sem entrada no manifesto do HEAD e sem mudança produzida neste lote. `node --check` desse fonte passa; causa da minificação não investigada neste escopo. Não declarar o gate global de minificação aprovado.
- `npm` sem extensão foi bloqueado pela política de execução do PowerShell; a suíte foi executada pelo binário local `vitest.cmd`, sem alterar políticas.
- Não houve deploy, validação visual de página autenticada, PHPUnit ou Playwright local neste lote: mudança restrita ao handler JS e à invalidação de cache, coberta pelos testes dirigidos e pela suíte JS. O workflow do release deve executar seus gates completos.
- Não executar compactação ampla da memória neste hotfix: as pastas já excediam dez artefatos ativos (20 requisições e 20 lotes antes da nova requisição); saneamento histórico fica fora do pedido pontual.
- `gestor/assets/asset-versions.json` e `gestor/db/data/VariaveisData.json` já constavam modificados no início e não integram o commit deste lote.

## Handoff para o agente de release

Projeto **conn2flow**, raiz **C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow**. REQ-276 / BATCH-285; topologia solo; autonomo_monitorado na correção. Branch `feat/req-276`, criada da main em `2eeb6234`. Evidências neste lote e no teste `tests/Unit/JS/PageVisibilityConfirmationReq276.test.js`.

Integrar a branch de correção na árvore usada pelo release e executar os gates completos antes de publicar. Esta entrega não cria/move tags nem publica release; o humano encaminhará ao outro agente. A tag `gestor-v3.0.4` continua no commit antigo, portanto reexecutar somente o run antigo não incorpora a correção. Decisão sobre recriar tag/versão pertence ao trabalho de release. A última release publicada consultada era `gestor-v3.0.3`.

Pendências: homologação humana e publicação pelo agente de release. Nenhum despacho automático para outro agente foi realizado.
