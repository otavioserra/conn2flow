<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\Memory\Indexer;
use RuntimeException;

final class MemorySetCommand implements CommandInterface
{
    public function __construct(private string $rootPath) {}
    public function getName(): string { return 'memory:set'; }
    public function getAliases(): array { return []; }
    public function getDescription(): string { return 'Atomically update scalar frontmatter and rebuild the local index.'; }
    public function getHelp(): string { return 'Usage: c2f memory:set <target> --field=value [--field=value ...] [--repo=PATH]'; }
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $input->getArgument(0) ?? throw new RuntimeException($this->getHelp());
        $updates = [];
        foreach ($input->getRawArgv() as $token) {
            if (str_starts_with($token, '--') && str_contains($token, '=')) {
                [$key, $value] = explode('=', substr($token, 2), 2);
                if ($key !== 'repo') {
                    $updates[$key] = $value;
                }
            } elseif (str_starts_with($token, '--') && !in_array($token, ['--verbose', '--help'], true)) {
                throw new RuntimeException('Metadata options require --field=value.');
            }
        }
        $indexer = new Indexer((string)$input->getOption('repo', $this->rootPath));
        $output->writeln($indexer->set($target, $updates));
        return 0;
    }
}
