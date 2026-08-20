<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class AiMcpSetupCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'ai:mcp-setup';
    }

    public function getDescription(): string
    {
        return 'Run 1-Click Automated Setup for Conn2Flow MCP Hub connectors in Claude Desktop, Cursor, and VS Code.';
    }

    public function getAliases(): array
    {
        return ['mcp:setup', 'mcp:connect'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f ai:mcp-setup\n\n" .
               "Detects and injects 'conn2flow-hub' MCP server into Claude Desktop, Cursor, and VS Code settings.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — 1-Click MCP Hub Connector Setup');

        $workspaceRoot = dirname($this->rootPath) . '/conn2flow-ai-workspace';
        $psScript = $workspaceRoot . '/scripts/setup-mcp-connectors.ps1';
        $shScript = $workspaceRoot . '/scripts/setup-mcp-connectors.sh';

        if (DIRECTORY_SEPARATOR === '\\' && file_exists($psScript)) {
            $cmd = sprintf('powershell -NoProfile -ExecutionPolicy Bypass -File %s', escapeshellarg($psScript));
            return $this->runShell($cmd, $output, $workspaceRoot);
        }

        if (file_exists($shScript)) {
            $cmd = sprintf('bash %s', escapeshellarg($shScript));
            return $this->runShell($cmd, $output, $workspaceRoot);
        }

        $output->error("Setup script not found at: {$psScript} or {$shScript}");
        return 1;
    }
}
