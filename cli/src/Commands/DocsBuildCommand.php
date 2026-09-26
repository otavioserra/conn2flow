<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\Docs\DocsBuilder;
use Conn2Flow\Cli\Support\Docs\DocsTree;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Throwable;

/**
 * `c2f docs:build` — publica as docs do Core como recursos de sistema de um projeto (req-178).
 *
 * Só grava arquivos no repositório do projeto; sincronizar para o banco e compilar o CSS é o
 * pipeline normal (`project:update-all`), que o operador roda no ambiente local.
 */
final class DocsBuildCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'docs:build';
    }

    public function getDescription(): string
    {
        return 'Build the Core Markdown docs into a project\'s system resources (publisher pages, pages, menu, llms.txt).';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function getHelp(): string
    {
        return "Usage: c2f docs:build --project=<id> [--dry-run] [--source=<dir>]\n\n"
            . "Reads <project gestor>/docs.config.json and ai-workspace/<lang>/docs/ (guides, concepts, reference,\n"
            . "whats-new) and writes, per language, resources/<lang>/{pages,publisher_pages,menus}/ plus pages.json,\n"
            . "publisher-pages.json and menus.json (merged by id: only ids 'docs' and 'docs-*' are managed), and\n"
            . "assets/docs/llms*.txt. With sdd.enabled in docs.config.json, also publishes filtered Core SDD\n"
            . "documents (pt-br only) under /docs/sdd/. Broken docs links or invalid frontmatter abort the build.\n\n"
            . "Next step (local only): php cli/c2f.php project:update-all <id>";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Docs Build');
        $projectId = $input->getOption('project');
        if (!is_string($projectId) || $projectId === '') {
            $output->error('Inform --project=<id> (a devProjects entry of environment.json).');

            return 1;
        }

        try {
            $project = (new ProjectEnvironmentResolver($this->rootPath))->resolve($projectId);
        } catch (Throwable $e) {
            $output->error($e->getMessage());

            return 1;
        }

        $gestorPath = $project['gestorPath'];
        $configFile = $gestorPath . DIRECTORY_SEPARATOR . 'docs.config.json';
        $config = is_file($configFile) ? json_decode((string)file_get_contents($configFile), true) : null;
        if (!is_array($config)) {
            $output->error("docs.config.json missing or invalid at {$configFile}.");

            return 1;
        }

        $source = $input->getOption('source');
        $tree = new DocsTree($this->rootPath, is_string($source) ? $source : null);
        // O rótulo do menu das docs de módulo é o nome do módulo na tabela `modulos` do core.
        $moduleNames = DocsBuilder::loadModuleNames($this->rootPath . '/gestor/db/data/ModulosData.json');
        $plan = (new DocsBuilder($tree, $gestorPath, $config, $moduleNames))->plan();

        foreach ($plan['warnings'] as $w) {
            $output->warning($w);
        }
        if ($plan['errors'] !== []) {
            foreach ($plan['errors'] as $e) {
                $output->error($e);
            }
            $output->error('Build aborted: nothing was written.');

            return 1;
        }

        $changed = [];
        foreach ($plan['write'] as $path => $content) {
            if (!is_file($path) || (string)file_get_contents($path) !== $content) {
                $changed[] = $path;
            }
        }
        $deleted = array_values(array_filter($plan['delete'], 'is_dir'));

        $output->info(sprintf(
            'Project %s: %d page(s), %d publication(s), %d landing page(s); %d file(s) to change, %d folder(s) to remove.',
            $projectId,
            $plan['stats']['pages'],
            $plan['stats']['publications'],
            $plan['stats']['landings'],
            count($changed),
            count($deleted)
        ));

        $rel = static fn (string $p): string => str_replace('\\', '/', substr($p, strlen($gestorPath) + 1));
        if ($input->hasOption('dry-run')) {
            foreach ($changed as $p) {
                $output->writeln('  ~ ' . $rel($p));
            }
            foreach ($deleted as $p) {
                $output->writeln('  - ' . $rel($p) . '/');
            }
            $output->success('Dry run: nothing was written.');

            return 0;
        }

        foreach ($changed as $p) {
            if (!is_dir(dirname($p))) {
                mkdir(dirname($p), 0777, true);
            }
            file_put_contents($p, $plan['write'][$p]);
        }
        foreach ($deleted as $dir) {
            $this->removeDir($dir);
        }

        $output->success(count($changed) === 0 && $deleted === [] ? 'Docs already up to date.' : 'Docs resources written.');
        $output->info("Next (local only): php cli/c2f.php project:update-all {$projectId}");

        return 0;
    }

    private function removeDir(string $dir): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }
}
