<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

abstract class BaseProcessCommand implements CommandInterface
{
    protected string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    protected function runShell(string $cmd, OutputInterface $output, ?string $cwd = null): int
    {
        $workingDir = $cwd ?: $this->rootPath;

        $process = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes, $workingDir);

        if (!is_resource($process)) {
            $output->error("Failed to spawn process: {$cmd}");
            return 1;
        }

        fclose($pipes[0]);

        while ($line = fgets($pipes[1])) {
            $output->write($line);
        }
        fclose($pipes[1]);

        $err = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $code = proc_close($process);
        if ($code !== 0 && !empty($err)) {
            $output->error($err);
        }

        return $code;
    }
}
