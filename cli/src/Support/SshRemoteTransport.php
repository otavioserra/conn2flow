<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support;

use RuntimeException;

/**
 * REQ-050 / BATCH-052 — contraparte PHP de `ai-workspace/en/scripts/lib/project-transport.sh`.
 *
 * Os comandos do CLI que precisam alcançar um Gestor publicado por SSH (`css:rebuild` dentro da
 * VM e `assets:publish` no docroot da VM) constroem aqui a linha de comando. As guardas são as
 * mesmas da biblioteca bash e existem pelas mesmas razões:
 *
 * 1. **Caminho remoto absoluto.** Um valor relativo cairia no home da conta SSH e `/` alcançaria a
 *    raiz do convidado — com `--delete` no rsync isso é destrutivo. A recusa é aqui, não no servidor.
 * 2. **Citação argumento a argumento.** `ssh` concatena os argumentos e o servidor os entrega ao
 *    shell; interpolar valor do `environment.json` na linha remota seria execução arbitrária.
 * 3. **BatchMode obrigatório.** Um pipeline que para num prompt de senha fica pendurado até o
 *    timeout do chamador sem dizer por quê.
 */
final class SshRemoteTransport
{
    private string $user;
    private string $host;
    private int $port;
    private string $path;
    private ?string $runAs;
    private bool $sudo;
    private ?string $identity;
    private ?string $publicPath;
    private string $cliEntrypoint;
    private ?string $cliPath;

    /**
     * @param array<string, mixed> $ssh Bloco `ssh` devolvido por ProjectEnvironmentResolver.
     * @param array<string, mixed> $project Configuração bruta do projeto no environment.json.
     */
    public function __construct(array $ssh, array $project = [])
    {
        $this->user = $this->requireString($ssh['user'] ?? null, 'ssh_user');
        $this->host = $this->requireString($ssh['host'] ?? null, 'ssh_host');
        $this->path = $this->requireAbsolutePath($ssh['path'] ?? null, 'ssh_target_path');

        $port = (int)($ssh['port'] ?? 22);
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException("ssh_port must be a valid TCP port (got '{$port}').");
        }
        $this->port = $port;

