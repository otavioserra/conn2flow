<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-143 (req-140) — compatibilidade entre a URL publicada e o nome físico em disco.
 *
 * O upload gravava `Ela (1).webp` (com espaço) e os consumidores publicavam a URL já sanitizada,
 * `Ela-(1).webp`. O `realpath()` do controlador falhava e o visitante recebia 404 de um arquivo que
 * estava lá. Estes casos cobrem a reconstrução do nome físico e, principalmente, as condições em
 * que ela NÃO deve acontecer — o fallback lê diretório, então precisa recusar cedo.
 */
final class ArquivoEstaticoNomeSanitizadoTest extends TestCase
{
    private static string $baseDir;

    public static function setUpBeforeClass(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }

        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'arquivo.php';
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores'
            . DIRECTORY_SEPARATOR . 'arquivo-estatico' . DIRECTORY_SEPARATOR . 'arquivo-estatico.php';

        self::$baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-estatico-' . uniqid();

        @mkdir(self::$baseDir . DIRECTORY_SEPARATOR . 'mini', 0777, true);
        @mkdir(self::$baseDir . DIRECTORY_SEPARATOR . 'Minha Pasta', 0777, true);

        // Nomes legados, gravados com espaço antes da correção.
        self::criar('Ela (1).webp');
        self::criar('mini' . DIRECTORY_SEPARATOR . 'Ela (1).webp');
        self::criar('Minha Pasta' . DIRECTORY_SEPARATOR . 'foto teste.png');
        self::criar('Foto-Final de Praia.webp');

