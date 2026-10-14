<?php

declare(strict_types=1);

use Conn2Flow\Cli\Commands\ProjectSyncResourcesCommand;
use Conn2Flow\Cli\Commands\ProjectUpdateAllCommand;
use Conn2Flow\Cli\Console\Input;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/cli/src/Contracts/CommandInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Contracts/InputInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Contracts/OutputInterface.php';
require_once CONN2FLOW_ROOT . '/cli/src/Console/Input.php';
require_once CONN2FLOW_ROOT . '/cli/src/Commands/BaseProcessCommand.php';
require_once CONN2FLOW_ROOT . '/cli/src/Commands/ProjectSyncResourcesCommand.php';
require_once CONN2FLOW_ROOT . '/cli/src/Commands/ProjectUpdateAllCommand.php';

/**
 * req-269: `--skip-css` pula a compilação Tailwind (core e projeto) e a regeneração do CSS derivado
 * nos pipelines `project:update-all` e `manager:update-all`. Sem a flag, nada muda.
 */
final class PipelineSkipCssReq269Test extends TestCase
{
    private static function fonte(string $relativo): string
    {
        return str_replace("\r\n", "\n", (string)file_get_contents(CONN2FLOW_ROOT . '/' . $relativo));
    }

    public function testFlagNaLinhaDeComandoPulaARegeneracao(): void
    {
        self::assertTrue(ProjectUpdateAllCommand::skipsCssRebuild(new Input(['c2f', 'project:update-all', 'lab', '--skip-css'])));
        self::assertFalse(ProjectUpdateAllCommand::skipsCssRebuild(new Input(['c2f', 'project:update-all', 'lab'])));
        self::assertFalse(ProjectUpdateAllCommand::skipsCssRebuild(new Input(['c2f', 'project:update-all', 'lab', '--no-verify'])));
    }

    public function testArgumentosDoScriptDeRecursos(): void
    {
        self::assertSame("--project 'lab' --skip-css", str_replace('"', "'", ProjectSyncResourcesCommand::scriptArguments('lab', true)));
        self::assertSame("--project 'lab'", str_replace('"', "'", ProjectSyncResourcesCommand::scriptArguments('lab', false)));
        self::assertSame('--skip-css', ProjectSyncResourcesCommand::scriptArguments(null, true));
        self::assertSame('', ProjectSyncResourcesCommand::scriptArguments(null, false));
    }

    public function testPipelineDoProjetoRepassaAFlagEPulaAEtapa7(): void
    {
        $fonte = self::fonte('cli/src/Commands/ProjectUpdateAllCommand.php');

        // Etapas 2 e 4 recebem o mesmo `$input` da linha de comando, onde está a flag.
        self::assertStringContainsString('$coreCmd->execute($input, $output)', $fonte);
        self::assertStringContainsString('$resCmd->execute($input, $output)', $fonte);
        self::assertMatchesRegularExpression(
            "/if \\(self::skipsCssRebuild\\(\\\$input\\)\\) \\{\\s*\\\$output->info\\('Etapa 7\\/8 ignorada por --skip-css\\.'\\);\\s*\\} else \\{\\s*\\\$this->rebuildCss\\(/",
            $fonte
        );
        self::assertStringContainsString('[--skip-css]', (new ProjectUpdateAllCommand(CONN2FLOW_ROOT))->getHelp());
    }

    public function testComandosDeSyncRepassamAFlagAoScript(): void
    {
        foreach (['ProjectSyncResourcesCommand', 'ProjectSyncCoreCommand'] as $classe) {
            $fonte = self::fonte("cli/src/Commands/{$classe}.php");
            self::assertStringContainsString("\$input->hasOption('skip-css')", $fonte, $classe);
            self::assertStringContainsString('scriptArguments(', $fonte, $classe);
        }
    }

    public function testPipelineDoSistemaPulaAEtapa5(): void
    {
        $fonte = self::fonte('cli/src/Commands/ManagerUpdateAllCommand.php');

        self::assertStringContainsString('$resCmd->execute($input, $output)', $fonte);
        self::assertMatchesRegularExpression(
            "/if \\(ProjectUpdateAllCommand::skipsCssRebuild\\(\\\$input\\)\\) \\{\\s*\\\$output->info\\('Etapa 5\\/6 ignorada por --skip-css\\.'\\);/",
            $fonte
        );
    }

    /** Os scripts aceitam a flag e a entregam ao compilador, que pula a etapa 3/8 com ela. */
    public function testScriptsShellEntregamAFlagAoCompilador(): void
    {
        foreach (['update-resource-data.sh', 'sync-core-to-project.sh'] as $script) {
            $fonte = self::fonte("ai-workspace/en/scripts/projects/{$script}");
            self::assertMatchesRegularExpression('/--skip-css\)\s*\n\s*RESOURCE_ARGS\+=\("--skip-css"\)/', $fonte, $script);
            self::assertSame(2, substr_count($fonte, '"${RESOURCE_ARGS[@]}"'), $script);
        }

        $compilador = self::fonte('gestor/controladores/agents/arquitetura/atualizacao-dados-recursos.php');
        self::assertStringContainsString("if (isset(\$GLOBALS['CLI_ARGS']['skip-css'])) {", $compilador);
    }
}
