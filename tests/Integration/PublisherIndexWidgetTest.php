<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'publisher-index' . DIRECTORY_SEPARATOR . 'publisher-index.widget.php';

final class PublisherIndexWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        global $_GESTOR;

        $_GESTOR['recursos-incluidos-hashes'] = [];
        $_GESTOR['html-extra-head'] = [];
        $_GESTOR['css'] = [];
        $_GESTOR['css-compiled'] = [];
    }

    public function testRenderItensSubstituiCamposMapeados(): void
    {
        $template = '<article><h2>[[item#titulo]]</h2><a href="@[[item#link]]@">Abrir</a></article>';
        $publicacoes = [
            ['nome' => 'Ignorado', 'headline' => 'Primeiro', 'url' => '/primeiro'],
            ['headline' => 'Segundo', 'url' => '/segundo'],
        ];

        $html = publisher_index_widget_render_itens($template, $publicacoes, [
            'titulo' => 'headline',
            'link' => 'url',
        ]);

        self::assertStringContainsString('<h2>Primeiro</h2>', $html);
        self::assertStringContainsString('href="/segundo"', $html);
        self::assertStringNotContainsString('Ignorado', $html);
    }

    public function testMontarSaidaDeduplicaRecursosDoWidget(): void
    {
        global $_GESTOR;

        $html = publisher_index_widget_montar_saida('<section>Indice</section>', '.index{display:grid}', '.compiled{}', '<meta name="widget" content="index">');
        publisher_index_widget_montar_saida('<section>Indice</section>', '.index{display:grid}', '.compiled{}', '<meta name="widget" content="index">');

        self::assertSame('<section>Indice</section>', $html);
        self::assertCount(3, $_GESTOR['recursos-incluidos-hashes']);
        self::assertCount(1, $_GESTOR['html-extra-head']);
        self::assertCount(3, $_GESTOR['css']);
        self::assertCount(3, $_GESTOR['css-compiled']);
    }
}
