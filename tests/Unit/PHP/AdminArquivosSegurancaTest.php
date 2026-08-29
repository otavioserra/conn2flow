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

    /**
     * BATCH-100: espaço no nome vira hífen já na entrada. Nomes de gravador de tela e do WhatsApp
     * trazem espaço por padrão, e isso produzia `src` com espaço literal (HTML inválido) e 403 na
     * reescrita do Apache sem a flag [B].
     */
    public function testSanitizarTrocaEspacosPorHifen(): void
    {
        self::assertSame('2026-07-30-16-03-46.mp4', arquivo_nome_sanitizar('2026-07-30 16-03-46.mp4'));
        self::assertSame('WhatsApp-Ptt-2026-07-30-at-13.16.55.ogg', arquivo_nome_sanitizar('WhatsApp Ptt 2026-07-30 at 13.16.55.ogg'));
        // Espaços repetidos e hífens duplicados colapsam num único hífen.
        self::assertSame('meu-arquivo.pdf', arquivo_nome_sanitizar('meu    arquivo.pdf'));
        self::assertSame('a-b.png', arquivo_nome_sanitizar('a - b.png'));
        // Sem espaços nas pontas sobrando.
        self::assertSame('nome.txt', arquivo_nome_sanitizar('  nome.txt  '));
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

    // ===== arquivo_nome_colisao (BATCH-143 / req-140) =====

    public function testColisaoUsaHifenENuncaEspaco(): void
    {
        self::assertSame('foto-(1).jpg', arquivo_nome_colisao('foto', 'jpg', 1));
        self::assertSame('foto-(2).jpg', arquivo_nome_colisao('foto', 'jpg', 2));
        self::assertSame('leia-me-(1)', arquivo_nome_colisao('leia-me', '', 1));
    }

    public function testColisaoNuncaProduzEspacoParaNenhumIndice(): void
    {
        // O espaço era a causa-raiz do 404: o disco guardava `foto (1).jpg` e a URL publicada
        // apontava para `foto-(1).jpg`.
        for ($i = 1; $i <= 25; $i++) {
            $nome = arquivo_nome_colisao('Ela', 'webp', $i);
            self::assertStringNotContainsString(' ', $nome, 'indice '.$i);
            self::assertSame('Ela-('.$i.').webp', $nome);
        }
    }

    public function testColisaoEhIdempotenteSobASanitizacao(): void
    {
        // A invariante que fecha o bug: o nome gravado no disco tem que sobreviver à sanitização
        // aplicada por quem monta a URL, senão a divergência volta pela porta dos fundos.
        foreach (['foto', 'Ela', 'leia-me', 'a-b-c', 'nome-'] as $base) {
            $nome = arquivo_nome_colisao($base, 'png', 3);
            self::assertSame($nome, arquivo_nome_sanitizar($nome), 'base '.$base);
        }
    }

    public function testColisaoColapsaHifenDuplicadoDeNomeBaseTerminadoEmHifen(): void
    {
        // `nome-` + `-(1)` daria `nome--(1)`, que a sanitização reduz a `nome-(1)`.
        self::assertSame('nome-(1).png', arquivo_nome_colisao('nome-', 'png', 1));
    }

    public function testArquivoDeColisaoContinuaAlcancavelPelaUrlQueOSistemaPublica(): void
    {
        // Blindagem do req-140 pelo COMPORTAMENTO, e nao pela formula: grava o arquivo com o nome
        // que o desempate produz e o procura pelo caminho que os consumidores publicam. Com o
        // sufixo antigo (` (1)`) o disco guardava `x (1).png` e a pagina pedia `x-(1).png`; este
        // teste falha de novo se o espaco voltar, com ou sem a funcao atual.
        $dir = self::$baseDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . '2026';

        $nomeGravado = arquivo_nome_colisao('captura-de-tela', 'png', 1);
        file_put_contents($dir . DIRECTORY_SEPARATOR . $nomeGravado, 'x');

        // O caminho que a pagina monta passa pela sanitizacao canonica.
        $urlPublicada = arquivo_caminho_relativo_seguro('files/2026/' . $nomeGravado);
        self::assertNotFalse($urlPublicada);

        $alvo = arquivo_caminho_resolver(self::$baseDir, $urlPublicada);
        self::assertNotFalse($alvo);
        self::assertFileExists(
            $alvo,
            'A URL publicada (' . $urlPublicada . ') nao encontrou o arquivo gravado (' . $nomeGravado . ').'
        );

        @unlink($dir . DIRECTORY_SEPARATOR . $nomeGravado);
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
