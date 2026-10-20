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

## Rodada 2 — deploy de projeto com HTTP 500 depois da 3.0.10 (2026-10-20)

A 3.0.10 atualizou o Lab pelo painel sem erro (1.423 choques pendentes). O `project:deploy conn2flow-site-local` seguinte deu HTTP 500.

**Causa:** fatal de memória (128 MB do PHP-FPM) no `json_encode` de `instalacao_choques_registrar()`, chamado pelo deploy por API (`api_project_aplicar_arquivos`). A entrega tinha 298 arquivos do projeto editados no servidor (todos regravados em 19/10 ~16h17 por um `sync-files` avulso, fora do manifesto, com `mtime` e conteúdo diferentes do deploy das 16h01). Cada um virou choque com diff, e tudo era codificado de uma vez, em JSON *pretty*. O 1 GB da API só valia para a etapa de banco.

**Agravante:** o fatal veio depois de `instalacao_aplicar()` gravar o manifesto do projeto e antes de registrar os choques. A entrega seguinte veria "versão já entregue" e deixaria os 298 arquivos velhos sem pedir decisão.

**Defeito antigo achado junto:** a API salvava o zip enviado como `project.zip` dentro da pasta extraída, e ele entrava na entrega do projeto (copiado para a raiz do gestor e listado no manifesto).

| Arquivo | O quê |
| --- | --- |
| `gestor/bibliotecas/instalacao-manifesto.php` | `instalacao_choques_registrar()` grava em fluxo (um choque por vez, sem PRETTY_PRINT, UTF-8 inválido substituído) e de forma atômica. `instalacao_aplicar(..., ?array $registro)` registra os choques antes do manifesto; se o registro falhar, o manifesto não é gravado (`manifesto_gravado=false`). |
| `gestor/controladores/api/api.php` | Zip enviado removido após a extração; `api_memoria_minima('1024M')` antes da etapa de arquivos; registro dos choques pelo `instalacao_aplicar`. |
| `gestor/controladores/atualizacoes/atualizacoes-sistema.php` | Mesmo registro antes do manifesto na atualização do core (com reserva para a biblioteca anterior). |

**Evidências:** testes novos `testChoquesRegistradosAntesDoManifestoEEmFluxo` (com emoji no arquivo), `testRegistroDosChoquesFalhouOManifestoNaoEGravado` (e o choque volta na entrega seguinte), `testApiNaoEntregaOProprioZipEnviado`. Suítes do atualizador e da API: 292 testes, só a falha de CRLF já conhecida. PHPUnit completo: 1.892 testes, as mesmas 2 falhas de ambiente.

**Lab:** `installation/manifests/projeto.json` voltou ao de antes da tentativa (`38cdbe60`, do snapshot `api-20261020-160646-bed1`); o gravado pela tentativa está em `/root/req285-projeto.json.apos-deploy-falho`. Nenhum arquivo do projeto foi mexido. Os 20 arquivos que a tentativa sobrescreveu já estão na versão nova (o próximo deploy não os reescreve).

**Pendente:** release 3.0.11 e novo `project:deploy`. Os 298 arquivos vão virar choques "Editado no servidor", e a tela de choques não tem decisão em lote: aplicar a versão do deploy exige 298 decisões, uma a uma.

## Rodada 3 — falsos positivos do Lab e selo "v3.0.8" depois da 3.0.11 (2026-10-20)

A 3.0.11 atualizou pelo painel sem erro, mas o dashboard mostrava "Gestor v3.0.8" e havia 1.423 choques pendentes.

**Diagnóstico:**
- O core no disco estava idêntico à 3.0.11 em 1.811 dos 1.845 arquivos (os outros 32 são do projeto). Só `config.php` e `vendor/composer/installed.php` diferiam: versões velhas com CRLF, regravadas em 19/10 por uma sincronização de teste e preservadas como "editado" em três releases. O `config.php` velho trazia `versao 3.0.8` e não registrava a biblioteca `api-crud` (REQ-283).
- 316 arquivos do projeto divergiam do deploy sem nenhum choque: a regra da req-199 ("mesmo conteúdo da entrega anterior e disco diferente → nada a decidir") esconde divergências que nunca foram decididas.
- O Engenheiro Chefe confirmou: no Lab, core e site vêm do mesmo Git, então esses choques são falsos positivos e o que vale é a versão entregue.

**Saneamento (aprovado e executado pelo Engenheiro Chefe):** script com simulação, usando o motor da tela de choques. Resultado: 2 arquivos do core sobrescritos com a cópia da 3.0.11; 1.367 choques superados resolvidos; 24 "sobreposto" mantidos; 316 arquivos do projeto registrados como intactos; 25 choques "editado" do projeto marcados. Em seguida, `project:deploy conn2flow-site-local`.

**Conferência final (só leitura):** projeto 1.443/1.443 idênticos ao deploy `4b249e89`; core 1.825 idênticos à 3.0.11, com os 20 restantes sendo arquivos que o projeto reivindica (`db/data`, manifestos de assets); `config.php` em 3.0.11 com `api-crud`; `manager_updates` 3.0.11; selo do dashboard em 3.0.11 (conferido pelo Engenheiro Chefe); sem erro novo no PHP; sem trava. Restam 12 choques legítimos (registros editados online que a camada deixou de entregar e arquivos retirados pelo projeto), para decisão do operador.

**Encaminhado:** REQ-286 (branch `docs/req-286`) para artefatos de sistema fora do choque, divergência nunca escondida, decisão em lote e diff em `utf8mb4`. Relatório de aprendizado para o workspace: `conn2flow-ai-workspace/memory/reports/REP-002-req285-atualizador-choques-learnings.md`.
