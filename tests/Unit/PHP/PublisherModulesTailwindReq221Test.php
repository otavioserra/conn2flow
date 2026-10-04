<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PublisherModulesTailwindReq221Test extends TestCase
{
    public function testPagesPreserveOriginalRuntimeContracts(): void
    {
        $contracts = json_decode((string)file_get_contents(__DIR__ . '/../../Fixtures/publisher-modules-req221-contracts.json'), true);
        foreach ($contracts as $path => $contract) {
            $html = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/' . $path);
            self::assertDoesNotMatchRegularExpression('/class="ui\s/', $html, $path);
            foreach ($contract as $pattern => $expected) {
                preg_match_all($pattern, $html, $matches);
                $actual = array_values(array_unique($matches[1]));
                sort($actual);
                self::assertSame($expected, $actual, $path . ': ' . $pattern);
            }
        }
    }

    public function testAllModulePagesSelectTailwindAndRuntimeDependencies(): void
    {
        foreach (['publisher', 'publisher-highlights'] as $module) {
            $base = CONN2FLOW_GESTOR_ROOT . '/modulos/' . $module;
            $metadata = json_decode((string)file_get_contents($base . '/' . $module . '.json'), true);
            self::assertTrue(version_compare($metadata['versao'], '1.0.0', '>'), $module . ': asset version incremented');
            foreach (['pt-br', 'en'] as $language) {
                foreach ($metadata['resources'][$language]['pages'] as $page) {
                    self::assertSame('layout-administrativo-tailwind', $page['layout']);
                    self::assertSame('tailwindcss', $page['framework_css']);
                    self::assertTrue($page['tailwind_bundle']);
                    $ids = array_column($page['tailwind_dependencies'], 'id');
                    self::assertContains('menu-principal-sistema-tailwind', $ids);
                    if ($page['option'] === 'listar') self::assertContains('interface-listar-tailwind', $ids);
                    elseif ($module === 'publisher-highlights') self::assertContains('html-editor-publisher-highlights-simulation-tailwind', $ids);
                }
            }
            $php = (string)file_get_contents($base . '/' . $module . '.php');
            self::assertSame(4, substr_count($php, "\$_GESTOR['tailwind-page-bundle'] = true;"));
            $js = (string)file_get_contents($base . '/' . $module . '.js');
            self::assertDoesNotMatchRegularExpression('/\b(?:alert|confirm|prompt)\s*\(/', $js);
            self::assertStringNotContainsString("$('.ui.form')", $js);
        }
    }
}
