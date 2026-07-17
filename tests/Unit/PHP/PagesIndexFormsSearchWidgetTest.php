<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-088 (req-088): cobre as funções PURAS dos widgets novos pages-index e forms-search
 * (sem banco de dados):
 *  - pages-index: resumo textual, render de itens ([[item#X]]), blocos condicionais e globais.
 *  - forms-search: resolução do action, força de method=get e garantia do campo name="search".
 */
final class PagesIndexFormsSearchWidgetTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . 'pages-index' . DIRECTORY_SEPARATOR . 'pages-index.widget.php';
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . 'forms-search' . DIRECTORY_SEPARATOR . 'forms-search.widget.php';

        $GLOBALS['_GESTOR']['url-raiz'] = 'https://site.test/';
        $GLOBALS['_GESTOR']['linguagem-codigo'] = 'pt-br';
    }

    // ===== pages-index: resumo textual =====

    public function testResumoRemoveTagsENormalizaEspacos(): void
    {
        $html = "<h1>Título</h1>\n<p>Primeiro   parágrafo.</p>\n\n<p>Segundo.</p>";
        self::assertSame('Título Primeiro parágrafo. Segundo.', pages_index_widget_resumo($html));
    }

    public function testResumoTruncaComReticencias(): void
    {
        $texto = str_repeat('a', 250);
        $resumo = pages_index_widget_resumo('<p>' . $texto . '</p>');
        // 200 caracteres + reticências.
        self::assertSame(201, mb_strlen($resumo, 'UTF-8'));
        self::assertStringEndsWith('…', $resumo);
    }

    public function testResumoVazio(): void
    {
        self::assertSame('', pages_index_widget_resumo(''));
        self::assertSame('', pages_index_widget_resumo('   '));
    }

    // ===== pages-index: render de itens =====

    public function testRenderItensResolveVariaveisFixas(): void
    {
        $tpl = '<a href="@[[item#url]]@"><h3>[[item#title]]</h3><p>[[item#summary]]</p></a>';
        $itens = [
            ['title' => 'Sobre', 'summary' => 'Resumo A', 'url' => 'https://site.test/sobre/', 'date' => ''],
            ['title' => 'Contato', 'summary' => 'Resumo B', 'url' => 'https://site.test/contato/', 'date' => ''],
        ];
        $out = pages_index_widget_render_itens($tpl, $itens, []);
        self::assertStringContainsString('<h3>Sobre</h3>', $out);
        self::assertStringContainsString('<p>Resumo A</p>', $out);
        self::assertStringContainsString('href="https://site.test/contato/"', $out);
        self::assertSame(2, substr_count($out, '<a href='));
    }

    public function testRenderItensAplicaVariableMapping(): void
    {
        $tpl = '<span>[[item#titulo]]</span>';
        $itens = [['title' => 'Página X', 'summary' => '', 'url' => '', 'date' => '']];
        // Alias: [[item#titulo]] -> campo 'title'.
        $out = pages_index_widget_render_itens($tpl, $itens, ['titulo' => 'title']);
        self::assertSame('<span>Página X</span>', $out);
    }

    // ===== pages-index: blocos condicionais e globais =====

    public function testBlocoCondicionalMantemOuRemove(): void
    {
        $html = 'A<!-- load-more < -->BOTAO<!-- load-more > -->B';
        self::assertSame('ABOTAOB', pages_index_widget_bloco_condicional($html, 'load-more', true));
        self::assertSame('AB', pages_index_widget_bloco_condicional($html, 'load-more', false));
    }

    public function testResolverGlobaisConsumeCercoDeArrobas(): void
    {
        $html = 'data-ordenacao="@[[ordenacao]]@" data-slug="[[grupo_slug]]"';
        $out = pages_index_widget_resolver_globais($html, ['ordenacao' => 'title_asc', 'grupo_slug' => 'padrao']);
        self::assertSame('data-ordenacao="title_asc" data-slug="padrao"', $out);
    }

    // ===== forms-search: action =====

    public function testResolverActionVazioUsaDestinoPadrao(): void
    {
        self::assertSame('https://site.test/busca/', forms_search_resolver_action(''));
    }

    public function testResolverActionRelativoRecebeUrlRaiz(): void
    {
        self::assertSame('https://site.test/resultados/', forms_search_resolver_action('resultados/'));
        // Barra inicial não duplica com a url-raiz.
        self::assertSame('https://site.test/resultados/', forms_search_resolver_action('/resultados/'));
    }

    public function testResolverActionAbsolutoPassaIntacto(): void
    {
        self::assertSame('https://outro.com/x', forms_search_resolver_action('https://outro.com/x'));
    }

    // ===== forms-search: método GET e campo search =====

    public function testForcarGetTrocaMethodEAction(): void
    {
        $html = '<form class="c2f" method="post" action="/velho">campos</form>';
        $out = forms_search_widget_forcar_get($html, 'https://site.test/busca/');
        self::assertStringContainsString('method="get"', $out);
        self::assertStringContainsString('action="https://site.test/busca/"', $out);
        self::assertStringNotContainsString('method="post"', $out);
        self::assertStringNotContainsString('action="/velho"', $out);
        self::assertStringContainsString('class="c2f"', $out);
    }

    public function testGarantirCampoSearchConverteNamePrimeiroInput(): void
    {
        $html = '<form><input type="text" name="nome"><input type="email" name="email"></form>';
        $out = forms_search_widget_garantir_campo_search($html);
        self::assertStringContainsString('name="search"', $out);
        self::assertStringNotContainsString('name="nome"', $out);
        // O segundo campo (email) não é tocado.
        self::assertStringContainsString('name="email"', $out);
    }

    public function testGarantirCampoSearchNaoDuplicaQuandoJaExiste(): void
    {
        $html = '<form><input type="search" name="search"></form>';
        self::assertSame($html, forms_search_widget_garantir_campo_search($html));
    }

    public function testGarantirCampoSearchAdicionaNameQuandoAusente(): void
    {
        $html = '<form><input type="text" placeholder="Buscar"></form>';
        $out = forms_search_widget_garantir_campo_search($html);
        self::assertStringContainsString('name="search"', $out);
    }
}
