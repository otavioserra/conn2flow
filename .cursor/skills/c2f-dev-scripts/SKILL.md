---
name: c2f-dev-scripts
description: "LEIA ANTES de rodar scripts em dev-environment/ ou ferramentas CLI do projeto. Se não ler: comandos falham por caminhos relativos errados, variáveis de ambiente ausentes ou execução fora do container Docker."
user-invocable: false
---

# Scripts de Automação e Desenvolvimento (`ai-workspace/scripts/`)

# ⚡ Gatilho Obrigatório
- **TRIGGER**: Executar tarefas de automação, sincronização, geração de recursos, migrações ou testes via scripts PowerShell/Bash em `dev-environment/` ou `scripts/`.
- **SKIP APENAS SE**: Edição manual de documentação Markdown sem execução de scripts auxiliares.
- **CONSEQUÊNCIA DE IGNORAR**: Comandos falham por caminhos incorretos, permissões do Docker ou sobrescrita indevida de arquivos de ambiente.

---

Consulte as convenções para uso e criação de scripts de desenvolvimento no Conn2Flow:

## 1. Estrutura de Diretórios de Scripts

Os scripts são bilíngues e vivem sob o idioma (`en/` ou `pt-br/`), nunca na raiz de `ai-workspace/`:

```
ai-workspace/<idioma>/scripts/
├── api/               # Sondas e clientes das APIs do gestor
├── commits/           # Validação e padronização de commits
├── dev-environment/   # Configuração e criação de novos módulos/instalações
├── projects/          # Deploy, recover, sync e atualização de projetos
├── releases/          # Tags e automação de releases Git
├── resources/         # CLI de manipulação de recursos (upsert-resources.php)
├── tests/             # Scripts de teste e diagnóstico
├── translates/        # Rotinas de tradução e paridade bilíngue
├── updates/           # Builds locais e compilação de pacotes
└── utils/             # Scripts auxiliares Node/JS (ex: fix-tailwind-spacing.js)
```

---

## 2. Principais Scripts Utilitários Disponíveis

* `ai-workspace/<idioma>/scripts/resources/upsert-resources.php`: CLI para criar, clonar, editar e deletar recursos com suporte a `--type`, `--id`, `--action=copy` e `--open`.
* `ai-workspace/<idioma>/scripts/projects/deploy-project-v2.sh`: Empacotamento e deploy automatizado de projetos via API OAuth.
* `ai-workspace/<idioma>/scripts/projects/recover-project.sh`: Recuperação/pull de dados e estrutura do servidor.
* `ai-workspace/<idioma>/scripts/dev-environment/create-new-module.sh`: Scaffold de novos módulos.
* `ai-workspace/<idioma>/scripts/dev-environment/create-new-installation.sh`: Setup de nova instalação local.
* `ai-workspace/pt-br/scripts/utils/fix-tailwind-spacing.js`: Utilitário Node para correção de classes Tailwind.

---

## 3. Contrato Canônico do Console CLI (`c2f`)

> [!IMPORTANT]
> O console `c2f` **não usa Symfony Console**. Ele tem contratos próprios em `Conn2Flow\Cli\Contracts`.
> Nunca invente `Symfony\Component\Console\Command\Command`, `configure()`, `InputArgument`, `addOption()`
> ou atributos `#[AsCommand]`: nada disso existe no projeto e o comando não será registrado.

Todo novo comando DEVE implementar `Conn2Flow\Cli\Contracts\CommandInterface` ou estender
`Conn2Flow\Cli\Commands\BaseProcessCommand` (que já implementa a interface e adiciona `runShell()`).

**Superfície obrigatória da `CommandInterface`** (`cli/src/Contracts/CommandInterface.php`):

| Método | Retorno | Papel |
| --- | --- | --- |
| `getName()` | `string` | Nome único no formato `grupo:acao` (ex.: `resources:sync`). |
| `getDescription()` | `string` | Descrição curta exibida no `c2f help`. |
| `getAliases()` | `array<string>` | Aliases aceitos (ex.: `['release:installer']`). |
| `getHelp()` | `string` | Texto de uso detalhado do comando. |
| `execute(InputInterface $input, OutputInterface $output)` | `int` | Lógica do comando. `0` = sucesso; diferente de `0` = falha. |

