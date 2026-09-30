<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\MigrationChecker;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Throwable;

/**
 * `c2f db:check-migrations [projectID] [--dir=<pasta>]` — req-197 / BATCH-201.
 *
 * Acusa versão ou classe de migração duplicada entre core, plugins e projeto antes do deploy (o Phinx
 * só acusaria no servidor, travando todos os deploys seguintes). Sai com 1 quando há choque; roda como
 * primeira verificação do `project:update-all` e serve para pre-commit.
 */
final class DbCheckMigrationsCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'db:check-migrations';
    }

    public function getDescription(): string
    {
        return 'Detect duplicated migration versions or classes across core, plugins and project (pre-deploy).';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function getHelp(): string
    {
        return "Usage: c2f db:check-migrations [projectID] [--dir=<migrations folder>]\n\n"
            . "Without a project, checks the core (and its plugins). With a project from environment.json, also "
            . "checks its migrations and plugins. --dir adds another folder. Exit code 1 when a duplicate is found.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getOption('project') ?? $input->getArgument(0);
        $projectPath = null;
        if ($project) {
            try {
                $projectPath = (new ProjectEnvironmentResolver($this->rootPath))->resolve((string)$project)['gestorPath'] ?? null;
            } catch (Throwable $e) {
                $output->error($e->getMessage());
                return 1;
            }
        }

        $sources = MigrationChecker::defaultSources($this->rootPath, $projectPath);
        $extra = $input->getOption('dir');
        if (is_string($extra) && $extra !== '') {
            $sources['dir'] = $extra;
        }

        $result = (new MigrationChecker())->check($sources);
        foreach ($result['invalid'] as $file) {
            $output->warning("Nome fora do padrão (ignorado pelo Phinx): {$file}");
        }
        if (!$result['duplicates']) {
            $output->success(sprintf('Migrações OK: %d arquivo(s), sem versão ou classe duplicada (%s).', $result['files'], implode(', ', array_keys($sources))));
            return 0;
        }
        foreach ($result['duplicates'] as $dup) {
            $what = $dup['kind'] === 'version' ? 'Versão duplicada' : 'Classe duplicada';
            $output->error("{$what} {$dup['key']}: " . implode(' | ', $dup['files']));
        }
        $output->error('Renumere ou renomeie a migração nova antes do deploy: o Phinx recusaria todas as execuções no servidor.');
        return 1;
    }
}
