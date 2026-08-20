<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Console;

use Conn2Flow\Cli\Contracts\InputInterface;

final class Input implements InputInterface
{
    private ?string $commandName = null;
    /** @var array<int, string> */
    private array $arguments = [];
    /** @var array<string, mixed> */
    private array $options = [];
    /** @var array<string> */
    private array $rawArgv;

    /**
     * @param array<string> $argv
     */
    public function __construct(array $argv = [])
    {
        $this->rawArgv = $argv;
        $this->parse($argv);
    }

    /**
     * @param array<string> $argv
     */
    private function parse(array $argv): void
    {
        // Discard script name ($argv[0])
        $tokens = array_slice($argv, 1);

        $commandFound = false;

        foreach ($tokens as $token) {
            if (str_starts_with($token, '--')) {
                $opt = substr($token, 2);
                if (str_contains($opt, '=')) {
                    [$key, $val] = explode('=', $opt, 2);
                    $this->options[$key] = $val;
                } else {
                    $this->options[$opt] = true;
                }
            } elseif (str_starts_with($token, '-') && strlen($token) > 1) {
                $short = substr($token, 1);
                $this->options[$short] = true;
            } elseif (!$commandFound) {
                $this->commandName = $token;
                $commandFound = true;
            } else {
                $this->arguments[] = $token;
            }
        }
    }

    public function getCommandName(): ?string
    {
        return $this->commandName;
    }

    public function getArgument(int $index, ?string $default = null): ?string
    {
        return $this->arguments[$index] ?? $default;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function getRawArgv(): array
    {
        return $this->rawArgv;
    }
}
