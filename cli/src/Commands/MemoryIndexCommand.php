<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\Memory\Indexer;

final class MemoryIndexCommand implements CommandInterface
{
    public function __construct(private string $rootPath) {}
    public function getName(): string { return 'memory:index'; }
    public function getAliases(): array { return []; }
    public function getDescription(): string { return 'Rebuild memory indexes from metadata with legacy fallback.'; }
    public function getHelp(): string { return 'Usage: c2f memory:index [folder] [--repo=PATH]'; }
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $indexer = new Indexer((string)$input->getOption('repo', $this->rootPath));
        foreach ($indexer->index($input->getArgument(0)) as $path) {
            $output->writeln($path);
        }
        return 0;
    }
}
