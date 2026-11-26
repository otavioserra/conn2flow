<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/dist-publicacao.php';

final class DistPublicacaoTest extends TestCase
{
    private string $raiz;
    private string $gestor;
    private string $dist;

    protected function setUp(): void
    {
        $this->raiz = sys_get_temp_dir() . '/c2f-dist-' . bin2hex(random_bytes(8));
        $this->gestor = $this->raiz . '/gestor';
        $this->dist = $this->raiz . '/public_html/dist';
        mkdir($this->gestor, 0755, true);
    }

    protected function tearDown(): void
    {
        $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->raiz, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($arquivos as $arquivo) {
            $arquivo->isDir() && !$arquivo->isLink() ? rmdir($arquivo->getPathname()) : unlink($arquivo->getPathname());
        }
        rmdir($this->raiz);
    }

    public function testCriaPublicacaoEDetectaNovosAssetsSemRegravarOsIguais(): void
    {
        $this->escrever($this->gestor . '/assets/images/logo.svg', 'logo');
        $this->escrever($this->gestor . '/assets/interface/app.js', 'original');
        $r = dist_publicar_completo($this->gestor, $this->dist);
        self::assertTrue($r['criado']);
        self::assertSame(2, $r['publicados']);
        self::assertSame(0, $r['falhas']);
        self::assertFileExists($this->dist . '/.htaccess');
        $logo = $this->dist . '/images/logo.svg';
        touch($logo, 1234567890);

        $this->escrever($this->gestor . '/assets/interface/app.js', 'versao nova');
        $this->escrever($this->gestor . '/modulos/ficha/ficha.css', 'css novo');
        $r = dist_publicar_completo($this->gestor, $this->dist);
        self::assertFalse($r['criado']);
        self::assertSame([3, 1, 1, 1, 0], array_values(array_intersect_key($r, array_flip(['verificados', 'publicados', 'atualizados', 'inalterados', 'falhas']))));
        self::assertSame('css novo', file_get_contents($this->dist . '/ficha/css.css'));
        self::assertSame(1234567890, filemtime($logo));
        $manifesto = $this->dist . '/.manifest.json';
        self::assertSame(sha1('versao nova'), $this->manifesto()['arquivos']['interface/app.js']['sha1']);
        touch($manifesto, 1234567890);
        $r = dist_publicar_completo($this->gestor, $this->dist);
        self::assertSame(3, $r['inalterados']);
        self::assertSame(0, $r['publicados'] + $r['atualizados']);
        self::assertSame(1234567890, filemtime($manifesto));

        unlink($this->dist . '/interface/app.js');
        self::assertSame(1, dist_publicar_completo($this->gestor, $this->dist)['publicados']);
        self::assertSame('versao nova', file_get_contents($this->dist . '/interface/app.js'));
    }

    public function testEscolheMinificadoDaArvoreMescladaEMantemModoDevESimulacao(): void
    {
        $projeto = $this->raiz . '/projeto';
        $this->escrever($this->gestor . '/assets/app.js', 'core');
        $this->escrever($projeto . '/assets/app.min.js', 'min projeto');
        $this->escrever($projeto . '/assets/vendor.min.js', 'vendor');
        $this->escrever($projeto . '/assets/empty.js', 'autoria');
        $this->escrever($projeto . '/assets/empty.min.js', '');

        self::assertSame(3, dist_publicar_completo($this->gestor, $this->dist, ['projeto' => $projeto, 'simular' => true])['publicados']);
        self::assertDirectoryDoesNotExist($this->dist);
        dist_publicar_completo($this->gestor, $this->dist, ['projeto' => $projeto]);
        self::assertSame('min projeto', file_get_contents($this->dist . '/app.js'));
        self::assertSame('autoria', file_get_contents($this->dist . '/empty.js'));
        self::assertSame('vendor', file_get_contents($this->dist . '/vendor.min.js'));
        self::assertFileDoesNotExist($this->dist . '/app.min.js');
        self::assertSame('assets/app.min.js', $this->manifesto()['arquivos']['app.js']['fonte']);
        dist_publicar_completo($this->gestor, $this->dist, ['projeto' => $projeto, 'preferir_minificado' => false]);
        self::assertSame('core', file_get_contents($this->dist . '/app.js'));
    }

    public function testExcluiInsumosERecusaTraversalMesmoComLimpezaExplicita(): void
    {
        foreach (['asset-versions.json', 'minify-manifest.json', 'script.php', 'script.php.js', '%2e%2e.js', 'invalido..js', '.privado/config.js', 'app.js', 'orphan.css'] as $arquivo) {
            $this->escrever($this->gestor . '/assets/' . $arquivo, 'conteudo');
        }
        dist_publicar_completo($this->gestor, $this->dist);
        $m = $this->manifesto();
        self::assertSame(['app.js', 'orphan.css'], array_keys($m['arquivos']));
        unlink($this->gestor . '/assets/orphan.css');

        $fora = dirname($this->dist) . '/fora.js';
        $this->escrever($fora, 'fora');
        $this->escrever($this->dist . '/manual.css', 'manual');
        $m['arquivos']['../fora.js'] = ['fonte' => '../fora.js', 'sha1' => sha1('fora')];
        $this->escrever($this->dist . '/.manifest.json', json_encode($m, JSON_THROW_ON_ERROR));
        dist_publicar_completo($this->gestor, $this->dist);
        self::assertFileExists($this->dist . '/orphan.css');
        self::assertArrayNotHasKey('../fora.js', $this->manifesto()['arquivos']);

        $r = dist_publicar_completo($this->gestor, $this->dist, ['limpar' => true]);
        self::assertSame(1, $r['removidos']);
        self::assertSame('fora', file_get_contents($fora));
        self::assertFileExists($this->dist . '/manual.css');
        self::assertFileDoesNotExist($this->dist . '/orphan.css');
    }

    public function testFalhaDeUmDestinoPreservaPublicacaoAnteriorEPermiteRetry(): void
    {
        $this->escrever($this->gestor . '/assets/a.js', 'a');
        $this->escrever($this->gestor . '/assets/b.js', 'b');
        mkdir($this->dist . '/b.js', 0755, true);
        $r = dist_publicar_completo($this->gestor, $this->dist);
        self::assertSame(1, $r['falhas']);
        self::assertSame(['a.js'], array_keys($this->manifesto()['arquivos']));
        rmdir($this->dist . '/b.js');
        $r = dist_publicar_completo($this->gestor, $this->dist);
        self::assertSame(0, $r['falhas']);
        self::assertSame(1, $r['publicados']);
        self::assertSame(1, $r['inalterados']);
    }

    private function escrever(string $caminho, string $conteudo): void
    {
        if (!is_dir(dirname($caminho))) mkdir(dirname($caminho), 0755, true);
        file_put_contents($caminho, $conteudo);
    }

    private function manifesto(): array
    {
        return json_decode(file_get_contents($this->dist . '/.manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    }
}