**Regras de implementação**:

1. **Entrada e saída pelos contratos**: leia argumentos com `$input->getArgument(<indice>, <padrao>)` e escreva com
   `$output->title()`, `$output->info()` e `$output->error()`. Não use `$argv`, `echo` ou `print_r` direto.
2. **Herde `BaseProcessCommand` para orquestrar processos**: ele recebe `string $rootPath` no construtor e expõe
   `runShell(string $cmd, OutputInterface $output, ?string $cwd = null): int`, que já faz streaming de stdout,
   captura de stderr e propagação do exit code.
3. **Resolva caminhos a partir de `$this->rootPath`**, nunca de `getcwd()` ou de caminhos relativos ao script.
4. **Valide argumentos antes de executar** e devolva `1` com `$output->error()` explicando o uso correto.
5. **Declare `declare(strict_types=1);`**, use o namespace `Conn2Flow\Cli\Commands` e marque a classe como `final`.
6. **Registre o comando** no console (`cli/src/Console/`) para que ele apareça no `c2f help`. Um comando que não
   consta no catálogo não é executável.

---

## 4. Regra Anti-Deadlock: Sondas HTTP Partem do Front-End

> [!WARNING]
> **PROIBIDO disparar sonda HTTP de loopback síncrona de dentro do próprio processo PHP em modo web.**

Em ambiente local, Docker e VPS single-pool, o PHP-FPM tem um número reduzido de workers. Se um script PHP
que está atendendo uma requisição abre um cURL para o **próprio host**, ele ocupa um worker enquanto espera outro
worker atender a sonda. Com o pool saturado, ninguém atende: o cURL só retorna no timeout e a requisição original
trava. É deadlock de auto-requisição, não lentidão.

**Regra normativa**:

1. **Modo web**: quem executa a sonda de conectividade, rewrite ou healthcheck de URL é o **navegador**
   (`fetch`/`XMLHttpRequest` assíncrono no JavaScript do front-end). O backend apenas **monta o plano da sonda**
   (URL alvo, resposta esperada, snippet de configuração) e **registra o veredito** devolvido pelo cliente.
2. **Modo CLI / headless**: aí sim o próprio PHP pode sondar, porque não há navegador e o runner não compete pelo
   pool do PHP-FPM. Proteja com uma checagem explícita de SAPI.
3. **Nunca** deixe a rota de diagnóstico depender do recurso que ela diagnostica. Ofereça sempre um caminho
   determinístico alcançável pelo front-controller (ex.: `?api=rewrite-probe` além de `api/rewrite-probe`).

**Implementação de referência** (sonda de rewrite do gestor instalador, REQ-027/REQ-028):

* `gestor-instalador/src/InstallerGuard.php`: constantes `REWRITE_PROBE`, `REWRITE_PROBE_OK` e `API_REWRITE_PROBE`.
* `gestor-instalador/index.php`: responde à sonda antes de sessão/trava e aceita `?api=rewrite-probe` como rota
  determinística; o veredito chega no request como `rewrite_ok`.
* `gestor-instalador/assets/js/installer.js`: é o **navegador** que dispara o `fetch` da sonda.
* `gestor-instalador/src/Installer.php`: `rewriteProbeReport()` consome o veredito do cliente; `probeRewrite()`
  aborta com `if (!InstallerGuard::isCli()) return null;` — a sonda pelo servidor só roda em CLI.

```php
// Padrão obrigatório para qualquer sonda HTTP no servidor.
private function probeSelf($url)
{
    // Em modo web a requisição atingiria o mesmo pool PHP-FPM que conduz esta execução.
    if (!InstallerGuard::isCli()) return null;
    if (!function_exists('curl_init')) return null;
    // ... cURL com CURLOPT_TIMEOUT e CURLOPT_CONNECTTIMEOUT curtos ...
}
```

---

## 5. Diretrizes para Criar Novos Scripts de Desenvolvimento

