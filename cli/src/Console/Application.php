<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Console;

use Conn2Flow\Cli\Commands\AiMcpSetupCommand;
use Conn2Flow\Cli\Commands\AiPruneMemoriesCommand;
use Conn2Flow\Cli\Commands\AiSyncCommand;
use Conn2Flow\Cli\Commands\AuthCookieCommand;
use Conn2Flow\Cli\Commands\DbTestCommand;
use Conn2Flow\Cli\Commands\DbUpdateCommand;
use Conn2Flow\Cli\Commands\DockerLogsCommand;
use Conn2Flow\Cli\Commands\DockerPhpVersionCommand;
use Conn2Flow\Cli\Commands\DockerStatusCommand;
use Conn2Flow\Cli\Commands\DockerTruncateLogsCommand;
use Conn2Flow\Cli\Commands\EnvSetCommand;
use Conn2Flow\Cli\Commands\EnvStatusCommand;
use Conn2Flow\Cli\Commands\HelpCommand;
use Conn2Flow\Cli\Commands\InstallerBuildCommand;
use Conn2Flow\Cli\Commands\InstallerNewCommand;
use Conn2Flow\Cli\Commands\InstallerReleaseCommand;
use Conn2Flow\Cli\Commands\InstallerSyncCommand;
use Conn2Flow\Cli\Commands\ManagerBuildCommand;
use Conn2Flow\Cli\Commands\ManagerCommitCommand;
use Conn2Flow\Cli\Commands\ManagerReleaseCommand;
use Conn2Flow\Cli\Commands\ManagerSyncFilesCommand;
use Conn2Flow\Cli\Commands\ManagerUpdateAllCommand;
use Conn2Flow\Cli\Commands\ModuleCreateCommand;
use Conn2Flow\Cli\Commands\PageInspectCommand;
use Conn2Flow\Cli\Commands\PluginBuildCommand;
use Conn2Flow\Cli\Commands\PluginCommitCommand;
use Conn2Flow\Cli\Commands\PluginReleaseCommand;
use Conn2Flow\Cli\Commands\PluginResourcesCommand;
use Conn2Flow\Cli\Commands\PluginSyncCommand;
use Conn2Flow\Cli\Commands\ProjectDeployCommand;
use Conn2Flow\Cli\Commands\ProjectRecoverCommand;
use Conn2Flow\Cli\Commands\ProjectSyncCoreCommand;
use Conn2Flow\Cli\Commands\ProjectSyncDbCommand;
use Conn2Flow\Cli\Commands\ProjectSyncFilesCommand;
use Conn2Flow\Cli\Commands\ProjectSyncResourcesCommand;
use Conn2Flow\Cli\Commands\ProjectUpdateAllCommand;
use Conn2Flow\Cli\Commands\ProjectUpdateSystemCommand;
use Conn2Flow\Cli\Commands\ResourcesSyncCommand;
use Conn2Flow\Cli\Commands\TailwindFixSpacingCommand;
use Conn2Flow\Cli\Contracts\CommandInterface;
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
        // General / Help
        $this->register(new HelpCommand($this));

        // Resources & DB
        $this->register(new ResourcesSyncCommand($this->rootPath));
        $this->register(new DbTestCommand($this->rootPath));
        $this->register(new DbUpdateCommand($this->rootPath));

        // AI & SDD
        $this->register(new AiSyncCommand($this->rootPath));
        $this->register(new AiPruneMemoriesCommand($this->rootPath));
        $this->register(new AiMcpSetupCommand($this->rootPath));

        // Modules & Manager
        $this->register(new ModuleCreateCommand($this->rootPath));
        $this->register(new ManagerBuildCommand($this->rootPath));
        $this->register(new ManagerSyncFilesCommand($this->rootPath));
        $this->register(new ManagerUpdateAllCommand($this->rootPath));
        $this->register(new ManagerCommitCommand($this->rootPath));
        $this->register(new ManagerReleaseCommand($this->rootPath));

        // Plugins
        $this->register(new PluginSyncCommand($this->rootPath));
        $this->register(new PluginBuildCommand($this->rootPath));
        $this->register(new PluginResourcesCommand($this->rootPath));
        $this->register(new PluginCommitCommand($this->rootPath));
        $this->register(new PluginReleaseCommand($this->rootPath));

        // Projects
        $this->register(new ProjectSyncCoreCommand($this->rootPath));
        $this->register(new ProjectSyncResourcesCommand($this->rootPath));
        $this->register(new ProjectSyncFilesCommand($this->rootPath));
        $this->register(new ProjectSyncDbCommand($this->rootPath));
        $this->register(new ProjectUpdateAllCommand($this->rootPath));
        $this->register(new ProjectDeployCommand($this->rootPath));
        $this->register(new ProjectRecoverCommand($this->rootPath));
        $this->register(new ProjectUpdateSystemCommand($this->rootPath));

        // Installer
        $this->register(new InstallerSyncCommand($this->rootPath));
        $this->register(new InstallerBuildCommand($this->rootPath));
        $this->register(new InstallerNewCommand($this->rootPath));
        $this->register(new InstallerReleaseCommand($this->rootPath));

        // Docker & Frontend
        $this->register(new DockerStatusCommand());
        $this->register(new DockerPhpVersionCommand());
        $this->register(new DockerLogsCommand());
        $this->register(new DockerTruncateLogsCommand());
        $this->register(new TailwindFixSpacingCommand($this->rootPath));

        // Environment
        $this->register(new EnvStatusCommand($this->rootPath));
        $this->register(new EnvSetCommand($this->rootPath));

        // Auth
        $this->register(new AuthCookieCommand($this->rootPath));

        // Inspect
        $this->register(new PageInspectCommand($this->rootPath));
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
