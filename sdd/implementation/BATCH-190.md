# BATCH-190: Módulo `documentation` no conn2flow-site

Execução da [req-186](../human-requests/req-186.md).

**Status**: `implemented`. Validado no Lab; falta o commit do repositório `conn2flow-site` (o stage dos ~195 arquivos foi barrado pela política de permissões da sessão e ficou para o Humano).

## Entregas

1. **Core, `c2f docs:build`:**
   - lê a configuração de `modulos/documentation/documentation.json` (chave `docs`) quando existir; senão, o `docs.config.json` antigo;
   - com `docs.module` definido, as páginas geradas levam `module` (dono no banco) e os templates são procurados primeiro nos recursos do módulo;
   - grava `modulos/<modulo>/documentation.status.json` com o resumo da geração (commit do core, data, contagens, avisos), só quando algo mudou.
2. **Site, módulo `documentation`:**
   - manifesto com a configuração das docs;
   - templates `docs-article`/`docs-sidebar` movidos para o módulo;
   - página administrativa `documentation/` (estado da última geração, sem regenerar), com os textos em variáveis;
   - registro em `ModulosData` (grupo `administracao-sistema`) e `UsuariosPerfisModulosData` (`administradores`);
   - `docs.config.json` removido.
3. **Site, migração** `20260926120000_move_docs_to_documentation_module.php`:
   - `UPDATE paginas SET modulo='documentation'` nas páginas globais `docs`/`docs-*`, preservando `id_paginas` e edições; mesmas URLs;
   - a tabela `templates` não tem coluna `modulo`: o `resources:sync` publica os templates do módulo como globais de mesmo id, e não há o que migrar. A primeira versão da migração tentava atualizá-la e falhou no Lab (`Unknown column 'modulo'`).

## Commits

- Core: `bd1b184d` (docs:build com módulo dono e status), `615170b7` (guia e referência), `7c480e5e` (correções achadas no deploy, ver [BATCH-191](BATCH-191.md)).
- Site (`feat/req-055`): **pendente**. Arquivos: `gestor/modulos/documentation/`, a migração, os templates movidos, `docs.config.json` removido, `resources/*/templates.json`, `db/data/*`, páginas e publicações regeneradas, `assets/docs/llms*.txt`.

## Validação

- PHPUnit das docs: 24 testes / 393 asserções OK (inclui `testModuloDonoConfiguracaoTemplatesEStatus`).
- `docs:build`: "Configuration: modulos/documentation/documentation.json", 320 páginas, status gravado.
- Lab `conn2flow-site-local`:
  - `project:update-all` 8/8 OK, depois de dois contornos: migração corrigida e, uma vez, `project:sync-files` seguido de `project:sync-db`, por causa da colisão em `menus` descrita no [BL-023](../backlog/BL-023-pipeline-etapa2-metadado-do-core.md);
  - banco: 184 páginas de docs em pt-br e 136 em en, todas com `modulo='documentation'` e `user_modified=0`; página `documentation` nos dois idiomas; módulo e permissão aplicados; templates `docs-*` presentes;
  - `/documentation/` (autenticado) 200, sem erros de console: 320 páginas geradas, 305 publicações, 5 páginas de entrada, 26 avisos, commit `615170b7` com link, 169 arquivos alterados, 184 páginas no banco (pt-br), 0 editadas;
  - `/en/documentation/` 200, título "Documentation", 136 páginas.
- Achados no deploy e corrigidos no [BATCH-191](BATCH-191.md): `auth:cookie` por SSH quebrado a partir do Windows, e o título da tela mostrando "Checkout" (variável de outro módulo).
