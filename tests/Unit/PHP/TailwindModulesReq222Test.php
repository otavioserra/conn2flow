<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TailwindModulesReq222Test extends TestCase
{
    private const MODULES = ['menus', 'galleries', 'forms', 'forms-search', 'forms-submissions', 'cookie-consent'];

    private function json(string $file): array
    {
        return json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testAllAdministrativePagesUseTailwindBundles(): void
    {
        foreach (self::MODULES as $module) {
            $base = CONN2FLOW_GESTOR_ROOT . '/modulos/' . $module;
            $meta = $this->json($base . '/' . $module . '.json');
            foreach (['pt-br', 'en'] as $language) {
                foreach ($meta['resources'][$language]['pages'] as $page) {
                    if (($page['type'] ?? '') !== 'system' || in_array($page['layout'], ['pagina-simples-tailwindcss', 'layout-pagina-simples'], true)) continue;
                    self::assertSame('layout-administrativo-tailwind', $page['layout'], $module . '/' . $page['id']);
                    self::assertSame('tailwindcss', $page['framework_css']);
                    self::assertTrue($page['tailwind_bundle']);
                    self::assertArrayNotHasKey('tailwind_sources', $page, 'Builder styles belong to authored resources');
                    $dependencies = array_column($page['tailwind_dependencies'], 'id');
                    self::assertContains('menu-principal-sistema-tailwind', $dependencies);
                    if ($page['option'] === 'listar') self::assertContains('interface-listar-tailwind', $dependencies);
                    $html = (string)file_get_contents($base . '/resources/' . $language . '/pages/' . $page['id'] . '/' . $page['id'] . '.html');
                    self::assertStringNotContainsString('class="ui ', $html);
                    foreach (($page['tailwind_sources'] ?? []) as $source) {
                        self::assertFileExists($base . '/resources/' . $language . '/pages/' . $page['id'] . '/' . $source);
                    }
                }
            }
            $js = (string)file_get_contents($base . '/' . $module . '.js');
            self::assertDoesNotMatchRegularExpression('/(?<![\w.])(?:alert|confirm|prompt)\s*\(/', $js);
            self::assertStringNotContainsString("$('.ui.form')", $js);
            self::assertStringContainsString("\$_GESTOR['tailwind-page-bundle'] = true;", (string)file_get_contents($base . '/' . $module . '.php'));
        }
    }

    public function testSimulationsKeepIdenticalJsonAndHooks(): void
    {
        foreach (['pt-br', 'en'] as $language) {
            $base = CONN2FLOW_GESTOR_ROOT . '/resources/' . $language;
            $registry = array_column($this->json($base . '/components.json'), null, 'id');
            foreach (['menus', 'galleries'] as $module) {
                $id = 'html-editor-' . $module . '-simulation';
                $old = (string)file_get_contents($base . '/components/' . $id . '/' . $id . '.html');
                $new = (string)file_get_contents($base . '/components/' . $id . '-tailwind/' . $id . '-tailwind.html');
                preg_match('/<script[^>]*>(.*?)<\/script>/s', $old, $oldJson);
                preg_match('/<script[^>]*>(.*?)<\/script>/s', $new, $newJson);
                self::assertSame(json_decode($oldJson[1], true, 512, JSON_THROW_ON_ERROR), json_decode($newJson[1], true, 512, JSON_THROW_ON_ERROR));
                self::assertStringContainsString($id . '-wrapper', $new);
                self::assertStringContainsString(' hidden"', $new);
                self::assertSame('tailwindcss', $registry[$id . '-tailwind']['framework_css']);
                self::assertStringContainsString("interface_componente_variante('" . $id . "')", (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/html-editor.php'));
            }
        }
    }

    public function testPagesPreserveTheOriginalContracts(): void
    {
        $contracts = $this->json(CONN2FLOW_ROOT . '/tests/Fixtures/req222-page-contracts.json');
        $patterns = [
            'markers' => '/<!--\s*([\w-]+\s*[<>])\s*-->/',
            'ids' => '/\sid="([^"#]+)"/',
            'names' => '/\sname="([^"]+)"/',
            'tabs' => '/\sdata-tab="([^"]+)"/',
            'placeholders' => '/(#[a-z][\w-]*#|@\[\[[^\]]+\]\]@)/',
        ];
        foreach ($contracts as $file => $original) {
            $html = (string)file_get_contents(CONN2FLOW_ROOT . '/' . $file);
            foreach ($patterns as $key => $pattern) {
                preg_match_all($pattern, $html, $matches);
                self::assertSame([], array_values(array_diff($original[$key], $matches[1])), $file . ': original ' . $key);
            }
        }
    }

    public function testInformationVariantsPreserveServerMarkersAndJsonFields(): void
    {
        foreach (['forms', 'forms-search'] as $module) {
            foreach (['pt-br', 'en'] as $language) {
                $base = CONN2FLOW_GESTOR_ROOT . '/modulos/' . $module . '/resources/' . $language . '/components/';
                $id = $module . '-info-definition';
                $old = (string)file_get_contents($base . $id . '/' . $id . '.html');
                $new = (string)file_get_contents($base . $id . '-tailwind/' . $id . '-tailwind.html');
                preg_match_all('/(#[\w-]+#|<!--[\w\s-]+[<>]\s*-->)/', $old, $markers);
                foreach ($markers[0] as $marker) self::assertStringContainsString($marker, $new);
                self::assertStringContainsString('<details ', $new);
                self::assertStringNotContainsString('class="ui ', $new);
            }
        }
    }
}
