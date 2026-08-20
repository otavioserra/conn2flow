---
name: c2f-json-resources-sync
description: "LEIA ANTES de editar *Data.json ou rodar a compilação/sincronização de recursos. Se não ler: checksums inválidos impedem o deploy, recursos do banco não atualizam e alterações locais são sobrescritas."
user-invocable: false
---

# Sincronização de recursos JSON Conn2Flow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Editar manifests JSON de recursos (`pages.json`, `components.json`, `layouts.json`, `templates.json`) ou executar o pipeline de compilação para `*Data.json`.
- **SKIP APENAS SE**: Edição de código PHP de controladores sem alteração na estrutura de recursos.
- **CONSEQUÊNCIA DE IGNORAR**: Divergência entre arquivos físicos e os dados no banco SQL (`Data.json`), falhas no cálculo de checksums e perda de alterações após deploy.

---

- Não calcule nem edite manualmente checksums de recursos.
- Em recurso novo, use a versão inicial exigida pelo manifesto, normalmente `1.0`, e deixe checksums como string vazia.
- Em recurso alterado, limpe os checksums afetados; não invente hashes nem faça bumps mecânicos fora do contrato do projeto.
- O pipeline de atualização/deploy executa `gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php` e recalcula versões/checksums.
- Após testes que regeneram data files, confira `git status` e mantenha apenas artefatos que pertencem ao escopo aprovado.
