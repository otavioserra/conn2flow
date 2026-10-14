---
id: "BATCH-278"
title: "REQ-269: --skip-css nos pipelines project:update-all e manager:update-all"
status: "implemented-pending-homologation"
date: "2026-10-14"
author: "executor"
target_repo: "conn2flow"
summary_short: "--skip-css pula a compilação Tailwind e o css:rebuild nos dois pipelines; Lab em 36 s com a flag."
summary_medium: "Repasse da flag ao project:sync-core, project:sync-resources e aos scripts shell; etapa de css:rebuild pulada com aviso.\nMedição: com cache quente, o pipeline completo também leva ~40 s; o gargalo de 10-15 min não vem do Tailwind."
---

# BATCH-278 — REQ-269: `--skip-css` nos pipelines

- Projeto: `conn2flow`
- Raiz: `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`
- Branch: `feat/req-269`, criada sobre `315cfbed` (reserva da REQ-269, que está em cima da `feat/req-268`). A base inclui a REQ-268, já publicada no Lab: publicar desta branch não desfaz aquela correção.
- Requisição: [REQ-269](../human-requests/req-269.md)
- Topologia e autonomia: `dupla`, `autonomo_monitorado`
- Status: `implemented-pending-homologation`

## Live Todo

- [x] Ler REQ-269, CURRENT e o código dos comandos e scripts.
- [x] `project:update-all`: opção na ajuda, repasse às etapas 2 e 4, etapa 7/8 pulada com aviso.
- [x] `project:sync-resources` e `update-resource-data.sh` repassam a flag ao compilador.
- [x] `project:sync-core` e `sync-core-to-project.sh` também (ver Decisões).
- [x] `manager:update-all`: etapa 5/6 pulada com aviso; a 2/6 já recebia a flag.
- [x] Teste `PipelineSkipCssReq269Test`.
- [x] Lab com e sem a flag, suítes completas, `git diff --check`.
- [ ] Homologação humana.

## Implementação

| Arquivo | Mudança |
| --- | --- |
| `cli/src/Commands/ProjectUpdateAllCommand.php` | `[--skip-css]` na ajuda; `skipsCssRebuild()` (pura); etapa 7/8 vira `rebuildCss()` e é pulada com `Etapa 7/8 ignorada por --skip-css.`. As etapas 2 e 4 já recebiam o `$input` da linha de comando. |
| `cli/src/Commands/ProjectSyncResourcesCommand.php` | `scriptArguments($project, $skipCss)` (pura) monta `--project … --skip-css`. |
| `cli/src/Commands/ProjectSyncCoreCommand.php` | Repassa a flag pelo mesmo `scriptArguments()`. |
| `ai-workspace/en/scripts/projects/update-resource-data.sh` | Aceita `--skip-css` e entrega a `atualizacao-dados-recursos.php`, que já pulava a etapa 3/8 (Tailwind por recurso) com ela. |
| `ai-workspace/en/scripts/projects/sync-core-to-project.sh` | Idem, para a compilação dos recursos do core antes da cópia. |
| `cli/src/Commands/ManagerUpdateAllCommand.php` | `[--skip-css]` na ajuda; etapa 5/6 pulada com `Etapa 5/6 ignorada por --skip-css.`. |
| `tests/Unit/PHP/PipelineSkipCssReq269Test.php` | 6 testes, 22 asserções: flag na linha de comando, argumentos do script, repasse e pulo nas duas pipelines e nos dois scripts. |

### Decisões

- **A etapa 2 também respeita a flag.** A REQ cita as etapas 4 e 7, mas o `sync-core-to-project.sh` compila o Tailwind dos recursos do core antes da cópia. Sem o repasse, `--skip-css` ainda compilaria CSS.
- **Com a flag, o CSS pré-compilado que já está em disco continua valendo.** Mudança de HTML ou CSS exige o pipeline completo; a ajuda dos dois comandos diz isso.

## Validação

| Verificação | Resultado |
| --- | --- |
| `project:update-all conn2flow-site-local --skip-css` (Lab) | saída 0 em **36 s**; etapas 2, 4 e 7 informam o pulo; banco, arquivos, `dist/` e conferência por hash rodam |
| `project:update-all conn2flow-site-local` sem a flag | saída 0 em **40 s**; Tailwind executado (core: 519 em cache; projeto: 1.293 em cache) e `css:rebuild` (7 analisados, 2 regenerados, 0 erros) |
| `manager:update-all --skip-css` | flag aceita, etapa 2/6 informa o pulo. Saída 1 nas etapas 3/6 e 4/6 porque o destino do sistema é Docker e não há `docker` neste host. **Sem a flag, falha igual**: limite do ambiente, não da flag |
| `composer test` | 1.788 testes, 20.810 asserções, saída 0 (5 pulados, 4 depreciações) |
| Vitest (`node node_modules/vitest/vitest.mjs run`) | 60 arquivos, 664 testes, OK |
| `git diff --check` | limpo |

### Testes antigos corrigidos para a suíte fechar verde

Eram as 4 falhas registradas no BATCH-277, anteriores a este lote. Só os testes mudaram:

- `StripePaymentIntentInstallmentsTest`, `StripeAssinaturaCupomTest`, `StripeAssinaturaItensAvulsosTest`: normalizam CRLF antes de recortar `stripe.php` por `"\n}\n"` (checkout do Windows com `autocrlf`).
- `MemoryDualReq266Test`: o catálogo tem 45 skills desde a REQ-072; o teste esperava 44.

## Achado para o Arquiteto

**O gargalo de 10 a 15 minutos não vem do Tailwind no estado atual.** Com o cache quente, o pipeline completo levou 40 s e o `--skip-css` economizou ~4 s. A compilação pesa quando muitos recursos mudam: no deploy da REQ-124, 26 recursos levaram ~24 s no projeto e ~3 s no core. Os deploys longos de hoje (cerca de 10 minutos) coincidiram com milhares de arquivos divergentes no destino, depois de deploys de outras árvores no mesmo Lab: sincronização de arquivos (`rsync`) e banco (`~1.189` páginas atualizadas). Medir por etapa (com hora em cada seção) indicaria onde está o tempo antes de otimizar mais.

## Limites

- Produção não foi tocada. O `manager:update-all` não foi exercitado até o fim (sem Docker no host).
