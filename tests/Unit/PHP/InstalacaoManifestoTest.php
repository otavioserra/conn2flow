<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/gestor/bibliotecas/instalacao-manifesto.php';

/**
 * req-198 / BATCH-202: manifesto por camada, precedência projeto > plugin > core e choques.
 */
final class InstalacaoManifestoTest extends TestCase
{
    private string $raiz;
    private string $base;

    protected function setUp(): void
    {
        $this->raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-man-' . uniqid() . DIRECTORY_SEPARATOR;
        $this->base = $this->raiz . 'inst' . DIRECTORY_SEPARATOR;
        mkdir($this->base, 0777, true);
    }

    protected function tearDown(): void
    {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->raiz, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        @rmdir($this->raiz);
    }

    /** Cria um pacote com os arquivos dados e devolve a raiz. */
    private function pacote(string $nome, array $arquivos): string
    {
        $dir = $this->raiz . $nome . DIRECTORY_SEPARATOR;
        foreach ($arquivos as $rel => $conteudo) {
            @mkdir(dirname($dir . $rel), 0777, true);
            file_put_contents($dir . $rel, $conteudo);
        }
        return $dir;
    }

    private function entregar(string $camada, array $arquivos, string $versao, bool $completo = true): array
    {
        $p = $this->pacote(preg_replace('/[^a-z0-9-]/i', '_', $camada) . '-' . $versao . '-' . uniqid(), $arquivos);
        $plano = instalacao_planejar($this->base, $camada, instalacao_mapa($p), $completo);
        return [$plano, instalacao_aplicar($this->base, $p, $camada, $plano, $versao)];
    }

    private function ler(string $rel): ?string
    {
        $f = $this->base . $rel;
        return is_file($f) ? file_get_contents($f) : null;
    }

    public function testPrimeiraEntregaEscreveTudoEGravaLinhaDeBase(): void
    {
        [$plano, $r] = $this->entregar('core', ['index.php' => 'core-1', 'bibliotecas/a.php' => 'a1', 'logs/x.log' => 'fora'], 'v1');
        $this->assertTrue($plano['primeira']);
        $this->assertSame(2, $r['escritos']);
        $this->assertSame('core-1', $this->ler('index.php'));
        $this->assertNull($this->ler('logs/x.log'), 'Pasta protegida não entra.');
        $m = instalacao_manifesto_ler($this->base, 'core');
        $this->assertSame(['bibliotecas/a.php', 'index.php'], array_keys($m['arquivos']));
        $this->assertSame('v1', $m['versao']);
    }

    public function testCoreNaoSobrescreveArquivoSobrepostoPeloProjeto(): void
    {
        $this->entregar('core', ['index.php' => "linha 1\ncore-1", 'bibliotecas/a.php' => 'a1'], 'v1');
        [$planoProj] = $this->entregar('projeto', ['index.php' => "linha 1\nprojeto"], 'p1', false);
        $this->assertSame(['index.php'], $planoProj['originais'], 'A versão do core é guardada.');
        $this->assertSame("linha 1\nprojeto", $this->ler('index.php'));
        $this->assertSame("linha 1\ncore-1", $this->ler('installation/originals/index.php'));

        [$plano, $r] = $this->entregar('core', ['index.php' => "linha 1\ncore-2", 'bibliotecas/a.php' => 'a2'], 'v2');
        $this->assertSame('sobreposto', $plano['preservar']['index.php']['motivo']);
        $this->assertSame('projeto', $plano['preservar']['index.php']['camada_dona']);
        $this->assertSame("linha 1\nprojeto", $this->ler('index.php'), 'A sobreposição continua valendo.');
        $this->assertSame('a2', $this->ler('bibliotecas/a.php'), 'O resto do core atualiza.');
        $this->assertSame("linha 1\ncore-2", $this->ler('backups/overrides/v2/index.php'), 'A versão nova do core fica guardada.');
        $this->assertCount(1, $r['choques']);
        $this->assertStringContainsString('- projeto', $r['choques'][0]['diff']);
        $this->assertStringContainsString('+ core-2', $r['choques'][0]['diff']);
        $this->assertSame(hash('sha256', "linha 1\ncore-2"), instalacao_manifesto_ler($this->base, 'core')['arquivos']['index.php'], 'O manifesto do core registra o que ele entregou.');
    }

