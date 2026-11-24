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

## 📚 Subskills (leitura sob demanda)

| Subskill | Leia quando | Armadilhas |
| --- | --- | --- |
| [`subskills/cli-e-ssh.md`](subskills/cli-e-ssh.md) | rsync/SSH a partir do Windows, saída colorida de CLI, `sudo -u` em tenant, `npx` em build | 7, 8, 9, 17 |
| [`subskills/scripts-e-caminhos-msys.md`](subskills/scripts-e-caminhos-msys.md) | gerar arquivos por script, heredoc com barra invertida, busca recursiva, reescrever JSON de módulo | 3, 4, 10, 11, 12 |
| [`subskills/worktrees-e-junctions.md`](subskills/worktrees-e-junctions.md) | remover worktree que tem junctions (`vendor`, `node_modules`) | 18 |

As regras abaixo são o mínimo obrigatório; a receita completa fica na subskill indicada.

---

## ⛔ Armadilhas Críticas de Shell e Windows

### 1. Conversão Automática de Caminhos no Git Bash (MSYS Path Conversion)

O Git Bash converte argumentos que começam com `/` em caminhos Windows. `docker exec conn2flow-app php /var/www/html/script.php` vira, sem erro visível, `php C:/Program Files/Git/var/www/html/script.php`.

**Solução Obrigatória**: prefixe TODOS os `docker exec` e todo `rsync` com `MSYS_NO_PATHCONV=1`:
```bash
MSYS_NO_PATHCONV=1 docker exec conn2flow-app php /var/www/html/script.php
MSYS_NO_PATHCONV=1 rsync -avu "/c/Users/.../origem/" "usuario@host:/destino/"
```
No `rsync`, sem a variável, a origem `/c/Users/...` vira `C:/Users/...`, o `C:` é lido como host e o comando falha com `The source and destination cannot both be remote`.

> [!WARNING]
> Esta armadilha é **silenciosa** — o comando executa sem erro visível, mas o caminho dentro do container está errado. O PHP simplesmente não encontra o arquivo e retorna um erro genérico.

### 2. `curl` com Caractere `<` em Valores de Formulário

`-F "campo=<valor"` lê o arquivo local `valor` em vez de enviar o texto. Use `--form-string` para todo campo que possa conter `<`:
```bash
curl --form-string "html=<div>teste</div>" http://localhost/api
```

### 3. Python Heredocs e Sequências de Escape

`\b` e `\s` dentro de string Python comum viram bytes de controle (backspace `0x08`). Use raw strings (`r"""..."""`) ou `chr(92)`. Receita: `subskills/scripts-e-caminhos-msys.md`.

### 4. Asserts com Falha Silenciosa em Scripts Intermediários

Script de transformação valida o resultado **antes** de gravar (`set -e` no Bash; `assert` com mensagem no Python; tamanho e estrutura do arquivo gerado). Nunca grave saída parcial. Receita: `subskills/scripts-e-caminhos-msys.md`.

### 5. Formulários do Gestor: `multipart/form-data` com Gatilhos Ocultos

Os formulários do Gestor são `multipart/form-data` e só processam o POST com os campos ocultos. Sem eles, o POST é aceito e nada é atualizado:
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

### 6. Paralelismo Concorrente em Comandos de Compilação em Lote (Supressão de Warnings PHP)

Comandos pesados (`css:rebuild`, `resources:sync`, `project:update-all`, `manager:update-all`, `db:migrate`) em paralelo no mesmo ambiente disputam I/O, travam o container e escondem warnings do PHP. *Caso real*: um `$fontesExtras` sem parâmetro declarado ficou invisível por horas porque o comando rodava em segundo plano e as classes de `tailwind_sources` eram descartadas em silêncio.

