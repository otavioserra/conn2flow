<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

if (!defined('SDD_NO_AUTORUN')) define('SDD_NO_AUTORUN', true);
if (!function_exists('tailwind_recursos_command_base')) {
    require_once dirname(__DIR__, 3)
        . DIRECTORY_SEPARATOR . 'gestor'
        . DIRECTORY_SEPARATOR . 'controladores'
        . DIRECTORY_SEPARATOR . 'agents'
        . DIRECTORY_SEPARATOR . 'arquitetura'
        . DIRECTORY_SEPARATOR . 'tailwind-recursos.php';
}

final class TailwindRecursosTest extends TestCase
{
    private array $temporaryFiles = [];
    private array $temporaryDirectories = [];

    public function testProjectGlobalDependencyFallsBackToCoreWithoutCopyingResources(): void
    {
        $oldRoot = $GLOBALS['GESTOR_DIR'] ?? null;
        $oldSystem = $GLOBALS['SYSTEM_PATH'] ?? null;
        try {
            $GLOBALS['GESTOR_DIR'] = sys_get_temp_dir() . '/c2f-no-global-resources';
            $GLOBALS['SYSTEM_PATH'] = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR;
            $dependency = ['type' => 'components', 'id' => 'interface-alerta-modal-tailwind', 'scope' => 'global', 'language' => 'pt-br'];
            $path = tailwind_recursos_dependency_path($dependency);
            self::assertFileExists($path);
            self::assertTrue(tailwind_recursos_path_dentro($path, $GLOBALS['SYSTEM_PATH'] . 'gestor'));
            $dependency['scope'] = 'module';
            $dependency['module'] = 'private-module';
            self::assertFileDoesNotExist(tailwind_recursos_dependency_path($dependency));
            $dependency['id'] = '../interface-alerta-modal-tailwind';
            self::assertNull(tailwind_recursos_dependency_path($dependency));
        } finally {
            $GLOBALS['GESTOR_DIR'] = $oldRoot;
            $GLOBALS['SYSTEM_PATH'] = $oldSystem;
        }
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->temporaryFiles) as $file) {
            if (is_file($file)) unlink($file);
        }
        foreach (array_reverse($this->temporaryDirectories) as $directory) {
            if (is_dir($directory)) rmdir($directory);
        }
    }

    public function testRemoveEntradaSaidaEMinificacaoDoComandoLegado(): void
    {
        $tokens = tailwind_recursos_parse_command(
            'npx @tailwindcss/cli -i "contents/tailwindcss/input.css" -o output.css --minify'
        );

        self::assertSame(['npx', '@tailwindcss/cli'], tailwind_recursos_command_base($tokens));
    }

    public function testSubstituiNpxPeloExecutavelTailwindLocal(): void
    {
        $tokens = ['npx', '@tailwindcss/cli', '--cwd', '/projeto'];

        self::assertSame(
            ['C:/repo/node_modules/.bin/tailwindcss.cmd', '--cwd', '/projeto'],
            tailwind_recursos_normalizar_runner($tokens, ['C:/repo/node_modules/.bin/tailwindcss.cmd'])
        );
    }

    public function testMantemComandoCustomizadoQueNaoUsaRunnerConhecido(): void
    {
        $tokens = ['docker', 'run', 'tailwind-builder'];

        self::assertSame(
            $tokens,
            tailwind_recursos_normalizar_runner($tokens, ['C:/repo/node_modules/.bin/tailwindcss.cmd'])
        );
    }

    #[DataProvider('saidasDeVersaoDoTailwind')]
    public function testExtraiVersaoDoTailwindDeSaidasComOuSemAnsi(string $output): void
    {
        self::assertSame('4.3.3', tailwind_recursos_cli_version_from_output($output));
    }

    public static function saidasDeVersaoDoTailwind(): array
    {
        return [
            'linux sem cor' => ["tailwindcss v4.3.3\n"],
            'windows com azul e reset' => ["\x1b[34mtailwindcss\x1b[39m \x1b[34mv4.3.3\x1b[39m\r\n"],
            'somente a versao colorida' => ["tailwindcss \x1b[1;34mv4.3.3\x1b[0m\n"],
        ];
    }

    public function testPreservaExecutavelTailwindAbsolutoConfigurado(): void
    {
        $tokens = ['D:/ferramentas/tailwindcss.exe', '--cwd', '/projeto'];

        self::assertSame(
            $tokens,
            tailwind_recursos_normalizar_runner($tokens, ['C:/repo/node_modules/.bin/tailwindcss.cmd'])
        );
    }

    public function testCalculaCaminhoRelativoEntreInputTemporarioEContrato(): void
    {
        self::assertSame('../../assets/input.css', tailwind_recursos_relativo('/gestor/.tailwind-build/inputs', '/gestor/assets/input.css'));
    }

    public function testContratoDoBrowserMantemTemaERemoveDiretivasDeBuild(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-tailwind-' . bin2hex(random_bytes(6));
        mkdir($directory, 0777, true);
        $this->temporaryDirectories[] = $directory;
        $input = $directory . DIRECTORY_SEPARATOR . 'input.css';
        $contract = $directory . DIRECTORY_SEPARATOR . 'browser-contract.css';
        file_put_contents($input, "@import \"tailwindcss\" source(none);\n@source \"./page.html\";\n@theme { --color-brand: #123456; }\n");
        $this->temporaryFiles[] = $input;
        $this->temporaryFiles[] = $contract;

        $result = tailwind_recursos_browser_contract($input);

        self::assertSame($contract, $result['path']);
        self::assertStringContainsString('--color-brand: #123456', $result['content']);
        self::assertStringNotContainsString('@import', $result['content']);
        self::assertStringNotContainsString('@source', $result['content']);
    }

    public function testContratoDoBrowserRecusaPluginQueNaoPodeRodarNoCdn(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-tailwind-' . bin2hex(random_bytes(6));
        mkdir($directory, 0777, true);
        $this->temporaryDirectories[] = $directory;
        $input = $directory . DIRECTORY_SEPARATOR . 'input.css';
        file_put_contents($input, "@plugin \"@tailwindcss/forms\";\n");
        $this->temporaryFiles[] = $input;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Diretiva @plugin');
        tailwind_recursos_browser_contract($input);
    }

    public function testRelataFontesDinamicasAdicionaisSemDuplicarRecursos(): void
    {
        self::assertSame(
            ['resources_with_sources' => 2, 'additional_sources' => 3],
            tailwind_recursos_estatisticas_fontes([
                ['sources' => ['/gestor/a.js', '/gestor/b.php']],
                ['sources' => []],
                ['sources' => ['/gestor/c.js']],
            ])
        );
    }

    public function testBundleCanonicoImportaTemaEBaseDoInputCentral(): void
    {
        $resource = [
            'layout' => false,
            'bundle' => true,
            'html' => '/gestor/page.html',
            'sources' => ['/gestor/layout.html'],
            'safelist' => [],
        ];

        $input = tailwind_recursos_input_temporario($resource, '/gestor/input.css', '/gestor/.tailwind-build');

        self::assertStringContainsString('@import "../input.css";', $input);
        self::assertStringNotContainsString('@reference', $input);
        self::assertStringNotContainsString('tailwindcss/utilities.css', $input);
        self::assertStringContainsString('@source "../page.html";', $input);
        self::assertStringContainsString('@source "../layout.html";', $input);
    }

    public function testRecusaFonteDinamicaSemJustificativaAuditavel(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tailwind_sources_reason');

        tailwind_recursos_sources(
            ['id' => 'recurso-dinamico', 'tailwind_sources' => ['./runtime.js']],
            sys_get_temp_dir()
        );
    }

    public function testSidecarVazioNaoEhConsideradoValido(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-tailwind-' . bin2hex(random_bytes(6));
        mkdir($directory, 0777, true);
        $this->temporaryDirectories[] = $directory;
        $empty = $directory . DIRECTORY_SEPARATOR . 'empty.css';
        $valid = $directory . DIRECTORY_SEPARATOR . 'valid.css';
        file_put_contents($empty, " \n");
        file_put_contents($valid, '.flex{display:flex}');
        $this->temporaryFiles[] = $empty;
        $this->temporaryFiles[] = $valid;

        self::assertFalse(tailwind_recursos_output_valido($empty));
        self::assertTrue(tailwind_recursos_output_valido($valid));
        self::assertFalse(tailwind_recursos_output_valido($directory . DIRECTORY_SEPARATOR . 'missing.css'));
    }

    public function testFiltroPorResourceSelecionaTodosOsIdiomasDoId(): void
    {
        $resources = [
            ['key' => 'pages|home|pt-br', 'id' => 'home', 'language' => 'pt-br'],
            ['key' => 'pages|home|en', 'id' => 'home', 'language' => 'en'],
            ['key' => 'components|footer|pt-br', 'id' => 'footer', 'language' => 'pt-br'],
        ];

        self::assertSame(
            ['pages|home|pt-br', 'pages|home|en'],
            array_column(tailwind_recursos_filtrar_resource($resources, 'home'), 'key')
        );
    }

    public function testFiltroPorResourceRejeitaIdInexistente(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('recurso não encontrado');

        tailwind_recursos_filtrar_resource([], 'ausente');
    }

    public function testFiltroPorResourceRejeitaIdAmbiguoEntreTipos(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('--resource é ambíguo');

        tailwind_recursos_filtrar_resource([
            ['id' => 'home', 'module' => '', 'type' => 'pages'],
            ['id' => 'home', 'module' => '', 'type' => 'components'],
        ], 'home');
    }

    // Único caso que precisa do compilador de recursos. Ele declara funções de mesmo nome que o
    // sincronizador de banco, então é carregado em processo próprio e não no da suíte.
    #[PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testCacheTailwindEvitaBuildNoHitEPreservaManifestEmBuildParcial(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . '/controladores/agents/arquitetura/atualizacao-dados-recursos.php';

        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-tailwind-cache-' . bin2hex(random_bytes(6));
        $gestor = $root . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR;
        $resourcesDirectory = $gestor . 'resources' . DIRECTORY_SEPARATOR;
        $logPath = $root . DIRECTORY_SEPARATOR . 'tailwind.log';
        $fakeCommandPath = $root . DIRECTORY_SEPARATOR . 'fake-tailwind.php';
        $directories = [
            $resourcesDirectory . 'en' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'home',
            $resourcesDirectory . 'en' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'footer',
            $resourcesDirectory . 'pt-br' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'home',
            $gestor . 'assets' . DIRECTORY_SEPARATOR . 'tailwindcss',
            $gestor . '.tailwind-build' . DIRECTORY_SEPARATOR . 'inputs',
        ];
        foreach ($directories as $directory) {
            if (!is_dir($directory)) mkdir($directory, 0777, true);
        }

        $map = [
            'languages' => [
                'en' => ['data' => ['components' => 'components.json']],
                'pt-br' => ['data' => ['components' => 'components.json']],
            ],
        ];
        file_put_contents($resourcesDirectory . 'en' . DIRECTORY_SEPARATOR . 'components.json', json_encode([
            ['id' => 'home', 'framework_css' => 'tailwindcss'],
            ['id' => 'footer', 'framework_css' => 'tailwindcss'],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($resourcesDirectory . 'pt-br' . DIRECTORY_SEPARATOR . 'components.json', json_encode([
            ['id' => 'home', 'framework_css' => 'tailwindcss'],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($gestor . 'assets' . DIRECTORY_SEPARATOR . 'tailwindcss' . DIRECTORY_SEPARATOR . 'system-input.css', "@import \"tailwindcss\";\n");
        $htmlPaths = [
            $resourcesDirectory . 'en' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'home' . DIRECTORY_SEPARATOR . 'home.html',
            $resourcesDirectory . 'en' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'footer' . DIRECTORY_SEPARATOR . 'footer.html',
            $resourcesDirectory . 'pt-br' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'home' . DIRECTORY_SEPARATOR . 'home.html',
        ];
        foreach ($htmlPaths as $htmlPath) file_put_contents($htmlPath, '<div class="flex"></div>');
        file_put_contents($fakeCommandPath, <<<'PHP'
<?php
$logPath = $argv[1];
$arguments = array_slice($argv, 2);
if (in_array('--help', $arguments, true)) {
    file_put_contents($logPath, "help\n", FILE_APPEND);
    echo "tailwindcss v4.3.3\n";
    exit(0);
}
file_put_contents($logPath, "build\n", FILE_APPEND);
$inputIndex = array_search('-i', $arguments, true);
$outputIndex = array_search('-o', $arguments, true);
if ($inputIndex === false || $outputIndex === false) exit(2);
$inputPath = $arguments[$inputIndex + 1];
$input = (string)file_get_contents($inputPath);
preg_match_all('/@source\s+"([^"]+)"/', $input, $matches);
$sourceContent = $input;
foreach ($matches[1] as $source) {
    $path = realpath(dirname($inputPath) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $source));
    if ($path !== false && is_file($path)) $sourceContent .= file_get_contents($path);
}
file_put_contents($arguments[$outputIndex + 1], '.built{--source-hash:' . hash('sha256', $sourceContent) . '}');
PHP);

        $globals = ['GESTOR_DIR', 'RESOURCES_DIR', 'MODULES_DIR', 'SYSTEM_PATH', 'LOG_FILE', 'CLI_ARGS', 'isProjectMode'];
        $previous = [];
        foreach ($globals as $name) $previous[$name] = $GLOBALS[$name] ?? null;
        $GLOBALS['GESTOR_DIR'] = $gestor;
        $GLOBALS['RESOURCES_DIR'] = $resourcesDirectory;
        $GLOBALS['MODULES_DIR'] = $gestor . 'modulos' . DIRECTORY_SEPARATOR;
        $GLOBALS['SYSTEM_PATH'] = $root . DIRECTORY_SEPARATOR;
        $GLOBALS['LOG_FILE'] = 'req202-tailwind-cache-test';
        $GLOBALS['isProjectMode'] = false;
        $GLOBALS['CLI_ARGS'] = [
            'tailwind-command-json' => json_encode([PHP_BINARY, $fakeCommandPath, $logPath], JSON_THROW_ON_ERROR),
        ];

        try {
            $initial = tailwind_recursos_compilar($map);
            self::assertSame(3, $initial['compiled']);
            self::assertSame(['help', 'build', 'build', 'build'], file($logPath, FILE_IGNORE_NEW_LINES));

            $GLOBALS['CLI_ARGS']['resource'] = 'home';
            unlink($logPath);
            $cached = tailwind_recursos_compilar($map);
            self::assertSame(2, $cached['cached']);
            self::assertSame(0, $cached['compiled']);
            self::assertSame(['help'], file($logPath, FILE_IGNORE_NEW_LINES));

            $homeEnOutput = $resourcesDirectory . 'en' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'home' . DIRECTORY_SEPARATOR . 'home.precompiled.css';
            $oldOutputHash = hash_file('sha256', $homeEnOutput);
            file_put_contents($htmlPaths[0], '<div class="flex grid"></div>');
            unlink($logPath);
            $GLOBALS['CLI_ARGS'] = [
                'tailwind-command-json' => json_encode([PHP_BINARY, $fakeCommandPath, $logPath], JSON_THROW_ON_ERROR),
                'resource' => 'home',
            ];
            $miss = tailwind_recursos_compilar($map);
            self::assertSame(1, $miss['cached']);
            self::assertSame(1, $miss['compiled']);
            self::assertNotSame($oldOutputHash, hash_file('sha256', $homeEnOutput));
            self::assertSame(['help', 'build'], file($logPath, FILE_IGNORE_NEW_LINES));

            $manifest = json_decode((string)file_get_contents($resourcesDirectory . '.tailwind-build-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
            self::assertCount(3, $manifest['resources']);
            self::assertFileExists($resourcesDirectory . 'en' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'footer' . DIRECTORY_SEPARATOR . 'footer.precompiled.css');
        } finally {
            foreach ($globals as $name) {
                if ($previous[$name] === null) unset($GLOBALS[$name]);
                else $GLOBALS[$name] = $previous[$name];
            }
            $this->removeTemporaryDirectory($root);
        }
    }

    public function testResolveDependenciasSemanticasDaToolbarSomenteNoBuild(): void
    {
        global $GESTOR_DIR;
        $previous = $GESTOR_DIR ?? null;
        $GESTOR_DIR = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'gestor';

        try {
            $resolved = tailwind_recursos_dependencies([
                'id' => 'dashboard-site-toolbar',
                'layout' => 'layout-iframe-tailwindcss',
                'tailwind_bundle' => true,
                'tailwind_dependencies_reason' => 'teste',
                'tailwind_dependencies' => [
                    ['type' => 'components', 'id' => 'dashboard-site-toolbar-menu'],
                    ['type' => 'components', 'id' => 'dashboard-site-toolbar-menu-item'],
                    ['type' => 'components', 'id' => 'dashboard-site-toolbar-menu-group'],
                    ['type' => 'components', 'id' => 'dashboard-site-toolbar-menu-empty'],
                ],
            ], 'module', 'dashboard', 'pt-br', 'pages');
        } finally {
            if ($previous === null) unset($GESTOR_DIR);
            else $GESTOR_DIR = $previous;
        }

        // 4 declaradas + o layout do bundle + os 3 modais de sistema (req-148), que toda página
        // passou a receber automaticamente porque `interface_alerta()` pode injectá-los em qualquer
        // tela. Antes eram 5; a diferença é exatamente a cobertura nova.
        self::assertCount(8, $resolved);

        $nomes = array_map(fn($p) => basename($p, '.html'), $resolved);
        foreach (['interface-alerta-modal-tailwind', 'interface-carregando-modal-tailwind',
                  'interface-delecao-modal-tailwind'] as $modal) {
            self::assertContains($modal, $nomes, 'modal de sistema ausente: ' . $modal);
        }

        self::assertTrue((bool)array_filter($resolved, fn($path) => str_ends_with(
            str_replace('\\', '/', $path),
            '/resources/pt-br/layouts/layout-iframe-tailwindcss/layout-iframe-tailwindcss.html'
        )));
        self::assertTrue((bool)array_filter($resolved, fn($path) => str_ends_with(
            str_replace('\\', '/', $path),
            '/modulos/dashboard/resources/pt-br/components/dashboard-site-toolbar-menu/dashboard-site-toolbar-menu.html'
        )));
    }

    public function testRecusaPathTraversalEmDependenciaSemantica(): void
    {
        global $GESTOR_DIR;
        $previous = $GESTOR_DIR ?? null;
        $GESTOR_DIR = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'gestor';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não encontrada');
        try {
            tailwind_recursos_dependencies([
                'id' => 'invalido',
                'tailwind_dependencies_reason' => 'teste',
                'tailwind_dependencies' => [
                    ['type' => 'components', 'id' => '../fora'],
                ],
            ], 'module', 'dashboard', 'pt-br', 'components');
        } finally {
            if ($previous === null) unset($GESTOR_DIR);
            else $GESTOR_DIR = $previous;
        }
    }

    public function testToolbarExtraiMenuPhpParaComponentesTailwind(): void
    {
        $root = dirname(__DIR__, 3);
        $moduleDirectory = $root . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . 'dashboard';
        $metadata = json_decode(
            (string) file_get_contents($moduleDirectory . DIRECTORY_SEPARATOR . 'dashboard.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach (['pt-br', 'en'] as $language) {
            $pages = $metadata['resources'][$language]['pages'] ?? [];
            $toolbar = null;
            foreach ($pages as $page) {
                if (($page['id'] ?? null) === 'dashboard-site-toolbar') {
                    $toolbar = $page;
                    break;
                }
            }

            self::assertIsArray($toolbar, "Toolbar ausente em {$language}");
            self::assertTrue($toolbar['tailwind_bundle'] ?? false);
            self::assertArrayNotHasKey('tailwind_sources', $toolbar);
            self::assertCount(4, $toolbar['tailwind_dependencies'] ?? []);
            self::assertSame('layout-iframe-tailwindcss', $toolbar['layout'] ?? null);

            foreach ($toolbar['tailwind_dependencies'] as $dependency) {
                self::assertSame('components', $dependency['type'] ?? null);
                self::assertStringNotContainsString('/', $dependency['id'] ?? '');
            }

            $components = [];
            foreach ($metadata['resources'][$language]['components'] ?? [] as $component) {
                $components[$component['id']] = $component;
            }

            $compiledCss = '';
            foreach ([
                'dashboard-site-toolbar-menu',
                'dashboard-site-toolbar-menu-item',
                'dashboard-site-toolbar-menu-group',
                'dashboard-site-toolbar-menu-empty',
            ] as $componentId) {
                self::assertSame('tailwindcss', $components[$componentId]['framework_css'] ?? null);
                $resourceDirectory = $moduleDirectory . DIRECTORY_SEPARATOR . 'resources'
                    . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'components'
                    . DIRECTORY_SEPARATOR . $componentId;
                self::assertFileExists($resourceDirectory . DIRECTORY_SEPARATOR . $componentId . '.html');
                $cssFile = $resourceDirectory . DIRECTORY_SEPARATOR . $componentId . '.precompiled.css';
                self::assertFileExists($cssFile);
                $compiledCss .= (string) file_get_contents($cssFile);
            }

            self::assertStringContainsString('.max-h-96', $compiledCss);
            self::assertStringContainsString('.overflow-auto', $compiledCss);
            self::assertStringContainsString('.text-\\[10px\\]', $compiledCss);
            self::assertStringContainsString('.focus\\:outline-none', $compiledCss);

            $layoutsMetadata = json_decode(
                (string) file_get_contents(
                    $root . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR . 'resources'
                    . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'layouts.json'
                ),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            $tailwindLayout = null;
            foreach ($layoutsMetadata as $layout) {
                if (($layout['id'] ?? null) === 'layout-iframe-tailwindcss') {
                    $tailwindLayout = $layout;
                    break;
                }
            }
            self::assertSame('tailwindcss', $tailwindLayout['framework_css'] ?? null);
            $layoutCss = $root . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR . 'resources'
                . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'layouts'
                . DIRECTORY_SEPARATOR . 'layout-iframe-tailwindcss'
                . DIRECTORY_SEPARATOR . 'layout-iframe-tailwindcss.precompiled.css';
            self::assertFileExists($layoutCss);
            $layoutPreflight = (string) file_get_contents($layoutCss);
            self::assertStringContainsString('@layer base', $layoutPreflight);
            self::assertStringContainsString('box-sizing:border-box', $layoutPreflight);
            self::assertStringContainsString('appearance:button', $layoutPreflight);

            $pageCss = $moduleDirectory . DIRECTORY_SEPARATOR . 'resources'
                . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'pages'
                . DIRECTORY_SEPARATOR . 'dashboard-site-toolbar'
                . DIRECTORY_SEPARATOR . 'dashboard-site-toolbar.precompiled.css';
            self::assertFileExists($pageCss);
        }

        $php = (string) file_get_contents($moduleDirectory . DIRECTORY_SEPARATOR . 'dashboard.php');
        self::assertStringNotContainsString('<div id="c2f-toolbar-menu"', $php);
        self::assertStringNotContainsString("'Modules'", $php);
        self::assertStringNotContainsString("'Módulos'", $php);
    }

    private function removeTemporaryDirectory(string $directory): void
    {
        if (!is_dir($directory)) return;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
