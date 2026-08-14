<?php

declare(strict_types=1);

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
            self::assertArrayNotHasKey('tailwind_sources', $toolbar);
            self::assertSame('layout-iframe-tailwindcss', $toolbar['layout'] ?? null);

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
        }

        $php = (string) file_get_contents($moduleDirectory . DIRECTORY_SEPARATOR . 'dashboard.php');
        self::assertStringNotContainsString('<div id="c2f-toolbar-menu"', $php);
        self::assertStringNotContainsString("'Modules'", $php);
        self::assertStringNotContainsString("'Módulos'", $php);
    }
}
