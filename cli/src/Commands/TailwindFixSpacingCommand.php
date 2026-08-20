<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class TailwindFixSpacingCommand extends BaseProcessCommand
{
    public function getName(): string
    {
        return 'tailwind:fix-spacing';
    }

    public function getDescription(): string
    {
        return 'Fix and suggest canonical classes using fix-tailwind-spacing.js.';
    }

    public function getAliases(): array
    {
        return ['tailwind:fix'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f tailwind:fix-spacing [--target=../target-folder]\n\n" .
               "Normalizes arbitrary tailwind classes into canonical project tokens.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $input->getOption('target', '../conn2flow-site');
        $output->title("Conn2Flow — Fix Tailwind Spacing Tokens");

        $script = $this->rootPath . '/ai-workspace/pt-br/scripts/utils/fix-tailwind-spacing.js';

        if (!file_exists($script)) {
            $output->error("Tailwind utility script not found at: {$script}");
            return 1;
        }

        $cmd = sprintf(
            'node %s --replace --apply-config --target %s',
            escapeshellarg($script),
            escapeshellarg((string)$target)
        );

        return $this->runShell($cmd, $output);
    }
}