**Solução Obrigatória**:
1. **Execução Sequencial Exclusiva**: NUNCA execute dois comandos de compilação ou pipeline em paralelo no mesmo container; aguarde o `exit code 0` de cada um.
2. **Foreground Obrigatório**: saída direta no terminal.
3. **Sem Buffer / Expor Warnings**: nada de `> /dev/null 2>&1`. `Undefined variable` ou `ArgumentCountError` DEVE aparecer no terminal.

### 7. `rsync: dup() in/out/err failed` — Pareamento de Runtimes cwRsync/SSH

O `rsync` do cwRsync (Cygwin) com o `ssh.exe` do Git Bash (MSYS2) morre com `rsync: dup() in/out/err failed` (code 14/12). Use o `ssh.exe` do próprio cwRsync com `-T`, `MSYS_NO_PATHCONV=1` e caminhos `/cygdrive/c/...`. Receita: `subskills/cli-e-ssh.md`.

### 8. Sequências ANSI em Saídas de Utilitários CLI (Cores Quebram Parsers)

Tailwind CLI, Vite e ESBuild emitem cores ANSI mesmo capturados (`tailwindcss \x1b[34mv4.3.3\x1b[39m`). Defina `NO_COLOR=1`/`FORCE_COLOR=0` e remova `\x1b\[[0-9;]*[a-zA-Z]` antes de comparar versões ou fazer asserts. Receita: `subskills/cli-e-ssh.md`.

### 9. `cd` Antes de `sudo -u` em Tenants SSH Restritos (HestiaCP e Similares)

Com home `750`/`700`, o `cd` roda como o usuário SSH e falha com `Permission denied`. Encapsule tudo na shell elevada: `sudo -u tenant sh -c 'cd /home/tenant/web/dominio.com && php artisan migrate'`. Receita: `subskills/cli-e-ssh.md`.

### 10. Colapso de Barra Invertida em Heredoc no Git Bash (MSYS2)

Heredoc do Git Bash colapsa `\\` em `\` sem erro, corrompendo caminhos Windows, regex e código PHP. Use `DIRECTORY_SEPARATOR`, `printf` ou placeholder + `sed`. Receita: `subskills/scripts-e-caminhos-msys.md`.

### 11. Timeout e Travamento de `grep -rn` na Raiz de Repositórios no Windows

> [!CAUTION]
> NUNCA execute `grep -rn` na raiz de repositórios com `node_modules` ou `.git` grandes. No Windows, o MSYS2 não tem kill automático por timeout — o processo pode travar indefinidamente.

Restrinja o diretório, use `--exclude-dir={node_modules,.git,vendor,dist,build}`, `rg` ou `timeout 30`. Receita: `subskills/scripts-e-caminhos-msys.md`.

### 12. Python `json.dumps()` Remove Escapes de Barra (`\/`) de JSON de Módulos PHP

O PHP grava `"products\/add\/"`; o `json.dumps()` grava `"products/add/"` e o diff marca o arquivo inteiro. Detecte `\/` no original e restaure após serializar, com `ensure_ascii=False` e o EOL original. Receita: `subskills/scripts-e-caminhos-msys.md`.

### 13. Heredoc com apóstrofo, crase ou barra invertida: escreva o arquivo, não o heredoc

**Sintoma**: `unexpected EOF while looking for matching` ou um `assert` que falha porque `\\r\\n` virou outra coisa.

Um script Python ou JavaScript com apóstrofos (`What's new`), crases ou `\r\n` dentro de um heredoc do Git Bash quebra de formas diferentes a cada caso, mesmo com o delimitador entre aspas.

**Regra**: script com mais de umas dez linhas, ou com qualquer um desses caracteres, é gravado como arquivo pela ferramenta de escrita e depois executado. Heredoc só para trechos curtos e sem aspas.

### 14. Argumento que começa com `/` passado a `node` ou `curl`

O Git Bash converte `/pro/` em `C:/Program Files/Git/pro/` também em argumentos de `node` e em URLs montadas com variável. Sintoma: `ERR_NAME_NOT_RESOLVED` num host como `conn2flow.localc`.

