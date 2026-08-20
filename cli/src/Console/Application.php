<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Console;

use Conn2Flow\Cli\Commands\AiSyncCommand;
use Conn2Flow\Cli\Commands\DbTestCommand;
use Conn2Flow\Cli\Commands\DbUpdateCommand;
use Conn2Flow\Cli\Commands\DockerLogsCommand;
use Conn2Flow\Cli\Commands\DockerStatusCommand;
use Conn2Flow\Cli\Commands\DockerTruncateLogsCommand;
use Conn2Flow\Cli\Commands\HelpCommand;
use Conn2Flow\Cli\Commands\ModuleCreateCommand;
use Conn2Flow\Cli\Commands\ResourcesSyncCommand;
use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Throwable;

final class Application
{
    private string $rootPath;
    /** @var array<string, CommandInterface> */
    private array $commands = [];
    /** @var array<string, string> */
    private array $aliases = [];

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
        $this->registerBuiltInCommands();
    }

    private function registerBuiltInCommands(): void
    {
        $this->register(new HelpCommand($this));
        $this->register(new ResourcesSyncCommand($this->rootPath));
        $this->register(new AiSyncCommand($this->rootPath));
        $this->register(new ModuleCreateCommand($this->rootPath));
        $this->register(new DockerStatusCommand());
        $this->register(new DockerLogsCommand());
        $this->register(new DockerTruncateLogsCommand());
        $this->register(new DbTestCommand($this->rootPath));
        $this->register(new DbUpdateCommand($this->rootPath));
    }

    public function register(CommandInterface $command): void
    {
        $name = $command->getName();
        $this->commands[$name] = $command;

        foreach ($command->getAliases() as $alias) {
            $this->aliases[$alias] = $name;
        }
    }

    public function findCommand(string $name): ?CommandInterface
    {
        if (isset($this->commands[$name])) {
            return $this->commands[$name];
        }

        if (isset($this->aliases[$name])) {
            return $this->commands[$this->aliases[$name]] ?? null;
        }

        return null;
    }

    /**
     * @return array<string, CommandInterface>
     */
    public function getAllCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param array<string> $argv
     */
    public function run(array $argv = []): int
    {
        $input = new Input($argv);
        $output = new Output();

        $commandName = $input->getCommandName() ?? 'help';

        if ($input->hasOption('help') || $input->hasOption('h')) {
            if ($commandName !== 'help') {
                $cmd = $this->findCommand($commandName);
                if ($cmd) {
                    $output->title("Help: {$cmd->getName()}");
                    $output->writeln($cmd->getDescription());
                    $output->writeln('');
                    $output->writeln($cmd->getHelp());
                    $output->writeln('');
                    return 0;
                }
            }
            $commandName = 'help';
        }

        $command = $this->findCommand($commandName);

        if (!$command) {
            $output->error("Command '{$commandName}' is not defined.");
            $output->writeln("Run 'c2f help' to see all available commands.\n");
            return 1;
        }

        try {
            return $command->execute($input, $output);
        } catch (Throwable $e) {
            $output->error("Fatal Exception: " . $e->getMessage());
            $output->writeln("File: " . $e->getFile() . ":" . $e->getLine());
            if ($input->hasOption('verbose') || $input->hasOption('v')) {
                $output->writeln("\nStack Trace:\n" . $e->getTraceAsString());
            }
            return 1;
        }
    }
}
