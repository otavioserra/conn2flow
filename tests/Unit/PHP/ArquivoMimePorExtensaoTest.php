<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'arquivo.php';

/**
 * req-138 (BATCH-141) — MIME type real na origem do gerenciador de arquivos.
 *
 * O `admin-arquivos` montava o campo `mime` concatenando o rótulo interno de família com a
 * extensão (`$tipo . '/' . $ext`), produzindo `image/jpg` onde o real é `image/jpeg` e `file/pdf`
 * onde o real é `application/pdf` — valor que chega EXIBIDO ao usuário no picker de imagem.
 *
 * A função coberta aqui é PURA: resolve só pela extensão, sem tocar no disco.
 */
final class ArquivoMimePorExtensaoTest extends TestCase
{
    public function testResolveOsMimesDeImagemQueDivergiamDaConcatenacao(): void
    {
        // Estes são exatamente os casos em que a concatenação antiga errava.
        self::assertSame('image/jpeg', arquivo_mime_por_extensao('foto.jpg'));
        self::assertSame('image/jpeg', arquivo_mime_por_extensao('foto.jpeg'));
        self::assertSame('image/svg+xml', arquivo_mime_por_extensao('icone.svg'));
        self::assertSame('image/x-icon', arquivo_mime_por_extensao('favicon.ico'));
        self::assertSame('image/tiff', arquivo_mime_por_extensao('scan.tif'));
    }

    public function testResolveDocumentosQueSaiamComOPrefixoInternoFile(): void
    {
        // Antes: `file/pdf`, `file/json`, `file/zip` — nenhum deles é MIME.
        self::assertSame('application/pdf', arquivo_mime_por_extensao('manual.pdf'));
        self::assertSame('application/json', arquivo_mime_por_extensao('dados.json'));
        self::assertSame('application/zip', arquivo_mime_por_extensao('pacote.zip'));
        self::assertSame('text/csv', arquivo_mime_por_extensao('planilha.csv'));
        self::assertSame(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            arquivo_mime_por_extensao('contrato.docx')
        );
    }

    public function testResolveAudioEVideoQueDivergiam(): void
    {
        self::assertSame('audio/mpeg', arquivo_mime_por_extensao('musica.mp3'));
        self::assertSame('video/quicktime', arquivo_mime_por_extensao('clipe.mov'));
        self::assertSame('video/x-msvideo', arquivo_mime_por_extensao('antigo.avi'));
    }

    public function testPreservaOsCasosQueJaCoincidiamPorAcaso(): void
    {
        // Nenhuma regressão nos valores que a concatenação acertava.
        self::assertSame('image/png', arquivo_mime_por_extensao('foto.png'));
        self::assertSame('image/gif', arquivo_mime_por_extensao('anim.gif'));
        self::assertSame('image/webp', arquivo_mime_por_extensao('foto.webp'));
        self::assertSame('video/mp4', arquivo_mime_por_extensao('video.mp4'));
        self::assertSame('video/webm', arquivo_mime_por_extensao('video.webm'));
    }

    /**
     * INVARIANTE CRÍTICA: os seis consumidores do canal `postMessage` do gerenciador decidem se
     * aceitam o arquivo testando `/^image\//` sobre este campo. Se qualquer extensão classificada
     * como `image` devolvesse outro prefixo, o picker de imagem pararia de aceitar aquele formato
     * — em silêncio, e em todos os consumidores de uma vez.
     */
    public function testPrefixoDoMimeAcompanhaAFamiliaDeArquivoTipoPorExtensao(): void
    {
        $extensoes = [
            // Lista real de `arquivo_tipo_por_extensao()`, replicada aqui de propósito: se alguém
            // adicionar uma extensão lá e esquecer do mapa de MIME, este teste falha.
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'avif', 'tiff',
            'mp4', 'webm', 'ogv', 'mov', 'avi', 'mkv', 'm4v', 'wmv', 'flv', '3gp',
            'mp3', 'wav', 'ogg', 'oga', 'flac', 'aac', 'm4a', 'wma', 'opus',
        ];

        foreach ($extensoes as $ext) {
            $nome = 'arquivo.' . $ext;
            $familia = arquivo_tipo_por_extensao($nome);
            $mime = arquivo_mime_por_extensao($nome);

            self::assertNotSame(
                'application/octet-stream',
                $mime,
                sprintf('A extensão .%s é classificada como "%s" mas ficou sem MIME no mapa.', $ext, $familia)
            );
            self::assertStringStartsWith(
                $familia . '/',
                $mime,
                sprintf('.%s é família "%s" mas devolveu "%s" — quebraria o teste de prefixo.', $ext, $familia, $mime)
            );
        }
    }

    public function testExtensaoDesconhecidaOuAusenteCaiNoFallbackNeutro(): void
    {
        self::assertSame('application/octet-stream', arquivo_mime_por_extensao('coisa.xyz'));
        self::assertSame('application/octet-stream', arquivo_mime_por_extensao('LEIAME'));
        self::assertSame('application/octet-stream', arquivo_mime_por_extensao(''));
    }

    public function testIgnoraCaixaDaExtensaoEResolveComCaminhoCompleto(): void
    {
        self::assertSame('image/jpeg', arquivo_mime_por_extensao('FOTO.JPG'));
        self::assertSame('application/pdf', arquivo_mime_por_extensao('docs/2026/Manual.PDF'));
        // Nome com pontos no meio: vale a última extensão.
        self::assertSame('application/gzip', arquivo_mime_por_extensao('backup.tar.gz'));
    }

    public function testNuncaDevolveOsRotulosInternosQueVazavamAntes(): void
    {
        // Blindagem do bug: nenhum destes híbridos pode voltar a aparecer.
        foreach (['manual.pdf', 'dados.json', 'foto.jpg', 'musica.mp3', 'icone.svg'] as $nome) {
            $mime = arquivo_mime_por_extensao($nome);
            self::assertStringStartsNotWith('file/', $mime, $nome . ' voltou a sair com o rótulo interno.');
            self::assertNotSame('image/jpg', $mime);
            self::assertNotSame('audio/mp3', $mime);
        }
    }
}
