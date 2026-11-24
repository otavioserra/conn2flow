# Scripts e caminhos no Git Bash (MSYS2) — receitas (Armadilhas 3, 4, 10, 11 e 12)

Subskill de `c2f-shell-and-windows-traps`. Leia antes de gerar ou transformar arquivos por script, escrever barras invertidas num heredoc, buscar recursivamente num repositório no Windows ou reescrever o JSON de um módulo PHP. As regras críticas também estão na skill-mãe.

## 3. Python Heredocs e Sequências de Escape

**Problema**: Ao gerar arquivos com conteúdo HTML/CSS/JS usando heredocs em Python, sequências como `\b` (word boundary em regex) e `\s` (whitespace) são interpretadas como bytes de controle (backspace `0x08` e escape sequences).

**Solução Obrigatória**: Usar raw strings (`r"""..."""`) ou construir caracteres com `chr(92)` (backslash):
```python
# ❌ ERRADO — \b e \s viram bytes de controle
content = """
  border: 1px solid #ccc;
  .selector { color: red; }
"""

# ✅ CORRETO — raw string preserva literais
content = r"""
  border: 1px solid #ccc;
  .selector { color: red; }
"""
```

## 4. Asserts com Falha Silenciosa em Scripts Intermediários

**Problema**: Asserts em scripts de transformação (Python, Node) que falham silenciosamente deixam arquivos de saída corrompidos pela metade. O script continua executando após o assert falhar, gerando dados parciais.

**Solução Obrigatória**:
1. Usar `set -e` em scripts Bash (exit on first error).
2. Em Python, usar `assert` com mensagens descritivas e verificar o resultado ANTES de gravar no disco.
3. Validar o arquivo de saída após a geração (tamanho > 0, estrutura JSON/HTML válida).

```python
# ✅ Validar ANTES de gravar
result = transform(input_data)
assert result is not None, f"Transform failed for {input_file}"
assert len(result) > 100, f"Result suspiciously small: {len(result)} bytes"

with open(output_file, 'w') as f:
    f.write(result)
```

## 10. Colapso de Barra Invertida em Heredoc no Git Bash (MSYS2)

**Problema**: No Git Bash (MSYS2), heredocs em scripts Bash ou invocações inline colapsam barras invertidas duplas (`\\`) em simples (`\`). Isso corrompe silenciosamente caminhos Windows, expressões regulares e constantes PHP como `DIRECTORY_SEPARATOR`.

Exemplo do problema:
```bash
# ❌ No Git Bash, o heredoc colapsa \\ para \
cat <<'EOF' > script.php
$sep = DIRECTORY_SEPARATOR;  // OK
$caminho = "C:\\Users\\otavi";  // Vira "C:\Users\otavi" (errado)
EOF
```

**Solução Obrigatória**:
1. **Para caminhos PHP**: Use `DIRECTORY_SEPARATOR` em vez de barras invertidas literais:
```php
// ✅ CORRETO — portável e imune ao heredoc
$caminho = 'C:' . DIRECTORY_SEPARATOR . 'Users' . DIRECTORY_SEPARATOR . 'otavi';
```

2. **Para scripts gerados**: Use `printf` ou `echo` com escape explícito em vez de heredoc:
```bash
# ✅ CORRETO — printf preserva as barras
printf '%s\n' '$caminho = "C:\\\\Users\\\\otavi";' > script.php
```

3. **Para heredocs inevitáveis**: Use substituição `sed` pós-geração:
```bash
cat <<'EOF' > temp.php
$caminho = "C:__SEP__Users__SEP__otavi";
EOF
sed -i 's/__SEP__/\\\\/g' temp.php
```

> [!WARNING]
> O colapso é **silencioso** — o arquivo é gerado sem erro, mas o conteúdo está corrompido. Caminhos como `C:\Users` funcionam no Windows mas diferem do esperado `C:\\Users` no código-fonte PHP.

## 11. Timeout e Travamento de `grep -rn` na Raiz de Repositórios no Windows

**Problema**: Executar `grep -rn` (busca recursiva) a partir da raiz de um repositório no Windows/Git Bash pode causar:
- **Timeout** por varredura de `node_modules/`, `.git/`, `vendor/` e outros diretórios pesados.
- **Travamento completo** do terminal quando o volume de arquivos excede limites de I/O do MSYS2.
- **Consumo excessivo de memória** com buffers de saída não drenados.

**Solução Obrigatória**:
1. **Sempre excluir diretórios pesados** com `--exclude-dir`:
```bash
# ✅ CORRETO — exclui diretórios que causam timeout
grep -rn 'padrão' --exclude-dir={node_modules,.git,vendor,dist,build} .
```

2. **Limitar a profundidade e escopo**:
```bash
# ✅ CORRETO — buscar em diretório específico
grep -rn 'padrão' src/
grep -rn 'padrão' modulos/meu-modulo/
```

3. **Usar alternativas otimizadas** quando disponíveis:
```bash
# ✅ ripgrep (rg) respeita .gitignore automaticamente
rg 'padrão' .

# ✅ findstr no PowerShell (nativo Windows)
Get-ChildItem -Recurse -Include *.php | Select-String 'padrão'
```

4. **Timeout defensivo** para scripts automatizados:
```bash
# ✅ Limitar tempo de execução
timeout 30 grep -rn 'padrão' --exclude-dir={node_modules,.git,vendor} .
```

> [!CAUTION]
> NUNCA execute `grep -rn` na raiz de repositórios com `node_modules` ou `.git` grandes. No Windows, o MSYS2 não tem kill automático por timeout — o processo pode travar indefinidamente.

## 12. Python `json.dumps()` Remove Escapes de Barra (`\/`) de JSON de Módulos PHP

**Problema**: O PHP serializa JSON de módulos com escape de barra por padrão (`"products\/add\/"`). O `json.dumps()` do Python **não produz** escape de barra (`"products/add/"`), causando ruídos massivos no Git diff — todas as linhas com caminhos aparecem como alteradas, mesmo sem modificação semântica.

**Solução Obrigatória**:
1. **Detectar** se o arquivo original contém `\/` antes de reescrever.
2. **Restaurar** o escape após `json.dumps()`:
```python
import json

with open(json_path, 'r', encoding='utf-8') as f:
    original = f.read()
    data = json.loads(original)

# Após modificações...
output = json.dumps(data, indent=4, ensure_ascii=False)

# Restaurar escape de barra nativo do PHP
if '\\/' in original:
    output = output.replace('/', '\\/')

with open(json_path, 'w', encoding='utf-8', newline='') as f:
    f.write(output)
```

3. **Sempre usar `ensure_ascii=False`** para preservar acentos e caracteres UTF-8.
4. **Preservar line endings nativas** (LF vs CRLF) do arquivo original.

> [!WARNING]
> O impacto é **estético mas destrutivo para o workflow**: centenas de linhas alteradas no diff dificultam a revisão do Auditor de Qualidade e escondem mudanças reais em meio ao ruído.
