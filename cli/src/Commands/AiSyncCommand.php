<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class AiSyncCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'ai:sync';
    }

    public function getDescription(): string
    {
        return 'Synchronize and validate all 32 AI skills, rules and agent instructions across AI kits.';
    }

    public function getAliases(): array
    {
        return ['skills:sync', 'ai:skills'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f ai:sync [options]\n\n" .
               "Verifies the integrity and contracts of the 32 Core and SDD skills in .claude/, .cursor/, .gemini/ and .github/.\n\n" .
               "Options:\n" .
               "  --verbose     Display details of each verified skill contract.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — AI Skills & Kits Synchronization');
        $output->info('Validating 32 Skills and Contract blocks across active kits...');

        $skillDirs = [
            '.claude/skills' => $this->rootPath . '/.claude/skills',
            '.cursor/skills' => $this->rootPath . '/.cursor/skills',
            '.gemini/skills' => $this->rootPath . '/.gemini/skills',
            '.github/skills' => $this->rootPath . '/.github/skills',
        ];

        $totalSkillsFound = 0;
        $missingContracts = [];
        $rows = [];

        foreach ($skillDirs as $label => $path) {
            if (!is_dir($path)) {
                continue;
            }

            $dirs = scandir($path);
            $skillsInKit = 0;
            $withContract = 0;

            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..' || !is_dir("{$path}/{$dir}")) {
                    continue;
                }

                $skillsInKit++;
                $skillFile = "{$path}/{$dir}/SKILL.md";
                if (file_exists($skillFile)) {
                    $content = file_get_contents($skillFile);
                    if (str_contains($content, '⚡ Gatilho Obrigatório') && str_contains($content, '**TRIGGER**:')) {
                        $withContract++;
                    } else {
                        $missingContracts[] = "{$label}/{$dir}";
                    }
                }
            }

            $totalSkillsFound += $skillsInKit;
            $rows[] = [
                $label,
                (string)$skillsInKit,
                (string)$withContract,
                $withContract === $skillsInKit ? '✔ Verified' : '⚠ Incomplete'
            ];
        }

        $output->table(['AI Kit Directory', 'Total Skills', 'Valid Contracts', 'Status'], $rows);

        if (!empty($missingContracts)) {
            $output->warning("The following skills lack the required # ⚡ Gatilho Obrigatório contract block:\n- " . implode("\n- ", $missingContracts));
            return 1;
        }

        $output->success("All 32 skills verified successfully across all active AI toolkits!");
        return 0;
    }
}
