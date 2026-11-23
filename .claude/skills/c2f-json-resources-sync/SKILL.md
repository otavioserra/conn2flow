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

## Commit seletivo de partes de `*Data.json`

1. Após gerar `*Data.json` (por exemplo, `PaginasData.json`, `ComponentesData.json`, `LayoutsData.json`, `TemplatesData.json` ou `ModulosData.json`), compare a base aprovada, o stage e o arquivo gerado. Inspecione adições, mudanças, reordenações e retiradas por entrada/ID. `git status` sozinho não demonstra o escopo do diff.

2. Se houver mudanças de lotes diferentes, preserve o arquivo gerado e o stage existente. Reconstitua uma cópia a partir da base e aplique somente os registros autorizados, mantendo os demais registros e sua ordem. Use essa cópia para preparar o stage sem descartar trabalho paralelo no arquivo de trabalho. Use `git add -p -- caminho/ArquivoData.json` somente se os hunks forem independentes e o JSON no stage continuar válido.

3. Preserve `\/`, UTF-8 e os finais de linha originais (LF ou CRLF). Se serializar com Python, use `ensure_ascii=False` e restaure os escapes de barra do formato PHP. Preserve os checksums e as versões que o pipeline gerou para os itens selecionados. Nunca calcule ou ajuste checksums manualmente.

4. Antes do commit, valide a sintaxe do arquivo final e do blob do stage (`git show :caminho/ArquivoData.json`). Confira `git diff --cached -- caminho/ArquivoData.json` por ID e `git diff --cached` para todo o stage. Só prossiga se o conteúdo preparado estiver limitado ao lote.

5. Se a autoria, o escopo de algum ID ou a legitimidade de uma retirada estiverem ambíguos, pare, preserve as duas versões e consulte o humano conforme a DEC-136.

## Merge atômico: partes e manifest do mesmo lado

1. Ao resolver conflitos de merge em recursos (`pages.json`, `components.json`, `layouts.json`, `templates.json` e arquivos físicos em `resources/<idioma>/...`), os arquivos físicos (HTML/CSS/MD) e a entrada correspondente no manifest devem vir do mesmo lado (`ours` ou `theirs`).
2. É proibido misturar o corpo físico de um lado com metadados, versão ou checksum do outro.
3. Se precisar combinar alterações dos dois lados no mesmo recurso, mescle o conteúdo conscientemente, limpe os checksums afetados no manifest e deixe o pipeline oficial recalcular (`atualizacao-dados-recursos.php`). Valide o JSON e o diff antes do commit.
