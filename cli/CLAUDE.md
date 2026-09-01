# ⚡ Contexto Especializado: Core CLI (`cli/`)

Ao atuar no diretório `cli/`, você está mantendo o console de automação e orquestração do Conn2Flow baseado em Symfony Console (`c2f`).

## ⛔ Regras Invioláveis do Core CLI
1. **Padrão Conn2Flow CLI**:
   - Novos comandos implementam `Conn2Flow\Cli\Contracts\CommandInterface` (ou estendem `Conn2Flow\Cli\Commands\BaseProcessCommand`) e são registrados no `Application.php`.
   - Sempre utilize métodos semânticos do console (`$output->title()`, `$output->info()`, `$output->success()`, `$output->error()`).
2. **Pipeline Mandatório e Sem Paralelismo**:
   - Comandos de lote (`manager:update-all`, `project:update-all`, `css:rebuild`, `resources:sync`, `db:migrate`) devem executar as etapas em sequência estrita, finalizando com `css:rebuild` para evitar o estado híbrido pós-deploy.
   - Proibição de paralelismo ou concorrência em lote no mesmo ambiente (evita travamento do PHP e supressão de warnings).
3. **Validação de Skills em `AiSyncCommand.php`**:
   - O comando `c2f ai:sync` audita rigorosamente todas as **36 Skills** e seus blocos contratuais (`# ⚡ Gatilho Obrigatório` e `**TRIGGER**:`) nos 5 kits de IA (`.claude`, `.cursor`, `.gemini`, `.github`, `.codex`).
   - Qualquer nova skill deve ser adicionada à constante `REQUIRED_SKILLS` de `AiSyncCommand.php` em ordem alfabética.
