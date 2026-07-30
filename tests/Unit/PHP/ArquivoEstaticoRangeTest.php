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
}
