# BATCH-190: Módulo `documentation` no conn2flow-site

Execução da [req-186](../human-requests/req-186.md).

**Status**: `in-progress`.

## Plano

1. **Core, `c2f docs:build`:**
   - lê a configuração de `modulos/documentation/documentation.json` (chave `docs`) quando existir; senão, o `docs.config.json` antigo;
   - com `docs.module` definido, as páginas geradas levam `module` (dono no banco) e os templates são procurados primeiro nos recursos do módulo;
   - grava `modulos/<modulo>/documentation.status.json` com o resumo da geração (commit do core, data, contagens, avisos), lido pela tela do painel.
2. **Site, módulo `documentation`:** manifesto com a configuração das docs, os templates `docs-article`/`docs-sidebar` movidos para o módulo, a página administrativa `documentation/` (estado da última geração, sem regenerar), textos em variáveis, registro em `ModulosData`/`UsuariosPerfisModulosData`.
3. **Site, migração:** apagar as páginas globais `docs`/`docs-*` e os templates globais `docs-article`/`docs-sidebar` (sem módulo) antes de o pipeline inserir os do módulo; mesmas URLs.

## Commits

## Validação
