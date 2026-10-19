---
id: "BATCH-292"
title: "Motor canônico de CRUD da API e RBAC — Fase 1 (REQ-283)"
status: "ready-for-review"
date: "2026-10-19"
author: "executor"
target_repo: "conn2flow"
summary_short: "Motor api-crud.php, fallback no api.php, RBAC por perfil e escopo, admin-categorias habilitado."
summary_medium: "CRUD opt-in por api.crud no manifesto (DEC-135); 7 rotas, envelope padrão e códigos 200/201/400/401/403/404/405/409/500.\nRodada 2 corrige F1–F4 da REV-001: PHPUnit 20 dirigidos (1.880 no total, sem falhas); sondas r2 verdes."
---

# BATCH-292 — Motor canônico de CRUD da API (Fase 1 da REQ-283)

- **Projeto**: conn2flow — `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`
- **Requisição**: [REQ-283](../human-requests/req-283.md) | **Branch**: `feat/req-283`
- **Topologia**: tríade | **Autonomia**: autônomo monitorado

## Escopo entregue

| Item | Onde |
| --- | --- |
| Motor (config, listar, ler, criar, PUT/PATCH, excluir, status, clonar, ciclo de vida) | `gestor/bibliotecas/api-crud.php` |
| Fallback do motor depois do hook `api` do módulo; corpo não-objeto → 400; `PATCH` e `If-Match` no CORS | `gestor/controladores/api/api.php` (`api_handle_modulo`) |
| Registro da biblioteca | `gestor/config.php` (`api-crud`) |
| Módulo padrão habilitado (`api.crud`, versão 1.0.2) | `gestor/modulos/admin-categorias/admin-categorias.json` |
| Testes | `tests/Unit/PHP/ApiCrudReq283Test.php`, `tests/Fixtures/ApiCrudHarness.php` |
| Decisão | DEC-135 (proposta) em `memory/decisions/DECISION-LOG.md` |

### Contrato

| Rota | Operação RBAC | Escopo | Sucesso |
| --- | --- | --- | --- |
| `GET /_api/{mod}/` (`page`, `per_page` ≤ 100, `sort`, `order`, `search`, `status` A/I, `language`) | listar | read | 200 `{items, pagination}` |
| `GET /_api/{mod}/{id}` (alfanumérico ou numérico) | visualizar | read | 200 |
| `POST /_api/{mod}/` | adicionar | write | 201 |
| `PUT` / `PATCH /_api/{mod}/{id}` (`versao` ou `If-Match`) | editar | write | 200; 409 em versão divergente |
| `DELETE /_api/{mod}/{id}` | excluir | write | 200 (`logica` = status D; `definitiva` apaga) |
| `POST /_api/{mod}/{id}/status` (`status` ou alterna) | status | write | 200 |
| `POST /_api/{mod}/{id}/clone` (corpo sobrepõe) | clonar | write | 201 |

Escopo `write` também aceita `mobile`; `read` aceita `write` e `mobile`. Recusas: 400 (campo fora de `api.crud.campos`, obrigatório ausente, valor não escalar, parâmetros inválidos), 401 (token), 403 (escopo ou perfil), 404 (registro, sub-rota, módulo sem `api.crud`), 405 (método), 409 (versão).

Ciclo de vida: `api.crud.hooks` aponta um arquivo do módulo com `{modulo}_api_crud_{before|after}_{create|update|delete}`. `before_*` aborta com `erro` ou troca `dados` (refiltrados pelas colunas graváveis); `status` dispara `*_update` e `clone` dispara `*_create`.

## Evidências

| Validação | Resultado |
| --- | --- |
| `ApiCrudReq283Test` (SQLite em processo à parte, funções reais de SQL do `banco.php`, zero avisos do PHP) | 13 testes, 146 asserções |
| PHPUnit completo (Windows, `OPENSSL_CONF` exportado) | 1.873 testes, 0 falhas, 9 skips. Rodada anterior: 1 falha em `AtualizacoesManifestoIntegracaoTest`, que passou isolado (5/5) e na rodada completa seguinte; o lote não toca o código dele. Sem `OPENSSL_CONF`, só o `CoreHelpersTest` conhecido. |
| Publicação no Lab: `./c2f project:sync-core conn2flow-site-local --skip-css` | saída 0; SHA-256 de `api-crud.php`, `api.php` e `admin-categorias.json` iguais no destino; `config.php` com `api-crud` |
| E2E HTTP real em `https://conn2flow.local/_api/admin-categorias/` com PATs temporários (read+write e read) | 22/22: 401 sem token e com token inválido, 201 e id `-1` em repetição, envelope, 400 (campo proibido, obrigatório, JSON inválido), paginação, leitura por slug e número, 403 de escrita com token read, PATCH, 409 com If-Match antigo, PUT, status, clone, exclusão lógica e 404 depois, 405, 404 de sub-rota e de módulo sem `api.crud` |
| `logs/php-error.log` do Lab | sem entradas novas |
| PATs do E2E | revogados (status `R`); a mesma chamada passou a 401 |

Por que só `sync-core`: a árvore do `conn2flow-site` tinha lote de outro agente (`feat/req-137`, arquivos alterados). O lote não altera banco nem CSS (Categoria A), então o pipeline completo do site levaria ao Lab o trabalho inacabado dele.

## Rodada 2 — correções da REV-001 (2026-10-19)

