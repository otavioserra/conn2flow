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

    private function entregar(string $camada, array $arquivos, string $versao, bool $completo = true, bool $retirarConfirmado = false): array
    {
        $p = $this->pacote(preg_replace('/[^a-z0-9-]/i', '_', $camada) . '-' . $versao . '-' . uniqid(), $arquivos);
        $plano = instalacao_planejar($this->base, $camada, instalacao_mapa($p), $completo, null, null, $retirarConfirmado);
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

    /** REQ-285: manter é o padrão — o que a camada deixou de entregar fica no disco até o operador decidir. */
    public function testRetiradaSemConfirmacaoFicaEViraChoque(): void
    {
        $this->entregar('core', ['velho.php' => 'v', 'editado.php' => 'e', 'fica.php' => 'f'], 'v1');
        file_put_contents($this->base . 'editado.php', 'mudei');
        [$plano, $r] = $this->entregar('core', ['fica.php' => 'f'], 'v2');
        $this->assertSame([], $plano['retirar']);
        $this->assertSame('v', $this->ler('velho.php'), 'Intacto também fica.');
        $this->assertSame('mudei', $this->ler('editado.php'));
        $this->assertSame(0, $r['retirados']);
        $this->assertSame(['editado.php' => 'retirado-editado', 'velho.php' => 'retirado'], array_column($r['choques'], 'motivo', 'caminho'));
        $this->assertSame(['sobrescrever', 'manter'], instalacao_choque_acoes(['motivo' => 'retirado']));
        $this->assertSame(['fica.php'], array_keys(instalacao_manifesto_ler($this->base, 'core')['arquivos']));

        // O operador aceita a retirada na tela de choques: só então o arquivo sai.
        $choque = ['camada' => 'core'] + array_column($r['choques'], null, 'caminho')['velho.php'];
        $this->assertTrue(instalacao_choque_resolver($this->base, $choque, 'sobrescrever')['ok']);
        $this->assertNull($this->ler('velho.php'));
    }

    public function testRetiradaConfirmadaIntactaSaiEEditadaViraChoque(): void
    {
        $this->entregar('core', ['velho.php' => 'v', 'editado.php' => 'e', 'fica.php' => 'f'], 'v1');
        file_put_contents($this->base . 'editado.php', 'mudei');
        [$plano, $r] = $this->entregar('core', ['fica.php' => 'f'], 'v2', true, true);
        $this->assertSame(['velho.php'], $plano['retirar']);
        $this->assertNull($this->ler('velho.php'));
        $this->assertSame('mudei', $this->ler('editado.php'));
        $this->assertSame('retirado-editado', $r['choques'][0]['motivo']);
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
        $this->assertSame([], $plano['retirar']);
        $this->assertSame('retirado', $plano['retirar_choque']['c.php']['motivo']);
        $this->assertSame('1', $this->ler('a.php'), 'a.php, fora do pacote e na lista, fica.');
        $this->assertSame('2b', $this->ler('b.php'));
        $this->assertSame('3', $this->ler('c.php'), 'Sem confirmação, c.php fica (REQ-285).');
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
        $plano = instalacao_planejar($this->base, 'core', instalacao_mapa($p), true, null, null, true);
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

    /** Core v1, edição no servidor e core v2: devolve o choque `editado` registrado. */
    private function choqueEditado(string $v2 = "l1\nl2 core v2\n"): array
    {
        $this->entregar('core', ['a.php' => "l1\nl2\n"], 'v1');
        file_put_contents($this->base . 'a.php', "l1 servidor\nl2\n");
        [, $r] = $this->entregar('core', ['a.php' => $v2], 'v2');
        $this->assertSame('editado', $r['choques'][0]['motivo']);
        return $r['choques'][0] + ['camada' => 'core'];
    }

    public function testMesmaVersaoDaCamadaNaoRepeteOChoque(): void
    {
        $c = $this->choqueEditado();
        // A mesma v2 de novo: nada novo a decidir, o disco fica e não há choque.
        [$plano, $r] = $this->entregar('core', ['a.php' => "l1\nl2 core v2\n"], 'v2b');
        $this->assertSame([], $r['choques']);
        $this->assertNotContains('a.php', $plano['escrever']);
        $this->assertSame("l1 servidor\nl2\n", $this->ler('a.php'));
        // Depois de uma mescla, idem.
        instalacao_choque_resolver($this->base, $c, 'mesclar', "mesclado\n");
        [, $r2] = $this->entregar('core', ['a.php' => "l1\nl2 core v2\n"], 'v2c');
        $this->assertSame([], $r2['choques']);
        $this->assertSame("mesclado\n", $this->ler('a.php'));
    }

    public function testAcoesPorMotivo(): void
    {
        $this->assertSame(['sobrescrever', 'manter', 'mesclar'], instalacao_choque_acoes(['motivo' => 'editado']));
        $this->assertSame(['manter', 'mesclar'], instalacao_choque_acoes(['motivo' => 'sobreposto']));
        $this->assertSame(['sobrescrever', 'manter'], instalacao_choque_acoes(['motivo' => 'retirado-editado']));
        $this->assertSame(['manter'], instalacao_choque_acoes(['motivo' => 'original-ausente']));
    }

    public function testVersoesDoChoque(): void
    {
        $c = $this->choqueEditado();
        $v = instalacao_choque_versoes($this->base, $c);
        $this->assertSame("l1 servidor\nl2\n", $v['no_ar']);
        $this->assertSame("l1\nl2 core v2\n", $v['nova']);
        $this->assertFalse($v['binario']);
    }

    public function testSobrescreverPoeAVersaoNova(): void
    {
        $c = $this->choqueEditado();
        $r = instalacao_choque_resolver($this->base, $c, 'sobrescrever');
        $this->assertTrue($r['ok'], $r['erro']);
        $this->assertSame("l1\nl2 core v2\n", $this->ler('a.php'));
        // Disco igual ao que o core entregou: a próxima entrega não acusa choque.
        [, $r3] = $this->entregar('core', ['a.php' => "l1\nl2 core v3\n"], 'v3');
        $this->assertSame([], $r3['choques']);
        $this->assertSame("l1\nl2 core v3\n", $this->ler('a.php'));
    }

    public function testMesclarGravaOConteudoDado(): void
    {
        $c = $this->choqueEditado();
        $this->assertFalse(instalacao_choque_resolver($this->base, $c, 'mesclar')['ok'], 'Sem conteúdo, recusa.');
        $r = instalacao_choque_resolver($this->base, $c, 'mesclar', "l1 servidor\nl2 core v2\n");
        $this->assertTrue($r['ok']);
        $this->assertSame("l1 servidor\nl2 core v2\n", $this->ler('a.php'));
        // REQ-286: a mescla é decisão sobre este disco (a mesma entrega não volta a chocar).
        $this->assertSame('mesclar', instalacao_regras_ler($this->base)['a.php']['acao']);
        $this->assertSame(hash('sha256', "l1 servidor\nl2 core v2\n"), instalacao_regras_ler($this->base)['a.php']['hash_disco']);
    }

    public function testManterViraRegraParaAProximaEntrega(): void
    {
        $c = $this->choqueEditado();
        $this->assertTrue(instalacao_choque_resolver($this->base, $c, 'manter')['ok']);
        $this->assertSame("l1 servidor\nl2\n", $this->ler('a.php'));
        $this->assertSame('manter', instalacao_regras_ler($this->base)['a.php']['acao']);
        // Nova versão do core: continua preservado, e o choque já nasce resolvido pela regra.
        [, $r3] = $this->entregar('core', ['a.php' => "l1\nl2 core v3\n"], 'v3');
        $this->assertSame("l1 servidor\nl2\n", $this->ler('a.php'));
        $this->assertSame('manter-regra', $r3['choques'][0]['resolucao']);
        // O servidor mudou de novo: a regra não vale mais e o choque volta a pedir decisão.
        file_put_contents($this->base . 'a.php', "outra edição\n");
        [, $r4] = $this->entregar('core', ['a.php' => "l1\nl2 core v4\n"], 'v4');
        $this->assertArrayNotHasKey('resolucao', $r4['choques'][0]);
    }

    public function testDecisaoInvalidaECaminhoInvalido(): void
    {
        $this->assertFalse(instalacao_choque_resolver($this->base, ['caminho' => 'x.php', 'motivo' => 'sobreposto'], 'sobrescrever')['ok']);
        $this->assertFalse(instalacao_choque_resolver($this->base, ['caminho' => '../fora.php', 'motivo' => 'editado'], 'manter')['ok']);
        $this->assertFalse(instalacao_choque_resolver($this->base, ['caminho' => 'logs/x.log', 'motivo' => 'editado'], 'manter')['ok']);
    }

    public function testRetiradaAceitaRemoveOArquivo(): void
    {
        $this->entregar('core', ['a.php' => 'a', 'b.php' => 'b'], 'v1');
        file_put_contents($this->base . 'b.php', 'b editado');
        [, $r] = $this->entregar('core', ['a.php' => 'a'], 'v2');
        $c = $r['choques'][0] + ['camada' => 'core'];
        $this->assertSame('retirado-editado', $c['motivo']);
        $this->assertTrue(instalacao_choque_resolver($this->base, $c, 'sobrescrever')['ok']);
        $this->assertNull($this->ler('b.php'));
    }

    public function testFiltroComResolucaoAutomatica(): void
    {
        $w = instalacao_choque_pendente_filtro(['caminho' => 'a.php', 'camada' => 'core', 'motivo' => 'editado', 'hash_disco' => 'h', 'hash_novo' => 'n', 'resolucao' => 'manter-regra'], 'addslashes');
        $this->assertStringStartsWith("resolucao='manter-regra' AND ", $w);
    }

    public function testPastaDoSnapshotPorId(): void
    {
        $s = DIRECTORY_SEPARATOR;
        $raiz = '/inst' . $s . 'backups' . $s . 'atualizacoes' . $s . 'snapshots' . $s;
        $this->assertSame($raiz . 'exec-12' . $s, instalacao_snapshot_dir('/inst', '12'));
        $this->assertSame($raiz . 'exec-12' . $s, instalacao_snapshot_dir('/inst', 'exec-12'));
        $this->assertSame($raiz . 'api-20260930-1200-ab' . $s, instalacao_snapshot_dir('/inst/', 'api-20260930-1200-ab'));
        $this->assertSame($raiz . 'x' . $s, instalacao_snapshot_dir('/inst', '../x'), 'Sem subir de pasta.');
        $this->assertNull(instalacao_snapshot_dir('/inst', '../'));
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

    /** req-284: arquivo acima do limite não é lido para o diff (seed de 83 MB derrubava a API com 500). */
    public function testDiffDeArquivoGrandeNaoCarregaOConteudo(): void
    {
        $grande = $this->raiz . 'grande.json';
        $pequeno = $this->raiz . 'pequeno.json';
        $fh = fopen($grande, 'wb');
        for ($i = 0; $i < 8; $i++) fwrite($fh, str_repeat('x', 1024 * 1024));
        fclose($fh);
        file_put_contents($pequeno, "a\nb\n");

        memory_reset_peak_usage();
        $antes = memory_get_peak_usage();
        $this->assertSame('(arquivo grande demais para o diff)', instalacao_diff_arquivos($grande, $pequeno, 'grande.json'));
        $this->assertLessThan(1024 * 1024, memory_get_peak_usage() - $antes, 'O arquivo de 8 MB não pode ser carregado.');

        file_put_contents($grande, "a\nc\n");
        $this->assertStringContainsString('@2 + b', (string)instalacao_diff_arquivos($grande, $pequeno, 'x'));
    }

    // ===== req-278: o atualizador do core sempre chega

    public function testAtualizadorDoCoreChegaMesmoComManifestoDesatualizado(): void
    {
        $u = 'controladores/atualizacoes/atualizacoes-sistema.php';
        $this->entregar('core', [$u => 'atualizador-3.0.3', 'modulos/x/x.php' => 'x-3.0.3'], 'v3.0.3');
        // Deploy por SSH da 3.0.4: o disco muda, o manifesto do core continua na 3.0.3.
        file_put_contents($this->base . $u, 'atualizador-3.0.4');
        file_put_contents($this->base . 'modulos/x/x.php', 'x-3.0.4');

        [$plano, $r] = $this->entregar('core', [$u => 'atualizador-3.0.5', 'modulos/x/x.php' => 'x-3.0.5'], 'v3.0.5');
        $this->assertSame('atualizador-3.0.5', $this->ler($u), 'o atualizador novo chega');
        $this->assertSame([$u], $plano['forcados']);
        $this->assertArrayNotHasKey($u, $plano['preservar']);
        $this->assertSame('editado', $plano['preservar']['modulos/x/x.php']['motivo'], 'o resto continua protegido');
        $this->assertSame('x-3.0.4', $this->ler('modulos/x/x.php'));
        $this->assertSame(1, $r['preservados']);
    }

    public function testAtualizadorReentregueComDiscoDiferenteNaoFicaParado(): void
    {
        $u = 'bibliotecas/deploy-lock.php';
        $this->entregar('core', [$u => 'trava-1'], 'v1');
        file_put_contents($this->base . $u, 'trava-local');
        // Mesmo conteúdo da entrega anterior e disco diferente: fora do atualizador isso é "nada a decidir".
        [$plano] = $this->entregar('core', [$u => 'trava-1'], 'v1');
        $this->assertSame('trava-1', $this->ler($u));
        $this->assertSame([$u], $plano['escrever']);
    }

    public function testAtualizadorSobrepostoPeloProjetoContinuaDoProjeto(): void
    {
        $u = 'modulos/admin-atualizacoes/admin-atualizacoes.php';
        $this->entregar('core', [$u => 'core-1'], 'v1');
        $this->entregar('projeto', [$u => 'projeto'], 'p1', false);
        [$plano] = $this->entregar('core', [$u => 'core-2'], 'v2');
        $this->assertSame('sobreposto', $plano['preservar'][$u]['motivo']);
        $this->assertSame('projeto', $this->ler($u));
    }

    public function testRegraDoAtualizadorSoValeParaOCore(): void
    {
        $this->assertTrue(instalacao_core_sempre_escrever('core', 'controladores/atualizacoes/atualizacoes-banco-de-dados.php'));
        $this->assertTrue(instalacao_core_sempre_escrever('core', 'bibliotecas/instalacao-manifesto.php'));
        $this->assertTrue(instalacao_core_sempre_escrever('core', 'bibliotecas\\db-data.php'));
        $this->assertFalse(instalacao_core_sempre_escrever('core', 'bibliotecas/gestor.php'));
        $this->assertFalse(instalacao_core_sempre_escrever('core', 'modulos/admin-paginas/admin-paginas.php'));
        $this->assertFalse(instalacao_core_sempre_escrever('projeto', 'controladores/atualizacoes/atualizacoes-sistema.php'));
        $this->assertFalse(instalacao_core_sempre_escrever('plugin:x', 'bibliotecas/deploy-lock.php'));
    }

    /**
     * REQ-285 (Lab, 3.0.8 -> 3.0.9): o projeto reivindica `db/data/ModulosData.json` (o do core fica em
     * overrides/) e deixou o particionado de `paginas`, que encobre o `PaginasData.json` do core. A etapa de
     * banco tem de ler só o que o core entregou.
     */
    public function testDadosDoCoreQuandoOProjetoOcupaDbData(): void
    {
        $this->entregar('core', ['db/data/PaginasData.json' => '[{"id":"core"}]', 'db/data/ModulosData.json' => '[{"id":"m-core"}]'], 'v1');
        $this->entregar('projeto', ['db/data/ModulosData.json' => '[{"id":"m-projeto"}]'], 'p1');
        $this->entregar('core', ['db/data/PaginasData.json' => '[{"id":"core"}]', 'db/data/ModulosData.json' => '[{"id":"m-core-2"}]'], 'v2');
        $this->assertSame('[{"id":"m-projeto"}]', $this->ler('db/data/ModulosData.json'), 'O arquivo do projeto fica no disco.');
        file_put_contents($this->base . 'db/data/PaginasData.manifest.json', '{"parts":[]}');
        file_put_contents($this->base . 'db/data/PaginasData.part-001.json', '[{"id":"docs"}]');
        file_put_contents($this->base . 'db/data/ProdutosData.json', '[]'); // tabela só do projeto: não importa ao core

        $destino = $this->raiz . 'dados-core' . DIRECTORY_SEPARATOR;
        $dir = instalacao_core_dados_dir($this->base, 'v2', $destino);
        $this->assertSame($destino, $dir);
        $arquivos = array_map('basename', glob($dir . '*'));
        sort($arquivos);
        $this->assertSame(['ModulosData.json', 'PaginasData.json'], $arquivos, 'Sem o particionado nem a tabela do projeto.');
        $this->assertSame('[{"id":"m-core-2"}]', file_get_contents($dir . 'ModulosData.json'), 'A versão do core vem de overrides/.');
        $this->assertSame('[{"id":"core"}]', file_get_contents($dir . 'PaginasData.json'));
    }

    public function testDadosDoCoreSemConflitoLeDbData(): void
    {
        $this->assertNull(instalacao_core_dados_dir($this->base, 'v1', $this->raiz . 'x'), 'Sem manifesto do core.');
        $this->entregar('core', ['db/data/PaginasData.json' => '[]', 'db/data/ModulosData.json' => '[]'], 'v1');
        file_put_contents($this->base . 'db/data/ProdutosData.json', '[]');
        $this->assertNull(instalacao_core_dados_dir($this->base, 'v1', $this->raiz . 'x'), 'Tabela a mais do projeto não é conflito.');
        file_put_contents($this->base . 'db/data/PaginasData.manifest.json', '{}');
        $this->assertNotNull(instalacao_core_dados_dir($this->base, 'v1', $this->raiz . 'x'), 'Particionado que encobre o core é conflito.');
    }

    /**
     * REQ-285 (deploy de projeto no Lab): 298 arquivos editados no servidor viraram choque com diff; o registro
     * codificava tudo de uma vez e estourava a memória depois de o manifesto já estar gravado. A entrega seguinte
     * via o mesmo conteúdo e deixava os arquivos velhos sem pedir decisão.
     */
    public function testChoquesRegistradosAntesDoManifestoEEmFluxo(): void
    {
        $this->entregar('projeto', ['a.php' => 'a1', 'b.css' => 'b1'], 'p1');
        file_put_contents($this->base . 'a.php', "editado\xF0\x9F\x8E\xAF à mão");
        file_put_contents($this->base . 'b.css', 'editado');
        $p = $this->pacote('p2', ['a.php' => 'a2', 'b.css' => 'b2']);
        $plano = instalacao_planejar($this->base, 'projeto', instalacao_mapa($p), true);
        $r = instalacao_aplicar($this->base, $p, 'projeto', $plano, 'p2', 'copiar', ['origem' => 'api-project-update', 'execucao' => 'site@x']);
        $this->assertTrue($r['manifesto_gravado']);
        $this->assertFileExists($r['choques_arquivo']);
        $d = json_decode((string)file_get_contents($r['choques_arquivo']), true);
        $this->assertSame(['origem' => 'api-project-update', 'camada' => 'projeto', 'versao' => 'p2', 'execucao' => 'site@x'],
            array_intersect_key($d, array_flip(['origem', 'camada', 'versao', 'execucao'])), 'Arquivo em fluxo é JSON válido.');
        $this->assertSame(['a.php', 'b.css'], array_column($d['choques'], 'caminho'));
        $linhas = [];
        $this->assertSame(2, instalacao_choques_gravar_pendentes($this->base, function ($l) use (&$linhas) { $linhas[] = $l; return true; }));
        $this->assertSame('editado', $linhas[0]['motivo']);
    }

    public function testRegistroDosChoquesFalhouOManifestoNaoEGravado(): void
    {
        $this->entregar('projeto', ['a.php' => 'a1'], 'p1');
        file_put_contents($this->base . 'a.php', 'editado');
        // `installation/choques` ocupado por um arquivo: o registro não tem onde gravar.
        file_put_contents($this->base . 'installation/choques', 'x');
        $p = $this->pacote('p2', ['a.php' => 'a2']);
        $plano = instalacao_planejar($this->base, 'projeto', instalacao_mapa($p), true);
        $r = instalacao_aplicar($this->base, $p, 'projeto', $plano, 'p2', 'copiar', ['origem' => 'api-project-update']);
        $this->assertNull($r['choques_arquivo']);
        $this->assertFalse($r['manifesto_gravado']);
        $this->assertSame(hash('sha256', 'a1'), instalacao_manifesto_ler($this->base, 'projeto')['arquivos']['a.php'], 'Manifesto continua o anterior.');
        // Na entrega seguinte, o choque volta (antes ficava "já entregue" e sumia).
        unlink($this->base . 'installation/choques');
        $plano = instalacao_planejar($this->base, 'projeto', instalacao_mapa($p), true);
        $this->assertSame('editado', $plano['preservar']['a.php']['motivo']);
    }

    public function testApiNaoEntregaOProprioZipEnviado(): void
    {
        $api = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/api/api.php');
        $extrai = strpos($api, '$zip->extractTo($extract_dir);');
        $this->assertNotFalse($extrai);
        $this->assertNotFalse(strpos($api, '@unlink($zip_path);', $extrai), 'O zip enviado sai da pasta extraída antes do plano.');
        $this->assertLessThan(strpos($api, 'api_project_aplicar_arquivos($project_content_dir'), strpos($api, "api_memoria_minima('1024M');", $extrai), 'Etapa de arquivos com 1 GB.');
    }

    /** REQ-286: artefatos de sistema são sempre escritos pela camada dona, sem choque. */
    public function testArtefatosDeSistemaSempreEscritos(): void
    {
        $this->assertTrue(instalacao_artefato_sistema('vendor/composer/installed.php'));
        $this->assertTrue(instalacao_artefato_sistema('db/data/PaginasData.part-001.json'));
        $this->assertTrue(instalacao_artefato_sistema('modulos/x/resources/pt-br/pages/x/x.precompiled.css'));
        $this->assertTrue(instalacao_artefato_sistema('modulos/x/x.min.js'));
        $this->assertTrue(instalacao_artefato_sistema('assets/asset-versions.json'));
        $this->assertFalse(instalacao_artefato_sistema('modulos/x/x.php'), 'Código continua com choque.');
        $this->assertFalse(instalacao_artefato_sistema('resources/pt-br/pages/home/home.html'));

        $this->entregar('projeto', ['vendor/composer/installed.php' => 'ref-1', 'db/data/FormsData.json' => '[1]', 'x.php' => 'x1'], 'p1');
        file_put_contents($this->base . 'vendor/composer/installed.php', 'ref-sync-de-teste');
        file_put_contents($this->base . 'db/data/FormsData.json', '[velho]');
        file_put_contents($this->base . 'x.php', 'mudei à mão');
        [$plano, $r] = $this->entregar('projeto', ['vendor/composer/installed.php' => 'ref-2', 'db/data/FormsData.json' => '[2]', 'x.php' => 'x2'], 'p2');
        $this->assertSame('ref-2', $this->ler('vendor/composer/installed.php'));
        $this->assertSame('[2]', $this->ler('db/data/FormsData.json'));
        $this->assertSame(['x.php'], array_column($r['choques'], 'caminho'), 'Só o código vira choque.');
        $this->assertContains('vendor/composer/installed.php', $plano['forcados']);
    }

    public function testArtefatoSobrepostoFicaComADonaEOChoqueNasceResolvido(): void
    {
        $this->entregar('core', ['db/data/ModulosData.json' => '[core-1]'], 'v1');
        $this->entregar('projeto', ['db/data/ModulosData.json' => '[projeto]'], 'p1');
        [$plano, $r] = $this->entregar('core', ['db/data/ModulosData.json' => '[core-2]'], 'v2');
        $this->assertSame('[projeto]', $this->ler('db/data/ModulosData.json'));
        $this->assertSame('automatico', $plano['preservar']['db/data/ModulosData.json']['resolucao']);
        $this->assertSame('[core-2]', file_get_contents($this->base . 'backups/overrides/v2/db/data/ModulosData.json'), 'Versão do core em overrides/ para a etapa de banco.');
    }

    public function testConfigDoCoreSempreEscrito(): void
    {
        $this->assertTrue(instalacao_core_sempre_escrever('core', 'config.php'));
        $this->assertFalse(instalacao_core_sempre_escrever('projeto', 'config.php'));
        $this->entregar('core', ['config.php' => "versao 3.0.8"], 'v1');
        file_put_contents($this->base . 'config.php', "versao 3.0.8\r\n# sync de teste");
        [, $r] = $this->entregar('core', ['config.php' => "versao 3.0.11"], 'v2');
        $this->assertSame('versao 3.0.11', $this->ler('config.php'));
        $this->assertSame([], $r['choques']);
    }

    /** REQ-286: disco mudado por fora com a mesma entrega não fica escondido; choque visível não se repete. */
    public function testDivergenciaNuncaDecididaViraChoque(): void
    {
        $this->entregar('projeto', ['a.php' => 'a1'], 'p1');
        file_put_contents($this->base . 'a.php', 'regravado por sync de teste');
        [, $r] = $this->entregar('projeto', ['a.php' => 'a2'], 'p2');
        $this->assertSame('editado', $r['choques'][0]['motivo']);
        // Agora o manifesto diz que a2 foi entregue. Reentregar a2: o choque já está pendente (visível), não repete.
        [, $r2] = $this->entregar('projeto', ['a.php' => 'a2'], 'p3');
        $this->assertSame([], $r2['choques']);
        $this->assertSame('pendente', instalacao_regras_ler($this->base)['a.php']['acao']);

        // Caso do Lab/produção: o disco mudou por fora DEPOIS de uma entrega e nunca houve choque.
        $this->entregar('projeto', ['b.php' => 'b1'], 'p4');
        file_put_contents($this->base . 'b.php', 'fora do deploy');
        [$plano, $r3] = $this->entregar('projeto', ['a.php' => 'a2', 'b.php' => 'b1'], 'p5');
        $this->assertSame('editado', $plano['preservar']['b.php']['motivo'], 'Antes pulava em silêncio.');
        $this->assertContains('b.php', array_column($r3['choques'], 'caminho'));
    }

    public function testDiscoComAVersaoDoCoreEmArquivoDoProjetoVoltaParaADoProjeto(): void
    {
        $this->entregar('core', ['comum.php' => 'core'], 'v1');
        $this->entregar('projeto', ['comum.php' => 'projeto'], 'p1');
        file_put_contents($this->base . 'comum.php', 'core'); // alguém regravou com a versão do core
        [$plano, $r] = $this->entregar('projeto', ['comum.php' => 'projeto'], 'p2');
        $this->assertContains('comum.php', $plano['escrever']);
        $this->assertSame('projeto', $this->ler('comum.php'));
        $this->assertSame([], $r['choques']);
    }

    /** REQ-286: CRLF não é edição. */
    public function testQuebraDeLinhaNaoEEdicao(): void
    {
        $this->entregar('core', ['a.php' => "l1\nl2\n", 'b.php' => "b1\n"], 'v1');
        file_put_contents($this->base . 'a.php', "l1\r\nl2\r\n"); // igual ao entregue antes, com CRLF
        file_put_contents($this->base . 'b.php', "b2\r\n");       // igual ao novo, com CRLF
        [$plano, $r] = $this->entregar('core', ['a.php' => "l1\nl2 v2\n", 'b.php' => "b2\n"], 'v2');
        $this->assertSame([], $r['choques']);
        $this->assertSame("l1\nl2 v2\n", $this->ler('a.php'), 'Intacto: recebe a versão nova.');
        $this->assertSame("b2\n", $this->ler('b.php'), 'Só a quebra de linha mudava: normaliza.');
    }

    public function testControleDeMigracoesForaDoManifesto(): void
    {
        $this->assertTrue(instalacao_fora('db/.c2f-migrations-projeto.json'));
        $this->assertFalse(instalacao_fora('db/migrations/2026_x.php'));
        $this->entregar('projeto', ['db/.c2f-migrations-projeto.json' => '{}', 'a.php' => 'a'], 'p1');
        $this->assertArrayNotHasKey('db/.c2f-migrations-projeto.json', instalacao_manifesto_ler($this->base, 'projeto')['arquivos']);
    }

    /** REQ-289: o dist/ publicado acompanha a entrega (antes ficava com o JS da última publicação manual). */
    public function testDistRepublicaOQueMudou(): void
    {
        $dist = $this->raiz . 'public' . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR;
        $escrever = function (string $f, string $c) { @mkdir(dirname($f), 0777, true); file_put_contents($f, $c); };
        $escrever($this->base . 'modulos/m/m.js', 'fonte nova');
        $escrever($this->base . 'modulos/m/m.min.js', 'min nova');
        $escrever($this->base . 'modulos/v/v.js', 'v');
        $escrever($this->base . 'modulos/v/v.min.js', '');            // min vazio: publica a de autoria
        $escrever($this->base . 'assets/a/a.js', 'igual');
        $escrever($dist . 'm/js.js', 'min velha');
        $escrever($dist . 'a/a.js', 'igual');
        $manifesto = ['versao' => 1, 'minificado' => true, 'arquivos' => [
            'm/js.js' => ['sha1' => sha1('min velha'), 'bytes' => 9, 'v' => substr(sha1('min velha'), 0, 16), 'fonte' => 'modulos/m/m.min.js'],
            'v/js.js' => ['sha1' => 'x', 'bytes' => 1, 'v' => 'x', 'fonte' => 'modulos/v/v.js'],
            'a/a.js' => ['sha1' => sha1('igual'), 'bytes' => 5, 'v' => substr(sha1('igual'), 0, 16), 'fonte' => 'assets/a/a.js'],
            'sumiu/js.js' => ['sha1' => 'y', 'bytes' => 1, 'v' => 'y', 'fonte' => 'modulos/sumiu/sumiu.js'],
        ]];
        $escrever($dist . '.manifest.json', json_encode($manifesto));

        $r = instalacao_dist_ressincronizar($this->base, $dist);
        $this->assertSame(['verificados' => 4, 'atualizados' => 2, 'sem_fonte' => 1, 'falhas' => 0], $r);
        $this->assertSame('min nova', file_get_contents($dist . 'm/js.js'));
        $this->assertSame('v', file_get_contents($dist . 'v/js.js'));
        $novo = json_decode((string)file_get_contents($dist . '.manifest.json'), true)['arquivos'];
        $this->assertSame(substr(sha1('min nova'), 0, 16), $novo['m/js.js']['v'], 'O ?v= da URL muda junto.');
        $this->assertSame('modulos/m/m.min.js', $novo['m/js.js']['fonte']);
        $this->assertSame('modulos/v/v.js', $novo['v/js.js']['fonte']);
        $this->assertSame(sha1('igual'), $novo['a/a.js']['sha1']);
        $this->assertArrayHasKey('sumiu/js.js', $novo, 'Nada é removido.');
        $this->assertSame(['verificados' => 4, 'atualizados' => 0, 'sem_fonte' => 1, 'falhas' => 0], instalacao_dist_ressincronizar($this->base, $dist), 'Segunda passada: nada a fazer.');
        $this->assertSame(['verificados' => 0, 'atualizados' => 0, 'sem_fonte' => 0, 'falhas' => 0], instalacao_dist_ressincronizar($this->base, ''), 'Sem dist/: nada.');
    }
}
