<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class DockerTruncateLogsCommand implements CommandInterface
{
    private string $container = 'conn2flow-app';

    public function getName(): string
    {
        return 'docker:truncate-logs';
    }

    public function getDescription(): string
    {
        return 'Truncate /var/log/php_errors.log inside the container.';
    }

    public function getAliases(): array
    {
        return ['docker:clean-logs'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f docker:truncate-logs\n\nCleans the PHP error log inside the Docker container.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Truncating PHP Error Logs in Docker');
        $process = proc_open("docker exec {$this->container} bash -c \"truncate -s 0 /var/log/php_errors.log 2>/dev/null || true\"", [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes);

        if (!is_resource($process)) {
            $output->error("Failed to run docker exec");
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
        if ($code === 0) {
            $output->success('PHP error log truncated successfully.');
        } elseif (!empty($err)) {
            $output->error($err);
        }

        return $code;
    }
}
