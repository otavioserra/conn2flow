<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Closure;
use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use RuntimeException;

final class AuthCookieCommand implements CommandInterface
{
    private const CONTAINER_NAME = 'conn2flow-app';

    private string $rootPath;
    private Closure $processRunner;

    public function __construct(string $rootPath, ?callable $processRunner = null)
    {
        $this->rootPath = rtrim($rootPath, '/\\');
        $this->processRunner = $processRunner !== null
            ? Closure::fromCallable($processRunner)
            : Closure::fromCallable([$this, 'runProcess']);
    }

    public function getName(): string
    {
        return 'auth:cookie';
    }

    public function getDescription(): string
    {
        return 'Generate authentication cookies (JWT + session) for automated access to authenticated routes.';
    }

    public function getAliases(): array
    {
        return ['auth:generate'];
    }

    public function getHelp(): string
    {
        return <<<HELP
Usage: c2f auth:cookie [--user=admin] [--project=ID] [--out=temp/agent-cookies.txt]

Generates a Netscape cookie jar file for use with curl -b or Playwright.
Also prints the Cookie header string to stdout.

Options:
  --user       User identifier (name or ID). Default: 'admin' (ID 1)
  --project    Project ID. Uses its test mirror and Docker mount when available
  --out        Output cookie jar path. Default: temp/agent-cookies.txt
HELP;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Generate Authentication Cookies');

        $userIdent = (string)$input->getOption('user', 'admin');
        $outPath = (string)$input->getOption('out', 'temp/agent-cookies.txt');
        $projectId = $input->getOption('project');
        $gestorPath = $this->rootPath . DIRECTORY_SEPARATOR . 'gestor';
        $dockerPath = null;
        $ssh = null;
        $accessUrl = 'http://localhost/';
        $host = 'localhost';

        if (is_string($projectId) && $projectId !== '') {
            try {
                $project = (new ProjectEnvironmentResolver($this->rootPath))->resolve($projectId);
            } catch (RuntimeException $exception) {
                $output->error($exception->getMessage());
                return 1;
            }

            $gestorPath = $project['gestorPath'];
            $dockerPath = $project['dockerPath'];
            $ssh = is_array($project['ssh'] ?? null) ? $project['ssh'] : null;
            $accessUrl = $project['accessUrl'];
            $host = $project['host'];
        }

        // Em `deploy_mode: "ssh"` o Gestor com `.env` e banco está na VM; o caminho local é o
        // repositório de autoria e não tem `config.php`. Exigi-lo aqui bloqueava toda a
        // homologação de rota autenticada depois da migração para o HestiaCP (req-034).
        $configFile = $gestorPath . DIRECTORY_SEPARATOR . 'config.php';
        if ($ssh === null && !is_file($configFile)) {
            $output->error("Gestor config.php not found at: {$configFile}");
            return 1;
        }

        $outPath = $this->absoluteOutputPath($outPath);
        $outDir = dirname($outPath);
        if (!is_dir($outDir) && !mkdir($outDir, 0755, true) && !is_dir($outDir)) {
            $output->error("Unable to create output directory at: {$outDir}");
            return 1;
        }

        $generator = $this->rootPath . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'scripts'
            . DIRECTORY_SEPARATOR . 'auth-cookie-generator.php';
        if (!is_file($generator)) {
            $output->error("Authentication cookie generator not found at: {$generator}");
            return 1;
        }

        $output->info("Using Gestor path: {$gestorPath}");
        $resultPath = null;

        try {
            if ($ssh !== null) {
                $output->info("Generating token over SSH on {$ssh['user']}@{$ssh['host']}:{$ssh['path']}...");
                $resultPath = $this->generateOverSsh($generator, $ssh, $host, $userIdent, $output);
            } elseif ($dockerPath !== null && !$this->isInsideContainer() && $this->isContainerRunning()) {
                $output->info('Generating token inside Docker container conn2flow-app...');
                $resultPath = $this->generateInDocker(
                    $generator,
                    $gestorPath,
                    $dockerPath,
                    $host,
                    $userIdent,
                    $output
                );
            } else {
                $output->info('Generating token with the local PHP runtime...');
                $resultPath = $this->generateLocally($generator, $gestorPath, $host, $userIdent, $output);
            }

            if ($resultPath === null) {
                return 1;
            }

            $result = $this->readGeneratorResult($resultPath, $output);
            if ($result === null) {
                return 1;
            }

            $cookieJar = $this->buildCookieJar($result);
            if (file_put_contents($outPath, $cookieJar, LOCK_EX) === false) {
                $output->error("Unable to write cookie jar at: {$outPath}");
                return 1;
            }

            $cookieHeader = "Cookie: {$result['cookieAuthName']}={$result['tokenJWT']}; "
                . "{$result['sessionAuthName']}={$result['sessionId']}";

            $output->success("User found: {$result['userName']} (ID: {$result['userId']})");
            $output->section('Cookie Jar');
            $output->success("Written to: {$outPath}");
            $output->section('HTTP Header');
            $output->writeln($cookieHeader);
            $output->section('Usage Examples');
            $output->writeln("  curl -b {$outPath} {$accessUrl}");
            $output->writeln("  curl -H \"{$cookieHeader}\" {$accessUrl}");
            $output->writeln('');
            $output->info('Token expires: ' . date('Y-m-d H:i:s', (int)$result['expiration']));

            return 0;
        } finally {
            if ($resultPath !== null && is_file($resultPath)) {
                unlink($resultPath);
            }
        }
    }