```bash
MSYS_NO_PATHCONV=1 node validar.cjs rotulo "/,/pro/,/en/"
MSYS_NO_PATHCONV=1 curl -sk "https://host$rota"
```

`curl ... -o /dev/null` no Windows pode sair com código 23 mesmo com a resposta correta: confira o `%{http_code}`, não o código de saída.

### 15. Repositório dentro de pasta sincronizada (OneDrive)

Arquivo grande regravado com frequência (um `*Data.json` de dezenas de MB) fica bloqueado por instantes pelo sincronizador. Sintomas: `OSError: [Errno 22]` no Python, `file_put_contents` devolvendo `false` no PHP.

- Toda escrita de arquivo gerado confere o resultado e tenta de novo; nunca ignore o retorno.
- Uma falha isolada de escrita que some na segunda tentativa é isso, não um defeito do seu código. Registre e siga.

### 16. Suíte PHP: rode no ambiente Linux

O PHP do Windows falha em testes que dependem de OpenSSL (`openssl.cnf`) e de `pdo_sqlite`. A suíte de referência roda no ambiente de teste por SSH:

```bash
ssh usuario@lab 'cd /mnt/c/caminho/do/repo && php vendor/bin/phpunit --configuration phpunit.xml'
```

Checkout com fim de linha CRLF lido pelo Linux faz falhar testes que executam scripts `.sh` (`syntax error near unexpected token`). É falha do ambiente: registre como pré-existente, não como regressão. Shebang de script copiado para uma worktree precisa estar em LF.

### 17. Desaparecimento de `npx` e Cache Volátil do NPM no Windows

> [!CAUTION]
> NUNCA use `npx tailwindcss` ou `npx terser` em scripts automatizados de compilação ou rotinas repetitivas de build. Dependa estritamente das dependências locais em `node_modules/.bin/`.

Windows/CMD: `.\node_modules\.bin\tailwindcss.cmd`; Git Bash/Linux/Docker: `./node_modules/.bin/tailwindcss`. Causas e exemplos: `subskills/cli-e-ssh.md`.

### 18. Remover worktree com junctions

No Windows, faça toda a inspeção e remoção em **uma única sessão do Prompt de Comando (CMD)**, com caminhos absolutos verificados. Passo a passo (confirmação de integração, inventário de links nível a nível, remoção individual, `git worktree remove`/`prune` e conferência final): **`subskills/worktrees-e-junctions.md`**.

- Remova cada `<JUNCTION>` pelo caminho literal com `rmdir "<pasta>\<atalho>"`, individualmente e sem `/S`; enumere com `dir /AL`, um nível por vez, nunca com `/S`.
- **Erro de consulta não é zero links (FB-007)**: zero só vale quando o caminho foi validado e o `dir /AL` respondeu `Arquivo não encontrado`/`File Not Found`. Erro de sintaxe, caminho ou acesso interrompe a limpeza. Não chame o inventário por `cmd //c` no Git Bash.

**Comandos proibidos nesta limpeza:** `rmdir /S`, `Remove-Item -Recurse`, `rm -rf` e enumeração no PowerShell seguida de exclusão no CMD. Nunca use `git worktree remove --force` para atropelar trabalho não integrado.

### 19. iconv //TRANSLIT não portável no PHP Windows

iconv('UTF-8', 'ASCII//TRANSLIT', $texto) pode substituir acentos por caracteres diferentes, interrogação ou retornar false conforme a plataforma. Para chaves/slugs que exigem determinismo, use tabela associativa explícita com strtr (inclua minúsculas/maiúsculas e o conjunto de caracteres aceito pelo produto), seguida de normalização controlada. Não converta falha em string vazia nem silencie warning. Exercite entrada acentuada em Windows e Linux, caracteres fora do mapa e repetibilidade; não aplique transliteração ao texto de apresentação. Preserve o comportamento de Unicode definido pelo contrato do recurso.

## 20. REP-002 — stdin, edição com asserções e CRLF

