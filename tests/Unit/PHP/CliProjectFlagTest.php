<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Contracts' . DIRECTORY_SEPARATOR . 'InputInterface.php';
require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Input.php';

use Conn2Flow\Cli\Console\Input;

/**
 * Testes unitários para a resolução de --project nos comandos Project* — REQ-133
 *
 * Valida que a classe Input parseia corretamente:
 *  1. --project=ID como opção
 *  2. ID como argumento posicional
 *  3. Ambos presentes (opção tem prioridade)
 */
final class CliProjectFlagTest extends TestCase
{
    public function testOptionProjectEParseada(): void
    {
        $input = new Input(['c2f', 'project:sync-resources', '--project=lumix']);

        self::assertSame('project:sync-resources', $input->getCommandName());
        self::assertSame('lumix', $input->getOption('project'));
        self::assertNull($input->getArgument(0));
    }

    public function testArgumentoPosicionalEParseado(): void
    {
        $input = new Input(['c2f', 'project:sync-resources', 'lumix']);

        self::assertSame('project:sync-resources', $input->getCommandName());
        self::assertNull($input->getOption('project'));
        self::assertSame('lumix', $input->getArgument(0));
    }

    public function testOptionTemPrioridadeSobreArgumento(): void
    {
        // Simula: c2f project:sync-resources photon --project=lumix
        $input = new Input(['c2f', 'project:sync-resources', 'photon', '--project=lumix']);

        // A resolução $input->getOption('project') ?? $input->getArgument(0) deve dar 'lumix'
        $project = $input->getOption('project') ?? $input->getArgument(0);
        self::assertSame('lumix', $project);
    }

    public function testSemProjectEArgumentoRetornaNull(): void
    {
        $input = new Input(['c2f', 'project:sync-resources']);

        $project = $input->getOption('project') ?? $input->getArgument(0);
        self::assertNull($project);
    }

    public function testOptionProjectComValorVazio(): void
    {
        $input = new Input(['c2f', 'project:deploy', '--project=']);

        // --project= com valor vazio deve retornar string vazia
        self::assertSame('', $input->getOption('project'));
    }

    public function testOptionProjectBooleana(): void
    {
        // --project sem = trata como flag booleana (true)
        $input = new Input(['c2f', 'project:deploy', '--project']);

        self::assertTrue($input->getOption('project'));
    }
}
