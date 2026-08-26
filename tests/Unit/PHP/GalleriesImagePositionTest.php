<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
    . DIRECTORY_SEPARATOR . 'galleries' . DIRECTORY_SEPARATOR . 'galleries.widget.php';

final class GalleriesImagePositionTest extends TestCase
{
    public function testNormalizaAllowlistComFallbackCenter(): void
    {
        foreach (['top', 'center', 'bottom'] as $position) {
            self::assertSame($position, galleries_widget_normalizar_image_position($position));
        }

        self::assertSame('top', galleries_widget_normalizar_image_position(' TOP '));

        foreach (['', 'middle', '<script>alert(1)</script>', null, 7] as $position) {
            self::assertSame('center', galleries_widget_normalizar_image_position($position));
        }
    }

    public function testResolverGlobaisExpoeValorPuroEClasseTailwind(): void
    {
        $html = 'data-position="@[[image_position]]@" class="@[[image_position_class]]@"';

        self::assertSame(
            'data-position="top" class="object-top"',
            galleries_widget_resolver_globais($html, ['image_position' => 'top'])
        );
        self::assertSame(
            'data-position="bottom" class="object-bottom"',
            galleries_widget_resolver_globais($html, ['image_position' => 'bottom'])
        );
        self::assertSame(
            'data-position="center" class="object-center"',
            galleries_widget_resolver_globais($html, ['image_position' => '"><script>'])
        );
    }

    public function testQuatroTemplatesNosDoisIdiomasAplicamAlinhamento(): void
    {
        foreach (['pt-br', 'en'] as $language) {
            foreach (['carousel', 'grid', 'masonry', 'slider'] as $model) {
                $path = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
                    . DIRECTORY_SEPARATOR . 'galleries' . DIRECTORY_SEPARATOR . 'resources'
                    . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'templates'
                    . DIRECTORY_SEPARATOR . 'galleries-' . $model
                    . DIRECTORY_SEPARATOR . 'galleries-' . $model . '.html';
                $html = (string) file_get_contents($path);

                self::assertStringContainsString('data-image-position="@[[image_position]]@"', $html, $path);
                self::assertStringContainsString('@[[image_position_class]]@', $html, $path);
                self::assertStringContainsString('object-position: @[[image_position]]@;', $html, $path);
            }
        }
    }

    public function testTresCrudsNosDoisIdiomasExpoemControleETextosLocalizados(): void
    {
        foreach (['pt-br', 'en'] as $language) {
            foreach (['adicionar', 'editar', 'clonar'] as $option) {
                $id = 'galleries-' . $option;
                $path = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
                    . DIRECTORY_SEPARATOR . 'galleries' . DIRECTORY_SEPARATOR . 'resources'
                    . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'pages'
                    . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . $id . '.html';
                $html = (string) file_get_contents($path);

                self::assertSame(1, substr_count($html, 'id="gallery-image-position"'), $path);
                self::assertStringContainsString('@[[image-position-label]]@', $html, $path);
                self::assertStringContainsString('data-settings-tooltip="@[[item-settings-tooltip]]@"', $html, $path);
                foreach (['center', 'top', 'bottom'] as $position) {
                    self::assertStringContainsString('value="' . $position . '"', $html, $path);
                }
            }
        }
    }

    public function testRuntimePublicoReaplicaObjectPositionNormalizado(): void
    {
        $path = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . 'galleries' . DIRECTORY_SEPARATOR . 'galleries.widget.js';
        $javascript = (string) file_get_contents($path);

        self::assertStringContainsString("['top', 'center', 'bottom'].indexOf(imagePosition)", $javascript);
        self::assertStringContainsString('$gallery.find(\'img\').css(\'object-position\', imagePosition)', $javascript);
    }
}
