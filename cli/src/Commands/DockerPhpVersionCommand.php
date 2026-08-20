<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class DockerPhpVersionCommand implements CommandInterface
{
    private string $container = 'conn2flow-app';

    public function getName(): string
    {
        return 'docker:php-version';
    }

    public function getDescription(): string
    {
        return 'Check the active PHP version inside the conn2flow-app Docker container.';
    }

    public function getAliases(): array
    {
        return ['docker:php', 'php:version'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f docker:php-version\n\nRuns 'docker exec conn2flow-app php -v'";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title("Docker PHP Version ({$this->container})");
        $process = proc_open("docker exec {$this->container} bash -c \"php -v\"", [
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
        if ($code !== 0 && !empty($err)) {
            $output->error($err);
        }

        return $code;
    }
}
