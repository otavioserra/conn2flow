<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../gestor/controladores/atualizacoes/atualizacoes-migracoes.php';

/**
 * req-194: limpeza por dono das migrações obsoletas no ambiente em execução.
 */
class AtualizacoesMigracoesTest extends TestCase
{
    private string $raiz;
    private string $dir;

    protected function setUp(): void
    {
        $this->raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-migr-' . uniqid();
        $this->dir = $this->raiz . DIRECTORY_SEPARATOR . 'migrations';
        mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->raiz . '/{,.}*', GLOB_BRACE) ?: [] as $f) if (is_file($f)) unlink($f);
        foreach (glob($this->dir . '/*') ?: [] as $f) unlink($f);
        @rmdir($this->dir);
        @rmdir($this->raiz);
    }

    private function criar(string ...$arquivos): void
    {
        foreach ($arquivos as $a) file_put_contents($this->dir . DIRECTORY_SEPARATOR . $a, '<?php');
    }

    private function existentes(): array
    {
        return atualizacoes_migracoes_listar($this->dir);
    }

    public function testClasseDerivadaDoNome(): void
    {
        $this->assertSame(['versao' => '20260929163000', 'classe' => 'CreateAffiliatesTables'], atualizacoes_migracoes_info('20260929163000_create_affiliates_tables.php'));
        $this->assertNull(atualizacoes_migracoes_info('../20260929163000_x.php'));
        $this->assertNull(atualizacoes_migracoes_info('leia-me.txt'));
    }

    public function testCopiaAntigaDeMigracaoRenomeadaSaiMesmoSemManifesto(): void
    {
        // O caso real: a migração foi renumerada e a antiga ficou no servidor.
        $this->criar('20250101000000_create_core_table.php', '20260929140000_create_affiliates_tables.php',
            '20260929140000_create_host_manager_settings_table.php', '20260929163000_create_affiliates_tables.php');
        $lista = ['20260929140000_create_host_manager_settings_table.php', '20260929163000_create_affiliates_tables.php'];
        $r = atualizacoes_migracoes_limpar($this->dir, 'projeto', $lista, $lista);

        $this->assertSame('20260929140000_create_affiliates_tables.php', $r['removidos'][0]['arquivo']);
        $this->assertStringStartsWith('renomeada:', $r['removidos'][0]['motivo']);
        $this->assertSame([], $r['choques']);
        $this->assertContains('20250101000000_create_core_table.php', $this->existentes(), 'Migração do core não é tocada pelo deploy do projeto.');
        $this->assertTrue($r['manifesto']);
    }

    public function testRetiradaPeloDonoSaiSoComManifestoAnterior(): void
    {
        $this->criar('20250101000000_create_core_table.php', '20260101000000_create_a.php', '20260102000000_create_b.php');
        atualizacoes_migracoes_limpar($this->dir, 'projeto', ['20260101000000_create_a.php', '20260102000000_create_b.php']);

        $r = atualizacoes_migracoes_limpar($this->dir, 'projeto', ['20260101000000_create_a.php']);
        $this->assertSame([['arquivo' => '20260102000000_create_b.php', 'motivo' => 'retirada-pelo-dono']], $r['removidos']);
        $this->assertSame(['20250101000000_create_core_table.php', '20260101000000_create_a.php'], $this->existentes());
    }

    public function testListaDesconhecidaNaoRemoveOQueNaoChegou(): void
    {
        // Pacote parcial (gitDeploy sem manifesto): só a regra da classe vale.
        $this->criar('20260101000000_create_a.php', '20260102000000_create_b.php');
        atualizacoes_migracoes_limpar($this->dir, 'projeto', ['20260101000000_create_a.php', '20260102000000_create_b.php']);
        $r = atualizacoes_migracoes_limpar($this->dir, 'projeto', null, ['20260101000000_create_a.php']);
        $this->assertSame([], $r['removidos']);
        $this->assertFalse($r['manifesto']);
        $this->assertCount(2, $this->existentes());
    }

    public function testMesmaVersaoComOutraClasseEChoqueSemRemocao(): void
    {
        $this->criar('20260929140000_create_host_manager_settings_table.php', '20260929140000_create_affiliates_tables.php');
        $r = atualizacoes_migracoes_limpar($this->dir, 'projeto', ['20260929140000_create_affiliates_tables.php'], [], false);
        $this->assertSame([], $r['removidos']);
        $this->assertSame('20260929140000_create_host_manager_settings_table.php', $r['choques'][0]['arquivo']);
        $this->assertCount(2, $this->existentes());
        $this->assertNotEmpty(atualizacoes_migracoes_log($r, 'projeto'));
    }

    public function testArquivoDoOutroDonoNuncaEApagado(): void
    {
        // O projeto entregou uma migração com a mesma classe de uma do core: extensão permitida,
        // conflito registrado, nada removido pela atualização do core.
        $this->criar('20260101000000_create_x.php');
        atualizacoes_migracoes_limpar($this->dir, 'core', ['20260101000000_create_x.php']);
        $this->criar('20260301000000_create_x.php');
        $r = atualizacoes_migracoes_limpar($this->dir, 'projeto', ['20260301000000_create_x.php']);
        $this->assertSame([], $r['removidos'], 'O deploy do projeto não apaga a migração do core.');
        $this->assertTrue($r['choques'][0]['outro_dono']);
        $r = atualizacoes_migracoes_limpar($this->dir, 'core', ['20260101000000_create_x.php']);
        $this->assertSame([], $r['removidos'], 'A atualização do core não apaga a do projeto.');
        $this->assertTrue($r['choques'][0]['outro_dono']);
        $this->assertCount(2, $this->existentes());
    }
}
