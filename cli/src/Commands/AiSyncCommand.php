<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class AiSyncCommand implements CommandInterface
{
    private const REQUIRED_SKILLS = [
        'c2f-database-operations',
        'c2f-database-testing',
        'c2f-dev-scripts',
        'c2f-docker-environment',
        'c2f-documentation-governance',
        'c2f-environment-configuration',
        'c2f-gd-image-safety',
        'c2f-gestor-functions',
        'c2f-global-variables',
        'c2f-hooks-system',
        'c2f-html-css-pages-and-components',
        'c2f-interface-v2-architecture',
        'c2f-javascript-ajax',
        'c2f-json-resources-sync',
        'c2f-modelo-templates',
        'c2f-module-crud-scaffolding',
        'c2f-multilingual-system',
        'c2f-mysql-utf8-emoji-encoding',
        'c2f-plugin-architecture',
        'c2f-preview-modals-system',
        'c2f-projects-system',
        'c2f-resources-system',
        'c2f-system-tasks',
        'c2f-tailwind-css-architecture',
        'c2f-variables-system',
        'c2f-widget-development',
        'continue-sdd-batch',
        'project-validation',
        'raise-spec-change',
        'review-current-batch',
        'sdd-memory-gardening',
        'sdd-workflow',
        'start-sdd-slice',
    ];

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
        return 'Synchronize and validate all 33 AI skills, rules and agent instructions across AI kits.';
    }

    public function getAliases(): array
    {
        return ['skills:sync', 'ai:skills'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f ai:sync [options]\n\n" .
               "Verifies the integrity and contracts of the 33 Core and SDD skills in .claude/, .cursor/, .gemini/ and .github/.\n\n" .
               "Options:\n" .
               "  --verbose     Display details of each verified skill contract.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — AI Skills & Kits Synchronization');
        $output->info('Validating 33 Skills and Contract blocks across active kits...');

        $skillDirs = [
            '.claude/skills' => $this->rootPath . '/.claude/skills',
            '.cursor/skills' => $this->rootPath . '/.cursor/skills',
            '.gemini/skills' => $this->rootPath . '/.gemini/skills',
            '.github/skills' => $this->rootPath . '/.github/skills',
        ];

        $totalSkillsFound = 0;
        $missingContracts = [];
        $missingRequiredSkills = [];
        $rows = [];

        foreach ($skillDirs as $label => $path) {
            if (!is_dir($path)) {
                continue;
            }

            $dirs = scandir($path);
            $skillsInKit = 0;
            $withContract = 0;
            $requiredSkillsInKit = 0;

            foreach (self::REQUIRED_SKILLS as $requiredSkill) {
                if (is_file("{$path}/{$requiredSkill}/SKILL.md")) {
                    $requiredSkillsInKit++;
                } else {
                    $missingRequiredSkills[] = "{$label}/{$requiredSkill}";
                }
            }

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
                sprintf('%d/%d', $requiredSkillsInKit, count(self::REQUIRED_SKILLS)),
                (string)$withContract,
                $withContract === $skillsInKit && $requiredSkillsInKit === count(self::REQUIRED_SKILLS)
                    ? '✔ Verified'
                    : '⚠ Incomplete'
            ];
        }

        $output->table(['AI Kit Directory', 'Total Skills', 'Required Skills', 'Valid Contracts', 'Status'], $rows);

        if (!empty($missingRequiredSkills) || !empty($missingContracts)) {
            if (!empty($missingRequiredSkills)) {
                $output->warning("The following required skills are missing:\n- " . implode("\n- ", $missingRequiredSkills));
            }

            if (!empty($missingContracts)) {
                $output->warning("The following skills lack the required # ⚡ Gatilho Obrigatório contract block:\n- " . implode("\n- ", $missingContracts));
            }

            return 1;
        }

        $output->success("All 33 skills verified successfully across all active AI toolkits!");
        return 0;
    }
}
