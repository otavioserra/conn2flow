---
name: c2f-shell-and-windows-traps
description: "LEIA ANTES de executar comandos Docker, cURL, scripts Python/Node de edição ou chamadas POST no ambiente Windows/Git Bash. Se não ler: caminhos corrompidos por path conversion do MSYS, uploads falhando silenciosamente, heredocs com bytes de controle e formulários rejeitados pelo Gestor."
user-invocable: false
---

# Armadilhas de Shell, Windows e Git Bash no Ambiente Conn2Flow

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Executar comandos Docker (`docker exec`), cURL com upload/POST, scripts Python/Node que geram arquivos, ou chamadas POST para formulários do Gestor no ambiente Windows/Git Bash.
- **SKIP APENAS SE**: Tarefas puramente de leitura de código ou edição de arquivos sem execução de shell.
- **CONSEQUÊNCIA DE IGNORAR**: Caminhos Linux corrompidos para `C:/Program Files/Git/...`, uploads interpretados como leitura de arquivo local, heredocs com `\b`/`\s` convertidos em bytes de controle, e formulários rejeitados silenciosamente por falta de campos ocultos.

---

## ⛔ As 5 Armadilhas Críticas

### 1. Conversão Automática de Caminhos no Git Bash (MSYS Path Conversion)

**Problema**: O Git Bash no Windows converte automaticamente caminhos que começam com `/` para caminhos Windows. O comando:
```bash
docker exec conn2flow-app php /var/www/html/script.php
```
É silenciosamente corrompido para:
```bash
docker exec conn2flow-app php C:/Program Files/Git/var/www/html/script.php
```

**Solução Obrigatória**: Prefixar TODOS os comandos `docker exec` com a variável de ambiente `MSYS_NO_PATHCONV=1`:
```bash
MSYS_NO_PATHCONV=1 docker exec conn2flow-app php /var/www/html/script.php
```

> [!WARNING]
> Esta armadilha é **silenciosa** — o comando executa sem erro visível, mas o caminho dentro do container está errado. O PHP simplesmente não encontra o arquivo e retorna um erro genérico.

---

### 2. `curl` com Caractere `<` em Valores de Formulário

**Problema**: A flag `-F "campo=<valor"` do cURL interpreta o caractere `<` como operador de leitura de arquivo local. O valor não é enviado como string — o cURL tenta abrir um arquivo chamado `valor`.

**Solução Obrigatória**: Usar `--form-string` em vez de `-F` para campos que podem conter `<`:
```bash
# ❌ ERRADO — interpreta <valor como leitura de arquivo
curl -F "html=<div>teste</div>" http://localhost/api

# ✅ CORRETO — envia como string literal
curl --form-string "html=<div>teste</div>" http://localhost/api
```

---

### 3. Python Heredocs e Sequências de Escape

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

---

### 4. Asserts com Falha Silenciosa em Scripts Intermediários

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

---

### 5. Formulários do Gestor: `multipart/form-data` com Gatilhos Ocultos

**Problema**: Os formulários do Gestor Conn2Flow são `multipart/form-data` e dependem de campos ocultos obrigatórios (`_gestor-atualizar`, `_gestor-registro-id`) para acionar o processamento server-side. Enviar o formulário sem esses campos resulta em rejeição silenciosa (o POST é recebido mas nenhuma atualização é executada).

**Solução Obrigatória**: Sempre incluir os campos ocultos ao submeter formulários programaticamente:
```bash
curl --form-string "_gestor-atualizar=1" \
     --form-string "_gestor-registro-id=42" \
     --form-string "titulo=Novo Título" \
     --form-string "html=<section>conteudo</section>" \
     -b cookies.txt \
     http://localhost/gestor/modulo/registro/42
```

| Campo Oculto | Valor | Função |
|---|---|---|
| `_gestor-atualizar` | `1` | Sinaliza que o POST é uma atualização válida |
| `_gestor-registro-id` | ID numérico | Identifica o registro alvo no banco |
| `ajax` | `sim` | (Se AJAX) Previne redirecionamento e retorna JSON |
