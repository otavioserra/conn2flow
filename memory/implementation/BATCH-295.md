---
id: "BATCH-295"
title: "Choques sem falsos positivos: artefatos de sistema, divergência nunca escondida, superados, decisão em lote e lista paginada (REQ-286)"
status: "implemented-pending-homologation"
date: "2026-10-20"
author: "executor"
target_repo: "conn2flow"
summary_short: "Artefatos de sistema (vendor, db/data, *.precompiled.css, *.min.js, manifestos de assets) e o config.php do core passam a ser sempre escritos pela camada dona; divergência só fica de lado quando foi decidida ou está pendente na tela; CRLF não é edição; choques superados saem sozinhos; decisão em lote com confirmação; lista com filtro de pendentes e paginação; tabela em utf8mb4 e falha de INSERT no log."
---

# BATCH-295 — REQ-286

- Branch `feat/req-286` (base `main` `54412d61`, com a 3.0.11).
- Requisição: [req-286.md](../human-requests/req-286.md). Decisão do Engenheiro Chefe: `config.php` do core sempre escrito. Regra de base: DEC-136 (nada sai sem confirmação).

## Live Todo

- [x] Motor: artefatos de sistema sempre escritos; sobreposto de artefato com choque já resolvido (`automatico`).
- [x] `config.php` em `INSTALACAO_CORE_SEMPRE_ESCREVER`.
- [x] Divergência nunca escondida: a regra da req-199 só pula com decisão (`manter`/`mesclar`) ou choque pendente (`pendente`) para aquele disco; disco com a versão da camada de baixo é reescrito pela de cima.
- [x] CRLF não é edição (comparação normalizada para extensões de texto).
- [x] `db/.c2f-migrations-*.json` fora do manifesto.
- [x] Choques superados resolvidos sozinhos depois de cada entrega (atualização e deploy).
- [x] Decisão em lote (biblioteca, AJAX, tela) com confirmação.
- [x] Lista abre com os pendentes, filtro "Todos", paginação de 25; retirada explicada no detalhe.
- [x] Migração `atualizacoes_choques` → utf8mb4; INSERT que falha não marca o pendente como gravado e vai para o log.
- [x] Testes; simulação do motor novo contra Lab e produção (só leitura).
- [ ] Release e validação pelo painel (Engenheiro Chefe).

## Mudanças

| Arquivo | O quê |
| --- | --- |
| `gestor/bibliotecas/instalacao-manifesto.php` | `instalacao_artefato_sistema()` (prefixos `vendor/`, `db/data/`; sufixos `.min.js`, `.precompiled.css`; `assets/asset-versions.json`, `assets/modulos/covers/manifest.json`, `resources/.tailwind-build-manifest.json`); `config.php` no sempre-escrever do core; `instalacao_hash_disco_lf()`; regra da req-199 só com decisão/pendência; regra `mesclar` gravada; marca `pendente` ao registrar choque editado; `instalacao_fora()` com `db/.c2f-migrations-*`; `instalacao_choque_superado()`. |
| `gestor/bibliotecas/atualizacoes-choques.php` | `atualizacoes_choques_resolver_superados()`, `atualizacoes_choques_resolver_lote()`, `atualizacoes_choques_entregas_pendentes()`; não recarrega a biblioteca do manifesto se o atualizador já carregou a do staging. |
| `gestor/controladores/api/api.php` | INSERT do choque devolve o resultado real (antes: sempre `true`, e o choque sumia); falhas no log; superados depois de gravar. |
| `gestor/controladores/atualizacoes/atualizacoes-sistema.php` | Falhas de INSERT no log da execução; superados depois de gravar. |
| `gestor/db/migrations/20261020180000_alter_atualizacoes_choques_utf8mb4.php` | `CONVERT TO CHARACTER SET utf8mb4` (só MySQL/MariaDB). |
| `gestor/modulos/admin-atualizacoes/admin-atualizacoes.php` / `.js` / `.json` | Filtro pendentes/todos, paginação, barra de decisão em lote, aviso no detalhe de retirada, aba de choques reaberta pela URL; 20 variáveis pt-br/en; módulo 1.0.8 → 1.0.9. |

## Evidências

- Testes novos: `InstalacaoManifestoTest` (artefatos sempre escritos, sobreposto automático, `config.php`, divergência nunca decidida, disco com a versão do core em arquivo do projeto, CRLF, controle de migrações) e `ChoquesLoteReq286Test` (arnês em processo à parte com SQLite: superados, entregas pendentes, lote passando pelo motor, tela e variáveis).
- PHPUnit completo: 1.905 testes; só as 2 falhas de ambiente do Windows (OpenSSL; CRLF do `api.php` no checkout). Vitest 700/700.
- Simulação do motor novo (só leitura), reentregando o que cada camada entregou por último:
  - Lab: projeto sem nada a fazer; core com 20 arquivos do projeto preservados e choque automático (0 pendentes novos).
  - Produção: o próximo deploy do projeto grava os 10 arquivos escondidos (`ModulosData`, `ModulosGrupos`, `ModulosOperacoes`, `UsuariosPerfis*`, `WidgetsData`, `CronTarefasData`, manifestos de assets); core com 10 automáticos.

