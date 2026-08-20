<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Contracts;

interface CommandInterface
{
    /**
     * Get the unique command name (e.g. 'resources:sync').
     */
    public function getName(): string;

    /**
     * Get a short description of what the command does.
     */
    public function getDescription(): string;

    /**
     * Get command aliases (e.g. ['sync:resources', 'res']).
     *
     * @return array<string>
     */
    public function getAliases(): array;

    /**
     * Get detailed help / usage text for this command.
     */
    public function getHelp(): string;

    /**
     * Execute the command logic.
     *
     * @return int Exit code (0 for success, non-zero for failure)
     */
    public function execute(InputInterface $input, OutputInterface $output): int;
}