| Finding | Correção | Onde |
| --- | --- | --- |
| F1 RBAC | Antes da operação, `gestor_acesso(false, $modulo, $usuario)` exige o vínculo em `usuarios_perfis_modulos`; sem ele, 403 mesmo com permissão residual da operação. Operação não cadastrada continua valendo o acesso ao módulo. `gestor_acesso()` do painel não mudou. | `api_crud_despachar` |
| F2 Persistência | `banco_insert_name`, `banco_update` e `banco_delete` passam a devolver o resultado de `banco_query` (antes descartado; nenhum chamador usava o retorno `void`). O motor confere o retorno e `banco_linhas_afetadas()`: falha → 500 genérico, detalhe só no `error_log`; INSERT sem linha gravada → 500; o id relido precisa ser o gerado. Nenhum `after_*` nem 200/201 sem escrita comprovada (criar, atualizar, excluir lógica/definitiva, status, clone). | `banco.php`, `api_crud_inserir`, `api_crud_gravar`, `api_crud_excluir`, `api_crud_status` |
| F3 Concorrência | `api_crud_gravar` decide pelo UPDATE condicional: zero linhas → 409 imediato (`versao_atual` relida); falha SQL → 500. Sem versão, zero linhas (registro removido/alterado) → 409. Releitura só decide quando o driver não tem contador (modo distribuído). DELETE definitivo sem linha apagada → 409. `api_crud_gravar` agora devolve `['registro' => ...]` ou erro no contrato do hook. | `api_crud_gravar`, `api_crud_excluir` |
| F4 i18n | 26 variáveis globais `api-crud-*` em `gestor/resources/{pt-br,en}/variables.json`, com marcadores `#campo#`; consumo por `api_crud_msg()` (variável ausente devolve o id, nunca texto fixo). Mensagem nova do controlador (`api-crud-corpo-invalido`) idem. `VariaveisData.json` recompilado por `c2f resources:sync --only=variaveis` (+52 entradas, nada além). | `api-crud.php`, `api.php`, `variables.json`, `VariaveisData.json` |

Arnês (`tests/Fixtures/ApiCrudHarness.php`): `banco_query` segue o contrato real (erro → `false`), expõe `banco_linhas_afetadas()`, injeta falha de escrita (`falhar_escritas`) e escritor concorrente entre o pré-teste e o UPDATE (`antes_da_escrita`), lê as mensagens dos `variables.json` reais e, com `API_CRUD_ACESSO_REAL=1`, usa o `gestor_acesso()` real do `gestor.php`.

| Validação da rodada 2 | Resultado |
| --- | --- |
| `ApiCrudReq283Test` | 20 testes, 311 asserções, zero avisos do PHP. Novos: RBAC real sem vínculo/com vínculo/operação negada/não cadastrada; falha de escrita em POST, PATCH, PUT, DELETE lógico e definitivo, status e clone (500, sem `after_*`, banco intacto); INSERT sem linha gravada; interleaving pela rota e pela primitiva (409), UPDATE de zero linhas (409), falha SQL (500); DELETE definitivo concorrente (409); mensagens pt-br/en; todo id `api-crud-*` usado existe nos dois idiomas com os mesmos marcadores. |
| PHPUnit completo (Windows, PHP 8.5.8, `OPENSSL_CONF`) | 1.880 testes, 0 falhas, 9 skips; 4 deprecations pré-existentes dos testes de instalador (`setAccessible`). |
| Sondas originais da REV-001 | Abortam no primeiro cenário com `Review reproduction changed: RBAC`, como previsto quando o defeito some. |
| [Sondas r2](../human-reviews/evidence/req-283-probes-r2.php) ([saída](../human-reviews/evidence/req-283-probes-r2.jsonl)) | Mesmos cenários afirmando a correção: F1 403/403 e nome intacto; F3 409 pela primitiva e pela rota; zero linhas 409; F2 500 em POST/PATCH/DELETE/status sem `dados`; F4 pt-br/en. Saída 0, sem avisos. |
| `php -l` | Sem erros nos quatro PHP alterados. |

Não feito nesta rodada: publicação no Lab (as variáveis precisam do pipeline completo para chegar ao banco, não só `sync-core`) e repetição do E2E HTTP.

## Limites e pendências

- Rodada 2 sem Lab: publicar com pipeline que leve `VariaveisData.json` ao banco e repetir o E2E, incluindo perfil restrito real.
- G3 (semântica do PUT) e G4 (corpo JSON em lista) da REV-001 não foram tratados: fora de F1–F4.
- Perfil herdado: `gestor_acesso()` não tem herança de perfil; nada a cobrir no contrato atual.
- Rate limit da API vale para estas rotas (padrão 100 por hora por método e módulo, `api.rate-limit-max`). Agentes com volume alto vão precisar de ajuste de configuração.
- `banco_identificador` (legado) faz `strtolower` antes de tirar acentos: "Épsilon" vira `Epsilon` no id. Comportamento do painel, mantido.
- Teto de 10 itens: `DECISION-LOG.md` e `VALIDATION-CHECKLIST.md` passam a 11 com este lote, e as raízes de `implementation/` e `human-requests/` já estavam acima de 10. `mdd compact` não está instalado neste ambiente e `memory:archive` só cobre as pastas; compactação fica com o Arquiteto.
- Fases 2 a 5 (BATCH-293 a 296) não iniciadas.
- Revisão independente (tríade) e homologação humana pendentes. Recibo: `memory/completions/BATCH-292-receipt.json`.
