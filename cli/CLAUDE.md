# ⚡ Contexto Especializado: Core CLI (`cli/`)

Ao atuar no diretório `cli/`, você está mantendo o console próprio de automação e orquestração do Conn2Flow (`c2f`).

> [!IMPORTANT]
> O console **não usa Symfony Console**. Os contratos são próprios, em `Conn2Flow\Cli\Contracts`.
> Nunca escreva `Symfony\Component\Console\Command\Command`, `configure()`, `InputArgument`, `addOption()`
> ou `#[AsCommand]`: nada disso existe aqui e o comando não será registrado.

## ⛔ Regras Invioláveis do Core CLI
1. **Padrão Conn2Flow CLI**:
   - Novos comandos implementam `Conn2Flow\Cli\Contracts\CommandInterface` (ou estendem `Conn2Flow\Cli\Commands\BaseProcessCommand`) e são registrados no `Application.php`.
   - A superfície obrigatória da interface é `getName(): string`, `getDescription(): string`, `getAliases(): array`, `getHelp(): string` e `execute(InputInterface $input, OutputInterface $output): int` (`0` = sucesso).
   - Leia argumentos por `$input->getArgument(<indice>, <padrao>)` — nunca por `$argv` — e resolva caminhos a partir de `$this->rootPath`, nunca de `getcwd()`.
   - `BaseProcessCommand` recebe `string $rootPath` no construtor e oferece `runShell(string $cmd, OutputInterface $output, ?string $cwd = null): int` para orquestrar processos externos com streaming de saída e propagação de exit code.
   - Sempre utilize métodos semânticos do console (`$output->title()`, `$output->info()`, `$output->success()`, `$output->error()`).
2. **Pipeline Mandatório e Sem Paralelismo**:
   - Comandos de lote (`manager:update-all`, `project:update-all`, `css:rebuild`, `resources:sync`, `db:migrate`) devem executar as etapas em sequência estrita, finalizando com `css:rebuild` para evitar o estado híbrido pós-deploy.
   - Proibição de paralelismo ou concorrência em lote no mesmo ambiente (evita travamento do PHP e supressão de warnings).
3. **Validação de Skills em `AiSyncCommand.php`**:
   - O comando `c2f ai:sync` audita rigorosamente todas as **36 Skills** e seus blocos contratuais (`# ⚡ Gatilho Obrigatório` e `**TRIGGER**:`) nos 5 kits de IA (`.claude`, `.cursor`, `.gemini`, `.github`, `.codex`).
   - Qualquer nova skill deve ser adicionada à constante `REQUIRED_SKILLS` de `AiSyncCommand.php` em ordem alfabética.
