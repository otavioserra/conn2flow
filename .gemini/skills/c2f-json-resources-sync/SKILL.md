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

---

## Preservação de Barras Escapadas (`\/`) em JSON de Módulos

O PHP serializa JSON de módulos com escape de barra por padrão (`"products\/add\/"`). Ao manipular esses arquivos com ferramentas externas (Python, Node), o escape pode ser perdido, gerando ruídos massivos no Git diff.

### Diretriz Normativa

Ao ler e reescrever arquivos JSON de módulos via Python:

```python
import json

# Ler preservando encoding
with open(json_path, 'r', encoding='utf-8') as f:
    original = f.read()
    data = json.loads(original)

# Modificar dados...
data['nova_chave'] = 'valor'

# Reescrever PRESERVANDO escapes e line endings
output = json.dumps(data, indent=4, ensure_ascii=False)

# Restaurar escape de barra (PHP padrão)
if '\\/' in original:
    output = output.replace('/', '\\/')

# Preservar line endings nativas (LF vs CRLF)
if '\\r\\n' in original:
    output = output.replace('\\n', '\\r\\n')

with open(json_path, 'w', encoding='utf-8', newline='') as f:
    f.write(output)
```

| Requisito | Implementação |
|---|---|
| **Escape de barra** | `output.replace('/', '\\/')` quando o original contém `\\/` |
| **Encoding** | `ensure_ascii=False` para preservar acentos e caracteres UTF-8 |
| **Line endings** | Detectar e manter LF ou CRLF nativo do arquivo original |

> [!CAUTION]
> Sem a preservação do escape de barra, o `git diff` aponta alterações artificiais em **todas as linhas** do arquivo que contêm caminhos com `/`, poluindo o histórico e dificultando revisões.
