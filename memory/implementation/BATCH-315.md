---
id: "BATCH-315"
title: "REQ-306 — Publicação de dist/ por PHP e memória de abas UI 3.0"
status: "implemented-pending-review"
date: "2026-11-26"
author: "executor"
target_repo: "conn2flow"
request: "REQ-306"
topology: "dupla"
autonomy: "autonomo_monitorado"
summary_short: "API, atualizador e CLI compartilham a publicação de dist/. O controle de abas restaura a seleção por rota, grupo e contexto de query."
---

# BATCH-315 — Publicação de dist/ e memória de abas UI 3.0

O lote implementa a [REQ-306](../human-requests/req-306.md) no repositório `conn2flow`, em `C:/Users/otavi/OneDrive/Documentos/GIT/conn2flow`. A branch `feat/req-306-dist-base-e-abas-memoria` partiu da `main` local em `c422ef42`. Topologia dupla, autonomia `autonomo_monitorado`.

## Rodada 1 de implementação em 26 de novembro de 2026

- `dist_publicar_completo()` combina a allowlist existente com `recursos_dist_mapear_fonte()`, cria o diretório e o `.htaccess` e publica alterações por SHA-1. O manifesto e os arquivos são substituídos atomicamente. Uma trava por destino serializa publicadores concorrentes. Uma nova execução corrige um asset ausente e preserva o mtime do que já está atualizado.
- O endpoint `/_api/project/update` publica a partir do Core instalado com o overlay aplicado e retorna `data.installation.dist`. O atualizador chama a mesma biblioteca após a verificação pós-entrega. Em modo `development-env`, a publicação usa o JS de autoria. A publicação automática preserva órfãos conforme DEC-136.
- `AssetsPublishCommand` delega seleção, gravação e manifesto à biblioteca. A limpeza local explícita alcança órfãos gerenciados cujo conteúdo ainda coincide com o manifesto. A publicação SSH manual continua disponível sob confirmação explícita. O deploy por API registra que quem publica o `dist/` é o servidor de destino.
- `data-c2f-abas` resolve `?tab=` e depois o hash antes da sessão e do padrão no HTML. A chave usa rota e grupo, com escopo opcional por parâmetro ou pela query sem `tab`. A restauração notifica `c2f:mudou`, os grupos aninhados têm seleção independente e o opt-out controla o acesso ao storage. `admin-atualizacoes` passou da memória própria em `localStorage` para a política de sessão do componente.

## Evidências

| Verificação | Resultado |
| --- | --- |
| PHPUnit focado em `DistPublicacaoTest`, `ProjectUpdateDistTest`, `AssetsDistReq028Test`, `ProjectAssetsReq264Test`, `ProjectSshPublicPathReq050Test` e `InstalacaoManifestoTest` | 76 testes e 388 asserções, com quatro depreciações de metadados do runner já presentes na base |
| PHPUnit completo no Windows com PHP 8.5.8 | 2.102 testes e 22.441 asserções. O comparativo JUnit por classe, método e tipo de resultado reproduziu na `main` os mesmos 21 casos de falha, um erro RSA/OpenSSL e nove skips. A base executou 2.097 testes |
| HTTP local com `ProjectUpdateDistTest` | Dois POSTs multipart reais no controlador da API criaram e atualizaram o manifesto com comandos externos desabilitados no servidor PHP de teste. A finalização web do atualizador criou o manifesto em outro DocumentRoot. A fixture usa dublês de OAuth, banco e sitemap e desativa a sondagem de saúde |
| Vitest | 725 testes em 65 arquivos. Os testes de abas verificam remontagem, retorno à rota, teclado, chave explícita, índice, parâmetro, query, grupos aninhados, deep link, fallback e storage bloqueado |
| Artefatos JS | `assets:minify` regenerou dois arquivos e `assets:minify --verificar` encontrou zero derivados desatualizados. Os tokens de versão do sistema, da interface e de `admin-atualizacoes` foram recalculados |
| Sintaxe e diff | Lint PHP, `bash -n` para os dois scripts e `git diff --check` passaram |

O verificador global de versões de assets parou em `admin-arquivos.json` tanto no lote quanto na `main`. Os tokens foram recalculados apenas para os assets alterados. O teste HTTP cobre o controlador e o filesystem em ambiente descartável. Apache, Nginx e o fluxo completo do atualizador contra um banco real ficam para homologação.

## Auditoria de abas do Core

`admin-atualizacoes` e `forms-submissions-view` usam `data-c2f-abas` nas duas versões de idioma. A primeira tinha memória própria, removida para respeitar o deep link. A segunda recebe a memória por rota e grupo. Se a tela precisar isolar a seleção por submissão, pode declarar o parâmetro no contêiner. `admin-environment` e as telas de inclusão e edição de `usuarios-perfis` ainda usam `data-tab` e são candidatas a adotar o componente.

## Recibo ao Macro-Arquiteto

Escopo implementado e validado no recorte local. Commits de código `829c63c6` e `179341d1`. Consulte também o [checklist do lote](../validation/VALIDATION-CHECKLIST.md#batch-315--req-306). A ação seguinte é revisar `git diff main...feat/req-306-dist-base-e-abas-memoria` e encaminhar a homologação. O MCP Hub ficou indisponível nesta sessão. O handoff foi registrado neste relatório. Push e deploy ficam sob controle do operador.

Para o relatório REQ-081, o alias `c2f-executor-agent` consta no guia do Executor sob o nome atual `c2f-persona-executor`. Alinhar o nome no catálogo do repositório reduziria a busca inicial. A comparação de JUnit registrou as falhas de base no Windows e a publicação foi conferida com a mesma entrada HTTP usada no deploy.
