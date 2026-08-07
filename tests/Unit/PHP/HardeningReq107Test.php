<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'seguranca.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'oauth2.php';
require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'gestor-instalador' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Installer.php';

if (!defined('SDD_NO_AUTORUN')) define('SDD_NO_AUTORUN', true);
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores'
    . DIRECTORY_SEPARATOR . 'arquivo-estatico' . DIRECTORY_SEPARATOR . 'arquivo-estatico.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores'
    . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'api.php';

final class HardeningReq107Test extends TestCase
{
    public function testTokenAleatorioTemPeloMenos128BitsEValoresUnicos(): void
    {
        $a = seguranca_token_aleatorio(16);
        $b = seguranca_token_aleatorio(16);

        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $a);
        self::assertNotSame($a, $b);
    }

    public function testCsrfUsaComparacaoEstritaComTokenEsperado(): void
    {
        $esperado = seguranca_token_aleatorio(32);

        self::assertTrue(gestor_csrf_validar($esperado, $esperado));
        self::assertFalse(gestor_csrf_validar($esperado . 'x', $esperado));
        self::assertFalse(gestor_csrf_validar('', $esperado));
    }

    public function testCsrfPermiteSomenteTransicaoDoAutoatualizadorAteVersao2925(): void
    {
        self::assertTrue(seguranca_csrf_atualizador_transicao_isento(['admin-atualizacoes'], '2.9.25'));
        self::assertTrue(seguranca_csrf_atualizador_transicao_isento(['admin-atualizacoes'], '2.9.24'));
        self::assertFalse(seguranca_csrf_atualizador_transicao_isento(['admin-atualizacoes'], '2.9.26'));
        self::assertFalse(seguranca_csrf_atualizador_transicao_isento(['admin-usuarios'], '2.9.25'));
    }

    public function testCsrfIsentaSomenteConsultaDeStatusLegadaDoAutoatualizador(): void
    {
        self::assertTrue(seguranca_csrf_atualizador_status_isento(
            ['admin-atualizacoes'],
            ['params' => ['acao' => 'status', 'sid' => 'abc123']]
        ));
        self::assertFalse(seguranca_csrf_atualizador_status_isento(
            ['admin-atualizacoes'],
            ['params' => ['acao' => 'finalize', 'sid' => 'abc123']]
        ));
        self::assertFalse(seguranca_csrf_atualizador_status_isento(
            ['admin-usuarios'],
            ['params' => ['acao' => 'status']]
        ));
    }

    public function testCsrfPermiteSomenteEtapaValidaDeSessaoLegadaDoAutoatualizador(): void
    {
        $agora = 1786050600;
        $sid = 'a94064ba170414e5';
        $raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-csrf-update-' . bin2hex(random_bytes(4));
        $diretorio = $raiz . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR . 'sessions';
        mkdir($diretorio, 0777, true);
        $arquivo = $diretorio . DIRECTORY_SEPARATOR . $sid . '.json';
        $estado = [
            'sid' => $sid,
            'created_at' => date(DATE_ATOM, $agora - 120),
            'opts' => [],
            'progress' => [
                'bootstrap' => ['done' => true],
                'deploy_files' => ['done' => true],
            ],
            'finished' => false,
        ];
        file_put_contents($arquivo, json_encode($estado));
        $cabecalhoAnterior = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);

        try {
            $requisicaoDb = ['params' => ['acao' => 'db', 'sid' => $sid]];
            self::assertTrue(seguranca_csrf_atualizador_sessao_legada_isento(
                ['admin-atualizacoes'],
                $requisicaoDb,
                $raiz,
                $agora
            ));

            self::assertFalse(seguranca_csrf_atualizador_sessao_legada_isento(
                ['admin-atualizacoes'],
                ['params' => ['acao' => 'finalize', 'sid' => $sid]],
                $raiz,
                $agora
            ));

            $estado['progress']['database'] = ['done' => true];
            file_put_contents($arquivo, json_encode($estado));
            self::assertTrue(seguranca_csrf_atualizador_sessao_legada_isento(
                ['admin-atualizacoes'],
                ['params' => ['acao' => 'finalize', 'sid' => $sid]],
                $raiz,
                $agora
            ));

            $estado['opts']['csrf-capable'] = 1;
            file_put_contents($arquivo, json_encode($estado));
            self::assertFalse(seguranca_csrf_atualizador_sessao_legada_isento(
                ['admin-atualizacoes'],
                ['params' => ['acao' => 'cancel', 'sid' => $sid]],
                $raiz,
                $agora
            ));

            $_SERVER['HTTP_X_CSRF_TOKEN'] = 'token-invalido';
            $estado['opts'] = [];
            file_put_contents($arquivo, json_encode($estado));
            self::assertFalse(seguranca_csrf_atualizador_sessao_legada_isento(
                ['admin-atualizacoes'],
                ['params' => ['acao' => 'cancel', 'sid' => $sid]],
                $raiz,
                $agora
            ));

            unset($_SERVER['HTTP_X_CSRF_TOKEN']);
            $estado['created_at'] = date(DATE_ATOM, $agora - 21601);
            file_put_contents($arquivo, json_encode($estado));
            self::assertFalse(seguranca_csrf_atualizador_sessao_legada_isento(
                ['admin-atualizacoes'],
                ['params' => ['acao' => 'cancel', 'sid' => $sid]],
                $raiz,
                $agora
            ));
        } finally {
            if($cabecalhoAnterior === null) unset($_SERVER['HTTP_X_CSRF_TOKEN']);
            else $_SERVER['HTTP_X_CSRF_TOKEN'] = $cabecalhoAnterior;
            @unlink($arquivo);
            @rmdir($diretorio);
            @rmdir(dirname($diretorio));
            @rmdir(dirname(dirname($diretorio)));
            @rmdir($raiz);
        }
    }

    public function testApiAceitaTokenSomenteNoAuthorizationBearer(): void
    {
        $_GET['token'] = 'token-na-query';
        unset($_SERVER['HTTP_AUTHORIZATION']);
        self::assertNull(api_bearer_token());

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-do-header';
        self::assertSame('token-do-header', api_bearer_token());

        unset($_GET['token'], $_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testEscapeLegadoFalhaSemConexaoMysqliAtiva(): void
    {
        global $_BANCO;
        $anterior = $_BANCO;
        $_BANCO = ['tipo' => 'sqlite'];

        try {
            banco_escape_field("valor' sem conexao");
            self::fail('O escape legado deveria falhar sem conexão mysqli.');
        } catch (LogicException $e) {
            self::assertStringContainsString('mysqli ativa', $e->getMessage());
        } finally {
            $_BANCO = $anterior;
        }
    }

    public function testSmartStripslashesPermaneceComoShimDeCompatibilidade(): void
    {
        self::assertTrue(function_exists('banco_smartstripslashes'));
        self::assertSame('texto\\com\\barras', banco_smartstripslashes('texto\\com\\barras'));
        self::assertSame('', banco_smartstripslashes(null));
        self::assertSame('123', banco_smartstripslashes(123));
    }

    public function testCaminhoEstaticoRejeitaTraversalNuloEBarraInvertida(): void
    {
        self::assertFalse(arquivo_estatico_caminho_valido('../config.php'));
        self::assertFalse(arquivo_estatico_caminho_valido('%252e%252e%252fconfig.php'));
        self::assertFalse(arquivo_estatico_caminho_valido("asset\0.js"));
        self::assertFalse(arquivo_estatico_caminho_valido('assets\\arquivo.js'));
        self::assertTrue(arquivo_estatico_caminho_valido('assets/app.js'));
    }

    public function testResolucaoFisicaPermaneceNaRaizAutorizada(): void
    {
        $raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req107-' . bin2hex(random_bytes(4));
        $base = $raiz . DIRECTORY_SEPARATOR . 'assets';
        mkdir($base, 0777, true);
        file_put_contents($base . DIRECTORY_SEPARATOR . 'ok.txt', 'ok');
        file_put_contents($raiz . DIRECTORY_SEPARATOR . 'secret.txt', 'secret');

        try {
            self::assertSame(
                str_replace('\\', '/', realpath($base . DIRECTORY_SEPARATOR . 'ok.txt')),
                arquivo_estatico_resolver_autorizado($base . DIRECTORY_SEPARATOR . 'ok.txt', [$base])
            );
            self::assertFalse(arquivo_estatico_resolver_autorizado($base . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'secret.txt', [$base]));
        } finally {
            unlink($base . DIRECTORY_SEPARATOR . 'ok.txt');
            unlink($raiz . DIRECTORY_SEPARATOR . 'secret.txt');
            rmdir($base);
            rmdir($raiz);
        }
    }

    public function testChecksumDoInstaladorAceitaArquivoIntegroERejeitaDivergencia(): void
    {
        $arquivo = tempnam(sys_get_temp_dir(), 'c2f-zip-');
        $checksum = tempnam(sys_get_temp_dir(), 'c2f-sha-');
        self::assertIsString($arquivo);
        self::assertIsString($checksum);
        file_put_contents($arquivo, 'release-test');
        file_put_contents($checksum, hash_file('sha256', $arquivo) . "  gestor.zip\n");

        try {
            $resultado = Installer::verifyZipSha256($arquivo, $checksum);
            self::assertSame($resultado['expected'], $resultado['got']);

            file_put_contents($arquivo, 'release-tampered');
            $this->expectException(Exception::class);
            Installer::verifyZipSha256($arquivo, $checksum);
        } finally {
            @unlink($arquivo);
            @unlink($checksum);
        }
    }

    public function testEscritaDoEnvTrataMetacaracteresComoValorLiteral(): void
    {
        $reflexao = new ReflectionClass(Installer::class);
        $installer = $reflexao->newInstanceWithoutConstructor();
        $metodo = $reflexao->getMethod('envSetLiteral');
        $resultado = $metodo->invoke($installer, "DB_PASSWORD=antiga\nDB_HOST=localhost\n", 'DB_PASSWORD', '$1\\senha#forte');

        self::assertStringNotContainsString('antiga', $resultado);
        self::assertStringContainsString('\\$1', $resultado);
        self::assertStringContainsString('senha#forte', $resultado);
        self::assertSame(1, substr_count($resultado, 'DB_PASSWORD='));
    }

    public function testRotacaoOauth2RevogaPrimeiroTokenAtivoEmFifo(): void
    {
        $tokens = [
            ['id_oauth2_tokens' => 30, 'tipo' => 'access', 'data_criacao' => '2026-01-03 00:00:00'],
            ['id_oauth2_tokens' => 10, 'tipo' => 'access', 'data_criacao' => '2026-01-01 00:00:00'],
            ['id_oauth2_tokens' => 20, 'tipo' => 'access', 'data_criacao' => '2026-01-02 00:00:00'],
        ];

        self::assertSame([10], oauth2_fifo_ids_para_revogar($tokens, 3));
        self::assertSame([10, 20], oauth2_fifo_ids_para_revogar($tokens, 2));
    }
}
