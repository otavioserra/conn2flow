<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-090 (req-090): cobre os helpers PUROS de segurança do gerenciador de
 * arquivos por árvore física (bibliotecas/arquivo.php), sem banco de dados:
 *  - sanitização de nomes de arquivo/pasta
 *  - bloqueio de extensões executáveis/perigosas
 *  - prevenção de path traversal (caminho relativo seguro + resolução sob a base)
 *  - derivação do caminho de miniatura e classificação de tipo por extensão
 */
final class AdminArquivosSegurancaTest extends TestCase
{
    private static string $baseDir;

    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'arquivo.php';

        self::$baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-arquivos-' . uniqid();
        @mkdir(self::$baseDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . '2026', 0777, true);
    }

    public static function tearDownAfterClass(): void
    {
        // Limpeza best-effort do diretório temporário.
        $base = self::$baseDir;
        if (is_dir($base)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) {
                $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
            }
            @rmdir($base);
        }
    }

    // ===== arquivo_nome_sanitizar =====

    public function testSanitizarRemoveCaracteresInvalidos(): void
    {
        self::assertSame('arquivo-1.jpg', arquivo_nome_sanitizar('arquivo:1.jpg'));
        self::assertSame('a-b-c.png', arquivo_nome_sanitizar('a*b?c.png'));
        self::assertSame('nome.txt', arquivo_nome_sanitizar('  nome.txt  '));
    }

    public function testSanitizarRemoveComponentesDeDiretorio(): void
    {
        self::assertSame('evil.php', arquivo_nome_sanitizar('../../evil.php'));
        self::assertSame('x.jpg', arquivo_nome_sanitizar('C:\\Windows\\x.jpg'));
        self::assertSame('foto.png', arquivo_nome_sanitizar('pasta/sub/foto.png'));
    }

    public function testSanitizarRemoveBytesNulosEControles(): void
    {
        self::assertSame('anb.txt', arquivo_nome_sanitizar("a\x00n\x01b.txt"));
    }

    public function testSanitizarVazioQuandoSoInvalidos(): void
    {
        self::assertSame('', arquivo_nome_sanitizar('...'));
        self::assertSame('', arquivo_nome_sanitizar('   '));
    }

    // ===== arquivo_extensao_perigosa =====

    public function testExtensaoPerigosaBloqueiaExecutaveis(): void
    {
        foreach (['x.php', 'x.PHP', 'x.phtml', 'x.php5', 'x.phar', 'x.py', 'x.sh', 'x.exe', 'x.htaccess'] as $nome) {
            self::assertTrue(arquivo_extensao_perigosa($nome), $nome . ' deveria ser perigoso');
        }
    }

    public function testExtensaoPerigosaCobreDuplaExtensao(): void
    {
        self::assertTrue(arquivo_extensao_perigosa('imagem.php.jpg'));
        self::assertTrue(arquivo_extensao_perigosa('imagem.jpg.php'));
    }

    public function testExtensaoPerigosaArquivosOcultosDeConfig(): void
    {
        self::assertTrue(arquivo_extensao_perigosa('.htaccess'));
        self::assertTrue(arquivo_extensao_perigosa('.user.ini'));
    }

    public function testExtensaoSeguraPermitida(): void
    {
        foreach (['foto.jpg', 'foto.png', 'doc.pdf', 'video.mp4', 'audio.mp3', 'planilha.xlsx'] as $nome) {
            self::assertFalse(arquivo_extensao_perigosa($nome), $nome . ' deveria ser permitido');
        }
    }

    // ===== arquivo_caminho_relativo_seguro (path traversal) =====

    public function testCaminhoRelativoValido(): void
    {
        self::assertSame('files/2026/foto.jpg', arquivo_caminho_relativo_seguro('files/2026/foto.jpg'));
        self::assertSame('files/2026', arquivo_caminho_relativo_seguro('files\\2026'));
        self::assertSame('', arquivo_caminho_relativo_seguro(''));
        self::assertSame('files', arquivo_caminho_relativo_seguro('./files/'));
    }

    public function testCaminhoRelativoRejeitaTraversal(): void
    {
        self::assertFalse(arquivo_caminho_relativo_seguro('../etc/passwd'));
        self::assertFalse(arquivo_caminho_relativo_seguro('files/../../secret'));
        self::assertFalse(arquivo_caminho_relativo_seguro('..\\..\\secret'));
    }

    public function testCaminhoRelativoRejeitaAbsolutoENulo(): void
    {
        self::assertFalse(arquivo_caminho_relativo_seguro('/etc/passwd'));
        self::assertFalse(arquivo_caminho_relativo_seguro('C:\\Windows\\system32'));
        self::assertFalse(arquivo_caminho_relativo_seguro("files/\x00foto.jpg"));
    }

    // ===== arquivo_caminho_resolver =====

    public function testResolverMantemDentroDaBase(): void
    {
        $abs = arquivo_caminho_resolver(self::$baseDir, 'files/2026');
        self::assertNotFalse($abs);
        self::assertStringStartsWith(
            str_replace('/', DIRECTORY_SEPARATOR, rtrim(str_replace('\\', '/', self::$baseDir), '/')),
            $abs
        );
    }

    public function testResolverRejeitaEscapeDaBase(): void
    {
        self::assertFalse(arquivo_caminho_resolver(self::$baseDir, '../fora'));
        self::assertFalse(arquivo_caminho_resolver(self::$baseDir, '/etc/passwd'));
    }

    public function testResolverAceitaAlvoInexistenteSeguro(): void
    {
        // Arquivo ainda não existe (upload): deve resolver sem realpath falhar.
        $abs = arquivo_caminho_resolver(self::$baseDir, 'files/2026/novo.jpg');
        self::assertNotFalse($abs);
        self::assertStringEndsWith('novo.jpg', $abs);
    }

    // ===== arquivo_mini_caminho_relativo =====

    public function testMiniCaminho(): void
    {
        self::assertSame('files/2026/mini/foto.jpg', arquivo_mini_caminho_relativo('files/2026/foto.jpg'));
        self::assertSame('mini/foto.jpg', arquivo_mini_caminho_relativo('foto.jpg'));
        self::assertSame('a/b/mini/c.png', arquivo_mini_caminho_relativo('a/b/c.png'));
    }

    // ===== arquivo_tipo_por_extensao =====

    public function testTipoPorExtensao(): void
    {
        self::assertSame('image', arquivo_tipo_por_extensao('foto.JPG'));
        self::assertSame('image', arquivo_tipo_por_extensao('logo.svg'));
        self::assertSame('video', arquivo_tipo_por_extensao('clipe.mp4'));
        self::assertSame('audio', arquivo_tipo_por_extensao('musica.mp3'));
        self::assertSame('file', arquivo_tipo_por_extensao('doc.pdf'));
        self::assertSame('file', arquivo_tipo_por_extensao('semextensao'));
    }
}
