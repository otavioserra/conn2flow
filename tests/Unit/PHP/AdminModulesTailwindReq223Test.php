<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminModulesTailwindReq223Test extends TestCase
{
    private const PAGES = [
        'admin-arquivos' => ['admin-arquivos', 'admin-arquivos-adicionar'],
        'admin-categorias' => ['admin-categorias', 'admin-categorias-adicionar', 'admin-categorias-editar', 'admin-categorias-adicionar-filho'],
        'admin-ia' => ['admin-ia-listar', 'admin-ia-adicionar', 'admin-ia-editar'],
        'admin-modos-ia' => ['admin-modos-ia-adicionar', 'admin-modos-ia-editar'],
        'admin-prompts-ia' => ['admin-prompts-ia-adicionar', 'admin-prompts-ia-editar'],
        'admin-plugins' => ['admin-plugins', 'admin-plugins-adicionar', 'admin-plugins-editar', 'admin-plugins-executar', 'admin-plugins-teste'],
        'admin-atualizacoes' => ['admin-atualizacoes', 'admin-atualizacoes-detalhe'],
        'admin-environment' => ['admin-environment'],
        'variables' => ['variables'],
        'modulos' => ['modulos-variaveis'],
    ];

    public function testAdminArquivosUsaLayoutTailwindEBundle(): void
    {
        $this->assertModulePagesUseTailwind('admin-arquivos', self::PAGES['admin-arquivos']);
    }

    public function testAdminArquivosAtivaBundleEPreservaContratoPicker(): void
    {
        $base = CONN2FLOW_GESTOR_ROOT . '/modulos/admin-arquivos';
        $php = (string)file_get_contents($base . '/admin-arquivos.php');
        $javascript = (string)file_get_contents($base . '/admin-arquivos.js');

        self::assertSame(2, substr_count($php, "\$_GESTOR['tailwind-page-bundle'] = true;"));
        self::assertGreaterThanOrEqual(4, substr_count($javascript, 'window.parent.postMessage('));
        self::assertStringContainsString('moduloOpcao: gestor.moduloOpcao', $javascript);
        self::assertStringContainsString('data: JSON.stringify(dados)', $javascript);
        self::assertStringContainsString('tipo: item.mime ||', $javascript);
        self::assertDoesNotMatchRegularExpression(
            '/window\.(?:prompt|confirm|alert)|\.modal\(|\.popup\(|\.progress\(|\.calendar\(|\.accordion\(|\.dropdown\(|\.checkbox\(/',
            $javascript
        );
    }

    public function testPaginasDosModulosUsamLayoutTailwindEBundle(): void
    {
        foreach (self::PAGES as $modulo => $idsEsperados) {
            $this->assertModulePagesUseTailwind($modulo, $idsEsperados);
        }
    }

    public function testSelectoresNativosNaoInicializamDropdownsFomantic(): void
    {
        $variablesJs = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/variables/variables.js');
        self::assertStringContainsString("select.is('[data-c2f-select]')", $variablesJs);
        self::assertStringContainsString("select.on('change'", $variablesJs);
        self::assertStringContainsString('select.dropdown(', $variablesJs);

        $modulosJs = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/modulos/modulos.js');
        self::assertStringContainsString("$('.ui.dropdown').not('[data-c2f-select]')", $modulosJs);
        self::assertStringContainsString('if ($.fn.dropdown && dropdowns.length)', $modulosJs);
    }

    public function testAdminPluginsSelecionaVarianteTailwindEPreservaComponenteLegado(): void
    {
        $base = CONN2FLOW_GESTOR_ROOT . '/modulos/admin-plugins';
        $manifesto = json_decode((string)file_get_contents($base . '/admin-plugins.json'), true);
        self::assertIsArray($manifesto);

        $php = (string)file_get_contents($base . '/admin-plugins.php');
        self::assertStringContainsString("interface_componente_variante('plugins-exec')", $php);

        foreach (['pt-br', 'en'] as $lingua) {
            $componentes = array_column($manifesto['resources'][$lingua]['components'] ?? [], 'id');
            self::assertContains('plugins-exec', $componentes, $lingua);
            self::assertContains('plugins-exec-tailwind', $componentes, $lingua);

            $componentesPath = $base . '/resources/' . $lingua . '/components';
            $legado = (string)file_get_contents($componentesPath . '/plugins-exec/plugins-exec.html');
            $tailwindPath = $componentesPath . '/plugins-exec-tailwind/plugins-exec-tailwind.html';
            self::assertFileExists($tailwindPath);
            self::assertStringContainsString('class="ui ', $legado);
            self::assertStringNotContainsString('class="ui ', (string)file_get_contents($tailwindPath));

            $paginas = array_column($manifesto['resources'][$lingua]['pages'] ?? [], null, 'id');
            $dependencias = array_column($paginas['admin-plugins-executar']['tailwind_dependencies'] ?? [], 'id');
            self::assertContains('plugins-exec-tailwind', $dependencias, $lingua);
        }
    }

    public function testAdminAtualizacoesSelecionaVariantesTailwindSemPluginsLegados(): void
    {
        $base = CONN2FLOW_GESTOR_ROOT . '/modulos/admin-atualizacoes';
        $manifesto = json_decode((string)file_get_contents($base . '/admin-atualizacoes.json'), true);
        self::assertIsArray($manifesto);

        $php = (string)file_get_contents($base . '/admin-atualizacoes.php');
        $javascript = (string)file_get_contents($base . '/admin-atualizacoes.js');
        self::assertStringContainsString("interface_componente_variante('atualizacoes-lista')", $php);
        self::assertStringContainsString("interface_componente_variante('atualizacoes-detalhe-comp')", $php);
        self::assertStringContainsString("\$_GESTOR['tailwind-page-bundle'] = true;", $php);
        self::assertStringNotContainsString('assets_externos_incluir(\'codemirror\')', $php);
        self::assertDoesNotMatchRegularExpression('/window\.confirm|\.progress\(|CodeMirror|ui inline loader/', $javascript);
        self::assertStringContainsString('c2fControles.dialogo.confirmar', $javascript);

        foreach (['pt-br', 'en'] as $lingua) {
            $componentes = array_column($manifesto['resources'][$lingua]['components'] ?? [], 'id');
            self::assertContains('atualizacoes-lista', $componentes, $lingua);
            self::assertContains('atualizacoes-lista-tailwind', $componentes, $lingua);
            self::assertContains('atualizacoes-detalhe-comp', $componentes, $lingua);
            self::assertContains('atualizacoes-detalhe-comp-tailwind', $componentes, $lingua);
            $componentesPorId = array_column($manifesto['resources'][$lingua]['components'] ?? [], null, 'id');
            self::assertSame('tailwindcss', $componentesPorId['atualizacoes-lista-tailwind']['framework_css'] ?? null);
            self::assertSame('tailwindcss', $componentesPorId['atualizacoes-detalhe-comp-tailwind']['framework_css'] ?? null);

            foreach (['atualizacoes-lista', 'atualizacoes-detalhe-comp'] as $id) {
                $legado = (string)file_get_contents($base . '/resources/' . $lingua . '/components/' . $id . '/' . $id . '.html');
                $tailwindId = $id . '-tailwind';
                $tailwindPath = $base . '/resources/' . $lingua . '/components/' . $tailwindId . '/' . $tailwindId . '.html';
                self::assertFileExists($tailwindPath);
                self::assertStringContainsString('class="ui ', $legado);
                self::assertStringNotContainsString('class="ui ', (string)file_get_contents($tailwindPath));
            }
        }
    }

    public function testAdminEnvironmentUsaInteracoesTailwindSemCodeMirror(): void
    {
        $base = CONN2FLOW_GESTOR_ROOT . '/modulos/admin-environment';
        $php = (string)file_get_contents($base . '/admin-environment.php');
        $javascript = (string)file_get_contents($base . '/admin-environment.js');

        $bundlePosition = strpos($php, "\$_GESTOR['tailwind-page-bundle'] = true;");
        $interfacePosition = strpos($php, 'interface_iniciar();');
        self::assertNotFalse($bundlePosition);
        self::assertNotFalse($interfacePosition);
        self::assertLessThan($interfacePosition, $bundlePosition);
        self::assertStringNotContainsString('assets_externos_incluir(\'codemirror\')', $php);
        self::assertDoesNotMatchRegularExpression(
            '/class="ui |CodeMirror|window\.alert|\.dropdown\(|\.checkbox\(|\.tab\(/',
            $php . $javascript
        );
        self::assertStringContainsString('debug-logs-output', $javascript);
        self::assertStringContainsString('admin-environment-tab', $javascript);
    }

    private function assertModulePagesUseTailwind(string $modulo, array $idsEsperados): void
    {
        $base = CONN2FLOW_GESTOR_ROOT . '/modulos/' . $modulo;
        $manifesto = json_decode((string)file_get_contents($base . '/' . $modulo . '.json'), true);
        self::assertIsArray($manifesto, $modulo);

        foreach (['pt-br', 'en'] as $lingua) {
            $paginas = array_column($manifesto['resources'][$lingua]['pages'] ?? [], null, 'id');
            foreach ($idsEsperados as $id) {
                self::assertArrayHasKey($id, $paginas, "$modulo/$lingua");
                $pagina = $paginas[$id];
                $htmlPath = $base . '/resources/' . $lingua . '/pages/' . $id . '/' . $id . '.html';
                self::assertFileExists($htmlPath, "$modulo/$lingua/$id");

                self::assertSame('layout-administrativo-tailwind', $pagina['layout'] ?? null, "$lingua/$id");
                self::assertSame('tailwindcss', $pagina['framework_css'] ?? null, "$lingua/$id");
                self::assertTrue($pagina['tailwind_bundle'] ?? false, "$lingua/$id");
                $dependencias = array_column($pagina['tailwind_dependencies'] ?? [], 'id');
                self::assertContains('menu-principal-sistema-tailwind', $dependencias, "$lingua/$id");
                if (!empty($pagina['root'])) {
                    if ($modulo === 'admin-atualizacoes') {
                        self::assertContains('atualizacoes-lista-tailwind', $dependencias, "$lingua/$id");
                    } elseif ($modulo === 'admin-environment') {
                        self::assertNotContains('interface-listar-tailwind', $dependencias, "$lingua/$id");
                    } elseif ($modulo === 'variables') {
                        self::assertContains('interface-formulario-edicao-tailwind', $dependencias, "$lingua/$id");
                    } else {
                        self::assertContains('interface-listar-tailwind', $dependencias, "$lingua/$id");
                    }
                }
                self::assertStringNotContainsString('class="ui ', (string)file_get_contents($htmlPath), "$lingua/$id");
            }
        }
    }
}