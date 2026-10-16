<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-281 (BL-033) — o rsync do deploy SSH grava com o dono do destino também quando o SSH entra como root.
 * temp.conn2flow.com, 15/10: `ssh_user: root` sem `ssh_sudo` gravava como root (e com o UID do Windows),
 * e o PHP-FPM do site deu 500 até o `chown` do fim da etapa.
 */
final class ProjectTransportChownReq281Test extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        if (DIRECTORY_SEPARATOR !== '/' || trim((string)shell_exec('command -v bash')) === '' || trim((string)shell_exec('command -v jq')) === '') {
            self::markTestSkipped('precisa de bash e jq');
        }
        $this->dir = sys_get_temp_dir() . '/c2f-281-' . uniqid();
        mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (isset($this->dir)) { @unlink($this->dir . '/environment.json'); @rmdir($this->dir); }
    }

    /** Resolve o transporte e devolve as opções do rsync. */
    private function opcoes(array $projeto): string
    {
        $env = $this->dir . '/environment.json';
        file_put_contents($env, json_encode(['devProjects' => ['p' => $projeto + [
            'deploy_mode' => 'ssh', 'ssh_host' => 'servidor', 'ssh_port' => 22,
            'ssh_target_path' => '/home/site/web/site.com/conn2flow-gestor', 'ssh_chown' => 'site:site',
        ]]]));
        $lib = CONN2FLOW_ROOT . '/ai-workspace/en/scripts/lib/project-transport.sh';
        $cmd = 'bash -c ' . escapeshellarg('pt_error(){ echo "ERRO $*"; }; source ' . escapeshellarg($lib)
            . ' && project_transport_resolve ' . escapeshellarg($env) . ' p && printf "%s\n" "${PT_RSYNC_OPTS[@]}"');
        return (string)shell_exec($cmd . ' 2>&1');
    }

    public function testRootSemSudoGravaComODonoDoDestino(): void
    {
        $saida = $this->opcoes(['ssh_user' => 'root', 'ssh_sudo' => false]);
        self::assertStringContainsString('--chown=site:site', $saida);
        self::assertStringNotContainsString('sudo rsync', $saida);
    }

    public function testUsuarioComumComSudoContinuaComChown(): void
    {
        $saida = $this->opcoes(['ssh_user' => 'deploy', 'ssh_sudo' => true]);
        self::assertStringContainsString('--chown=site:site', $saida);
        self::assertStringContainsString('sudo rsync', $saida);
    }

    public function testUsuarioComumSemSudoNaoPedeChown(): void
    {
        // Sem privilégio o rsync nem conseguiria trocar o dono: grava como o próprio usuário SSH.
        $saida = $this->opcoes(['ssh_user' => 'site', 'ssh_sudo' => false]);
        self::assertStringNotContainsString('--chown', $saida);
    }
}
