<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class DbTestCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'db:test';
    }

    public function getDescription(): string
    {
        return 'Run PHPUnit automated database tests against SQLite/MySQL test harness.';
    }

    public function getAliases(): array
    {
        return ['test:db', 'test:unit'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f db:test [--filter=TestName]\n\nRuns PHPUnit tests configured in phpunit.xml.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Automated Database Unit Tests');
        $phpunitBin = $this->rootPath . '/vendor/bin/phpunit';

        if (DIRECTORY_SEPARATOR === '\\' && file_exists($phpunitBin . '.bat')) {
            $phpunitBin .= '.bat';
        }

        if (!file_exists($phpunitBin) && !file_exists($this->rootPath . '/vendor/bin/phpunit')) {
            $output->warning('PHPUnit binary not found in vendor/bin. Falling back to global phpunit.');
            $phpunitBin = 'phpunit';
        }

        $filter = $input->getOption('filter');
        $cmd = escapeshellcmd($phpunitBin) . ' -c ' . escapeshellarg($this->rootPath . '/phpunit.xml');
        if ($filter) {
            $cmd .= ' --filter ' . escapeshellarg((string)$filter);
        }

        $output->info("Executing: {$cmd}");

        $process = proc_open($cmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes, $this->rootPath);

        if (!is_resource($process)) {
            $output->error("Failed to run command: {$cmd}");
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
