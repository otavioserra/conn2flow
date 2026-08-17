---
name: c2f-json-resources-sync
description: Use ao criar ou alterar recursos JSON de módulos Conn2Flow, especialmente pages, components, templates, layouts e ai_modes.
user-invocable: false
---

# Sincronização de recursos JSON Conn2Flow

- Não calcule nem edite manualmente checksums de recursos.
- Em recurso novo, use a versão inicial exigida pelo manifesto, normalmente `1.0`, e deixe checksums como string vazia.
- Em recurso alterado, limpe os checksums afetados; não invente hashes nem faça bumps mecânicos fora do contrato do projeto.
- O pipeline de atualização/deploy executa `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` e recalcula versões/checksums.
- Após testes que regeneram data files, confira `git status` e mantenha apenas artefatos que pertencem ao escopo aprovado.
