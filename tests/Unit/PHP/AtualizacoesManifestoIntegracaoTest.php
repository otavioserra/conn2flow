<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-198 / BATCH-202: o atualizador do sistema aplica a precedência de camadas antes de mover o
 * staging, e os choques pendentes chegam à tabela depois do banco.
 */
final class AtualizacoesManifestoIntegracaoTest extends TestCase
{
    private static string $base;

    public static function setUpBeforeClass(): void
    {
        self::$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-updman-' . uniqid() . DIRECTORY_SEPARATOR;
        mkdir(self::$base . 'bibliotecas', 0777, true);
        foreach (['deploy-lock.php', 'instalacao-manifesto.php'] as $f) copy(CONN2FLOW_ROOT . '/gestor/bibliotecas/' . $f, self::$base . 'bibliotecas/' . $f);
        global $_GESTOR;
        $_GESTOR = is_array($_GESTOR ?? null) ? $_GESTOR : [];
        $_GESTOR['ROOT_PATH'] = self::$base;
        if (!defined('ATUALIZACOES_SISTEMA_SEM_EXECUCAO')) define('ATUALIZACOES_SISTEMA_SEM_EXECUCAO', true);
        require_once CONN2FLOW_ROOT . '/gestor/controladores/atualizacoes/atualizacoes-sistema.php';
        // Independe da ordem dos testes (o PHPUnit do core roda primeiro os que falharam antes).
        require_once CONN2FLOW_ROOT . '/gestor/bibliotecas/instalacao-manifesto.php';
    }

    public static function tearDownAfterClass(): void
    {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        @rmdir(self::$base);
    }

