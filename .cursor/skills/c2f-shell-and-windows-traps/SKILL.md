---
name: c2f-shell-and-windows-traps
description: "LEIA ANTES de executar comandos Docker, cURL, scripts Python/Node de edição ou chamadas POST no ambiente Windows/Git Bash. Se não ler: caminhos corrompidos por path conversion do MSYS, uploads falhando silenciosamente, heredocs com bytes de controle, concorrência travando processos PHP e mascarando warnings e formulários rejeitados pelo Gestor."
user-invocable: false
---

# Armadilhas de Shell, Windows e Git Bash no Ambiente Conn2Flow

## Chrome DevTools MCP: perfil e processos no Windows

- Use o lançador da matriz `conn2flow-ai-workspace/scripts/mcp/launch-devtools-chrome.ps1` com `-Headless` (padrão) ou `-Visible`. Ele mantém o perfil em `%TEMP%\conn2flow-chrome-sandbox`, CDP loopback e porta 9222; nunca reutilize o perfil pessoal, copie credenciais/histórico ou acrescente `--no-sandbox`.
- Para conectar o MCP ao lançador, acrescente `--browser-url=http://127.0.0.1:9222` na sessão. Sem essa opção o servidor inicia seu próprio Chrome. Verifique `/json/version` antes de tentar conectar; porta ocupada não comprova que a instância pertence ao sandbox.
- Em erro de porta/perfil em uso, consulte `Get-NetTCPConnection -LocalPort 9222 -State Listen` e `Get-CimInstance Win32_Process` pelo PID/propriedade `CommandLine`. Encerre apenas processos cuja linha de comando aponta para o sandbox ou o PID retornado pelo lançador. Nunca use `Stop-Process -Name chrome` ou `taskkill /IM chrome.exe`.
- Caminhos com espaços exigem aspas no argumento `--user-data-dir`. Não apague o perfil enquanto houver processo proprietário; qualquer limpeza deve verificar caminho absoluto dentro de TEMP e recusar junctions/symlinks. O lançador não faz remoção recursiva automática.
- Antes de usar `npx ...@latest`, confira requisitos de Node do pacote. A versão 1.10.1 exige `^20.19.0 || ^22.12.0 || >=23`; Node 20.14 falha antes do handshake. Node 22 temporário via `npm exec --yes --package=node@22 --package=chrome-devtools-mcp@latest -- chrome-devtools-mcp --help` permite diagnosticar sem alterar o Node global; configure um runtime compatível no PATH do cliente para uso persistente.

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Executar comandos Docker (`docker exec`), cURL com upload/POST, scripts Python/Node que geram arquivos, ou chamadas POST para formulários do Gestor no ambiente Windows/Git Bash.
- **SKIP APENAS SE**: Tarefas puramente de leitura de código ou edição de arquivos sem execução de shell.
- **CONSEQUÊNCIA DE IGNORAR**: Caminhos Linux corrompidos para `C:/Program Files/Git/...`, uploads interpretados como leitura de arquivo local, heredocs com `\b`/`\s` convertidos em bytes de controle, concorrência travando processos PHP e engolindo warnings vitais, e formulários rejeitados silenciosamente por falta de campos ocultos.

---

## ⛔ As 17 Armadilhas Críticas

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

O mesmo mecanismo quebra transportes SSH com `rsync`. O MSYS2 converte uma origem local como
`/c/Users/...` para `C:/Users/...`; como o `rsync` interpreta `C:` como especificação de host, um
destino `usuario@host:/caminho` faz ambos os lados parecerem remotos e produz
`The source and destination cannot both be remote`.

