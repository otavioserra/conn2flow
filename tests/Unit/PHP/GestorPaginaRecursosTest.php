<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GestorPaginaRecursosTest extends TestCase
{
    protected function setUp(): void
    {
        global $_GESTOR;

        $_GESTOR['recursos-incluidos-hashes'] = [];
        $_GESTOR['html-extra-head'] = [];
        $_GESTOR['css'] = [];
        $_GESTOR['css-precompiled'] = [];
        $_GESTOR['css-compiled'] = [];
    }

    public function testIncluiRecursosUmaVezPorConteudo(): void
    {
        global $_GESTOR;

        gestor_pagina_recursos_incluir([
            'html_extra_head' => '<meta name="x-test" content="1">',
            'css' => '.card { color: red; }',
            'css_precompiled' => '.precompiled { display: grid; }',
            'css_compiled' => '.compiled { display: block; }',
        ]);
        gestor_pagina_recursos_incluir([
            'html_extra_head' => '<meta name="x-test" content="1">',
            'css' => '.card { color: red; }',
            'css_precompiled' => '.precompiled { display: grid; }',
            'css_compiled' => '.compiled { display: block; }',
        ]);

        self::assertCount(4, $_GESTOR['recursos-incluidos-hashes']);
        self::assertCount(1, $_GESTOR['html-extra-head']);
        self::assertCount(3, $_GESTOR['css']);
        self::assertCount(1, $_GESTOR['css-precompiled']);
        self::assertCount(3, $_GESTOR['css-compiled']);
    }

    public function testConteudosDistintosSaoMantidosSeparadamente(): void
    {
        global $_GESTOR;

        gestor_pagina_recursos_incluir(['css' => '.a { color: red; }']);
        gestor_pagina_recursos_incluir(['css' => '.b { color: blue; }']);

        self::assertCount(2, $_GESTOR['recursos-incluidos-hashes']);
        self::assertSame(6, count($_GESTOR['css']));
        self::assertStringContainsString('.a { color: red; }', implode('', $_GESTOR['css']));
        self::assertStringContainsString('.b { color: blue; }', implode('', $_GESTOR['css']));
    }

    public function testOrdenaLayoutAntesDeDependenciasERecursosTailwind(): void
    {
        global $_GESTOR;

        gestor_pagina_recursos_incluir([
            'css_precompiled' => '.resource { display: block; }',
            'css_precompiled_role' => 'resource-precompiled',
        ]);
        gestor_pagina_recursos_incluir([
            'css_precompiled' => '.layout { box-sizing: border-box; }',
            'css_precompiled_role' => 'layout-precompiled',
        ]);
        gestor_pagina_recursos_incluir([
            'css_precompiled' => '.dependency { display: grid; }',
            'css_precompiled_role' => 'dependency-precompiled',
        ]);

        $ordered = gestor_css_precompiled_ordenar($_GESTOR['css-precompiled']);
        self::assertCount(3, $ordered);
        self::assertStringContainsString('layout-precompiled', $ordered[0]);
        self::assertStringContainsString('dependency-precompiled', $ordered[1]);
        self::assertStringContainsString('resource-precompiled', $ordered[2]);
    }
}
