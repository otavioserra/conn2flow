<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class DockerStatusCommand implements CommandInterface
{
    private string $container = 'conn2flow-app';

    public function getName(): string
    {
        return 'docker:status';
    }

    public function getDescription(): string
    {
        return 'Check the status of Docker containers in the dev environment.';
    }

    public function getAliases(): array
    {
        return ['docker:ps'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f docker:status\n\nShows running containers and ports.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Docker Environment Status');
        $process = proc_open('docker ps', [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes);

        if (!is_resource($process)) {
            $output->error("Failed to run docker ps");
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
