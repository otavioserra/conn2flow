<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ASSET_VERSIONS_NO_AUTORUN')) define('ASSET_VERSIONS_NO_AUTORUN', true);
require_once dirname(__DIR__, 3)
    . DIRECTORY_SEPARATOR . 'gestor'
    . DIRECTORY_SEPARATOR . 'controladores'
    . DIRECTORY_SEPARATOR . 'agents'
    . DIRECTORY_SEPARATOR . 'arquitetura'
    . DIRECTORY_SEPARATOR . 'atualizacao-versoes-assets.php';

final class AssetVersionsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-assets-' . bin2hex(random_bytes(6));
        mkdir($this->root . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'demo', 0777, true);
        mkdir($this->root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'interface', 0777, true);
        mkdir($this->root . DIRECTORY_SEPARATOR . 'contents' . DIRECTORY_SEPARATOR . 'project', 0777, true);
        file_put_contents($this->root . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'demo.json', "{\n  \"id\": \"demo\",\n  \"versao\": \"1.2.3\"\n}\n");
        file_put_contents($this->root . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'demo.js', 'console.log(1);');
        file_put_contents($this->root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'interface' . DIRECTORY_SEPARATOR . 'interface.js', 'window.demo = 1;');
        file_put_contents($this->root . DIRECTORY_SEPARATOR . 'contents' . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'app.js', 'window.project = 1;');
    }

    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) rmdir($item->getPathname());
            else unlink($item->getPathname());
        }
        if (is_dir($this->root)) rmdir($this->root);
    }

    public function testTokenEhDeterministicoEPreservaVersaoSemantica(): void
    {
        $first = asset_versions_update($this->root, false, true);
        $modulePath = $this->root . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'demo.json';
        $module = json_decode((string)file_get_contents($modulePath), true);

        self::assertSame('1.2.3', $module['versao']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $module['asset_version']);
        self::assertSame(1, $first['modules_changed']);

        $second = asset_versions_update($this->root, false, true);
        self::assertSame(0, $second['modules_changed']);
        self::assertFalse($second['system_changed']);
        self::assertFalse($second['project_changed']);
    }

    public function testAlterarAssetMudaSomenteTokenDoProprietario(): void
    {
        asset_versions_update($this->root, false, true);
        $modulePath = $this->root . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'demo.json';
        $before = json_decode((string)file_get_contents($modulePath), true);
        $systemBefore = json_decode((string)file_get_contents($this->root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'asset-versions.json'), true);

        file_put_contents($this->root . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'demo.js', 'console.log(2);');
        asset_versions_update($this->root, false, true);
        $after = json_decode((string)file_get_contents($modulePath), true);
        $systemAfter = json_decode((string)file_get_contents($this->root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'asset-versions.json'), true);

        self::assertNotSame($before['asset_version'], $after['asset_version']);
        self::assertSame($systemBefore['system'], $systemAfter['system']);
    }

    public function testCheckFalhaQuandoTokenEstaDesatualizado(): void
    {
        asset_versions_update($this->root, false, true);
        file_put_contents($this->root . DIRECTORY_SEPARATOR . 'contents' . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . 'app.js', 'window.project = 2;');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('desatualizada');
        asset_versions_update($this->root, true, true);
    }

    public function testResourcesNaoParticipamDoTokenDoModulo(): void
    {
        asset_versions_update($this->root, false, true);
        $modulePath = $this->root . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'demo.json';
        $before = json_decode((string)file_get_contents($modulePath), true)['asset_version'];
        $resourceDirectory = dirname($modulePath) . DIRECTORY_SEPARATOR . 'resources';
        mkdir($resourceDirectory);
        file_put_contents($resourceDirectory . DIRECTORY_SEPARATOR . 'generated.css', '.generated{}');

        asset_versions_update($this->root, false, true);
        $after = json_decode((string)file_get_contents($modulePath), true)['asset_version'];
        self::assertSame($before, $after);
    }

    public function testJsonInvalidoBloqueiaAntesDeAlterarOutroModulo(): void
    {
        $demoPath = $this->root . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'demo.json';
        $before = (string)file_get_contents($demoPath);
        $invalidDirectory = $this->root . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'z-invalid';
        mkdir($invalidDirectory);
        file_put_contents($invalidDirectory . DIRECTORY_SEPARATOR . 'z-invalid.json', '{invalido');
        file_put_contents($invalidDirectory . DIRECTORY_SEPARATOR . 'z-invalid.js', 'alert(1);');

        try {
            asset_versions_update($this->root, false, true);
            self::fail('Era esperado erro para JSON invalido.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('JSON de modulo invalido', $error->getMessage());
            self::assertSame($before, (string)file_get_contents($demoPath));
        }
    }
}
