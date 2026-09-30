<?php

declare(strict_types=1);

use Conn2Flow\Cli\Commands\UpdateRollbackCommand;
use PHPUnit\Framework\TestCase;

foreach (['Contracts/CommandInterface.php', 'Contracts/InputInterface.php', 'Contracts/OutputInterface.php', 'Commands/BaseProcessCommand.php',
    'Support/ProjectEnvironmentResolver.php', 'Support/SshRemoteTransport.php', 'Commands/UpdateRollbackCommand.php'] as $f) {
    require_once CONN2FLOW_ROOT . '/cli/src/' . $f;
}

/** req-198 / BATCH-204: `c2f update:rollback` — id aceito e o que vai para o SSH e para a API. */
final class UpdateRollbackCommandTest extends TestCase
{
    public function testIdsAceitos(): void
    {
        foreach (['exec-12', '12', 'api-20260930-120000-ab12'] as $id) $this->assertTrue(UpdateRollbackCommand::snapshotValido($id), $id);
        foreach (['', '../x', 'a b', 'exec;rm'] as $id) $this->assertFalse(UpdateRollbackCommand::snapshotValido($id), $id);
    }

    public function testArgumentosDoAtualizadorNoServidor(): void
    {
        $this->assertSame(
            ['php', 'controladores/atualizacoes/atualizacoes-sistema.php', '--rollback=api-1', '--domain=site.test', '--com-banco'],
            UpdateRollbackCommand::argvSsh('api-1', 'site.test', true)
        );
        $this->assertNotContains('--com-banco', UpdateRollbackCommand::argvSsh('exec-3', 'site.test', false));
    }

    public function testCorpoDaApi(): void
    {
        $this->assertSame(['snapshot' => 'exec-3', 'com_banco' => false], UpdateRollbackCommand::corpoApi('exec-3', false));
    }
}