- Nunca execute `cat > arquivo;` sem entrada delimitada: fica esperando stdin até timeout. Prefira ferramenta de edição; scripts complexos devem ser escritos em arquivo e executados depois.
- Substituições exigem caminho explícito e asserção da contagem esperada antes de gravar. Zero ou múltiplos inesperados abortam sem escrita; valide todos os alvos antes de alteração em lote.
- Arquivos CRLF exigem ferramenta de edição segura ou script que leia bytes e preserve/normalize conscientemente os finais de linha; não aplique regex multilinha presumindo LF.
- **FB-008**: `sed -i` do Git Bash converte CRLF em LF; `Path.write_text()` no Python do Windows grava CRLF por padrão ao receber `\n`. Use `Path.read_bytes()` e `Path.write_bytes()`, preserve o EOL original e confira os bytes depois.
- `grep -c $'\r'` pode produzir falso negativo no Windows/Git Bash (medido: 0 num arquivo CRLF). Antes de montar um patch, detecte o EOL pelos bytes: `node -e "const s=require('fs').readFileSync(process.argv[1],'latin1');console.log((s.match(/\r\n/g)||[]).length,'CRLF')" <arquivo>` (ou `Path(...).read_bytes().count(b'\r\n')`, PHP `bin2hex`); saída zero de grep não prova LF.
- Em arquivo CRLF, um padrão de substituição com `\n` embutido casa 0 vezes. Monte o trecho antigo sem a quebra de linha final, ou use o EOL detectado (`\r\n`) entre as linhas, e exija a contagem esperada antes de gravar.
- Ao restaurar um caminho específico no Windows, o checkout pode converter LF em CRLF. Confira com `git ls-files --eol -- <caminho>` (`i/` índice, `w/` checkout) e compare os bytes com o blob de referência (`git show HEAD:<caminho>`) antes de declarar paridade.
- Na remoção Windows, use uma única shell de ponta a ponta; verifique caminhos absolutos e reparse points antes de excluir. Não enumere no PowerShell para passar exclusão ao CMD. Nunca atravesse junctions na limpeza.

### 21. Como reconhecer um `index.lock` órfão no Windows

1. **Sintoma**: `fatal: Unable to create '.../.git/index.lock': File exists` (ou `.git/worktrees/<nome>/index.lock`) após comando Git interrompido, timeout ou travamento no Windows/OneDrive.
2. **Proibição**: nunca apague `index.lock` sem verificar se há `git.exe` ativo no repositório. Apagar um lock ativo corrompe o índice.
3. **Diagnóstico obrigatório (PowerShell)**:
   ```powershell
   Get-CimInstance Win32_Process -Filter "Name='git.exe'" | Select-Object ProcessId, CreationDate, CommandLine
   Get-Item -LiteralPath .git/index.lock | Select-Object FullName, Length, LastWriteTime
   ```
   Confirme que o timestamp do lock não muda.
4. **Ação segura**: com `git.exe` ativo naquele repo/worktree, aguarde e nunca apague lock alheio. Sem nenhum `git.exe` atuando e com o arquivo parado (órfão), remova só o arquivo literal (`Remove-Item -LiteralPath .git/index.lock`) e confira com `git status --short` (e `git fsck` se a interrupção ocorreu durante gravação).
5. **Deploy em repositório no OneDrive**: o `git diff` disparado pelo pipeline de deploy frequentemente deixa `.git/index.lock` órfão quando o repositório está no OneDrive. Antes de repetir o deploy, aplique o diagnóstico dos passos 3 e 4.

### Busca de skills em pastas iniciadas por ponto

Por padrão, o `glob(..., recursive=True)` do Python ignora pastas iniciadas por ponto, como `.gemini`, `.claude`, `.codex` e `.github`. Para varrer os kits de skills, use `os.walk` ou `Path.rglob`.
Na matriz `conn2flow-ai-workspace`, rode `.\.venv-mdd\Scripts\pytest.exe -q tools/mdd-client/tests`.
