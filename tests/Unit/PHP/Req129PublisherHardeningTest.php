<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-129 — blindagem da inicialização de Publisher e Publisher Pages.
 */
final class Req129PublisherHardeningTest extends TestCase
{
    private static function publisherSource(): string
    {
        return (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'publisher' . DIRECTORY_SEPARATOR . 'publisher.php'
        );
    }

    private static function publisherPagesSource(): string
    {
        return (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'publisher-pages' . DIRECTORY_SEPARATOR . 'publisher-pages.php'
        );
    }

    private static function functionSource(string $source, string $functionName, string $nextFunctionName): string
    {
        $start = strpos($source, 'function ' . $functionName . '(');
        $end = strpos($source, 'function ' . $nextFunctionName . '(', $start === false ? 0 : $start);

        self::assertNotFalse($start, 'Função não encontrada: ' . $functionName);
        self::assertNotFalse($end, 'Limite da função não encontrado: ' . $nextFunctionName);

        return substr($source, (int)$start, (int)$end - (int)$start);
    }

    public function testDropdownsInicializamOptionsEProtegemRetornoDeTemplates(): void
    {
        $source = self::publisherSource();

        self::assertSame(3, substr_count($source, "\$template_id_options = '';"));
        self::assertSame(3, substr_count($source, 'if($templates && is_array($templates))'));
        self::assertStringNotContainsString("if(\$templates)\n\tforeach(\$templates", $source);
    }

    public function testClonagemExtraiNomeAntesDeRenderizarPagina(): void
    {
        $source = self::functionSource(
            self::publisherPagesSource(),
            'publisher_pages_clonar',
            'publisher_pages_listar_cabecalho'
        );

        $extract = "\$nome = (isset(\$retorno_bd['nome']) ? \$retorno_bd['nome'] : '');";
        $render = "modelo_var_troca_tudo(\$_GESTOR['pagina'],'#pagina-nome#',\$nome)";

        self::assertStringContainsString($extract, $source);
        self::assertLessThan(strpos($source, $render), strpos($source, $extract));
    }

    public function testCamposOpcionaisSaoLidosSemWarningsNaInclusaoEClonagem(): void
    {
        $source = self::publisherPagesSource();
        $adicionar = self::functionSource($source, 'publisher_pages_adicionar', 'publisher_pages_editar');
        $clonar = self::functionSource($source, 'publisher_pages_clonar', 'publisher_pages_listar_cabecalho');

        foreach ([$adicionar, $clonar] as $functionSource) {
            foreach (['modulo', 'pagina-opcao', 'paginaCaminho'] as $requestName) {
                self::assertMatchesRegularExpression(
                    '/\$post_nome = (?:\$campo_nome|[\'"]' . preg_quote($requestName, '/') . '[\'"]);[^\r\n]*if\(!empty\(\$_REQUEST\[\$post_nome\]\)\)/',
                    $functionSource
                );
            }

            foreach (['raiz', 'sem_permissao'] as $fieldName) {
                self::assertMatchesRegularExpression(
                    '/\$campo_nome = "' . $fieldName . '";[^\r\n]*if\(!empty\(\$_REQUEST\[\$post_nome\]\)\)/',
                    $functionSource
                );
            }
        }
    }

    public function testFallbackPreservaHtmlAlteradoERecuperaTemplateQuandoVazio(): void
    {
        if (!function_exists('publisher_pages_html_inclusao_resolver')) {
            $source = self::publisherPagesSource();
            $functionSource = self::functionSource(
                $source,
                'publisher_pages_html_inclusao_resolver',
                'publisher_pages_adicionar'
            );
            eval($functionSource);
        }

        self::assertSame(
            '<article>alterado</article>',
            publisher_pages_html_inclusao_resolver('<article>alterado</article>', '<main>template</main>')
        );
        self::assertSame(
            '<main>template</main>',
            publisher_pages_html_inclusao_resolver('', '<main>template</main>')
        );
        self::assertSame('', publisher_pages_html_inclusao_resolver('   ', "\n\t"));
    }

    public function testInclusaoEClonagemValidamHtmlAntesDoInsert(): void
    {
        $source = self::publisherPagesSource();
        $adicionar = self::functionSource($source, 'publisher_pages_adicionar', 'publisher_pages_editar');
        $clonar = self::functionSource($source, 'publisher_pages_clonar', 'publisher_pages_listar_cabecalho');

        foreach ([$adicionar, $clonar] as $functionSource) {
            $fallbackPosition = strpos($functionSource, "\$_REQUEST['htmlWithValues'] = publisher_pages_html_inclusao_resolver");
            $validationPosition = strpos($functionSource, "'campo' => 'htmlWithValues'");
            $insertPosition = strpos($functionSource, 'banco_insert_name');

            self::assertNotFalse($fallbackPosition);
            self::assertNotFalse($validationPosition);
            self::assertNotFalse($insertPosition);
            self::assertLessThan($validationPosition, $fallbackPosition);
            self::assertLessThan($insertPosition, $validationPosition);
        }
    }
}
