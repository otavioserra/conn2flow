<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-088 (req-088): cobre as funções PURAS dos widgets novos pages-index e forms-search
 * (sem banco de dados):
 *  - pages-index: resumo textual, render de itens ([[item#X]]), blocos condicionais e globais.
 *  - forms-search: action GET, campo search intrínseco e caixa de autocomplete.
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
        self::assertSame('https://site.test/pages-index-search/', forms_search_resolver_action(''));
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
        $out = forms_search_widget_forcar_get($html, 'https://site.test/pages-index-search/', 'busca-site');
        self::assertStringContainsString('method="get"', $out);
        self::assertStringContainsString('action="https://site.test/pages-index-search/"', $out);
        self::assertStringContainsString('data-form-id="busca-site"', $out);
        self::assertStringContainsString('conn2flow-search-form', $out);
        self::assertStringNotContainsString('method="post"', $out);
        self::assertStringNotContainsString('action="/velho"', $out);
        self::assertStringContainsString('class="c2f conn2flow-search-form"', $out);
    }

    public function testCelulaSearchInjetaContratoSemAlterarCampoExtra(): void
    {
        $html = '<form><!-- input-search < --><input class="visual" placeholder="Buscar"><!-- input-search > -->'
            . '<input type="text" name="categoria"></form>';
        $out = forms_search_widget_render_search_cell($html, 'busca-site');
        self::assertStringContainsString('name="search"', $out);
        self::assertStringContainsString('type="search"', $out);
        self::assertStringContainsString('id="busca-site-search"', $out);
        self::assertStringContainsString('aria-controls="busca-site-autocomplete-results"', $out);
        self::assertStringContainsString('required', $out);
        self::assertStringContainsString('name="categoria"', $out);
        self::assertStringNotContainsString('input-search <', $out);
    }

    public function testCelulaResultadosInjetaContratoAcessivel(): void
    {
        $html = '<form><!-- results-box < --><div class="visual"></div><!-- results-box > --></form>';
        $out = forms_search_widget_render_results_cell($html, 'busca-site');
        self::assertStringContainsString('class="visual forms-search-results"', $out);
        self::assertStringContainsString('id="busca-site-autocomplete-results"', $out);
        self::assertStringContainsString('role="listbox"', $out);
        self::assertStringContainsString('aria-live="polite"', $out);
        self::assertStringNotContainsString('results-box <', $out);
    }

    public function testRenderInlineSempreIncluiSearchMesmoSemCamposExtras(): void
    {
        $html = '<form><!-- input-search < --><input class="visual"><!-- input-search > -->'
            . '<!-- item < --><input name="[[item#name]]"><!-- item > -->'
            . '<!-- results-box < --><div></div><!-- results-box > --></form>';
        $out = forms_search_widget_render_inline([
            'form_id' => 'busca-site',
            'html' => $html,
            'fields_schema' => '{"fields":[{"name":"search","type":"text"},{"name":"categoria","type":"text"}]}',
        ]);
        self::assertStringContainsString('name="search"', $out);
        self::assertSame(1, substr_count($out, 'name="search"'));
        self::assertStringContainsString('name="categoria"', $out);
        self::assertStringContainsString('forms-search-results', $out);
        self::assertStringNotContainsString('[[item#name]]', $out);
        self::assertStringContainsString('action="https://site.test/pages-index-search/"', $out);
    }

    public function testRenderInlineNaoMaterializaPlaceholderComoSlugDoPreview(): void
    {
        $out = forms_search_widget_render_inline([
            'form_id' => '[slug-do-formulario]',
            'html' => '<form><input type="search" name="search"><div class="forms-search-results"></div></form>',
            'fields_schema' => '{"fields":[]}',
        ]);

        self::assertStringNotContainsString('[slug-do-formulario]', $out);
        self::assertStringContainsString('data-form-id="forms-search-preview"', $out);
        self::assertStringContainsString('id="forms-search-preview-search"', $out);
        self::assertStringContainsString('id="forms-search-preview-autocomplete-results"', $out);
    }

    public function testResumoAutocompleteRemoveHtmlETrunca(): void
    {
        $out = forms_search_autocomplete_summary('<h1>Título</h1><p>' . str_repeat('x', 200) . '</p>', 30);
        self::assertStringStartsWith('Título ', $out);
        self::assertSame(31, mb_strlen($out, 'UTF-8'));
        self::assertStringEndsWith('…', $out);
    }

    public function testCallbackAjaxDoWidgetRespondeTambemAoPreviewDoCrud(): void
    {
        $requestBefore = $_REQUEST;
        $ajaxBefore = $GLOBALS['_GESTOR']['ajax-json'] ?? null;
        $ajaxOpcaoBefore = $GLOBALS['_GESTOR']['ajax-opcao'] ?? null;
        try {
            $GLOBALS['_GESTOR']['ajax-opcao'] = 'forms-search-autocomplete';
            $_REQUEST = [
                'ajaxRegistroId' => 'busca-site',
                'params' => ['search' => 'ab', 'page' => 1],
            ];
            unset($GLOBALS['_GESTOR']['ajax-json']);

            self::assertSame('', forms_search_render_ajax(['form_id' => 'busca-site']));
            self::assertSame('Ok', $GLOBALS['_GESTOR']['ajax-json']['status']);
            self::assertSame([], $GLOBALS['_GESTOR']['ajax-json']['results']);
            self::assertFalse($GLOBALS['_GESTOR']['ajax-json']['tem_mais']);
        } finally {
            $_REQUEST = $requestBefore;
            if ($ajaxOpcaoBefore === null) unset($GLOBALS['_GESTOR']['ajax-opcao']);
            else $GLOBALS['_GESTOR']['ajax-opcao'] = $ajaxOpcaoBefore;
            if ($ajaxBefore === null) unset($GLOBALS['_GESTOR']['ajax-json']);
            else $GLOBALS['_GESTOR']['ajax-json'] = $ajaxBefore;
        }
    }

    public function testTodosOsTemplatesPossuemCelulasERenderizamContratoDeBusca(): void
    {
        $base = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'forms-search' . DIRECTORY_SEPARATOR . 'resources';
        $files = [];
        foreach (['pt-br', 'en'] as $language) {
            $pattern = $base . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'templates'
                . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.html';
            $files = array_merge($files, glob($pattern) ?: []);
        }

        self::assertCount(10, $files);
        foreach ($files as $file) {
            $html = (string)file_get_contents($file);
            self::assertSame(1, substr_count($html, '<!-- input-search < -->'), $file);
            self::assertSame(1, substr_count($html, '<!-- results-box < -->'), $file);

            $out = forms_search_widget_render_inline([
                'form_id' => 'busca-template',
                'html' => $html,
                'fields_schema' => '{"fields":[]}',
            ]);
            self::assertSame(1, substr_count($out, 'name="search"'), $file);
            self::assertSame(1, substr_count($out, 'forms-search-results'), $file);
            self::assertStringContainsString('method="get"', $out, $file);
            self::assertStringContainsString('action="https://site.test/pages-index-search/"', $out, $file);
            self::assertStringNotContainsString('input-search <', $out, $file);
            self::assertStringNotContainsString('results-box <', $out, $file);
        }
    }
}
