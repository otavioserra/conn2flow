<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-300 (BATCH-308) — o projeto define o layout de uma página do núcleo (`project-paginas-layout`)
 * e quais layouts recebem a biblioteca de controles (`project-controles-layouts`).
 */
final class Req300LayoutEControlesPorProjetoTest extends TestCase
{
    private static string $source;

    public static function setUpBeforeClass(): void
    {
        self::$source = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/gestor.php');
        self::assertSame(1, preg_match('/function gestor_roteador_layout_projeto\(.*?\r?\n}\r?\n/s', self::$source, $match));
        if (!function_exists('gestor_roteador_layout_projeto')) eval($match[0]);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_GESTOR']['project-paginas-layout']);
    }

    public function testSemMapaOuSemEntradaMantemOLayoutDaPagina(): void
    {
        $GLOBALS['_GESTOR'] = $GLOBALS['_GESTOR'] ?? [];
        self::assertSame('layout-administrativo-tailwind', gestor_roteador_layout_projeto('equipe', 'layout-administrativo-tailwind'));
        $GLOBALS['_GESTOR']['project-paginas-layout'] = ['perfil' => 'photon-admin'];
        self::assertSame('layout-administrativo-tailwind', gestor_roteador_layout_projeto('equipe', 'layout-administrativo-tailwind'));
        self::assertSame('layout-x', gestor_roteador_layout_projeto('', 'layout-x'));
    }

    public function testEntradaDoProjetoTrocaOLayout(): void
    {
        $GLOBALS['_GESTOR']['project-paginas-layout'] = ['equipe' => 'photon-admin', 'vazio' => '  '];
        self::assertSame('photon-admin', gestor_roteador_layout_projeto('equipe', 'layout-administrativo-tailwind'));
        self::assertSame('layout-base', gestor_roteador_layout_projeto('vazio', 'layout-base'), 'valor vazio não troca');
        $GLOBALS['_GESTOR']['project-paginas-layout'] = 'equipe=photon-admin';
        self::assertSame('layout-base', gestor_roteador_layout_projeto('equipe', 'layout-base'), 'mapa que não é array é ignorado');
    }

    public function testRoteadorAplicaOMapaAntesDoLayoutPorPerfilEDecideOBundle(): void
    {
        $troca = strpos(self::$source, '$layoutTrocadoProjeto = gestor_roteador_layout_projeto(');
        $perfil = strpos(self::$source, "if(!empty(\$paginas[0]['layouts_users_profiles'])){");
        self::assertNotFalse($troca);
        self::assertNotFalse($perfil);
        self::assertLessThan($perfil, $troca, 'o perfil continua podendo vencer o projeto');
        self::assertStringContainsString("\$_GESTOR['tailwind-page-bundle'] = in_array(\$layoutTrocadoProjeto, gestor_css_layouts_cobertos((string)\$css_precompiled), true);", self::$source);
    }

    public function testControlesEntramNosLayoutsDoProjeto(): void
    {
        self::assertStringContainsString("\$_GESTOR['project-controles-layouts']", self::$source);
        $bloco = substr(self::$source, (int)strpos(self::$source, '$layoutsComControles = '), 900);
        self::assertStringContainsString('|| in_array($layoutDaPagina, $layoutsComControles, true)', $bloco);
        self::assertStringContainsString('|| in_array($layoutDoProjeto, $layoutsComControles, true)){', $bloco);
        self::assertStringContainsString("gestor_incluir_biblioteca('controles');", $bloco);
    }

    public function testEquipeUsaOContainerDoPainelPorVariavel(): void
    {
        foreach (['pt-br', 'en'] as $lang) {
            $html = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . "/modulos/equipe/resources/$lang/pages/equipe/equipe.html");
            self::assertStringContainsString('id="equipe-painel" class="c2f-painel-pagina space-y-5"', $html, $lang);
            self::assertStringNotContainsString('max-w-6xl', $html, $lang);
            self::assertStringNotContainsString('bg-sky-50 px-3 py-1', $html, "$lang: selo de assentos segue o token de ação");
        }
    }
}