1. **Parâmetros Flexíveis**: Suporte a flags explícitas (`--project=ID`, `--lang=pt-br`, `--force`) e fallbacks seguros para execução interativa se os argumentos omitirem parâmetros requeridos.
2. **Idempotência e Segurança**: Scripts de atualização/sincronização devem ser idempotentes (poder rodar múltiplas vezes sem corromper dados).
3. **Logs e Retorno**: Emita saída colorida/formatada no console (`echo -e "\033[0;32mOK...\033[0m"`) e verifique códigos de saída HTTP ou status de comandos.
4. **Modo `--dry-run`**: scripts que mutam arquivos versionados (ex.: bump de versão em release) devem oferecer
   `--dry-run`, calculando e imprimindo o resultado sem escrever em disco, para que os gates de release possam
   pré-validar tags e documentação antes da mutação.
5. **Staging Git explícito**: scripts que commitam DEVEM listar os caminhos (`git add -- "$ARQUIVO"`). `git add .`
   e `git add -A` são proibidos: arrastam trabalho concorrente de outros agentes para o commit.

---

## Transporte de deploy: local ou SSH (req-034)

Os scripts de projeto deixaram de assumir que o destino é um diretório do sistema
de arquivos local. O transporte é **declarado**, não inferido, na entrada do
projeto em `dev-environment/data/environment.json`:

```jsonc
"conn2flow-site-local": {
  "path": "/c/.../conn2flow-site/gestor",   // fonte de autoria (Windows)
  "deploy_mode": "ssh",                      // ausente ou "local" = comportamento antigo
  "ssh_host": "192.168.1.108",
  "ssh_user": "otavio",
  "ssh_port": 22,
  "ssh_target_path": "/home/admin/web/conn2flow.local/conn2flow-gestor",
  "ssh_sudo": true,                          // rsync remoto sob sudo
  "ssh_chown": "admin:admin",                // devolve a posse ao dono do docroot
  "ssh_run_as": "admin"                      // usuário do PHP remoto
}
```

Com `deploy_mode: "ssh"`, `target` e `path_tests` **não são lidos** — o destino é
`ssh_target_path` na VM. Sem a chave, nada muda para os projetos Docker e
bare-metal existentes.

### Biblioteca compartilhada

`ai-workspace/en/scripts/lib/project-transport.sh` centraliza a resolução. Quem
precisar de um novo script com destino de projeto deve dar `source` nela em vez
de reimplementar o `jq`:

```bash
. "$SCRIPT_DIR/../lib/project-transport.sh"
project_transport_resolve "$ENV_FILE" "$PROJECT_ID" || exit 1
if project_transport_is_ssh; then
  project_transport_check || exit 1        # falha cedo, com diagnóstico
  project_transport_ensure_dest || exit 1  # mkdir -p remoto
  DESTINO="$PT_DEST"                       # usuario@host:/caminho
fi
rsync -av "${PT_RSYNC_OPTS[@]}" "$ORIGEM/" "$DESTINO/"
project_transport_finalize                 # chown remoto
```

`PT_RSYNC_OPTS` fica **vazio** em transporte local, então a mesma linha serve aos
dois modos. Para rodar um comando na raiz do Gestor remoto:
`project_transport_remote_exec php <script> <args...>` (cita cada argumento; nunca
monte a linha remota por interpolação).

Já usam a lib: `projects/sync-core-to-project.sh`,
`projects/synchronize-project.sh` e `dev-environment/updates-manager-database.sh`
(que ganhou o `EXECUTION_MODE=ssh` ao lado de `docker` e `host`).

### O que o `project:update-all` alcança e o que não alcança em SSH

As etapas 1 a 5 (core, banco, recursos, arquivos, banco) operam na VM e devem
terminar em `SUCCESS`. As etapas 6 e 8 (`css:rebuild` e `assets:publish`) rodam
**localmente** e não têm `.env` no repositório de autoria: elas avisam e o
pipeline segue. Não é falha de sincronização — é limite conhecido do transporte.
Para CSS derivado de recurso editado online, rode o comando na VM.