        // Nome já em conformidade: serve para provar que o fallback não se intromete.
        self::criar('logo-conn2flow.svg');
    }

    public static function tearDownAfterClass(): void
    {
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

    private static function criar(string $rel): void
    {
        file_put_contents(self::$baseDir . DIRECTORY_SEPARATOR . $rel, 'binario');
    }

    // ===== arquivo_estatico_caminho_variantes (pura) =====

    public function testVariantesMantemOCaminhoOriginalEmPrimeiroLugar(): void
    {
        $variantes = arquivo_estatico_caminho_variantes('pasta/arquivo.webp');
        self::assertSame(['pasta/arquivo.webp'], $variantes);
    }

    public function testVarianteDecodificadaEntraQuandoOPercentEncodingChegaLiteral(): void
    {
        // Ambiente sem a flag [B] na reescrita: o `%20` chega como texto no caminho.
        $variantes = arquivo_estatico_caminho_variantes('Ela%20(1).webp');
        self::assertSame(['Ela%20(1).webp', 'Ela (1).webp'], $variantes);
    }

    public function testVarianteDecodificadaNaoPodeIntroduzirTraversal(): void
    {
        // `%2e%2e%2f` decodifica para `../`: a variante tem que ser recusada, não servida.
        $variantes = arquivo_estatico_caminho_variantes('%2e%2e%2fsegredo.env');
        self::assertSame(['%2e%2e%2fsegredo.env'], $variantes);

        $variantes = arquivo_estatico_caminho_variantes('pasta%5carquivo.webp');
        self::assertSame(['pasta%5carquivo.webp'], $variantes);
    }

    // ===== arquivo_estatico_resolver_nome_sanitizado =====

    public function testResolveArquivoGravadoComEspacoQuandoRequisitadoComHifen(): void
    {
        $fisico = arquivo_estatico_resolver_nome_sanitizado(self::$baseDir, 'Ela-(1).webp');

        self::assertNotFalse($fisico);
        self::assertStringEndsWith('Ela (1).webp', $fisico);
        self::assertFileExists($fisico);
    }

    public function testResolveAMiniaturaPeloMesmoCaminho(): void
    {
        $fisico = arquivo_estatico_resolver_nome_sanitizado(self::$baseDir, 'mini/Ela-(1).webp');

        self::assertNotFalse($fisico);
        self::assertStringEndsWith('mini/Ela (1).webp', $fisico);
        self::assertFileExists($fisico);
    }

    public function testResolveTambemOSegmentoDeDiretorioComEspaco(): void
    {
        $fisico = arquivo_estatico_resolver_nome_sanitizado(self::$baseDir, 'Minha-Pasta/foto-teste.png');

        self::assertNotFalse($fisico);
        self::assertStringEndsWith('Minha Pasta/foto teste.png', $fisico);
        self::assertFileExists($fisico);
    }

    public function testResolveNomeMistoDeHifenRealEEspaco(): void
    {
        // Trocar hífen por espaço às cegas erraria aqui: o nome físico tem os DOIS. A comparação é
        // pelo resultado da sanitização, então o caso cai naturalmente.
        $fisico = arquivo_estatico_resolver_nome_sanitizado(self::$baseDir, 'Foto-Final-de-Praia.webp');

        self::assertNotFalse($fisico);
        self::assertStringEndsWith('Foto-Final de Praia.webp', $fisico);
        self::assertFileExists($fisico);
    }

    public function testCaminhoQueJaExisteNaoEntraNoFallback(): void
    {
        // Sem divergência o resultado é false: quem chama já resolveu pela busca direta, e devolver
        // o mesmo caminho faria o controlador reenviar o arquivo pelo caminho mais caro.
        self::assertFalse(arquivo_estatico_resolver_nome_sanitizado(self::$baseDir, 'logo-conn2flow.svg'));
    }

    public function testArquivoInexistenteContinuaSemResolver(): void
    {
        self::assertFalse(arquivo_estatico_resolver_nome_sanitizado(self::$baseDir, 'nao-existe.webp'));
        self::assertFalse(arquivo_estatico_resolver_nome_sanitizado(self::$baseDir, 'mini/nao-existe.webp'));
    }

    public function testTraversalEhRecusadoAntesDeTocarODisco(): void
    {
        self::assertFalse(arquivo_estatico_resolver_nome_sanitizado(self::$baseDir, '../Ela-(1).webp'));
        self::assertFalse(arquivo_estatico_resolver_nome_sanitizado(self::$baseDir, 'mini/../../Ela-(1).webp'));
        self::assertFalse(arquivo_estatico_resolver_nome_sanitizado(self::$baseDir, ''));
    }

    public function testBaseInexistenteNaoResolve(): void
    {
        self::assertFalse(arquivo_estatico_resolver_nome_sanitizado(
            self::$baseDir . DIRECTORY_SEPARATOR . 'inexistente',
            'Ela-(1).webp'
        ));
    }

    // ===== arquivo_estatico_entrada_por_nome_sanitizado =====

    public function testEntradaExigeQueOTipoBata(): void
    {
        // `Minha Pasta` é diretório: pedir como arquivo não pode devolvê-la.
        self::assertFalse(arquivo_estatico_entrada_por_nome_sanitizado(self::$baseDir, 'Minha-Pasta', true));
        self::assertSame(
            'Minha Pasta',
            arquivo_estatico_entrada_por_nome_sanitizado(self::$baseDir, 'Minha-Pasta', false)
        );
    }

    public function testEntradaNaoInventaCorrespondenciaParcial(): void
    {
        self::assertFalse(arquivo_estatico_entrada_por_nome_sanitizado(self::$baseDir, 'Ela-(2).webp', true));
        self::assertFalse(arquivo_estatico_entrada_por_nome_sanitizado(self::$baseDir, 'Ela.webp', true));
    }

    // ===== integração com a guarda de containment =====

    public function testResultadoContinuaSubordinadoABaseAutorizada(): void
    {
        $fisico = arquivo_estatico_resolver_nome_sanitizado(self::$baseDir, 'Ela-(1).webp');
        self::assertNotFalse($fisico);

        // A autorização é de quem chama: o fallback descobre o nome, não libera o envio.
        self::assertNotFalse(arquivo_estatico_resolver_autorizado($fisico, [self::$baseDir]));
        self::assertFalse(arquivo_estatico_resolver_autorizado(
            $fisico,
            [sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'outra-base-c2f-inexistente']
        ));
    }
}
