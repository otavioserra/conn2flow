<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Throwable;

/**
 * req-141 / CR-002 — mede a procedência e a cobertura do CSS dos recursos.
 *
 * Existe porque o defeito era invisível: o runtime entregava HTML de uma origem com CSS derivado de
 * outra sem emitir erro nenhum. Sem número não há como saber se uma correção melhorou o acervo.
 */
final class CssAuditCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'css:audit';
    }

    public function getDescription(): string
    {
        return 'Audit CSS provenance (stale derived CSS) and class coverage across resource tables.';
    }

    public function getAliases(): array
    {
        return ['css:auditoria', 'audit:css'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f css:audit [--project=ID] [--gestor=PATH] [--limite=N] [--json]\n\n" .
               "Reports, per resource table, how many records have derived CSS that no longer matches\n" .
               "the stored authorship (stale) and how many CSS classes the HTML uses without any rule\n" .
               "being delivered. Only resources with framework_css=tailwindcss are considered.\n\n" .
               "Options:\n" .
               "  --project=ID  Resolve the gestor path from environment.json (devProjects).\n" .
               "  --gestor=PATH Audit this gestor path directly (skips project resolution).\n" .
               "  --limite=N    How many worst cases to list (default 10).\n" .
               "  --json        Emit the raw report as JSON.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $gestorPath = (string)($input->getOption('gestor') ?? '');
        $projectId = (string)($input->getOption('project') ?? '');
        $envFile = '';

        if ($gestorPath === '' && $projectId !== '') {
            try {
                $resolvido = (new ProjectEnvironmentResolver($this->rootPath))->resolve($projectId);
                $gestorPath = (string)$resolvido['gestorPath'];
                $envFile = (string)($resolvido['envFile'] ?? '');
            } catch (Throwable $e) {
                $output->error($e->getMessage());
                return 1;
            }
        }

        if ($gestorPath === '') {
            $gestorPath = $this->rootPath . DIRECTORY_SEPARATOR . 'gestor';
        }

        $scriptPath = $this->rootPath . '/gestor/controladores/agents/arquitetura/css-auditoria.php';
        if (!file_exists($scriptPath)) {
            $output->error("CSS audit script not found at: {$scriptPath}");
            return 1;
        }

        $cmd = [PHP_BINARY, $scriptPath, '--gestor=' . $gestorPath];
        if ($envFile !== '') {
            $cmd[] = '--env=' . $envFile;
        }
        if ($input->getOption('limite')) {
            $cmd[] = '--limite=' . (string)$input->getOption('limite');
        }
        if ($input->getOption('json')) {
            $cmd[] = '--json';
        }

        $process = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $this->rootPath);

        if (!is_resource($process)) {
            $output->error('Failed to spawn the CSS audit subprocess.');
            return 1;
        }

        fclose($pipes[0]);

        while ($line = fgets($pipes[1])) {
            $output->write($line);
        }
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 && is_string($stderr) && trim($stderr) !== '') {
            $output->error(trim($stderr));
        }

        return $exitCode === 0 ? 0 : 1;
    }
}
