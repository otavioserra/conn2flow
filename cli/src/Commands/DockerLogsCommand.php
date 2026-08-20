<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class DockerLogsCommand implements CommandInterface
{
    private string $container = 'conn2flow-app';

    public function getName(): string
    {
        return 'docker:logs';
    }

    public function getDescription(): string
    {
        return 'Show recent PHP / Apache error logs from the conn2flow-app container.';
    }

    public function getAliases(): array
    {
        return ['docker:log'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f docker:logs [--lines=50]\n\nDisplays container logs.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $lines = (int)($input->getOption('lines') ?: 50);
        $output->title("Docker Logs ({$this->container} - Last {$lines} lines)");

        $process = proc_open("docker logs {$this->container} --tail {$lines}", [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes);

        if (!is_resource($process)) {
            $output->error("Failed to run docker logs");
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
