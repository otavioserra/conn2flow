<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Conn2Flow\Cli\Support\SshRemoteTransport;
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
               "  --json        Emit the raw report as JSON.\n" .
               "  --simular-remoto  Print the SSH command without executing it.";
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

            // Em projetos SSH, `.env`, banco e CSS derivado pertencem ao Gestor publicado na VM.
            // A auditoria é somente leitura, portanto pode ser delegada sem a confirmação exigida
            // pelas operações que reescrevem dados (`css:rebuild` e `assets:publish`).
            if (is_array($resolvido['ssh'] ?? null)) {
                return $this->auditarViaSsh($resolvido, $projectId, $input, $output);
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

    /**
     * Executa o próprio CLI na raiz remota, sem repassar `--project`: dentro da VM o Gestor
     * corrente já é o alvo e resolve seu `.env` nativamente.
     *
     * @param array<string, mixed> $resolvido
     */
    private function auditarViaSsh(
        array $resolvido,
        string $projectId,
        InputInterface $input,
        OutputInterface $output
    ): int {
        /** @var array<string, mixed> $ssh */
        $ssh = $resolvido['ssh'];
        /** @var array<string, mixed> $config */
        $config = is_array($resolvido['config'] ?? null) ? $resolvido['config'] : [];

        try {
            $transport = new SshRemoteTransport($ssh, $config);
        } catch (Throwable $e) {
            $output->error($e->getMessage());
            return 1;
        }

        // O CLI de desenvolvimento não é publicado em todas as instalações. O controlador da
        // auditoria, por outro lado, faz parte do Gestor remoto; chamá-lo diretamente mantém o
        // mesmo relatório e deixa a resolução do `.env` a cargo da própria VM.
        $argv = [
            'php',
            'controladores/agents/arquitetura/css-auditoria.php',
            '--gestor=.',
        ];

        $limite = $input->getOption('limite');
        if (is_string($limite) && $limite !== '') {
            $argv[] = '--limite=' . $limite;
        }
        if ($input->hasOption('json')) {
            $argv[] = '--json';
        }

        $comando = $transport->buildRemoteCommand($argv);
        // `--json` precisa permanecer consumível por máquina: no caminho real, somente o JSON
        // produzido pela VM vai para stdout. A simulação continua exibindo a linha SSH auditável.
        if (!$input->hasOption('json') || $input->hasOption('simular-remoto')) {
            $output->write('  transporte: ssh ' . $transport->describe() . PHP_EOL);
            $output->write('  comando remoto: ' . $comando . PHP_EOL);
        }

        if ($input->hasOption('simular-remoto')) {
            $output->info('Simulação: a auditoria acima não foi executada (--simular-remoto).');
            return 0;
        }

        $codigo = $this->runShellCommand($comando, $output);
        if ($codigo !== 0) {
            $output->error(
                "A auditoria remota de CSS falhou para '{$projectId}' (código {$codigo}). "
                . 'Confirme o acesso SSH sem senha e a presença do CLI na raiz remota.'
            );
            return 1;
        }

        return 0;
    }

    private function runShellCommand(string $comando, OutputInterface $output): int
    {
        $process = proc_open($comando, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $this->rootPath);

        if (!is_resource($process)) {
            $output->error('Não foi possível iniciar o processo SSH.');
            return 1;
        }

        fclose($pipes[0]);
        while ($linha = fgets($pipes[1])) {
            $output->write($linha);
        }
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $codigo = proc_close($process);

        if ($codigo !== 0 && is_string($stderr) && trim($stderr) !== '') {
            $output->error(trim($stderr));
        }

        return $codigo;
    }
}