## Produção (conn2flow.com), mesma sessão

- O defeito da 3.0.9 não chegou: produção foi 3.0.8 → 3.0.11.
- Formulário de contato vazio: o deploy `a3dbc89c` preservou 4 `db/data` do projeto como "editado" (template `conn2flow-form-contacts` fora do banco). Com aprovação do Engenheiro Chefe, decisão "sobrescrever" pelo motor (script `prodfix285.php`) e novo `project:deploy`: `templates +2`, formulário de volta nos dois idiomas.
- O redeploy criou 104 choques de registro "retirado" (arquivos de dados velhos do disco traziam chaves do core). Nada foi desativado (DEC-136). Para decidir com a barra de lote ("Manter todos").

## Pendências

- Release e atualização pelo painel; no Lab e na produção, conferir a aba de choques (pendentes, lote, paginação).
- Produção: o próximo deploy do projeto vai gravar os 10 arquivos de dados escondidos. O Lab roda com esses mesmos arquivos do projeto; mesmo assim, conferir o painel depois do deploy.
- REQ-287 (etapas visíveis no deploy) registrada.

## Rodada 2 — produção na 3.0.12 (2026-10-20)

Relato do Engenheiro Chefe: 115 choques pendentes; filtro e páginas da lista recarregavam a tela inteira e voltavam ao topo, sem a aba de choques.

**Achados na produção (só leitura):**
- **O `admin-atualizacoes.min.js` servido era o de antes da REQ-285.** O painel usa o `.min.js` fora do ambiente de desenvolvimento, o pipeline da release não roda `assets:minify` e ninguém regenerava o derivado. A decisão em lote, a reabertura da aba e o "HTTP" nas falhas de etapa nunca chegaram ao navegador.
- **`admin-categorias.min.js` com código removido:** a fonte foi esvaziada na req-223 (saída do Fomantic); o minificador "falhava" com fonte vazia e o `.min.js` antigo (com `.dropdown()`/`.popup()` do Fomantic) seguia sendo servido.
- O `--verificar` acusava 12 desatualizados no Windows: o hash era do conteúdo cru (CRLF). Só os dois acima estavam velhos de fato.
- **"MySQL server has gone away"** nos dois deploys do projeto (14h45 e 15h32): a conexão do gestor ficava parada durante dump e sincronização e o MariaDB de produção tem `wait_timeout` de 10 s. Hooks ficaram completos (125), mas gravações depois da etapa de banco podiam falhar.
- Pendentes: 104 propostas de retirada de registro (`db:prompts_ia`, `db:modos_ia`, `db:alvos_ia`…), 7 "sobreposto" e 3 "editado" anteriores à 3.0.12, 1 retirada antiga. A 3.0.12 já resolveu 28 superados e 9 automáticos.
- Versão 3.0.12, módulos/perfis íntegros, formulário de contato corrigido (REQ-144 do site, teste do Engenheiro Chefe).

| Arquivo | O quê |
| --- | --- |
| `cli/src/Commands/AssetsMinifyCommand.php` | Hash sem diferença de CRLF; fonte vazia gera derivado vazio. |
| `gestor/modulos/admin-atualizacoes/admin-atualizacoes.js` (+ `.min.js`) | Aba de choques sem recarregar: filtro, páginas e lote trocam só o conteúdo do painel (selo de pendentes junto); entrada direta com `?choques=` abre a aba. Módulo 1.0.10. |
| `gestor/modulos/admin-categorias/admin-categorias.min.js`, `gestor/assets/minify-manifest.json` | Derivados regenerados (manifesto com hash normalizado). |
| `gestor/controladores/api/api.php` | `SET SESSION wait_timeout = GREATEST(..., 28800)` no deploy de projeto. |
| `tests/Unit/PHP/MinifyDerivadosReq286Test.php` | Reprova `.min.js` desatualizado no `composer test` (o CI da release roda antes de empacotar). |
| `tests/Unit/PHP/InstalacaoManifestoTest.php` | String do hash com quebra de linha literal (quebrava no checkout CRLF) trocada por `\n`. |

**Evidências:** `assets:minify --verificar` 0 desatualizados; PHPUnit 1.907 (só as 2 falhas de ambiente); Vitest 700/700.

**Depois da próxima release:** na produção, "Decidir em lote" → "Todos os pendentes" → "Manter todos" resolve os 115 (os "sobreposto" aceitam manter; nada é removido).

## Rodada 3 — choque órfão do controle de migrações (2026-10-20)

Produção na 3.0.14: "Manter todos" resolveu 114 de 115; falhou o choque de retirada `db/.c2f-migrations-projeto.json` (deploy `a3dbc89c`). Causa: a REQ-286 tirou esse arquivo de controle das entregas (`instalacao_fora()`), e o resolvedor passou a recusá-lo como "caminho inválido". Correção: "manter" vale para os arquivos de controle `db/.c2f-migrations-*.json` (não mexe no disco nem grava regra; remover continua recusado) e a passada de superados os resolve sozinha. Teste `testChoqueAntigoDeCaminhoForaDasEntregas`; PHPUnit 1.911 (só as 2 falhas de ambiente). Na produção o choque se resolve na próxima entrega (passada de superados) ou com "Manter" depois da release.
