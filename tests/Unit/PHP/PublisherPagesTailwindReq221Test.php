<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PublisherPagesTailwindReq221Test extends TestCase
{
    public function testSwitchTracksAndCaptionsBelongToTheirNativeCheckboxLabel(): void
    {
        foreach (['publisher-pages', 'publisher-index', 'pages-index'] as $module) {
            foreach (['pt-br', 'en'] as $language) {
                foreach (['adicionar', 'editar', 'clonar'] as $option) {
                    $id = $module . '-' . $option;
                    $html = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/' . $module . '/resources/' . $language . '/pages/' . $id . '/' . $id . '.html');
                    self::assertStringNotContainsString('<div class="c2fc-chave checkbox">', $html);
                    preg_match_all('/<label class="c2fc-chave checkbox">(.*?)<\/label>/s', $html, $labels);
                    self::assertCount($module === 'publisher-pages' ? 2 : 4, $labels[1]);
                    foreach ($labels[1] as $label) {
                        self::assertStringContainsString('<input type="checkbox"', $label);
                        self::assertStringContainsString('class="c2fc-chave-trilho"', $label);
                        self::assertStringContainsString('class="c2fc-chave-rotulo"', $label);
                        self::assertStringNotContainsString('<label', $label);
                    }
                }
            }
        }
    }

    public function testEveryPublisherDependencyResolvesInItsDeclaredScope(): void
    {
        foreach (['publisher', 'publisher-pages', 'publisher-highlights', 'publisher-index', 'pages-index'] as $module) {
            $manifest = json_decode((string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/' . $module . '/' . $module . '.json'), true, 512, JSON_THROW_ON_ERROR);
            foreach ($manifest['resources'] as $language => $resources) {
                foreach ($resources as $items) {
                    if (!is_array($items)) continue;
                    foreach ($items as $resource) {
                        if (!is_array($resource)) continue;
                        foreach ($resource['tailwind_dependencies'] ?? [] as $dependency) {
                            if (!empty($dependency['opcional'])) continue;
                            $dependencyModule = ($dependency['scope'] ?? '') === 'global' ? null : ($dependency['module'] ?? $module);
                            $base = CONN2FLOW_GESTOR_ROOT . ($dependencyModule === null ? '/resources/' : '/modulos/' . $dependencyModule . '/resources/');
                            $id = $dependency['id'];
                            self::assertFileExists($base . ($dependency['language'] ?? $language) . '/' . $dependency['type'] . '/' . $id . '/' . $id . '.html', $module . ':' . ($resource['id'] ?? 'resource'));
                        }
                    }
                }
            }
        }
    }

    public function testAdministrativePagesUseTheBundleAndRuntimeDependencies(): void
    {
        foreach (['publisher-pages', 'publisher-index', 'pages-index'] as $module) {
            $base = CONN2FLOW_GESTOR_ROOT . '/modulos/' . $module;
            $manifest = json_decode((string)file_get_contents($base . '/' . $module . '.json'), true, 512, JSON_THROW_ON_ERROR);
            foreach (['pt-br', 'en'] as $language) {
                foreach ($manifest['resources'][$language]['pages'] as $page) {
                    if (($page['type'] ?? '') !== 'system') continue;
                    self::assertSame('layout-administrativo-tailwind', $page['layout']);
                    self::assertSame('tailwindcss', $page['framework_css']);
                    self::assertTrue($page['tailwind_bundle']);
                    $dependencies = array_column($page['tailwind_dependencies'], 'id');
                    self::assertContains('menu-principal-sistema-tailwind', $dependencies);
                    self::assertContains(($page['option'] === 'listar' ? 'interface-listar' : 'html-editor') . '-tailwind', $dependencies);
                    $html = (string)file_get_contents($base . '/resources/' . $language . '/pages/' . $page['id'] . '/' . $page['id'] . '.html');
                    self::assertDoesNotMatchRegularExpression('/class="ui\s/', $html);
                    self::assertDoesNotMatchRegularExpression('/data-checked=/', $html);
                }
            }
            $php = (string)file_get_contents($base . '/' . $module . '.php');
            self::assertSame(4, substr_count($php, "\$_GESTOR['tailwind-page-bundle'] = true;"));
        }
    }

    public function testPublisherFieldsVariantsPreserveTheResourceContract(): void
    {
        $base = CONN2FLOW_GESTOR_ROOT . '/modulos/publisher-pages/resources/';
        foreach (['pt-br', 'en'] as $language) {
            foreach (['publisher-fields', 'layout-profile-mapping', 'layout-profile-row', 'lista-pagina-ou-sistema-ou-publisher'] as $id) {
                $prefix = $base . $language . '/components/';
                $original = (string)file_get_contents($prefix . $id . '/' . $id . '.html');
                $variant = (string)file_get_contents($prefix . $id . '-tailwind/' . $id . '-tailwind.html');
                foreach (['/<!--\s*([\w-]+\s*[<>]|[\w-]+-opcoes)\s*-->/', '/(#[a-z][\w-]*#|@\[\[[^\]]+\]\]@|\[\[field-[\w-]+\]\])/', '/\s(?:id|name|data-id)="([^"]+)"/'] as $pattern) {
                    preg_match_all($pattern, $original, $matches);
                    foreach (array_unique($matches[1]) as $token) self::assertStringContainsString($token, $variant, $language . ':' . $id);
                }
                self::assertDoesNotMatchRegularExpression('/class="ui\s/', $variant);
            }
        }
    }

    public function testCuratorPagesKeepAllEditingAndPreviewHooks(): void
    {
        foreach (['publisher-index', 'pages-index'] as $module) {
            foreach (['pt-br', 'en'] as $language) {
                foreach (['adicionar', 'editar', 'clonar'] as $option) {
                    $id = $module . '-' . $option;
                    $html = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/' . $module . '/resources/' . $language . '/pages/' . $id . '/' . $id . '.html');
                    foreach (['manual_search_input', 'selected-labels-container', 'template_id', 'fields_schema', '#html-editor#', 'hep-preview', 'hep-editor', 'hep-widget', 'template-loading', 'hep-preview-dimmer', 'menuConteudoDestaque'] as $hook) self::assertStringContainsString($hook, $html);
                }
            }
        }
    }
}