        $this->runAs = $this->optionalUserName($ssh['runAs'] ?? null, 'ssh_run_as');
        $this->sudo = filter_var($ssh['sudo'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->identity = $this->optionalString($project['ssh_identity'] ?? null);

        // REQ-050: docroot da VM. Opcional — sem ele, `assets:publish` apenas informa que a
        // instalação não usa `dist/` em vez de publicar no lugar errado.
        $publicPath = $this->optionalString($project['ssh_public_path'] ?? null);
        $this->publicPath = $publicPath === null ? null : $this->requireAbsolutePath($publicPath, 'ssh_public_path');

        $this->cliEntrypoint = $this->optionalString($project['ssh_cli_entrypoint'] ?? null) ?? './c2f';
        $cliPath = $this->optionalString($project['ssh_cli_path'] ?? null);
        $this->cliPath = $cliPath === null ? null : $this->requireAbsolutePath($cliPath, 'ssh_cli_path');
    }

    public function target(): string
    {
        return $this->user . '@' . $this->host;
    }

    public function remotePath(): string
    {
        return $this->path;
    }

    public function publicPath(): ?string
    {
        return $this->publicPath;
    }

    /** Diretório em que o `c2f` remoto é executado (raiz do Gestor por padrão). */
    public function cliWorkingDirectory(): string
    {
        return $this->cliPath ?? $this->path;
    }

    /**
     * Entrypoint do CLI remoto já quebrado em argumentos, para que cada um seja citado
     * individualmente. Aceita tanto `./c2f` quanto `php cli/c2f.php`.
     *
     * @return list<string>
     */
    public function cliEntrypointArgv(): array
    {
        $parts = preg_split('/\s+/', $this->cliEntrypoint, -1, PREG_SPLIT_NO_EMPTY);

        return $parts === false || $parts === [] ? ['./c2f'] : $parts;
    }

    public function describe(): string
    {
        return sprintf('%s:%d%s', $this->target(), $this->port, $this->path);
    }

    /**
     * Linha de comando `ssh` que executa `$argv` na raiz do Gestor remoto.
     *
     * @param list<string> $argv
     */
    public function buildRemoteCommand(array $argv, ?string $workingDirectory = null): string
    {
        if ($argv === []) {
            throw new RuntimeException('A remote command needs at least one argument.');
        }

        $remote = implode(' ', array_map([$this, 'posixQuote'], $argv));

        if ($this->runAs !== null) {
            $remote = 'sudo -u ' . $this->posixQuote($this->runAs) . ' ' . $remote;
        }

        $directory = $workingDirectory ?? $this->cliWorkingDirectory();
        $remote = 'cd ' . $this->posixQuote($directory) . ' && ' . $remote;

        return 'ssh ' . implode(' ', $this->sshOptions()) . ' ' . escapeshellarg($this->target())
            . ' ' . escapeshellarg($remote);
    }

    /**
     * Linha de comando `rsync` que envia um diretório local para a VM.
     * Ambos os caminhos são tratados como diretórios (barra final obrigatória no rsync).
     */
    public function buildRsyncCommand(string $localDirectory, string $remoteDirectory, bool $delete = false): string
    {
        $local = rtrim(str_replace('\\', '/', $localDirectory), '/') . '/';
        $remote = $this->requireAbsolutePath($remoteDirectory, 'remote directory') . '/';

        $options = ['-az'];
        if ($delete) {
            $options[] = '--delete';
        }

        $options[] = '-e';
        $options[] = escapeshellarg('ssh ' . implode(' ', $this->sshOptions()));

        if ($this->sudo) {
            // O usuário SSH normalmente não é o dono do docroot: elevar apenas o rsync remoto
            // evita conceder shell privilegiado ao pipeline inteiro.
            $options[] = '--rsync-path';
            $options[] = escapeshellarg('sudo rsync');
        }

        return 'rsync ' . implode(' ', $options) . ' ' . escapeshellarg($local)
            . ' ' . escapeshellarg($this->target() . ':' . $remote);
    }

    /** Cria o diretório remoto antes do primeiro rsync. */
    public function buildEnsureDirectoryCommand(string $remoteDirectory): string
    {
        $remote = $this->requireAbsolutePath($remoteDirectory, 'remote directory');
        $mkdir = ($this->sudo ? 'sudo ' : '') . 'mkdir -p ' . $this->posixQuote($remote);

        return 'ssh ' . implode(' ', $this->sshOptions()) . ' ' . escapeshellarg($this->target())
            . ' ' . escapeshellarg($mkdir);
    }

    /** @return list<string> */
    public function sshOptions(): array
    {
        $options = ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=15', '-p', (string)$this->port];

        if ($this->identity !== null) {
            $options[] = '-i';
            $options[] = escapeshellarg($this->identity);
        }

        return $options;
    }

    /** Citação POSIX do lado REMOTO — independente do shell local que dispara o ssh. */
    public function posixQuote(string $value): string
    {
        return "'" . str_replace("'", "'\\''", $value) . "'";
    }

    private function requireString(mixed $value, string $field): string
    {
        $string = $this->optionalString($value);
        if ($string === null) {
            throw new RuntimeException("deploy_mode \"ssh\" requires {$field} in environment.json.");
        }

        return $string;
    }

    private function requireAbsolutePath(mixed $value, string $field): string
    {
        $path = $this->requireString($value, $field);
        $path = rtrim(str_replace('\\', '/', $path), '/');

        if ($path === '') {
            throw new RuntimeException("{$field} cannot be the filesystem root.");
        }

        if (!str_starts_with($path, '/')) {
            throw new RuntimeException("{$field} must be an absolute path (got '{$value}').");
        }

        return $path;
    }

    private function optionalUserName(mixed $value, string $field): ?string
    {
        $name = $this->optionalString($value);
        if ($name === null) {
            return null;
        }

        if (preg_match('/^[a-zA-Z0-9._-]+$/', $name) !== 1) {
            throw new RuntimeException("{$field} must be a plain user name (got '{$name}').");
        }

        return $name;
    }

    private function optionalString(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