    private function absoluteOutputPath(string $path): string
    {
        if (str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Za-z]:/', $path)) {
            return $path;
        }

        return $this->rootPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function isInsideContainer(): bool
    {
        return is_file('/.dockerenv') || getenv('CONN2FLOW_IN_DOCKER') === '1';
    }

    private function isContainerRunning(): bool
    {
        $result = $this->callProcess([
            'docker',
            'inspect',
            '--format={{.State.Running}}',
            self::CONTAINER_NAME,
        ]);

        return $result['code'] === 0 && trim($result['stdout']) === 'true';
    }

    private function generateLocally(
        string $generator,
        string $gestorPath,
        string $host,
        string $userIdent,
        OutputInterface $output
    ): ?string {
        $resultPath = tempnam(sys_get_temp_dir(), 'c2f-auth-cookie-');
        if ($resultPath === false) {
            $output->error('Unable to reserve a temporary result file.');
            return null;
        }

        $result = $this->callProcess([
            PHP_BINARY,
            $generator,
            '--gestor=' . $gestorPath,
            '--host=' . $host,
            '--user=' . $userIdent,
            '--result=' . $resultPath,
        ]);

        if ($result['code'] !== 0) {
            @unlink($resultPath);
            $output->error('Authentication cookie generation failed: ' . trim($result['stderr'] ?: $result['stdout']));
            return null;
        }

        return $resultPath;
    }

    /**
     * Gera o token na VM que hospeda o Gestor publicado.
     *
     * Mesma coreografia do caminho Docker — copia o gerador, executa, lê o JSON de volta —,
     * trocando `docker cp`/`docker exec` por `scp`/`ssh`. O gerador roda sob o usuário dono do
     * docroot (`ssh_run_as`), porque é ele que enxerga o `.env` e o banco do tenant.
     *
     * @param array{user: string, host: string, port: int, path: string, runAs: ?string, sudo: bool} $ssh
     */
    private function generateOverSsh(
        string $generator,
        array $ssh,
        string $host,
        string $userIdent,
        OutputInterface $output
    ): ?string {
        $alvo = $ssh['user'] . '@' . $ssh['host'];
        $porta = (string)$ssh['port'];
        $sufixo = getmypid() . '-' . bin2hex(random_bytes(4));
        $scriptRemoto = '/tmp/c2f-auth-cookie-' . $sufixo . '.php';
        $resultadoRemoto = '/tmp/c2f-auth-cookie-' . $sufixo . '.json';

        $copia = $this->callProcess([
            'scp', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=15',
            '-P', $porta, $generator, $alvo . ':' . $scriptRemoto,
        ]);
        if ($copia['code'] !== 0) {
            $output->error('Unable to copy cookie generator over SSH: ' . trim($copia['stderr'] ?: $copia['stdout']));
            return null;
        }

        $resultPath = null;

        try {
            $php = 'php ' . escapeshellarg($scriptRemoto)
                . ' --gestor=' . escapeshellarg($ssh['path'])
                . ' --host=' . escapeshellarg($host)
                . ' --user=' . escapeshellarg($userIdent)
                . ' --result=' . escapeshellarg($resultadoRemoto);

            if (is_string($ssh['runAs']) && $ssh['runAs'] !== '') {
                if (preg_match('/^[A-Za-z0-9._-]+$/', $ssh['runAs']) !== 1) {
                    $output->error("Invalid ssh_run_as value: {$ssh['runAs']}");
                    return null;
                }
                $php = 'sudo -u ' . escapeshellarg($ssh['runAs']) . ' ' . $php;
            }

            // O JSON nasce com a posse de quem rodou o gerador; sem isto o `scp` de volta,
            // feito pela conta SSH, esbarra na permissão do próprio arquivo que acabou de criar.
            $comando = 'cd ' . escapeshellarg($ssh['path']) . ' && ' . $php
                . ' && sudo chmod 0644 ' . escapeshellarg($resultadoRemoto);

            $execucao = $this->callProcess([
                'ssh', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=15',
                '-p', $porta, $alvo, '--', $comando,
            ]);
            if ($execucao['code'] !== 0) {
                $output->error(
                    'Authentication cookie generation failed over SSH: '
                    . trim($execucao['stderr'] ?: $execucao['stdout'])
                );
                return null;
            }

            $resultPath = tempnam(sys_get_temp_dir(), 'c2f-auth-cookie-');
            if ($resultPath === false) {
                $output->error('Unable to reserve a temporary result file.');
                return null;
            }

            $retorno = $this->callProcess([
                'scp', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=15',
                '-P', $porta, $alvo . ':' . $resultadoRemoto, $resultPath,
            ]);
            if ($retorno['code'] !== 0) {
                @unlink($resultPath);
                $output->error('Unable to retrieve the cookie result over SSH: ' . trim($retorno['stderr']));
                return null;
            }

            return $resultPath;
        } finally {
            // O gerador e o JSON carregam credencial de sessão: não podem sobrar em /tmp da VM.
            $this->callProcess([
                'ssh', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=15',
                '-p', $porta, $alvo, '--',
                'sudo rm -f ' . escapeshellarg($scriptRemoto) . ' ' . escapeshellarg($resultadoRemoto),
            ]);
        }
    }

