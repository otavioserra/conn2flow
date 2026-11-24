# CLI e SSH no Windows — receitas (Armadilhas 7, 8, 9 e 17)

Subskill de `c2f-shell-and-windows-traps`. Leia antes de usar `rsync`/SSH a partir do Windows, interpretar a saída de utilitários CLI, rodar comandos como outro usuário num servidor multi-tenant ou chamar binários Node num build. As regras críticas também estão na skill-mãe.

## 7. `rsync: dup() in/out/err failed` — Pareamento de Runtimes cwRsync/SSH

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

## 8. Sequências ANSI em Saídas de Utilitários CLI (Cores Quebram Parsers)

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

## 9. `cd` Antes de `sudo -u` em Tenants SSH Restritos (HestiaCP e Similares)

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

## 17. Desaparecimento de `npx` e Cache Volátil do NPM no Windows

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
