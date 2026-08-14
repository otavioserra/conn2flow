<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-100 — requisição parcial (HTTP Range) e Content-Type do servidor de arquivos estáticos.
 *
 * Reprodução de vídeo/áudio depende de resposta 206: o navegador pede faixas para iniciar rápido e
 * para permitir o seek, e o Safari/iOS não toca mídia servida sem isso.
 */
final class ArquivoEstaticoRangeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }

        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores'
            . DIRECTORY_SEPARATOR . 'arquivo-estatico' . DIRECTORY_SEPARATOR . 'arquivo-estatico.php';
    }

    public function testSemCabecalhoRangeRetornaNull(): void
    {
        self::assertNull(arquivo_estatico_range('', 1000));
        self::assertNull(arquivo_estatico_range(null, 1000));
    }

    public function testFaixaFechadaEAberta(): void
    {
        self::assertSame([0, 99], arquivo_estatico_range('bytes=0-99', 1000));
        self::assertSame([500, 999], arquivo_estatico_range('bytes=500-', 1000));
        // O pedido inicial do Chrome para mídia.
        self::assertSame([0, 999], arquivo_estatico_range('bytes=0-', 1000));
    }

    public function testSufixoRetornaOsUltimosBytes(): void
    {
        self::assertSame([900, 999], arquivo_estatico_range('bytes=-100', 1000));
        // Sufixo maior que o arquivo devolve o arquivo inteiro.
        self::assertSame([0, 999], arquivo_estatico_range('bytes=-5000', 1000));
    }

    public function testFimAlemDoArquivoEhLimitadoAoTamanho(): void
    {
        self::assertSame([100, 999], arquivo_estatico_range('bytes=100-999999', 1000));
    }

    public function testFaixasInvalidasSinalizam416(): void
    {
        self::assertFalse(arquivo_estatico_range('bytes=2000-3000', 1000)); // início fora do arquivo
        self::assertFalse(arquivo_estatico_range('bytes=800-200', 1000));   // invertida
        self::assertFalse(arquivo_estatico_range('bytes=-0', 1000));        // sufixo vazio
        self::assertFalse(arquivo_estatico_range('bytes=-', 1000));         // sem limites
        self::assertFalse(arquivo_estatico_range('items=0-10', 1000));      // unidade não suportada
        self::assertFalse(arquivo_estatico_range('bytes=0-99', 0));         // arquivo vazio
    }

    public function testContentTypeUsaCharsetApenasEmFormatosDeTexto(): void
    {
        self::assertSame('application/javascript; charset=UTF-8', arquivo_estatico_content_type('js'));
        self::assertSame('text/css; charset=UTF-8', arquivo_estatico_content_type('css'));
        self::assertSame('image/svg+xml; charset=UTF-8', arquivo_estatico_content_type('svg'));
        // Binário sem arquivo resolvível cai no tipo genérico, nunca com charset.
        self::assertStringNotContainsString('charset', arquivo_estatico_content_type('mp4'));
        self::assertSame('application/octet-stream', arquivo_estatico_content_type('mp4'));
    }

    public function testCacheLongoEhRestritoAUrlComVersaoValida(): void
    {
        self::assertTrue(arquivo_estatico_versao_cache_valida(['v' => '2.9.35']));
        self::assertTrue(arquivo_estatico_versao_cache_valida(['v' => 'build-20260814_1']));
        self::assertFalse(arquivo_estatico_versao_cache_valida([]));
        self::assertFalse(arquivo_estatico_versao_cache_valida(['v' => '']));
        self::assertFalse(arquivo_estatico_versao_cache_valida(['v' => ['invalido']]));
        self::assertFalse(arquivo_estatico_versao_cache_valida(['v' => "versao\r\nX-Test: 1"]));

        self::assertSame(
            'public, max-age=31536000, immutable',
            arquivo_estatico_cache_control(['v' => '2.9.35'])
        );
        self::assertSame(
            'public, max-age=86400, stale-while-revalidate=604800',
            arquivo_estatico_cache_control([])
        );
    }

    public function testEtagEhEstavelEConsideraDataETamanho(): void
    {
        self::assertSame('"64-3e8"', arquivo_estatico_etag(1000, 100));
        self::assertNotSame(arquivo_estatico_etag(1000, 100), arquivo_estatico_etag(1001, 100));
        self::assertNotSame(arquivo_estatico_etag(1000, 100), arquivo_estatico_etag(1000, 101));
    }

    public function testIfNoneMatchAceitaListaWildcardEComparacaoFraca(): void
    {
        $etag = '"64-3e8"';

        self::assertTrue(arquivo_estatico_etag_corresponde($etag, $etag));
        self::assertTrue(arquivo_estatico_etag_corresponde('"outro", W/"64-3e8"', $etag));
        self::assertTrue(arquivo_estatico_etag_corresponde('*', $etag));
        self::assertFalse(arquivo_estatico_etag_corresponde('"outro"', $etag));
    }

    public function testIfNoneMatchTemPrecedenciaSobreIfModifiedSince(): void
    {
        $etag = '"64-3e8"';
        $modificado = strtotime('2026-08-14 12:00:00 UTC');
        $depois = gmdate('D, d M Y H:i:s', $modificado + 3600).' GMT';

        self::assertTrue(arquivo_estatico_nao_modificado($etag, '', $etag, $modificado));
        self::assertTrue(arquivo_estatico_nao_modificado('', $depois, $etag, $modificado));
        self::assertFalse(arquivo_estatico_nao_modificado('"outro"', $depois, $etag, $modificado));
        self::assertFalse(arquivo_estatico_nao_modificado('', 'data-invalida', $etag, $modificado));
    }

    public function testIfRangeSoPermiteFaixaComValidadorAtual(): void
    {
        $etag = '"64-3e8"';
        $modificado = strtotime('2026-08-14 12:00:00 UTC');

        self::assertTrue(arquivo_estatico_if_range_permite('', $etag, $modificado));
        self::assertTrue(arquivo_estatico_if_range_permite($etag, $etag, $modificado));
        self::assertFalse(arquivo_estatico_if_range_permite('W/'.$etag, $etag, $modificado));
        self::assertFalse(arquivo_estatico_if_range_permite('"outro"', $etag, $modificado));
        self::assertTrue(arquivo_estatico_if_range_permite(
            gmdate('D, d M Y H:i:s', $modificado + 60).' GMT',
            $etag,
            $modificado
        ));
        self::assertFalse(arquivo_estatico_if_range_permite(
            gmdate('D, d M Y H:i:s', $modificado - 60).' GMT',
            $etag,
            $modificado
        ));
    }
}