**Solução obrigatória para rsync**: toda invocação deve usar `MSYS_NO_PATHCONV=1`, diretamente ou
por helper compartilhado:
```bash
MSYS_NO_PATHCONV=1 rsync -avu "/c/Users/.../origem/" "usuario@host:/destino/"
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

---

### 6. Paralelismo Concorrente em Comandos de Compilação em Lote (Supressão de Warnings PHP)

**Problema**: Executar múltiplos comandos pesados de compilação, sincronização ou banco simultaneamente (`css:rebuild`, `resources:sync`, `project:update-all`, `manager:update-all`, `db:migrate`) no mesmo ambiente Windows/Docker gera disputas de I/O, trava o container e mascara erros de runtime críticos. Quando executados em background ou com buffers suprimidos, warnings e notices do PHP não chegam ao terminal.

*Caso Real Documentado*: Um bug na assinatura de método (`$fontesExtras` sem parâmetro declarado) permaneceu invisível por horas no Core — as classes Tailwind declaradas em `tailwind_sources` eram descartadas silenciosamente e o terminal não exibia nenhum erro porque o comando rodava em segundo plano com buffer engolindo os avisos do PHP.

**Solução Obrigatória**:
1. **Execução Sequencial Exclusiva**: NUNCA execute dois comandos de compilação ou pipeline em paralelo no mesmo container. Execute um de cada vez, aguardando o término (`exit code 0`).
2. **Foreground Obrigatório**: Mantenha os comandos rodando em foreground com saída direta no terminal.
3. **Sem Buffer / Expor Warnings**: Não use redirecionamentos cegos (`> /dev/null 2>&1`). Se um warning do PHP for disparado (ex: `Undefined variable`, `ArgumentCountError`), ele DEVE aparecer no terminal para resolução imediata.

---

### 7. `rsync: dup() in/out/err failed` — Pareamento de Runtimes cwRsync/SSH

**Problema**: O `rsync` 3.4.x do pacote cwRsync (runtime Cygwin) usa pipes Win32 nativos. Quando o `ssh.exe` invocado vem do Git Bash (runtime MSYS2), os descritores não são compatíveis e o processo morre com:
```
rsync: dup() in/out/err failed
rsync error: error in IPC code (code 14) at pipe.c(...)
```
O exit code 12 não produz mensagem legível sem `-v`.

**Solução Obrigatória**:
1. **Pareamento mandatório de runtimes**: Use o `ssh.exe` do próprio pacote cwRsync (`C:\cwrsync\bin\ssh.exe`), nunca o do Git Bash.
2. **`-e` com flags SSH explícitas**: `-e "C:/cwrsync/bin/ssh.exe -T -i <chave>"`. A flag `-T` bloqueia pseudo-TTY (o protocolo binário do rsync não funciona com PTY).
3. **`MSYS_NO_PATHCONV=1`** continua obrigatório (mesma razão da Armadilha 1).
4. **Caminhos locais**: Converter para `/cygdrive/c/...` (formato Cygwin), nunca `/c/...` (MSYS2).

```bash
MSYS_NO_PATHCONV=1 rsync -avz \
  -e "C:/cwrsync/bin/ssh.exe -T -i $HOME/.ssh/id_ed25519" \
  "/cygdrive/c/Users/otavi/projeto/src/" \
  "usuario@host:/opt/projeto/src/"
