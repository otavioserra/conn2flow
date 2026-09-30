<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/gestor/bibliotecas/atualizacoes-execucao.php';

/** req-201 / BATCH-209: atualização do sistema disparada pela API — argumentos, estado e ids. */
final class AtualizacoesExecucaoTest extends TestCase
{
    public function testArgumentosDaListaBranca(): void
    {
        $r = atualizacoes_execucao_argv([
            'tag' => 'gestor-v2.11.0', 'backup' => '1', 'dry_run' => false, 'no_health' => 'true',
            'health_url' => 'https://site.test:8443/', 'health_ip' => '127.0.0.1', 'tables' => 'paginas,variaveis', 'logs_retention_days' => '30',
        ], 'site.test');
        $this->assertSame([], $r['recusadas']);
        $this->assertSame(['--domain=site.test', '--log-stdout', '--tag=gestor-v2.11.0', '--backup', '--no-health',
            '--health-url=https://site.test:8443/', '--health-ip=127.0.0.1', '--tables=paginas,variaveis', '--logs-retention-days=30'], $r['argv']);
    }

    public function testRecusaOpcaoDesconhecidaEValorPerigoso(): void
    {
        $r = atualizacoes_execucao_argv([
            'wipe' => '1', 'tag' => 'v1; rm -rf /', 'health_url' => 'https://x.test/?a=1&b=2', 'health_ip' => 'x', 'tables' => 'a;b', 'logs_retention_days' => '-1',
        ], 'site.test; id');
        $this->assertEqualsCanonicalizing(['wipe', 'tag', 'health_url', 'health_ip', 'tables', 'logs_retention_days'], $r['recusadas']);
        $this->assertSame('--domain=site.testid', $r['argv'][0], 'Domínio só com caracteres de nome.');
    }

    public function testEstadoPeloLogEPeloCodigo(): void
    {
        $log = "[2026-09-30 10:00:00][INFO] Snapshot exec-15: 2 sobrescrito(s)\n"
            . "[2026-09-30 10:00:03][ERROR] Verificação pós-atualização FALHOU: HTTP 500 em https://x/\n"
            . "ROLLBACK: Rollback automático dos arquivos: 2 restaurado(s)\n";
        $e = atualizacoes_execucao_estado($log, "6\n");
        $this->assertSame('rolled_back', $e['status']);
        $this->assertSame(6, $e['codigo']);
        $this->assertSame('exec-15', $e['snapshot']);
        $this->assertStringContainsString('FALHOU', (string)$e['saude']);
        $this->assertStringContainsString('Rollback automático', (string)$e['rollback']);
        $this->assertSame('running', atualizacoes_execucao_estado('', null)['status']);
        $this->assertSame('success', atualizacoes_execucao_estado('', '0')['status']);
        $this->assertSame('locked', atualizacoes_execucao_estado('', '8')['status']);
        $this->assertSame('error', atualizacoes_execucao_estado('', '42')['status']);
    }

    public function testOpcoesDoComandoUpdateCore(): void
    {
        foreach (['Contracts/CommandInterface.php', 'Contracts/InputInterface.php', 'Contracts/OutputInterface.php', 'Commands/BaseProcessCommand.php',
            'Support/ProjectEnvironmentResolver.php', 'Support/ProjectApiClient.php', 'Commands/UpdateCoreCommand.php'] as $f) {
            require_once CONN2FLOW_ROOT . '/cli/src/' . $f;
        }
        $flags = ['tag' => 'gestor-v2.11.0', 'backup' => true, 'health-url' => 'https://s.test:8443/', 'wait' => true];
        $o = \Conn2Flow\Cli\Commands\UpdateCoreCommand::opcoesDaEntrada(fn($n) => $flags[$n] ?? null, fn($n) => array_key_exists($n, $flags));
        $this->assertSame(['tag' => 'gestor-v2.11.0', 'backup' => true, 'health_url' => 'https://s.test:8443/'], $o, '--wait não vai para a API.');
        $this->assertSame([], atualizacoes_execucao_argv($o, 's.test')['recusadas'], 'Tudo que o comando manda a API aceita.');
    }

    public function testIdsEPasta(): void
    {
        $id = atualizacoes_execucao_novo_id();
        $this->assertTrue(atualizacoes_execucao_id_valido($id));
        $this->assertFalse(atualizacoes_execucao_id_valido('../run-1'));
        $this->assertStringEndsWith('temp' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR . 'runs' . DIRECTORY_SEPARATOR, atualizacoes_execucao_pasta('/inst/'));
    }
}