    private function staging(string $nome, array $arquivos): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-stg-' . $nome . '-' . uniqid() . DIRECTORY_SEPARATOR;
        foreach ($arquivos as $rel => $c) { @mkdir(dirname($dir . $rel), 0777, true); file_put_contents($dir . $rel, $c); }
        return $dir;
    }

    public function testAtualizacaoDoCorePreservaASobreposicaoDoProjeto(): void
    {
        global $CONTEXT, $BASE_PATH;
        $inst = $BASE_PATH;
        // Entrega 1 do core (linha de base) e sobreposição do projeto.
        $CONTEXT['release_tag'] = 'v1';
        $s1 = $this->staging('v1', ['controladores/x.php' => 'core-1', 'bibliotecas/y.php' => 'y1', 'gestor.zip' => 'zip']);
        aplicarManifestoCore($s1, $inst);
        moverConteudoStaging($s1, $inst, ['contents', 'logs', 'backups', 'temp', 'autenticacoes', 'installation']);
        $this->assertSame('core-1', file_get_contents($inst . 'controladores/x.php'));
        $this->assertTrue($CONTEXT['installation']['primeira']);
        $this->assertArrayNotHasKey('gestor.zip', instalacao_manifesto_ler($inst, 'core')['arquivos'], 'Artefato do pacote fora do manifesto.');
        $p = $this->staging('proj', ['controladores/x.php' => 'projeto']);
        instalacao_aplicar($inst, $p, 'projeto', instalacao_planejar($inst, 'projeto', instalacao_mapa($p), false), 'p1');

        // Entrega 2 do core: x.php mudou no core, mas o projeto sobrepõe.
        $CONTEXT['release_tag'] = 'v2';
        $s2 = $this->staging('v2', ['controladores/x.php' => 'core-2', 'bibliotecas/y.php' => 'y2']);
        $r = aplicarManifestoCore($s2, $inst);
        $this->assertFileDoesNotExist($s2 . 'controladores/x.php', 'O arquivo preservado sai do staging.');
        moverConteudoStaging($s2, $inst, ['contents', 'logs', 'backups', 'temp', 'autenticacoes', 'installation']);
        $this->assertSame('projeto', file_get_contents($inst . 'controladores/x.php'));
        $this->assertSame('y2', file_get_contents($inst . 'bibliotecas/y.php'));
        $this->assertSame('core-2', file_get_contents($inst . 'backups/overrides/v2/controladores/x.php'));
        $this->assertSame(1, $r['preservados']);
        $pendentes = glob($inst . 'installation/choques/*.pendente.json');
        $this->assertCount(1, $pendentes);
        $this->assertSame('sobreposto', json_decode(file_get_contents($pendentes[0]), true)['choques'][0]['motivo']);
    }

    public function testSaudeAcusaFatalNovoEIgnoraOsAntigos(): void
    {
        $base = self::$base . 'saude' . DIRECTORY_SEPARATOR;
        @mkdir($base . 'logs', 0777, true);
        file_put_contents($base . 'logs/php-error.log', "[x] PHP Fatal error: antigo\n");
        $offset = saudeLogOffset($base);
        $this->assertTrue(saudeVerificar($base, $offset, 'localhost')['ok'], 'Fatal de antes não conta.');
        file_put_contents($base . 'logs/php-error.log', "[y] PHP Warning: nada\n[z] PHP Fatal error: Uncaught Error: novo\n", FILE_APPEND);
        $s = saudeVerificar($base, $offset, 'localhost');
        $this->assertFalse($s['ok']);
        $this->assertStringContainsString('novo', $s['motivos'][0]);
    }

    public function testSaudeHttpTentaDnsDepoisLocalENaoReprovaSemConexao(): void
    {
        $base = self::$base . 'saude-http' . DIRECTORY_SEPARATOR;
        @mkdir($base . 'logs', 0777, true);
        $chamadas = [];
        $falso = function (array $respostas) use (&$chamadas) {
            $chamadas = [];
            return function ($url, $ip) use (&$chamadas, $respostas) { $chamadas[] = [$url, $ip]; return $respostas[$ip ?? 'dns']; };
        };
        // DNS sem conexão, 127.0.0.1 responde 500: reprova.
        $s = saudeVerificar($base, 0, 'exemplo.test', ['http' => $falso(['dns' => 0, '127.0.0.1' => 500])]);
        $this->assertFalse($s['ok']);
        $this->assertSame([['https://exemplo.test/', null], ['https://exemplo.test/', '127.0.0.1']], $chamadas);
        // DNS responde 200: não tenta o local.
        $s = saudeVerificar($base, 0, 'exemplo.test', ['http' => $falso(['dns' => 200, '127.0.0.1' => 500])]);
        $this->assertTrue($s['ok']);
        $this->assertCount(1, $chamadas);
        // Sem conexão nenhuma: inconclusivo, só aviso (não dispara o rollback).
        $s = saudeVerificar($base, 0, 'exemplo.test', ['http' => $falso(['dns' => 0, '127.0.0.1' => 0])]);
        $this->assertTrue($s['ok']);
        $this->assertNull($s['http']);
        $this->assertStringContainsString('--health-url', $s['avisos'][0]);
        // --health-url e --health-ip: uma tentativa só, com o IP dado.
        $s = saudeVerificar($base, 0, 'exemplo.test', ['url' => 'https://exemplo.test:8443/', 'ip' => '10.0.0.5', 'http' => $falso(['10.0.0.5' => 502])]);
        $this->assertFalse($s['ok']);
        $this->assertSame([['https://exemplo.test:8443/', '10.0.0.5']], $chamadas);
    }

    public function testRollbackManualDeUmaExecucao(): void
    {
        $base = self::$base . 'rb' . DIRECTORY_SEPARATOR;
        @mkdir($base, 0777, true);
        file_put_contents($base . 'a.php', 'antes');
        $plano = ['escrever' => ['a.php', 'novo.php'], 'retirar' => [], 'restaurar' => []];
        instalacao_snapshot_criar($base, $plano, $base . 'backups/atualizacoes/snapshots/exec-42/');
        file_put_contents($base . 'a.php', 'depois');
        file_put_contents($base . 'novo.php', 'n');
        ob_start();
        $codigo = rollbackExecucao($base, '42', false);
        $saida = ob_get_clean();
        $this->assertSame(EXIT_OK, $codigo);
        $this->assertSame('antes', file_get_contents($base . 'a.php'));
        $this->assertFileDoesNotExist($base . 'novo.php');
        $this->assertStringContainsString('--com-banco', $saida, 'Diz como voltar o banco.');
        ob_start();
        $this->assertSame(EXIT_GENERIC, rollbackExecucao($base, '999', false));
        ob_end_clean();
    }

    public function testGravaPendentesEMarcaComoGravado(): void
    {
        $base = self::$base . 'grava' . DIRECTORY_SEPARATOR;
        instalacao_choques_registrar($base, 'update-system', 'core', 'v9', '7', [
            ['caminho' => 'a.php', 'tipo' => 'arquivo', 'motivo' => 'editado', 'camada_dona' => null, 'hash_disco' => 'h1', 'hash_novo' => 'h2', 'copia' => 'backups/overrides/v9/a.php', 'diff' => '@1 - x'],
        ]);
        $linhas = [];
        $n = instalacao_choques_gravar_pendentes($base, function ($l) use (&$linhas) { $linhas[] = $l; return true; });
        $this->assertSame(1, $n);
        $this->assertSame(['execucao' => '7', 'origem' => 'update-system', 'camada' => 'core', 'versao' => 'v9', 'caminho' => 'a.php'], array_slice($linhas[0], 0, 5));
        $this->assertCount(0, glob($base . 'installation/choques/*.pendente.json'));
        $this->assertCount(1, glob($base . 'installation/choques/*.gravado.json'));
        // Falha ao inserir: continua pendente.
        instalacao_choques_registrar($base, 'api-project-update', 'projeto', 'p2', null, [['caminho' => 'b.php', 'motivo' => 'editado']]);
        $this->assertSame(0, instalacao_choques_gravar_pendentes($base, function () { return false; }));
        $this->assertCount(1, glob($base . 'installation/choques/*.pendente.json'));
    }
}