```

> [!WARNING]
> O erro `dup()` aparece SOMENTE com o `ssh.exe` errado. A mensagem não menciona SSH — o diagnóstico natural é culpar o rsync ou as permissões.

---

### 8. Sequências ANSI em Saídas de Utilitários CLI (Cores Quebram Parsers)

**Problema**: Utilitários multiplataforma (Tailwind CLI, Vite, ESBuild) emitem sequências de cor ANSI mesmo quando capturados por `proc_open()`, `exec()` ou backticks. A saída de versão:
```
tailwindcss \x1b[34mv4.3.3\x1b[39m
```
quebra comparadores de versão (`version_compare()`, `semver.satisfies()`) e asserções em testes.

**Solução Obrigatória**:
1. **Variáveis de ambiente**: Definir `NO_COLOR=1` e/ou `FORCE_COLOR=0` antes de invocar o processo.
2. **Higienização mandatória** antes de qualquer parsing ou comparação de versão:
```php
$limpo = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $saida_bruta);
```
```javascript
const limpo = saidaBruta.replace(/\x1b\[[0-9;]*[a-zA-Z]/g, '');
```
3. **Asserts/testes**: Sempre higienizar ANTES de comparar, nunca confiar que a saída é texto puro.

> [!WARNING]
> A contaminação é **invisível** no terminal (que renderiza as cores) mas **quebradora** em strings capturadas. `"v4.3.3" !== "\x1b[34mv4.3.3\x1b[39m"` falha sem mensagem explicativa.

---

### 9. `cd` Antes de `sudo -u` em Tenants SSH Restritos (HestiaCP e Similares)

**Problema**: Em servidores multi-tenant (HestiaCP, cPanel, Plesk) com diretórios home em modo `750` ou `700`, o usuário SSH de deploy não tem permissão para entrar no diretório do tenant antes da elevação de privilégios:
```bash
# ❌ FALHA — cd executa como o usuário SSH, que não tem acesso a /home/tenant/
cd /home/tenant/web/dominio.com && sudo -u tenant php artisan migrate
```

**Solução Obrigatória**: Encapsular a troca de diretório e o comando na mesma shell elevada:
```bash
# ✅ CORRETO — cd e php executam ambos como `tenant`
sudo -u tenant sh -c 'cd /home/tenant/web/dominio.com && php artisan migrate'
```

> [!WARNING]
> O erro é `Permission denied` no `cd`, não no `sudo`. O diagnóstico natural é culpar a configuração do sudo, mas o problema está na ordem das operações.

---

### 10. Colapso de Barra Invertida em Heredoc no Git Bash (MSYS2)

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

---

### 11. Timeout e Travamento de `grep -rn` na Raiz de Repositórios no Windows

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

---

### 12. Python `json.dumps()` Remove Escapes de Barra (`\/`) de JSON de Módulos PHP

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

---

### 13. Heredoc com apóstrofo, crase ou barra invertida: escreva o arquivo, não o heredoc

**Sintoma**: `unexpected EOF while looking for matching` ou um `assert` que falha porque `\\r\\n` virou outra coisa.

Um script Python ou JavaScript com apóstrofos (`What's new`), crases ou `\r\n` dentro de um heredoc do Git Bash quebra de formas diferentes a cada caso, mesmo com o delimitador entre aspas.

**Regra**: script com mais de umas dez linhas, ou com qualquer um desses caracteres, é gravado como arquivo pela ferramenta de escrita e depois executado. Heredoc só para trechos curtos e sem aspas.

---

### 14. Argumento que começa com `/` passado a `node` ou `curl`

O Git Bash converte `/pro/` em `C:/Program Files/Git/pro/` também em argumentos de `node` e em URLs montadas com variável. Sintoma: `ERR_NAME_NOT_RESOLVED` num host como `conn2flow.localc`.

```bash
MSYS_NO_PATHCONV=1 node validar.cjs rotulo "/,/pro/,/en/"
MSYS_NO_PATHCONV=1 curl -sk "https://host$rota"
```

`curl ... -o /dev/null` no Windows pode sair com código 23 mesmo com a resposta correta: confira o `%{http_code}`, não o código de saída.

---

### 15. Repositório dentro de pasta sincronizada (OneDrive)

Arquivo grande regravado com frequência (um `*Data.json` de dezenas de MB) fica bloqueado por instantes pelo sincronizador. Sintomas: `OSError: [Errno 22]` no Python, `file_put_contents` devolvendo `false` no PHP.

- Toda escrita de arquivo gerado confere o resultado e tenta de novo; nunca ignore o retorno.
- Uma falha isolada de escrita que some na segunda tentativa é isso, não um defeito do seu código. Registre e siga.

---

### 16. Suíte PHP: rode no ambiente Linux

O PHP do Windows falha em testes que dependem de OpenSSL (`openssl.cnf`) e de `pdo_sqlite`. A suíte de referência roda no ambiente de teste por SSH:

```bash
ssh usuario@lab 'cd /mnt/c/caminho/do/repo && php vendor/bin/phpunit --configuration phpunit.xml'
```

Checkout com fim de linha CRLF lido pelo Linux faz falhar testes que executam scripts `.sh` (`syntax error near unexpected token`). É falha do ambiente: registre como pré-existente, não como regressão. Shebang de script copiado para uma worktree precisa estar em LF.

---

### 17. Desaparecimento de `npx` e Cache Volátil do NPM no Windows

**Problema**: No Windows/Git Bash, invocar `npx tailwindcss` ou `npx terser` pode falhar intermitentemente ou desaparecer durante compilações contínuas, disparando erros como `npx: command not found`, `npm ERR! code ENOENT` ou travamentos por resolução e download de pacotes em cache volátil de `%LOCALAPPDATA%\npm-cache`.

**Causa Raiz**:
1. O comando `npx` tenta verificar e consultar registros remotos ou temporários quando invocado genericamente, sujeitando o build a timeouts de rede ou locks de arquivo pelo antivírus/OneDrive.
2. Problemas de resolução de caminhos no PATH do Windows fazem executáveis `npx` globais divergirem das versões fixadas no repositório.

**Solução Obrigatória**:
1. **Uso Estrito dos Binários Locais**: Sempre invoque diretamente os binários instalados no repositório em `node_modules/.bin/`:
```cmd
# No Windows / PowerShell / CMD:
.\node_modules\.bin\tailwindcss.cmd -i input.css -o output.css --minify
.\node_modules\.bin\terser.cmd script.js -o script.min.js
```
```bash
# No Git Bash / sh:
./node_modules/.bin/tailwindcss -i input.css -o output.css --minify
./node_modules/.bin/terser script.js -o script.min.js
```
2. **Nos scripts e pipelines PHP/CLI**: O framework e ferramentas devem referenciar diretamente o caminho relativo para `node_modules/.bin/tailwindcss.cmd` (no Windows) ou `node_modules/.bin/tailwindcss` (no Linux/Docker), sem intermediar chamadas através de `npx`.

> [!CAUTION]
> NUNCA use `npx tailwindcss` ou `npx terser` em scripts automatizados de compilação ou rotinas repetitivas de build. Dependa estritamente das dependências locais em `node_modules/.bin/`.

