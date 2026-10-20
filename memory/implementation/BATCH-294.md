---
id: "BATCH-294"
title: "Atualização pelo painel parava no finalize: a etapa de banco lia os dados do projeto e a retirada desativava o core (REQ-285)"
status: "implemented-pending-homologation"
date: "2026-10-20"
author: "executor"
target_repo: "conn2flow"
summary_short: "O 404 do finalize era consequência: a etapa de banco leu db/data do projeto como se fosse do core e a retirada automática desativou ou apagou 1.069 registros do core, inclusive a página do admin-atualizacoes. A etapa passa a ler o que o core entregou, nada sai sem confirmação do operador (DEC-136) e o Lab foi reparado pelo dump do snapshot."
---

# BATCH-294 — REQ-285

- Branch `feat/req-285` (base `main` `de61821f`, release 3.0.9).
- Requisição: [req-285.md](../human-requests/req-285.md). Decisão: [DEC-136](../decisions/DECISION-LOG.md).
- Topologia `dupla`, autonomia `autonomo_monitorado`. Sem commit nem release neste lote: o Engenheiro Chefe gera a release e refaz a atualização pelo painel.

## Live Todo

- [x] Logs reais do Lab (`conn2flow.local`, sessão `881b1016fe1c9b0c`, exec 4).
- [x] Causa raiz identificada (abaixo).
- [x] Etapa de banco lê os dados entregues pelo core (`instalacao_core_dados_dir()`).
- [x] Manter por padrão: retirada de registro e de arquivo vira choque `retirado`; só `--confirmar-retirada` remove direto.
- [x] `finalize` com a mesma proteção das outras etapas; tela mostra o HTTP da falha.
- [x] Testes (PHPUnit e Vitest).
- [x] Reparo do Lab sem apagar nada (dump do snapshot).
- [ ] Release do core e nova atualização pelo painel (Engenheiro Chefe).

## Causa raiz

O diagnóstico preliminar (timeout, memória, sessão) não se confirmou. O log da sessão termina em `WebDatabase: concluído`: o `finalize` nunca entrou em `webFinalize()`. O nginx registrou o POST com **HTTP 404 e 39 bytes** (`{"error":"404","info":"JSON not found"}`, do roteador). A página `admin-atualizacoes/` estava `status='D'` desde 12:27:38, durante a etapa de banco.

1. O projeto instalado no Lab grava no `projeto.json` os mesmos `db/data/*Data.json` do core.
2. A atualização do core preservou os 21 arquivos do projeto (choque "sobreposto, dono projeto") e guardou os seus em `backups/overrides/gestor-v3.0.9/`. Em `paginas`, o projeto ainda deixou o conjunto particionado (`PaginasData.manifest.json` + partes, 1.217 registros), que tem precedência sobre o `PaginasData.json` do core (274).
3. A etapa de banco leu `db/data/`, ou seja, os dados do projeto, como se fossem do core.
4. A retirada por dono (REQ-199) viu as chaves do core "saírem" e agiu sozinha: 1.069 registros com `status='D'` (269 páginas, 72 módulos, 243 componentes, 244 templates...) e **3.568 linhas apagadas** de tabelas sem `status` (3.524 `variaveis`, 39 `usuarios_perfis_modulos`, 5 `usuarios_perfis_modulos_operacoes`).
5. Sem a página do próprio módulo, o POST do `finalize` caiu no 404 → `Falha finalize`.

## Mudanças

