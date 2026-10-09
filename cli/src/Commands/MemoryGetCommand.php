<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\Memory\Indexer;
use RuntimeException;

final class MemoryGetCommand implements CommandInterface
{
    public function __construct(private string $rootPath) {}
    public function getName(): string { return 'memory:get'; }
    public function getAliases(): array { return []; }
    public function getDescription(): string { return 'Read memory metadata by path or unique document ID.'; }
    public function getHelp(): string { return 'Usage: c2f memory:get <target> [field] [--json] [--repo=PATH]'; }
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $input->getArgument(0) ?? throw new RuntimeException($this->getHelp());
        $indexer = new Indexer((string)$input->getOption('repo', $this->rootPath));
        $value = $indexer->get($target, $input->getArgument(1));
        $output->writeln(is_array($value) || $input->hasOption('json') ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : $value);
        return 0;
    }
}