    private function generateInDocker(
        string $generator,
        string $gestorPath,
        string $dockerPath,
        string $host,
        string $userIdent,
        OutputInterface $output
    ): ?string {
        $tempDir = $gestorPath . DIRECTORY_SEPARATOR . 'temp';
        if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
            $output->error("Unable to create project temp directory at: {$tempDir}");
            return null;
        }

        $suffix = getmypid() . '-' . bin2hex(random_bytes(4));
        $containerScript = '/tmp/c2f-auth-cookie-' . $suffix . '.php';
        $hostResult = $tempDir . DIRECTORY_SEPARATOR . '.c2f-auth-cookie-' . $suffix . '.json';
        $dockerResult = rtrim($dockerPath, '/') . '/temp/.c2f-auth-cookie-' . $suffix . '.json';

        $copyResult = $this->callProcess([
            'docker',
            'cp',
            $generator,
            self::CONTAINER_NAME . ':' . $containerScript,
        ]);
        if ($copyResult['code'] !== 0) {
            $output->error('Unable to copy cookie generator into Docker: ' . trim($copyResult['stderr']));
            return null;
        }

        try {
            $execResult = $this->callProcess([
                'docker',
                'exec',
                self::CONTAINER_NAME,
                'php',
                $containerScript,
                '--gestor=' . rtrim($dockerPath, '/'),
                '--host=' . $host,
                '--user=' . $userIdent,
                '--result=' . $dockerResult,
            ]);

            if ($execResult['code'] !== 0) {
                @unlink($hostResult);
                $output->error('Docker cookie generation failed: ' . trim($execResult['stderr'] ?: $execResult['stdout']));
                return null;
            }
        } finally {
            $this->callProcess(['docker', 'exec', self::CONTAINER_NAME, 'rm', '-f', $containerScript]);
        }

        if (!is_file($hostResult)) {
            $output->error("Docker generator did not create its result at: {$hostResult}");
            return null;
        }

        return $hostResult;
    }

    /** @return array<string, mixed>|null */
    private function readGeneratorResult(string $path, OutputInterface $output): ?array
    {
        $contents = file_get_contents($path);
        $result = $contents !== false ? json_decode($contents, true) : null;
        $required = [
            'userId',
            'userName',
            'expiration',
            'domain',
            'cookieAuthName',
            'sessionAuthName',
            'tokenJWT',
            'sessionId',
        ];

        if (!is_array($result) || array_diff($required, array_keys($result)) !== []) {
            $output->error('Authentication cookie generator returned an invalid result.');
            return null;
        }

        return $result;
    }

    /** @param array<string, mixed> $result */
    private function buildCookieJar(array $result): string
    {
        $expiration = (string)$result['expiration'];
        $cookieJar = "# Netscape HTTP Cookie File\n";
        $cookieJar .= '# Generated by c2f auth:cookie on ' . date('Y-m-d H:i:s') . "\n";
        $cookieJar .= "# User: {$result['userName']} (ID: {$result['userId']})\n";
        $cookieJar .= "{$result['domain']}\tFALSE\t/\tFALSE\t{$expiration}\t{$result['cookieAuthName']}\t{$result['tokenJWT']}\n";
        $cookieJar .= "{$result['domain']}\tFALSE\t/\tFALSE\t{$expiration}\t{$result['sessionAuthName']}\t{$result['sessionId']}\n";

        return $cookieJar;
    }

    /**
     * @param list<string> $command
     * @return array{code: int, stdout: string, stderr: string}
     */
    private function callProcess(array $command): array
    {
        $runner = $this->processRunner;
        $result = $runner($command);

        if (!is_array($result) || !isset($result['code'], $result['stdout'], $result['stderr'])) {
            throw new RuntimeException('Process runner returned an invalid result.');
        }

        return [
            'code' => (int)$result['code'],
            'stdout' => (string)$result['stdout'],
            'stderr' => (string)$result['stderr'],
        ];
    }

    /**
     * @param list<string> $command
     * @return array{code: int, stdout: string, stderr: string}
     */
    private function runProcess(array $command): array
    {
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $this->rootPath);

        if (!is_resource($process)) {
            return ['code' => 1, 'stdout' => '', 'stderr' => 'Failed to start process.'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return [
            'code' => proc_close($process),
            'stdout' => $stdout !== false ? $stdout : '',
            'stderr' => $stderr !== false ? $stderr : '',
        ];
    }
}
