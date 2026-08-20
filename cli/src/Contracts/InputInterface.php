<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Contracts;

interface InputInterface
{
    /**
     * Get the primary command name from the CLI input.
     */
    public function getCommandName(): ?string;

    /**
     * Get positional argument by index (0-indexed after command name).
     */
    public function getArgument(int $index, ?string $default = null): ?string;

    /**
     * Get all positional arguments.
     *
     * @return array<int, string>
     */
    public function getArguments(): array;

    /**
     * Get option value (--option=val or --option).
     */
    public function getOption(string $name, mixed $default = null): mixed;

    /**
     * Check if an option exists in the input.
     */
    public function hasOption(string $name): bool;

    /**
     * Get raw argv array.
     *
     * @return array<string>
     */
    public function getRawArgv(): array;
}
