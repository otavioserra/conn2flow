<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-240 (segundo adendo) — o que o `admin-arquivos` herdou do módulo `arquivos` do site para poder
 * substituí-lo: conferência do conteúdo contra a extensão, sanitização de SVG, confinamento do
 * caminho ao escopo do usuário e soma do espaço usado.
 */
final class ArquivoUploadReq240Test extends TestCase
{
    private static string $dir;

    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'arquivo.php';
        self::$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req240-' . uniqid();
        mkdir(self::$dir, 0777, true);
    }

    public static function tearDownAfterClass(): void
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::$dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir(self::$dir);
    }

    private function gravar(string $nome, string $conteudo): string
    {
        $caminho = self::$dir . DIRECTORY_SEPARATOR . $nome;
        if (!is_dir(dirname($caminho))) {
            mkdir(dirname($caminho), 0777, true);
        }
        file_put_contents($caminho, $conteudo);
        return $caminho;
    }

    public function testConteudoQueBateComAExtensaoPassa(): void
    {
        $casos = [
            'foto.png' => "\x89PNG\r\n\x1A\n" . str_repeat("\0", 8),
            'foto.JPG' => "\xFF\xD8\xFF\xE0" . str_repeat("\0", 12),
            'anim.gif' => 'GIF89a' . str_repeat("\0", 10),
            'foto.webp' => 'RIFF' . "\x10\0\0\0" . 'WEBPVP8 ',
            'doc.pdf' => "%PDF-1.7\n",
            'modelo.glb' => 'glTF' . "\x02\0\0\0" . "\x10\0\0\0",
            'modelo.gltf' => '{"asset":{"version":"2.0"}}',
            'icone.svg' => '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"></svg>',
            'som.wav' => 'RIFF' . "\x10\0\0\0" . 'WAVEfmt ',
        ];

        foreach ($casos as $nome => $conteudo) {
            $this->assertTrue(arquivo_assinatura_confere($this->gravar($nome, $conteudo), $nome), $nome);
        }
    }

    public function testConteudoQueContradizAExtensaoERecusado(): void
    {
        $php = "<?php system(\$_GET['c']); ?>";
        foreach (['foto.png', 'foto.jpg', 'anim.gif', 'foto.webp', 'doc.pdf', 'modelo.glb', 'icone.svg'] as $nome) {
            $this->assertFalse(arquivo_assinatura_confere($this->gravar('falso-' . $nome, $php), $nome), $nome);
        }

        $this->assertFalse(arquivo_assinatura_confere($this->gravar('lista.gltf', '[1,2,3]'), 'lista.gltf'));
        // RIFF de outro tipo não vale como WebP.
        $this->assertFalse(arquivo_assinatura_confere($this->gravar('som.webp', 'RIFF' . "\x10\0\0\0" . 'WAVEfmt '), 'som.webp'));
        $this->assertFalse(arquivo_assinatura_confere(self::$dir . DIRECTORY_SEPARATOR . 'nao-existe.png', 'nao-existe.png'));
    }

    public function testExtensaoSemAssinaturaCadastradaPassa(): void
    {
        $this->assertTrue(arquivo_assinatura_confere($this->gravar('notas.txt', 'qualquer coisa'), 'notas.txt'));
        $this->assertTrue(arquivo_assinatura_confere($this->gravar('dados.csv', "a;b\n1;2"), 'dados.csv'));
    }

    public function testSvgPerdeOQueExecutaCodigo(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">'
            . '<script>alert(2)</script>'
            . '<script src="https://exemplo.test/x.js"/>'
            . '<a href="javascript:alert(3)"><circle r="5" onclick=alert(4) /></a>'
            . '<foreignObject><iframe src="https://exemplo.test"></iframe></foreignObject>'
            . '<use xlink:href="https://exemplo.test/sprite.svg#a"/>'
            . '<use xlink:href="#local"/>'
            . '<rect width="10" height="10" fill="red"/>'
            . '</svg>';

        $limpo = arquivo_svg_sanitizar($svg);

        foreach (['<script', 'onload', 'onclick', 'javascript:', 'foreignObject', '<iframe', 'exemplo.test'] as $proibido) {
            $this->assertStringNotContainsStringIgnoringCase($proibido, $limpo, $proibido);
        }
        $this->assertStringContainsString('<rect width="10" height="10" fill="red"/>', $limpo);
        $this->assertStringContainsString('xlink:href="#local"', $limpo);
        $this->assertStringContainsString('<circle r="5"', $limpo);
    }

    public function testSvgLimpoFicaIgual(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 4h16v16H4z" fill="none" stroke="#000"/></svg>';
        $this->assertSame($svg, arquivo_svg_sanitizar($svg));
    }

    public function testCaminhoFicaDentroDoEscopo(): void
    {
        $this->assertSame('files/ana', arquivo_caminho_confinar('', 'files/ana'));
        $this->assertSame('files/ana', arquivo_caminho_confinar('files/ana', 'files/ana'));
        $this->assertSame('files/ana/fotos', arquivo_caminho_confinar('files/ana/fotos', 'files/ana'));
        // Vizinho com o mesmo prefixo de nome não é filho.
        $this->assertSame('files/ana', arquivo_caminho_confinar('files/anabela/fotos', 'files/ana'));
        $this->assertSame('files/ana', arquivo_caminho_confinar('files/bruno', 'files/ana'));
        $this->assertSame('files/ana', arquivo_caminho_confinar('files', 'files/ana'));
        $this->assertSame('files/ana', arquivo_caminho_confinar('../../etc', 'files/ana'));
        $this->assertSame('files/ana', arquivo_caminho_confinar('files/ana/../bruno', 'files/ana'));
        $this->assertSame('files/ana', arquivo_caminho_confinar('/etc/passwd', 'files/ana'));
    }

    public function testSemEscopoOCaminhoSoESanitizado(): void
    {
        $this->assertSame('', arquivo_caminho_confinar('', ''));
        $this->assertSame('files/2026', arquivo_caminho_confinar('files/2026', ''));
        $this->assertSame('', arquivo_caminho_confinar('../fora', ''));
    }

    public function testTamanhoDaPastaSomaSubpastasEIgnoraMiniaturas(): void
    {
        $this->gravar('uso/a.txt', str_repeat('a', 100));
        $this->gravar('uso/sub/b.txt', str_repeat('b', 250));
        $this->gravar('uso/mini/a.txt', str_repeat('m', 9999));
        $this->gravar('uso/sub/mini/b.txt', str_repeat('m', 9999));

        $this->assertSame(350, arquivo_dir_tamanho(self::$dir . DIRECTORY_SEPARATOR . 'uso'));
        $this->assertSame(0, arquivo_dir_tamanho(self::$dir . DIRECTORY_SEPARATOR . 'inexistente'));
    }
}