    public function testEdicaoNoServidorViraChoqueEVersaoIgualNaoConta(): void
    {
        $this->entregar('core', ['a.php' => 'a1', 'b.php' => 'b1'], 'v1');
        file_put_contents($this->base . 'a.php', 'editado à mão');
        [$plano, $r] = $this->entregar('core', ['a.php' => 'a2', 'b.php' => 'b1'], 'v2');
        $this->assertSame('editado', $plano['preservar']['a.php']['motivo']);
        $this->assertSame('editado à mão', $this->ler('a.php'));
        $this->assertSame([], $plano['escrever'], 'b.php igual não é reescrito.');
        $this->assertSame(1, $r['preservados']);
    }

    public function testRetiradaIntactaSaiEEditadaViraChoque(): void
    {
        $this->entregar('core', ['velho.php' => 'v', 'editado.php' => 'e', 'fica.php' => 'f'], 'v1');
        file_put_contents($this->base . 'editado.php', 'mudei');
        [$plano, $r] = $this->entregar('core', ['fica.php' => 'f'], 'v2');
        $this->assertSame(['velho.php'], $plano['retirar']);
        $this->assertNull($this->ler('velho.php'));
        $this->assertSame('mudei', $this->ler('editado.php'));
        $this->assertSame('retirado-editado', $r['choques'][0]['motivo']);
        $this->assertSame(['fica.php'], array_keys(instalacao_manifesto_ler($this->base, 'core')['arquivos']));
    }

    public function testProjetoDeixaDeSobreporERestauraOCore(): void
    {
        $this->entregar('core', ['index.php' => 'core-1'], 'v1');
        $this->entregar('projeto', ['index.php' => 'projeto', 'modulos/x.php' => 'x'], 'p1');
        [$plano, $r] = $this->entregar('projeto', ['modulos/x.php' => 'x'], 'p2');
        $this->assertSame(['index.php' => 'core'], $plano['restaurar']);
        $this->assertSame(1, $r['restaurados']);
        $this->assertSame('core-1', $this->ler('index.php'), 'A versão do core volta ao ar.');
        $this->assertFileDoesNotExist($this->base . 'installation/originals/index.php');
    }

    public function testArquivoDoCoreQueOProjetoAssumiuNaoSaiNaRetirada(): void
    {
        $this->entregar('core', ['comum.php' => 'core'], 'v1');
        $this->entregar('projeto', ['comum.php' => 'projeto'], 'p1');
        [$plano] = $this->entregar('core', ['outro.php' => 'o'], 'v2');
        $this->assertSame([], $plano['retirar']);
        $this->assertSame('projeto', $this->ler('comum.php'));
    }

    public function testPacoteParcialNaoRetiraEJuntaNoManifesto(): void
    {
        $this->entregar('projeto', ['a.php' => '1', 'b.php' => '2'], 'p1');
        [$plano] = $this->entregar('projeto', ['b.php' => '3'], 'p2', false);
        $this->assertSame([], $plano['retirar']);
        $this->assertSame('1', $this->ler('a.php'));
        $this->assertSame(['a.php', 'b.php'], array_keys(instalacao_manifesto_ler($this->base, 'projeto')['arquivos']));
    }

    public function testPacoteParcialComListaCompletaRetiraOQueSaiuDaLista(): void
    {
        $this->entregar('projeto', ['a.php' => '1', 'b.php' => '2', 'c.php' => '3'], 'p1');
        // gitDeploy: só b.php mudou; a lista completa diz que c.php saiu do projeto.
        $p = $this->pacote('parcial', ['b.php' => '2b']);
        $lista = ['a.php' => hash('sha256', '1'), 'b.php' => hash('sha256', '2b')];
        $plano = instalacao_planejar($this->base, 'projeto', instalacao_mapa($p), true, null, $lista);
        instalacao_aplicar($this->base, $p, 'projeto', $plano, 'p2');
        $this->assertSame(['c.php'], $plano['retirar']);
        $this->assertSame('1', $this->ler('a.php'), 'a.php, fora do pacote e na lista, fica.');
        $this->assertSame('2b', $this->ler('b.php'));
        $this->assertNull($this->ler('c.php'));
        $this->assertSame(['a.php', 'b.php'], array_keys(instalacao_manifesto_ler($this->base, 'projeto')['arquivos']));
    }

