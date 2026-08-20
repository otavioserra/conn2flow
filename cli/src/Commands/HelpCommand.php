<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Console\Application;
use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class HelpCommand implements CommandInterface
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getName(): string
    {
        return 'help';
    }

    public function getDescription(): string
    {
        return 'Display help and list all available Conn2Flow CLI commands.';
    }

    public function getAliases(): array
    {
        return ['list', '--help', '-h'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f [command] [options] [arguments]\n\nType 'c2f help [command]' for details on a specific command.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandName = $input->getArgument(0);

        if ($commandName) {
            $cmd = $this->app->findCommand($commandName);
            if (!$cmd) {
                $output->error("Command '{$commandName}' not found.");
                return 1;
            }

            $output->title("Conn2Flow CLI — {$cmd->getName()}");
            $output->writeln($cmd->getDescription());
            $output->writeln('');
            if (!empty($cmd->getAliases())) {
                $output->writeln("Aliases: " . implode(', ', $cmd->getAliases()));
                $output->writeln('');
            }
            $output->section('Usage');
            $output->writeln($cmd->getHelp());
            $output->writeln('');
            return 0;
        }

        $output->title('Conn2Flow Core CLI (c2f) v2.5.0');
        $output->writeln('Modern OOP CLI Subsystem for Conn2Flow Platform.');
        $output->writeln('');
        $output->section('Available Commands');

        $commands = $this->app->getAllCommands();
        $tableRows = [];

        // Group commands by namespace
        $grouped = [];
        foreach ($commands as $cmd) {
            $name = $cmd->getName();
            $parts = explode(':', $name, 2);
            $ns = count($parts) > 1 ? $parts[0] : 'general';
            $grouped[$ns][] = $cmd;
        }

        foreach ($grouped as $ns => $cmds) {
            foreach ($cmds as $cmd) {
                $aliases = !empty($cmd->getAliases()) ? ' (' . implode(', ', $cmd->getAliases()) . ')' : '';
                $tableRows[] = [
                    $cmd->getName() . $aliases,
                    $cmd->getDescription()
                ];
            }
        }

        $output->table(['Command', 'Description'], $tableRows);
        $output->writeln("Run 'c2f help <command>' or 'c2f <command> --help' for command-specific instructions.");
        $output->writeln('');

        return 0;
    }
}