| Arquivo | O quê |
| --- | --- |
| `gestor/bibliotecas/instalacao-manifesto.php` | `instalacao_core_dados_dir()`: com conflito em `db/data/` (arquivo do core desviado para `overrides/` ou particionado que encobre o do core), monta uma pasta só com os dados que o core entregou; sem conflito, `null`. `instalacao_planejar(..., $retirarConfirmado = false)`: arquivo intacto que saiu da entrega vira choque `retirado`. Ações `sobrescrever`/`manter`; `sobrescrever` remove. |
| `gestor/controladores/atualizacoes/atualizacoes-recursos-retirada.php` | Sem `--confirmar-retirada`, registro que saiu vira choque `retirado` (tipo `registro`, `pendentes` no log); nada de `status='D'` nem `DELETE`. Reativação da REQ-206 mantida. |
| `gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php` | Aceita `CLI_OPTS['data-dir']`. |
| `gestor/controladores/atualizacoes/atualizacoes-sistema.php` | `atualizacoes_core_dados_dir()` passa a pasta à etapa de banco (CLI e web; a web recupera a `release_tag` da sessão). `finalize` com `atualizacoes_web_recursos()` e trava liberada no fatal. Flag `--confirmar-retirada`. |
| `gestor/modulos/admin-atualizacoes/admin-atualizacoes.js` / `.json` | Falha de deploy, banco e finalize mostra o HTTP. Rótulos pt-br/en do motivo `retirado`. Módulo 1.0.7 → 1.0.8. |

## Evidências

- PHPUnit completo: 1.889 testes; restam 2 falhas de ambiente do Windows, fora do lote: `CoreHelpersTest` (OpenSSL sem `openssl.cnf`) e `ManutencaoReq210Test` (o `api.php`, não tocado, está com CRLF no checkout do Windows).
- Suítes do atualizador (`Atualiz|Instalacao|Recursos|DbData|Manifesto|Choque`): 234 testes, 4.783 asserções, verdes.
- Testes novos: `testSemConfirmacaoNadaSaiEViraChoqueRetirado`, `testEntregaComDadosDeOutroDonoNaoDesativaNada` (incidente reproduzido), `testRetiradaSemConfirmacaoFicaEViraChoque` (com a confirmação do operador removendo depois), `testDadosDoCoreQuandoOProjetoOcupaDbData`, `testDadosDoCoreSemConflitoLeDbData`. Testes antigos que afirmavam remoção automática passaram a exercitar o caminho confirmado.
- Vitest: 700/700.
- Função nova contra a instalação real do Lab: a etapa leria 274 páginas, 72 módulos e 3.576 variáveis do core (as 4 páginas do `admin-atualizacoes` presentes), sem o particionado do projeto.

## Reparo do Lab (conn2flow.local)

Feito a pedido do Engenheiro Chefe, sem apagar nada e sem `update-all`:

1. Backup do banco no estado quebrado: `/root/req285-admin_conn2flow-antes-reparo.sql.gz` (no `ssh lab`).
2. Dump do snapshot (`backups/atualizacoes/snapshots/exec-4/banco.sql.gz`, gravado antes da etapa de banco) carregado no banco auxiliar `req285_restore`.
3. Status de volta ao do dump onde o ar tinha `D` e o dump não: 1.069 registros em 13 tabelas. Linhas cuja PK sumiu reinseridas: 3.524 + 39 + 5.
4. `installation/manifests/recursos-core.json` regravado com as chaves do core 3.0.9 (estava com as do projeto); anterior em `/root/req285-recursos-core.antes.json`.
5. Trava presa movida para `/root/req285-deploy.lock.preso`.

Resultado: módulos `A=154 / D=4` e 9.386 variáveis, iguais ao dump; home 200; `/admin-atualizacoes/` e `/dashboard/` redirecionam para o login (antes: 404). Os arquivos seguem os da 3.0.9 (a etapa de arquivos estava correta). Ficaram no Lab: o banco auxiliar `req285_restore` e os 1.386 choques pendentes da execução 4 em `atualizacoes_choques` (vários são do conflito acima e podem ser marcados como mantidos).

## Pendências

- Release do core com este lote e nova atualização do Lab pelo painel (Engenheiro Chefe).
- Projeto que entrega `db/data` com os nomes do core continua sendo a raiz estrutural (já registrada na REQ-284). Aqui o core passou a não ler esses arquivos; a separação física das pastas fica para uma requisição própria.