    public function testPluginFicaEntreCoreEProjeto(): void
    {
        $this->assertGreaterThan(instalacao_nivel('core'), instalacao_nivel('plugin:loja'));
        $this->assertGreaterThan(instalacao_nivel('plugin:loja'), instalacao_nivel('projeto'));
        $this->entregar('core', ['x.php' => 'core'], 'v1');
        $this->entregar('plugin:loja', ['x.php' => 'plugin'], 'l1');
        [$plano] = $this->entregar('core', ['x.php' => 'core-2'], 'v2');
        $this->assertSame('plugin:loja', $plano['preservar']['x.php']['camada_dona']);
    }

    public function testSnapshotVoltaTudoAoEstadoAnterior(): void
    {
        $this->entregar('core', ['a.php' => 'a1', 'velho.php' => 'v1', 'b.php' => 'b1'], 'v1');
        $p = $this->pacote('v2', ['a.php' => 'a2', 'b.php' => 'b1', 'novo.php' => 'n']);
        $plano = instalacao_planejar($this->base, 'core', instalacao_mapa($p), true);
        $snap = $this->raiz . 'snap' . DIRECTORY_SEPARATOR;
        $c = instalacao_snapshot_criar($this->base, $plano, $snap, ['versao' => 'v2']);
        $this->assertSame(['sobrescritos' => 1, 'removidos' => 1, 'novos' => 1], $c, 'Só a.php (muda), velho.php (sai) e novo.php (novo).');
        instalacao_aplicar($this->base, $p, 'core', $plano, 'v2');
        $this->assertSame('a2', $this->ler('a.php'));
        $this->assertNull($this->ler('velho.php'));

        $r = instalacao_snapshot_restaurar($this->base, $snap);
        $this->assertSame(['restaurados' => 2, 'removidos_novos' => 1, 'falhas' => []], $r);
        $this->assertSame('a1', $this->ler('a.php'));
        $this->assertSame('v1', $this->ler('velho.php'));
        $this->assertNull($this->ler('novo.php'));
        $this->assertSame('v1', instalacao_manifesto_ler($this->base, 'core')['versao'], 'Manifesto de antes volta.');
        instalacao_snapshot_anotar($snap, ['dump_banco' => 'x.sql.gz']);
        $this->assertSame('x.sql.gz', json_decode(file_get_contents($snap . 'snapshot.json'), true)['dump_banco']);
    }

    public function testFiltroDoChoquePendenteIgual(): void
    {
        $w = instalacao_choque_pendente_filtro(['caminho' => "a'b.php", 'camada' => 'core', 'motivo' => 'editado', 'hash_disco' => 'h1', 'hash_novo' => null], 'addslashes');
        $this->assertSame("resolucao IS NULL AND caminho='a\\'b.php' AND camada='core' AND motivo='editado' AND hash_disco='h1' AND hash_novo IS NULL", $w);
    }

    public function testPodaMantemOsMaisRecentes(): void
    {
        $raiz = $this->raiz . 'snaps' . DIRECTORY_SEPARATOR;
        foreach (['a', 'b', 'c'] as $i => $n) { mkdir($raiz . $n, 0777, true); file_put_contents($raiz . $n . '/f', 'x'); touch($raiz . $n, time() - 100 + $i); }
        $this->assertSame(1, instalacao_snapshot_podar($raiz, 2));
        $this->assertDirectoryDoesNotExist($raiz . 'a');
        $this->assertDirectoryExists($raiz . 'c');
    }

    public function testDiffBinarioEGrande(): void
    {
        $this->assertNull(instalacao_diff('igual', 'igual'));
        $this->assertSame('(arquivo binário)', instalacao_diff("a\0b", 'c'));
        $this->assertSame('(arquivo grande demais para o diff)', instalacao_diff(str_repeat('x', INSTALACAO_DIFF_MAX_BYTES + 1), 'y'));
    }
}
