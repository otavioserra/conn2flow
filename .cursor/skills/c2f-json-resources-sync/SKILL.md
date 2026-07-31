---
name: c2f-json-resources-sync
description: Use ao criar ou alterar recursos JSON de módulos Conn2Flow, especialmente pages, components, templates, layouts e ai_modes.
---

# Sincronização de recursos JSON

- Não calcule nem edite manualmente checksums de recursos.
- Em recurso novo, use normalmente `version: "1.0"` e checksums vazios.
- Em recurso alterado, limpe os checksums afetados; não invente hashes nem faça bumps mecânicos fora do contrato.
- O pipeline executa `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` e recalcula os metadados.
- Após testes que regeneram data files, confira `git status` e mantenha apenas artefatos do escopo.
